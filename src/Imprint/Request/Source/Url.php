<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Request\Source;

use DecodeLabs\Imprint\Request\Source;

/**
 * @implements Source<string>
 */
class Url implements Source
{
    public function __construct(
        public string $value
    ) {
    }
}
