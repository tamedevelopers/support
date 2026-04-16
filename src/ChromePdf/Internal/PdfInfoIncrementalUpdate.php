<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfBoolean;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfHexString;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObjectReference;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfNull;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfString;
use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use Tamedevelopers\Support\ChromePdf\PdfRebuildOptions;
use Throwable;

/**
 * Appends a new {@code Info} dictionary via PDF incremental update so page tree and annotations stay untouched.
 */
final class PdfInfoIncrementalUpdate
{
    /**
     * @throws ConversionFailedException
     */
    public static function apply(string $binary, PdfRebuildOptions $options): string
    {
        if ($binary === '') {
            throw new ConversionFailedException('Cannot update metadata on an empty PDF payload.');
        }

        try {
            $parser = new PdfParser(StreamReader::createByString($binary));
            $xref = $parser->getCrossReference();
            $trailer = $xref->getTrailer();
            $rootVal = PdfDictionary::get($trailer, 'Root');
            if (!($rootVal instanceof PdfIndirectObjectReference)) {
                throw new ConversionFailedException('PDF trailer has no valid /Root reference; cannot update metadata.');
            }
            $rootStr = $rootVal->value . ' ' . $rootVal->generationNumber . ' R';

            $catalog = $parser->getCatalog();
            $oldInfoDict = null;
            $infoRef = PdfDictionary::get($catalog, 'Info');
            if ($infoRef instanceof PdfIndirectObjectReference) {
                try {
                    $infoObj = $parser->getIndirectObject($infoRef->value);
                    $oldInfoDict = PdfDictionary::ensure($infoObj->value);
                } catch (Throwable) {
                    $oldInfoDict = null;
                }
            }

            $dictBody = self::buildInfoDictionaryBody($options, $oldInfoDict);
            $newObjNum = (int) $xref->getSize();

            $objChunk = "\n" . $newObjNum . " 0 obj\n" . $dictBody . "\nendobj\n";
            $objStartOffset = \strlen($binary) + 1;

            $xrefSection = "xref\n" . $newObjNum . " 1\n" . \sprintf("%010d %05d n \n", $objStartOffset, 0);

            $extraTrailerKeys = self::serializeExtraTrailerKeys($trailer);
            $prevStart = self::readLastStartxref($binary);
            $newSize = $newObjNum + 1;
            $trailerDict = '<< /Size ' . $newSize
                . ' /Root ' . $rootStr
                . ' /Info ' . $newObjNum . ' 0 R'
                . ' /Prev ' . $prevStart
                . $extraTrailerKeys
                . ' >>';

            $xrefKeywordOffset = \strlen($binary) + \strlen($objChunk);
            $tail = $xrefSection . "trailer\n" . $trailerDict . "\nstartxref\n" . $xrefKeywordOffset . "\n%%EOF\n";

            $parser->cleanUp();

            return $binary . $objChunk . $tail;
        } catch (ConversionFailedException $e) {
            throw $e;
        } catch (CrossReferenceException | PdfParserException $e) {
            throw new ConversionFailedException(
                'Incremental PDF metadata update failed (try a linearized or repaired PDF): ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        } catch (Throwable $e) {
            throw new ConversionFailedException('Incremental PDF metadata update failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private static function readLastStartxref(string $binary): int
    {
        $last = 0;
        $offset = 0;
        while (($pos = \strpos($binary, 'startxref', $offset)) !== false) {
            if (\preg_match('/startxref\s+(\d+)/', $binary, $mm, 0, $pos) === 1) {
                $last = (int) $mm[1];
            }
            $offset = $pos + 9;
        }
        if ($last < 1) {
            throw new ConversionFailedException('Could not locate startxref for incremental PDF update.');
        }

        return $last;
    }

    private static function buildInfoDictionaryBody(PdfRebuildOptions $options, ?PdfDictionary $oldInfoDict): string
    {
        $lines = ['<<'];
        $overlayKeys = ['Title', 'Author', 'Subject', 'Keywords'];
        $skipCopy = \array_merge($overlayKeys, ['ModDate']);

        if ($oldInfoDict !== null) {
            foreach ($oldInfoDict->value as $key => $value) {
                if (\in_array($key, $skipCopy, true)) {
                    continue;
                }
                try {
                    $lines[] = '/' . $key . ' ' . self::serializePdfValue($value);
                } catch (Throwable) {
                    // Omit entries we cannot round-trip (e.g. rare stream types in Info).
                }
            }
        }

        $map = [
            'Title' => $options->metaTitle,
            'Author' => $options->metaAuthor,
            'Subject' => $options->metaSubject,
            'Keywords' => $options->metaKeywords,
        ];
        foreach ($map as $pdfKey => $phpVal) {
            if ($phpVal !== null && $phpVal !== '') {
                $lines[] = '/' . $pdfKey . ' (' . PdfString::escape($phpVal) . ')';
            } elseif ($oldInfoDict !== null && isset($oldInfoDict->value[$pdfKey])) {
                try {
                    $lines[] = '/' . $pdfKey . ' ' . self::serializePdfValue($oldInfoDict->value[$pdfKey]);
                } catch (Throwable) {
                }
            }
        }

        $modDate = 'D:' . gmdate('YmdHis') . 'Z';
        $lines[] = '/ModDate (' . PdfString::escape($modDate) . ')';
        $lines[] = '>>';

        return \implode("\n", $lines);
    }

    private static function serializeExtraTrailerKeys(PdfDictionary $trailer): string
    {
        $out = '';
        foreach ($trailer->value as $key => $val) {
            if (\in_array($key, ['Size', 'Prev', 'Root', 'Info', 'XRefStm'], true)) {
                continue;
            }
            if ($val instanceof PdfNull) {
                continue;
            }
            try {
                $out .= ' /' . $key . ' ' . self::serializePdfValue($val);
            } catch (Throwable) {
            }
        }

        return $out;
    }

    private static function serializePdfValue(mixed $v): string
    {
        if ($v instanceof PdfIndirectObjectReference) {
            return $v->value . ' ' . $v->generationNumber . ' R';
        }
        if ($v instanceof PdfName) {
            return '/' . $v->value;
        }
        if ($v instanceof PdfNumeric) {
            return (string) $v->value;
        }
        if ($v instanceof PdfBoolean) {
            return $v->value ? 'true' : 'false';
        }
        if ($v instanceof PdfString) {
            return '(' . PdfString::escape(PdfString::unescape($v->value)) . ')';
        }
        if ($v instanceof PdfHexString) {
            return '<' . $v->value . '>';
        }
        if ($v instanceof PdfArray) {
            $parts = [];
            foreach ($v->value as $item) {
                $parts[] = self::serializePdfValue($item);
            }

            return '[' . \implode(' ', $parts) . ']';
        }

        throw new \InvalidArgumentException('Unsupported PDF type in trailer/info serialization.');
    }
}
