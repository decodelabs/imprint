<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Request\Target;

use DecodeLabs\Imprint\Request\Target;

/**
 * @implements Target<string>
 */
class S3 implements Target
{
    public string $value;

    public function __construct(
        public string $fileName
    ) {
    }
}
