<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Request\Target;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\File;
use DecodeLabs\Atlas\File\Local as DiskFile;
use DecodeLabs\Imprint\Request\Target;

/**
 * @implements Target<File>
 */
class LocalFile implements Target
{
    public DiskFile $value;

    public string $fileName {
        get => basename($this->value->path);
    }

    public function __construct(
        string|DiskFile $value
    ) {
        if (is_string($value)) {
            /** @var DiskFile $value */
            $value = Atlas::getFile($value);
        }

        $this->value = $value;
    }
}
