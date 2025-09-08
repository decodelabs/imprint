<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Options;

enum PageSize
{
    case A0;
    case A1;
    case A2;
    case A3;
    case A4;
    case A5;
    case A6;
    case A7;
    case A8;
    case A9;

    case B0;
    case B1;
    case B2;
    case B3;
    case B4;
    case B5;
    case B6;
    case B7;
    case B8;
    case B9;

    case C5E;
    case Comm10E;
    case DLE;
    case Executive;
    case Folio;
    case Ledger;
    case Legal;
    case Letter;
    case Tabloid;
}
