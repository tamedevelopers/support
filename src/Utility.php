<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Traits\EmailUtilityTrait;
use Tamedevelopers\Support\Traits\TextUtilityTrait;


/**
 * Class Utility
 *
 * DRY, well-documented utility for Email and String manipulations analysis.
 *
 * Features:
 * - readingTime(): estimates reading time using 200 wpm and returns
 *   formatted output like "30 seconds", "1 minute", "2 minutes", "1 hr 20 mins"
 * - wordCount(), charCount(), sentenceCount()
 * - reverse(), isPalindrome() (ignores punctuation, spaces, case)
 * - maskEmail() equalsEmail()
 * - validateEmail()
 *   Validate an email address.
 * - normalizeEmail()
 *   Normalize an email address using rules from `emailProviders` file.
 *
 * Global configuration: use StrUtility::config([...]) to change unit labels.
 */
class Utility
{
    use TextUtilityTrait,
        EmailUtilityTrait; 
    
    /**
     * Providers rules cache loaded from stubs/emailProviders.php
     * @var array<string, array{strip_plus: bool, strip_dots: bool}>
     */
    protected static $providers = [];
    
    /**
     * Providers rules children data loaded from <$providers>
     * @var array
     */
    protected static $providersChildren = [];

    /**
     * Text to analyze/manipulate.
     * @var string
     */
    protected string $text;

    /**
     * Configuration for unit text labels and behavior.
     * Keys:
     *  - second (string) e.g. 'seconds'
     *  - minute (string) e.g. 'minutes'
     *  - hour   (string) e.g. 'hrs'
     *  - pluralize (bool) whether to use singular when value == 1 (default true)
     */
    protected static array $config = [
        'second'    => 'seconds',
        'minute'    => 'minutes',
        'hour'      => 'hrs',
        'pluralize' => true,
    ];

    /**
     * Constructor.
     * @param string|null $text
     */
    public function __construct($text = null)
    {
        $this->text = $text;
    }

    /**
     * Create instance from text.
     *
     * @param string $text
     * @return static
     */
    public static function text($text)
    {
        return new static($text);
    }
    
    /**
     * Return as a string on wrong call
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getText();
    }

    /**
     * Magic: customize what is displayed during var-dump/dd().
     * Provides a pretty, safe snapshot of the current class.
     *
     * - text: the text input
     * - readingTime: reading time using 200 words per minute
     * - wordCount: Count words in text.
     * - charCount: Count words in text.
     * - sentenceCount: Count text sentences (approximate) by splitting on punctuation.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'text'          => $this->getText(),
            'readingTime'   => $this->readingTime(),
            'wordCount'     => $this->wordCount(),
            'charCount'     => $this->charCount(),
            'sentenceCount' => $this->sentenceCount(),
        ];
    }

}