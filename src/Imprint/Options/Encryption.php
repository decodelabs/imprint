<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Options;

enum Encryption: int
{
    case Bit40 = 40;
    case Bit128 = 128;
}
