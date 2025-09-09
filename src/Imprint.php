<?php

/**
 * @package Imprint
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs;

use DecodeLabs\Atlas\File;
use DecodeLabs\Atlas\File\Local as LocalFile;
use DecodeLabs\Atlas\File\Memory as MemoryFile;
use DecodeLabs\Imprint\Adapter;
use DecodeLabs\Imprint\Options;
use DecodeLabs\Imprint\Request;
use DecodeLabs\Imprint\Request\Source\File as FileSource;
use DecodeLabs\Imprint\Request\Source\Url as UrlSource;
use DecodeLabs\Imprint\Request\Target;
use DecodeLabs\Imprint\Request\Target\LocalFile as LocalFileTarget;
use DecodeLabs\Imprint\Request\Target\S3 as S3Target;
use DecodeLabs\Imprint\Request\Target\TempFile as TempFileTarget;
use DecodeLabs\Imprint\Request\Target\TempUrl as TempUrlTarget;
use DecodeLabs\Kingdom\Service;
use DecodeLabs\Kingdom\ServiceTrait;

class Imprint implements Service
{
    use ServiceTrait;

    public function __construct(
        protected Adapter $adapter,
        protected Hydro $hydro
    ) {
    }

    public function urlToLocalFile(
        string $url,
        string|LocalFile $target,
        ?Options $options = null
    ): LocalFile {
        $options ??= new Options();

        $request = new Request(
            new UrlSource($url),
            $target = new LocalFileTarget($target),
            $options
        );

        $output = $this->adapter->convert($request);
        return $this->toLocalFile($output, $target);
    }

    public function fileToLocalFile(
        File $file,
        string|LocalFile $target,
        ?Options $options = null
    ): LocalFile {
        $options ??= new Options();

        $request = new Request(
            new FileSource($file),
            $target = new LocalFileTarget($target),
            $options
        );

        $output = $this->adapter->convert($request);
        return $this->toLocalFile($output, $target);
    }

    public function stringToLocalFile(
        string $string,
        string|LocalFile $target,
        ?Options $options = null
    ): LocalFile {
        $file = MemoryFile::create();
        $file->write($string);

        return $this->fileToLocalFile($file, $target, $options);
    }

    /**
     * @param Target<string>|Target<File> $output
     */
    protected function toLocalFile(
        Target $output,
        LocalFileTarget $target
    ): LocalFile {
        if ($output instanceof LocalFileTarget) {
            return $output->value;
        }

        if ($output instanceof TempFileTarget) {
            $target->value->putContents($output->value);
            return $target->value;
        }

        if (
            $output instanceof S3Target ||
            $output instanceof TempUrlTarget
        ) {
            return $this->hydro->getFile($output->value, $target->value->path);
        }

        throw Exceptional::ComponentUnavailable(
            message: 'Unsupported output type: ' . get_class($output),
            data: $output
        );
    }

    public function urlToTempFile(
        string $url,
        ?string $fileName = null,
        ?Options $options = null
    ): MemoryFile {
        $options ??= new Options();

        if ($fileName === null) {
            $parts = parse_url($url);
            $fileName = basename($parts['path'] ?? 'document') . '.pdf';
        }

        $request = new Request(
            new UrlSource($url),
            $target = new TempFileTarget($fileName),
            $options
        );

        $output = $this->adapter->convert($request);
        return $this->toTempFile($output, $target);
    }

    public function fileToTempFile(
        string|File $file,
        ?string $fileName = null,
        ?Options $options = null
    ): MemoryFile {
        $options ??= new Options();

        if ($fileName === null) {
            if (is_string($file)) {
                $fileName = basename($file) . '.pdf';
            } else {
                $fileName = $file->name . '.pdf';
            }
        }

        $request = new Request(
            new FileSource($file),
            $target = new TempFileTarget($fileName),
            $options
        );

        $output = $this->adapter->convert($request);
        return $this->toTempFile($output, $target);
    }

    public function stringToTempFile(
        string $string,
        ?string $fileName = null,
        ?Options $options = null
    ): MemoryFile {
        $file = MemoryFile::create();
        $file->write($string);
        return $this->fileToTempFile($file, $fileName, $options);
    }

    /**
     * @param Target<string>|Target<File> $output
     */
    protected function toTempFile(
        Target $output,
        TempFileTarget $target
    ): MemoryFile {
        if ($output instanceof TempFileTarget) {
            return $output->value;
        }

        if ($output instanceof LocalFileTarget) {
            $target->value->putContents($output->value);
            return $target->value;
        }

        if (
            $output instanceof S3Target ||
            $output instanceof TempUrlTarget
        ) {
            return $this->hydro->getTempFile($output->value);
        }

        throw Exceptional::ComponentUnavailable(
            message: 'Unsupported output type: ' . get_class($output),
            data: $output
        );
    }

    public function urlToTempUrl(
        string $url,
        ?string $fileName = null,
        ?Options $options = null
    ): string {
        $options ??= new Options();

        if ($fileName === null) {
            $parts = parse_url($url);
            $fileName = basename($parts['path'] ?? 'document') . '.pdf';
        }

        $request = new Request(
            new UrlSource($url),
            new TempUrlTarget($fileName),
            $options
        );

        $output = $this->adapter->convert($request);

        if (!$output instanceof TempUrlTarget) {
            throw Exceptional::UnexpectedValue(
                message: 'Unable to convert ' . get_class($output) . ' to TempUrlTarget',
                data: $output
            );
        }

        return $output->value;
    }

    public function fileToTempUrl(
        string|File $file,
        ?string $fileName = null,
        ?Options $options = null
    ): string {
        $options ??= new Options();

        if ($fileName === null) {
            if (is_string($file)) {
                $fileName = basename($file) . '.pdf';
            } else {
                $fileName = $file->name . '.pdf';
            }
        }

        $request = new Request(
            new FileSource($file),
            new TempUrlTarget($fileName),
            $options
        );

        $output = $this->adapter->convert($request);

        if (!$output instanceof TempUrlTarget) {
            throw Exceptional::UnexpectedValue(
                message: 'Unable to convert ' . get_class($output) . ' to TempUrlTarget',
                data: $output
            );
        }

        return $output->value;
    }
}
