<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Request\Target;

use DecodeLabs\Atlas\File;
use DecodeLabs\Atlas\File\Memory as MemoryFile;
use DecodeLabs\Imprint\Request\Target;

/**
 * @implements Target<File>
 */
class TempFile implements Target
{
    public MemoryFile $value {
        get => $this->value ?? MemoryFile::create();
    }

    public function __construct(
        public string $fileName
    ) {
    }
}
