<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Adapter;

use DecodeLabs\Atlas\File;
use DecodeLabs\Exceptional;
use DecodeLabs\Hydro;
use DecodeLabs\Imprint\Adapter;
use DecodeLabs\Imprint\Options;
use DecodeLabs\Imprint\Options\Orientation;
use DecodeLabs\Imprint\Options\PageSize;
use DecodeLabs\Imprint\Options\Quality;
use DecodeLabs\Imprint\Options\ResponseMode;
use DecodeLabs\Imprint\Options\Unit;

class PdfLayer implements Adapter
{
    public function __construct(
        protected Hydro $hydro,
        protected string $apiKey,
        protected ?string $secretKey = null,
    ) {
    }

    public function convertUrl(
        string $url,
        Options $options
    ): File {
        return $this->convert(
            input: $url,
            options: $options
        );
    }

    public function convertFile(
        File $file,
        Options $options
    ): File {
        return $this->convert(
            input: $file,
            options: $options
        );
    }

    private function convert(
        string|File $input,
        Options $options
    ): File {
        $postData = [];
        $data = ['access_key' => $this->apiKey];

        if ($input instanceof File) {
            if ($this->secretKey !== null) {
                throw Exceptional::Setup(
                    message: 'Secret keyword cannot be used with file input'
                );
            }

            $postData['document_html'] = $input->getContents();
        } else {
            $data['document_url'] = $input;

            if ($this->secretKey !== null) {
                $data['secret_key'] = md5($input . $this->secretKey);
            }
        }

        if ($options->fileName !== null) {
            $data['document_name'] = $options->fileName;
        }

        if ($options->title !== null) {
            $data['title'] = $options->title;
        }

        if ($options->subject !== null) {
            $data['subject'] = $options->subject;
        }

        if ($options->creator !== null) {
            $data['creator'] = $options->creator;
        }

        if ($options->author !== null) {
            $data['author'] = $options->author;
        }

        if ($options->quality === Quality::Low) {
            $data['low_quality'] = true;
        }

        if ($options->unit !== Unit::Pixels) {
            $data['custom_unit'] = $options->unit->value;
        }

        if ($options->orientation !== Orientation::Portrait) {
            $data['orientation'] = lcfirst($options->orientation->name);
        }

        if ($options->pageSize !== PageSize::A4) {
            $data['page_size'] = $options->pageSize->name;
        }

        if ($options->width !== null) {
            $data['page_width'] = $options->width;
        }

        if ($options->height !== null) {
            $data['page_height'] = $options->height;
        }

        if ($options->marginTop !== null) {
            $data['margin_top'] = $options->marginTop;
        }

        if ($options->marginBottom !== null) {
            $data['margin_bottom'] = $options->marginBottom;
        }

        if ($options->marginLeft !== null) {
            $data['margin_left'] = $options->marginLeft;
        }

        if ($options->marginRight !== null) {
            $data['margin_right'] = $options->marginRight;
        }

        if (null !== ($viewport = $options->viewport)) {
            $data['viewport'] = $viewport;
        }

        if ($options->dpi !== 96) {
            $data['dpi'] = $options->dpi;
        }

        if ($options->encryption !== null) {
            $data['encryption'] = $options->encryption->value;
        }

        if ($options->ownerPassword !== null) {
            $data['owner_password'] = $options->ownerPassword;
        }

        if ($options->userPassword !== null) {
            $data['user_password'] = $options->userPassword;
        }

        if ($options->responseMode === ResponseMode::Inline) {
            $data['inline'] = true;
        }

        if (!$options->cached) {
            $data['force'] = true;
        }

        if ($options->userAgent !== null) {
            $data['user_agent'] = $options->userAgent;
        }

        if ($options->httpUserName !== null) {
            $data['auth_user'] = $options->httpUserName;
        }

        if ($options->httpPassword !== null) {
            $data['auth_pass'] = $options->httpPassword;
        }

        if (!$options->images) {
            $data['no_images'] = true;
        }

        if (!$options->links) {
            $data['no_hyperlinks'] = true;
        }

        if (!$options->backgrounds) {
            $data['no_backgrounds'] = true;
        }

        if ($options->forms) {
            $data['forms'] = true;
        }

        if ($options->printMedia) {
            $data['use_print_media'] = true;
        }

        if ($options->greyscale) {
            $data['greyscale'] = true;
        }

        if (!$options->allowPrint) {
            $data['no_print'] = true;
        }

        if (!$options->allowModify) {
            $data['no_modify'] = true;
        }

        if (!$options->allowCopy) {
            $data['no_copy'] = true;
        }

        $response = $this->hydro->request('POST', [
            'url' => 'https://api.pdflayer.com/api/convert?' . http_build_query($data),
            'form_params' => $postData
        ]);

        if ($response->getHeaderLine('Content-Type') === 'application/pdf') {
            return $this->hydro->responseToMemoryFile($response);
        }

        /** @var array{error:array{info:string,code:int}} */
        $json = json_decode($response->getBody()->getContents(), true);

        throw Exceptional::Runtime(
            message: $json['error']['info'],
            code: $json['error']['code'],
            data: $json
        );
    }
}
