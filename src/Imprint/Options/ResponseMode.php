<?php

/**
 * Imprint
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Options;

enum ResponseMode
{
    case Attachment;
    case Inline;
}
