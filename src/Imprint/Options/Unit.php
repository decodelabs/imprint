<?php

/**
 * Imprint
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Options;

enum Unit: string
{
    case Pixels = 'px';
    case Points = 'pt';
    case Inches = 'in';
    case Millimeters = 'mm';
}
