<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs;

use DecodeLabs\Atlas\File;
use DecodeLabs\Atlas\File\Memory as MemoryFile;
use DecodeLabs\Imprint\Adapter;
use DecodeLabs\Imprint\Options;
use DecodeLabs\Kingdom\Service;
use DecodeLabs\Kingdom\ServiceTrait;

class Imprint implements Service
{
    use ServiceTrait;

    public function __construct(
        protected Adapter $adapter
    ) {
    }

    public function convertUrl(
        string $url,
        ?Options $options = null
    ): File {
        $options ??= new Options();
        return $this->adapter->convertUrl($url, $options);
    }

    public function convertFile(
        File $file,
        ?Options $options = null
    ): File {
        $options ??= new Options();
        return $this->adapter->convertFile($file, $options);
    }

    public function convertString(
        string $string,
        ?Options $options = null
    ): File {
        $file = MemoryFile::create();
        $file->write($string);

        return $this->convertFile($file, $options);
    }
}
