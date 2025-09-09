<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint;

use DecodeLabs\Atlas\File;
use DecodeLabs\Imprint\Request\Target;

interface Adapter
{
    /**
     * @return Target<string>|Target<File>
     */
    public function convert(
        Request $request
    ): Target;
}
