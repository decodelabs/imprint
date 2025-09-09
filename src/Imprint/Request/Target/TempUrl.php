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
class TempUrl implements Target
{
    public string $value;

    public string $fileName {
        get {
            if (isset($this->fileName)) {
                return $this->fileName;
            }

            if (isset($this->value)) {
                return $this->fileName = basename($this->value);
            }

            return 'temp.pdf';
        }
    }

    public function __construct(
        ?string $fileName = null
    ) {
        if ($fileName !== null) {
            $this->fileName = $fileName;
        }
    }
}
