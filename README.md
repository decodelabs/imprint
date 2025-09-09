# Imprint

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/imprint?style=flat)](https://packagist.org/packages/decodelabs/imprint)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/imprint.svg?style=flat)](https://packagist.org/packages/decodelabs/imprint)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/imprint.svg?style=flat)](https://packagist.org/packages/decodelabs/imprint)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/decodelabs/imprint/integrate.yml?branch=develop)](https://github.com/decodelabs/imprint/actions/workflows/integrate.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/imprint?style=flat)](https://packagist.org/packages/decodelabs/imprint)

### PDF conversion API interface

Imprint provides a simple and intuitive interface for converting HTML documents to PDF via various 3rd party services.

PDF generation is a notoriously difficult task requiring access to complex systems that require significant setup and resources. A number of services exist to handle this task, but each has their own unique API and set of features - Imprint fills the gap, abstracting the complexity away and making the whole process... less awful.

---

## Installation

Install via Composer:

```bash
composer require decodelabs/imprint
```

## Usage

Imprint uses the `Kingdom` `Service` interface for it's main entry point - if you are using a Service Container then you should provide an `Adapter` implementation to your Container at bootstrap:

```php
use DecodeLabs\Dovetail\Env;
use DecodeLabs\Hydro;
use DecodeLabs\Imprint\Adapter;
use DecodeLabs\Imprint\Adapter\Doppio;

$pandora->setFactory(
    Adapter::class,
    fn () => new Doppio(
        $pandora->get(Hydro::class),
        Env::asString('DOPPIO_API_KEY'),
    )
)
```

### Adapters

Two adapters are currently supported: [Doppio](https://doppio.sh/) and [PdfLayer](https://pdflayer.com/). While more options will be added in the future, these two should cover most use cases. Doppio is likely to be the preferred choice as it uses headless Chromium to render the markup _exactly_ as it would in a browser.


### Conversion

The Imprint `Service` has a number of methods for converting HTML documents to PDF, depending on the source and expected output format.

All of these methods accept an optional [`Options`](./src/Imprint/Options.php) object to control the conversion process. However, not all options are supported by all adapters - unsupported options will be ignored.

```php
use DecodeLabs\Imprint;
use DecodeLabs\Imprint\Options;
use DecodeLabs\Imprint\Options\PageSize;
use DecodeLabs\Monarch;

$imprint = Monarch::getService(Imprint::class);

$options = new Options(
    marginTop: 10,
    marginBottom: 10,
    marginLeft: 10,
    marginRight: 10,
    pageSize: PageSize::A5,
);

// Returns an Atlas File\Local which has been saved to disk
$diskFile = $imprint->urlToLocalFile(
    'https://example.com/document.html',
    '/path/to/save/document.pdf', // Or Atlas File
    $options
);

$diskFile = $imprint->fileToLocalFile(
    '/path/to/document.html',
    '/path/to/save/document.pdf', // Or Atlas File
    $options
);

$diskFile = $imprint->stringToLocalFile(
    '<h1>Hello, world!</h1>',
    '/path/to/save/document.pdf', // Or Atlas File
    $options
);

// Returns an Atlas MemoryFile which can be used directly or saved to disk
$tempFile = $imprint->urlToTempFile(
    'https://example.com/document.html',
    'document.pdf',
    $options
);

$tempFile = $imprint->fileToTempFile(
    '/path/to/document.html',
    'document.pdf',
    $options
);

$tempFile = $imprint->stringToTempFile(
    '<h1>Hello, world!</h1>',
    'document.pdf',
    $options
);

// Returns a temporary URL on the service, only if supported by the adapter
$tempUrl = $imprint->urlToTempUrl(
    'https://example.com/document.html',
    'document.pdf',
    $options
);

$tempUrl = $imprint->fileToTempUrl(
    '/path/to/document.html',
    'document.pdf',
    $options
);

$tempUrl = $imprint->stringToTempUrl(
    '<h1>Hello, world!</h1>',
    'document.pdf',
    $options
);
```

## Licensing

Imprint is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
