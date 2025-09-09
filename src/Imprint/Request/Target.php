<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Request;

/**
 * @template T
 */
interface Target
{
    /**
     * @var T
     */
    public mixed $value { get; }

    public string $fileName { get; }
}
