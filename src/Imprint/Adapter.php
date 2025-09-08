<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint;

use DecodeLabs\Atlas\File;

interface Adapter
{
    public function convertUrl(
        string $url,
        Options $options
    ): File;

    public function convertFile(
        File $file,
        Options $options
    ): File;
}
