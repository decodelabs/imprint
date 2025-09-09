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
use DecodeLabs\Imprint\Request;
use DecodeLabs\Imprint\Request\Target;
use DecodeLabs\Imprint\Request\Target\LocalFile as LocalFileTarget;
use DecodeLabs\Imprint\Request\Target\S3 as S3Target;
use DecodeLabs\Imprint\Request\Target\TempFile as TempFileTarget;
use DecodeLabs\Imprint\Request\Target\TempUrl as TempUrlTarget;

class Doppio implements Adapter
{
    public function __construct(
        protected Hydro $hydro,
        protected string $apiKey,
    ) {
    }



    public function convert(
        Request $request
    ): Target {
        $data = [
            'doppio' => [
                'contentDisposition' => lcfirst($request->options->responseMode->name),
            ]
        ];

        $page = $viewport = $pdf = $margin = [];

        if ($request->source->value instanceof File) {
            $page['setContent'] = [
                'html' => base64_encode($request->source->value->getContents()),
                'options' => [
                    'waitUntil' => ['networkidle0'],
                ],
            ];
        } else {
            $page['goto'] = [
                'url' => $request->source->value,
                'options' => [
                    'waitUntil' => ['networkidle0'],
                ],
            ];

            if ($request->options->referrer !== null) {
                $page['goto']['referrer'] = $request->options->referrer;
            }
        }

        $page['setJavaScriptEnabled'] = $request->options->javascript;
        $page['emulateMediaType'] = $request->options->printMedia ? 'print' : 'screen';

        if ($request->options->userAgent !== null) {
            $page['setUserAgent'] = $request->options->userAgent;
        }

        if (
            $request->options->httpUserName !== null ||
            $request->options->httpPassword !== null
        ) {
            $page['authenticate'] = [
                'username' => $request->options->httpUserName,
                'password' => $request->options->httpPassword,
            ];
        }


        if (
            $request->options->viewportWidth !== null &&
            $request->options->viewportHeight !== null
        ) {
            $viewport['width'] = $request->options->viewportWidth;
            $viewport['height'] = $request->options->viewportHeight;
        }

        if ($request->options->orientation !== Orientation::Portrait) {
            $viewport['isLandscape'] = true;
            $pdf['landscape'] = true;
        }

        $pdf['printBackground'] = $request->options->backgrounds;

        if (!empty($viewport)) {
            $data['launch'] = ['defaultViewport' => $viewport];
        }

        if ($request->options->pageSize !== PageSize::A4) {
            $pdf['format'] = match ($request->options->pageSize) {
                PageSize::A0,
                PageSize::A1,
                PageSize::A2,
                PageSize::A3,
                PageSize::A5,
                PageSize::A6,
                PageSize::Letter,
                PageSize::Legal,
                PageSize::Tabloid,
                PageSize::Ledger
                    => $request->options->pageSize->name,
                default => throw Exceptional::UnexpectedValue(
                    'Doppio does not support \'' . $request->options->pageSize->name . '\' page size'
                ),
            };
        }

        if ($request->options->width !== null) {
            $pdf['width'] = $request->options->width;
        }

        if ($request->options->height !== null) {
            $pdf['height'] = $request->options->height;
        }

        if ($request->options->marginTop !== null) {
            $margin['top'] = $request->options->marginTop;
        }

        if ($request->options->marginBottom !== null) {
            $margin['bottom'] = $request->options->marginBottom;
        }

        if ($request->options->marginLeft !== null) {
            $margin['left'] = $request->options->marginLeft;
        }

        if ($request->options->marginRight !== null) {
            $margin['right'] = $request->options->marginRight;
        }

        if (!empty($margin)) {
            $pdf['margin'] = $margin;
        }

        $pdf['preferCSSPageSize'] = true;

        $page['pdf'] = $pdf;
        $data['page'] = $page;

        $target = $request->target;

        if ($target instanceof TempUrlTarget) {
            $url = 'https://api.doppio.sh/v1/render/pdf/sync';
        } else {
            $url = 'https://api.doppio.sh/v1/render/pdf/direct';
        }

        $response = $this->hydro->request('POST', [
            'url' => $url,
            'body' => json_encode($data),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            /** @var array{statusCode:int,message:array<string>,error:string} */
            $json = json_decode($response->getBody()->getContents(), true);

            throw Exceptional::Runtime(
                message: implode("\n", $json['message']),
                code: $json['statusCode'],
                data: $json
            );
        }

        if ($target instanceof TempFileTarget) {
            $target->value = $this->hydro->responseToMemoryFile($response);
            return $target;
        }

        if ($target instanceof LocalFileTarget) {
            $target->value = $this->hydro->responseToFile($response, $target->value);
            return $target;
        }

        /*
        if ($target instanceof S3Target) {
            $target = new TempFileTarget($target->fileName);
            $target->value = $this->hydro->responseToMemoryFile($response);
            return $target;
        }
        */

        if ($target instanceof TempUrlTarget) {
            /** @var array{documentUrl:string} */
            $json = json_decode($response->getBody()->getContents(), true);
            $target->value = $json['documentUrl'];
            return $target;
        }

        throw Exceptional::ComponentUnavailable(
            message: 'Unsupported output type: ' . get_class($target),
            data: $target
        );
    }
}
