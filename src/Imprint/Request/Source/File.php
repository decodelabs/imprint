<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Request\Source;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\File as AtlasFile;
use DecodeLabs\Imprint\Request\Source;

/**
 * @implements Source<AtlasFile>
 */
class File implements Source
{
    public AtlasFile $value;

    public function __construct(
        string|AtlasFile $value
    ) {
        if (is_string($value)) {
            /** @var AtlasFile $value */
            $value = Atlas::getFile($value);
        }

        $this->value = $value;
    }
}
