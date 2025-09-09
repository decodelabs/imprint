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
use DecodeLabs\Imprint\Options\Orientation;
use DecodeLabs\Imprint\Options\PageSize;
use DecodeLabs\Imprint\Options\Quality;
use DecodeLabs\Imprint\Options\ResponseMode;
use DecodeLabs\Imprint\Options\Unit;
use DecodeLabs\Imprint\Request;
use DecodeLabs\Imprint\Request\Target;
use DecodeLabs\Imprint\Request\Target\LocalFile as LocalFileTarget;
use DecodeLabs\Imprint\Request\Target\TempFile as TempFileTarget;

class PdfLayer implements Adapter
{
    public function __construct(
        protected Hydro $hydro,
        protected string $apiKey,
        protected ?string $secretKey = null,
    ) {
    }

    public function convert(
        Request $request
    ): Target {
        $postData = [];
        $data = ['access_key' => $this->apiKey];

        if ($request->source->value instanceof File) {
            if ($this->secretKey !== null) {
                throw Exceptional::Setup(
                    message: 'Secret keyword cannot be used with file input'
                );
            }

            $postData['document_html'] = $request->source->value->getContents();
        } else {
            $data['document_url'] = $url = $request->source->value;

            if ($this->secretKey !== null) {
                $data['secret_key'] = md5($url . $this->secretKey);
            }
        }

        $data['document_name'] = $request->target->fileName;

        if ($request->options->title !== null) {
            $data['title'] = $request->options->title;
        }

        if ($request->options->subject !== null) {
            $data['subject'] = $request->options->subject;
        }

        if ($request->options->creator !== null) {
            $data['creator'] = $request->options->creator;
        }

        if ($request->options->author !== null) {
            $data['author'] = $request->options->author;
        }

        if ($request->options->quality === Quality::Low) {
            $data['low_quality'] = true;
        }

        if ($request->options->unit !== Unit::Pixels) {
            $data['custom_unit'] = $request->options->unit->value;
        }

        if ($request->options->orientation !== Orientation::Portrait) {
            $data['orientation'] = lcfirst($request->options->orientation->name);
        }

        if ($request->options->pageSize !== PageSize::A4) {
            $data['page_size'] = $request->options->pageSize->name;
        }

        if ($request->options->width !== null) {
            $data['page_width'] = $request->options->width;
        }

        if ($request->options->height !== null) {
            $data['page_height'] = $request->options->height;
        }

        if ($request->options->marginTop !== null) {
            $data['margin_top'] = $request->options->marginTop;
        }

        if ($request->options->marginBottom !== null) {
            $data['margin_bottom'] = $request->options->marginBottom;
        }

        if ($request->options->marginLeft !== null) {
            $data['margin_left'] = $request->options->marginLeft;
        }

        if ($request->options->marginRight !== null) {
            $data['margin_right'] = $request->options->marginRight;
        }

        if (null !== ($viewport = $request->options->viewport)) {
            $data['viewport'] = $viewport;
        }

        if ($request->options->dpi !== 96) {
            $data['dpi'] = $request->options->dpi;
        }

        if ($request->options->encryption !== null) {
            $data['encryption'] = $request->options->encryption->value;
        }

        if ($request->options->ownerPassword !== null) {
            $data['owner_password'] = $request->options->ownerPassword;
        }

        if ($request->options->userPassword !== null) {
            $data['user_password'] = $request->options->userPassword;
        }

        if ($request->options->responseMode === ResponseMode::Inline) {
            $data['inline'] = true;
        }

        if (!$request->options->cached) {
            $data['force'] = true;
        }

        if ($request->options->userAgent !== null) {
            $data['user_agent'] = $request->options->userAgent;
        }

        if ($request->options->httpUserName !== null) {
            $data['auth_user'] = $request->options->httpUserName;
        }

        if ($request->options->httpPassword !== null) {
            $data['auth_pass'] = $request->options->httpPassword;
        }

        if (!$request->options->images) {
            $data['no_images'] = true;
        }

        if (!$request->options->links) {
            $data['no_hyperlinks'] = true;
        }

        if (!$request->options->backgrounds) {
            $data['no_backgrounds'] = true;
        }

        if (!$request->options->javascript) {
            $data['no_javascript'] = true;
        }

        if ($request->options->forms) {
            $data['forms'] = true;
        }

        if ($request->options->printMedia) {
            $data['use_print_media'] = true;
        }

        if ($request->options->greyscale) {
            $data['greyscale'] = true;
        }

        if (!$request->options->allowPrint) {
            $data['no_print'] = true;
        }

        if (!$request->options->allowModify) {
            $data['no_modify'] = true;
        }

        if (!$request->options->allowCopy) {
            $data['no_copy'] = true;
        }

        $response = $this->hydro->request('POST', [
            'url' => 'https://api.pdflayer.com/api/convert?' . http_build_query($data),
            'form_params' => $postData
        ]);

        if ($response->getHeaderLine('Content-Type') !== 'application/pdf') {
            /** @var array{error:array{info:string,code:int}} */
            $json = json_decode($response->getBody()->getContents(), true);

            throw Exceptional::Runtime(
                message: $json['error']['info'],
                code: $json['error']['code'],
                data: $json
            );
        }

        $target = $request->target;

        if ($target instanceof TempFileTarget) {
            $target->value = $this->hydro->responseToMemoryFile($response);
            return $target;
        }

        if ($target instanceof LocalFileTarget) {
            $target->value = $this->hydro->responseToFile($response, $target->value);
            return $target;
        }

        throw Exceptional::ComponentUnavailable(
            message: 'Unsupported output type: ' . get_class($target),
            data: $target
        );
    }
}
