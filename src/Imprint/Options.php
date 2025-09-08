<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint;

use DecodeLabs\Imprint\Options\Encryption;
use DecodeLabs\Imprint\Options\Orientation;
use DecodeLabs\Imprint\Options\PageSize;
use DecodeLabs\Imprint\Options\Quality;
use DecodeLabs\Imprint\Options\ResponseMode;
use DecodeLabs\Imprint\Options\Unit;
use DecodeLabs\Imprint\Options\Watermark;

class Options
{
    public float $margin {
        get => ($this->marginTop + $this->marginBottom + $this->marginLeft + $this->marginRight) / 4;
        set {
            $this->marginTop = $value;
            $this->marginBottom = $value;
            $this->marginLeft = $value;
            $this->marginRight = $value;
        }
    }

    public ?string $viewport {
        get =>
            $this->viewportWidth !== null &&
            $this->viewportHeight !== null ?
                $this->viewportWidth . 'x' . $this->viewportHeight :
                null;
    }

    public function __construct(
        public ?string $fileName = null,
        public ?string $title = null,
        public ?string $subject = null,
        public ?string $creator = null,
        public ?string $author = null,
        public Quality $quality = Quality::High,
        public Unit $unit = Unit::Pixels,
        public Orientation $orientation = Orientation::Portrait,
        public PageSize $pageSize = PageSize::A4,
        public ?float $width = null,
        public ?float $height = null,
        public ?float $marginTop = null,
        public ?float $marginBottom = null,
        public ?float $marginLeft = null,
        public ?float $marginRight = null,
        public ?string $cssUrl = null,
        public ?Watermark $watermark = null,
        public ?int $viewportWidth = null,
        public ?int $viewportHeight = null,
        public int $dpi = 96,
        public ?Encryption $encryption = null,
        public ?string $ownerPassword = null,
        public ?string $userPassword = null,
        public ResponseMode $responseMode = ResponseMode::Attachment,
        public bool $cached = true,
        public ?string $userAgent = null,
        public ?string $httpUserName = null,
        public ?string $httpPassword = null,
        public bool $images = true,
        public bool $links = true,
        public bool $backgrounds = true,
        public bool $forms = false,
        public bool $printMedia = true,
        public bool $greyscale = false,
        public bool $allowPrint = true,
        public bool $allowModify = true,
        public bool $allowCopy = true,
    ) {
    }
}
