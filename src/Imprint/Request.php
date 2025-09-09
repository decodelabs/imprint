<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint;

use DecodeLabs\Atlas\File;
use DecodeLabs\Imprint\Request\Source;
use DecodeLabs\Imprint\Request\Target;

class Request
{
    /**
     * @param Source<string>|Source<File> $source
     * @param Target<string>|Target<File> $target
     */
    public function __construct(
        public Source $source,
        public Target $target,
        public Options $options,
    ) {
    }
}
