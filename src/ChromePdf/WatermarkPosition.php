<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf;

/**
 * Placement of text or image watermarks on each PDF page (FPDI/TCPDF pass).
 */
enum WatermarkPosition: string
{
    /** Page center (previous default). */
    case Center = 'center';

    case TopLeft = 'top_left';
    case TopCenter = 'top_center';
    case TopRight = 'top_right';

    /** Vertical middle, horizontal left. */
    case MiddleLeft = 'middle_left';
    /** Vertical middle, horizontal right. */
    case MiddleRight = 'middle_right';

    case BottomLeft = 'bottom_left';
    case BottomCenter = 'bottom_center';
    case BottomRight = 'bottom_right';
}
