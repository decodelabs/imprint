<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Imprint\Adapter;

use DecodeLabs\Atlas\File;
use DecodeLabs\Coercion;
use DecodeLabs\Exceptional;
use DecodeLabs\Hydro;
use DecodeLabs\Imprint\Adapter;
use DecodeLabs\Imprint\Request;
use DecodeLabs\Imprint\Request\Target;
use DecodeLabs\Imprint\Request\Target\LocalFile as LocalFileTarget;
use DecodeLabs\Imprint\Request\Target\TempFile as TempFileTarget;
use DecodeLabs\Imprint\Request\Target\TempUrl as TempUrlTarget;

class DocRaptor implements Adapter
{
    public function __construct(
        protected Hydro $hydro,
        protected string $apiKey,
        protected bool $test = false,
    ) {
    }



    public function convert(
        Request $request
    ): Target {
        $data = [
            'type' => 'pdf',
        ];

        if (
            $this->apiKey === 'YOUR_API_KEY_HERE' ||
            $this->test
        ) {
            $data['test'] = true;
        }

        if ($request->source->value instanceof File) {
            $data['document_content'] = $request->source->value->getContents();
        } else {
            $data['document_url'] = $request->source->value;
        }

        $data['name'] = $request->options->title ?? $request->target->fileName;

        if (!$request->options->printMedia) {
            $data['media_type'] = 'screen';
        }

        $data['javascript'] = $request->options->javascript;
        $data['ignore_console_messages'] = true;

        $prince = [];

        if ($request->options->httpUserName !== null) {
            $prince['http_user'] = $request->options->httpUserName;
        }

        if ($request->options->httpPassword !== null) {
            $prince['http_password'] = $request->options->httpPassword;
        }

        if (!$request->options->fonts) {
            $prince['no_embed_fonts'] = true;
        }

        if ($request->options->encryption !== null) {
            $prince['encrypt'] = true;
            $prince['key_bits'] = $request->options->encryption->value;
        }

        if ($request->options->userPassword !== null) {
            $prince['user_password'] = $request->options->userPassword;
        }

        if ($request->options->ownerPassword !== null) {
            $prince['owner_password'] = $request->options->ownerPassword;
        }

        if (!$request->options->allowPrint) {
            $prince['disallow_print'] = true;
        }

        if (!$request->options->allowCopy) {
            $prince['disallow_copy'] = true;
            $prince['allow_cope_for_accessibility'] = true;
        }

        if (!$request->options->allowModify) {
            $prince['disallow_modify'] = true;
        }

        if ($request->options->dpi !== 96) {
            $prince['css_dpi'] = $request->options->dpi;
        }

        if ($request->options->title !== null) {
            $prince['pdf_title'] = $request->options->title;
        }

        if (!empty($prince)) {
            $data['prince_options'] = $prince;
        }

        $async = false;
        $target = $request->target;

        if ($target instanceof TempUrlTarget) {
            $data['async'] = $async = true;
        }

        $response = $this->hydro->request('POST', [
            'url' => 'https://api.docraptor.com/docs',
            'body' => json_encode($data),
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            if (false === ($data = simplexml_load_string($response->getBody()->getContents()))) {
                throw Exceptional::Runtime(
                    message: 'Invalid response from DocRaptor',
                    data: $response
                );
            }

            throw Exceptional::Runtime(
                message: $data->errors[0]->error,
                code: $$response->getStatusCode(),
                data: $data
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

        if (
            $target instanceof TempUrlTarget &&
            $async
        ) {
            /** @var array{status_id:string} */
            $json = json_decode($response->getBody()->getContents(), true);
            $jobId = $json['status_id'];
            $target->value = $this->awaitAsyncJob($jobId);
            return $target;
        }

        throw Exceptional::Runtime(
            message: 'Unsupported output type: ' . get_class($target),
            data: $target
        );
    }

    private function awaitAsyncJob(
        string $jobId
    ): string {
        $maxAttempts = 10;
        $attempts = 0;

        while (true) {
            $response = $this->hydro->getJson(
                'https://' . $this->apiKey . '@docraptor.com/status/' . $jobId,
                fn ($response) => throw Exceptional::Runtime(
                    message: 'Failed to get status for job ' . $jobId,
                    data: $response
                )
            );

            if (!isset($response['status'])) {
                throw Exceptional::Runtime(
                    message: 'Invalid status response for job ' . $jobId,
                    data: $response
                );
            }

            switch ($response['status']) {
                case 'completed':
                    return Coercion::asString($response['download_url']);

                case 'failed':
                    throw Exceptional::Runtime(
                        message: 'Failed to convert document: ' . Coercion::toString($response['message'] ?? null),
                        data: $response
                    );

                default:
                    $attempts++;

                    if ($attempts >= $maxAttempts) {
                        throw Exceptional::Runtime(
                            message: 'Max status attempts reached for job ' . $jobId,
                            data: $response
                        );
                    }

                    sleep(1);
            }
        }
    }
}
