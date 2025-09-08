<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Options;

class Watermark
{
    public function __construct(
        public string $url,
        public float $x = 0,
        public float $y = 0,
        public float $opacity = 0.2,
        public bool $background = false,
    ) {
    }
}
