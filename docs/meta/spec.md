# Imprint — Package Specification

> **Cluster:** `content`
> **Language:** `php`
> **Milestone:** `m5`
> **Repo:** `https://github.com/decodelabs/imprint`
> **Role:** HTML to PDF generator

This document describes the purpose, contracts, and design of **Imprint** within the Decode Labs ecosystem.

It is aimed at:

- Developers **using** Imprint in their own applications or libraries.
- Contributors **maintaining or extending** Imprint.
- Tools and AI assistants that need to reason about its behaviour.

---

## 1. Overview

### 1.1 Purpose

Imprint provides a simple and intuitive interface for converting HTML documents to PDF via various third-party services. PDF generation is a complex task requiring significant setup and resources, and multiple services exist with different APIs and feature sets. Imprint abstracts this complexity by providing a unified interface that works with multiple PDF generation services (adapters), allowing developers to switch between services without changing their code. The package supports multiple input sources (URLs, files, strings) and output targets (local files, temporary files, temporary URLs) while maintaining a consistent API.

### 1.2 Non-Goals

Imprint does **not**:

- Provide a local PDF generation engine (relies on third-party services)
- Handle PDF manipulation or editing (only generation)
- Provide PDF viewing or rendering capabilities
- Handle document templates or complex layout systems
- Manage service authentication or billing (handled by adapters)
- Provide caching or rate limiting (handled by services or application layer)

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `content` (see Chorus taxonomy)
- Imprint is a content transformation package that sits between HTML content and PDF output. It integrates with Hydro for HTTP requests to PDF services, Atlas for file handling, and Kingdom for service resolution. It provides a service abstraction layer that makes PDF generation accessible without dealing with individual service APIs.

### 2.2 Typical Usage Contexts

Typical places Imprint appears:

- Converting HTML reports to PDF for download
- Generating PDF documents from web pages
- Creating PDF exports of content
- Converting user-generated HTML content to PDF
- Batch PDF generation workflows

Imprint is intended to be used whenever you need to convert HTML to PDF and want a simple, service-agnostic interface that can work with multiple PDF generation providers.

---

## 3. Public Surface

> This section focuses on the conceptual API, not every symbol.

### 3.1 Key Types

The primary public types are:

- `DecodeLabs\Imprint`
  The main service class implementing Kingdom Service interface. Provides convenience methods for converting HTML to PDF with various input/output combinations.

- `DecodeLabs\Imprint\Adapter`
  Interface for PDF generation service adapters. Implementations handle communication with specific PDF services (Doppio, PdfLayer, DocRaptor).

- `DecodeLabs\Imprint\Adapter\Doppio`
  Adapter for Doppio service (headless Chromium-based rendering).

- `DecodeLabs\Imprint\Adapter\PdfLayer`
  Adapter for PdfLayer service.

- `DecodeLabs\Imprint\Adapter\DocRaptor`
  Adapter for DocRaptor service (Prince XML-based).

- `DecodeLabs\Imprint\Options`
  Configuration class for PDF generation options including page size, margins, orientation, quality, encryption, and rendering options.

- `DecodeLabs\Imprint\Request`
  Request object containing source (URL or File), target (output destination), and options.

- `DecodeLabs\Imprint\Request\Source`
  Interface for input sources (URL or File implementations).

- `DecodeLabs\Imprint\Request\Target`
  Interface for output targets (LocalFile, TempFile, TempUrl, S3 implementations).

- `DecodeLabs\Imprint\Options\PageSize`
  Enum for page sizes (A0-A9, B0-B9, Letter, Legal, Tabloid, etc.).

- `DecodeLabs\Imprint\Options\Orientation`
  Enum for page orientation (Portrait, Landscape).

- `DecodeLabs\Imprint\Options\Quality`
  Enum for PDF quality (High, Low).

### 3.2 Main Entry Points

The main usage pattern is via the Imprint service with convenience methods:

```php
use DecodeLabs\Imprint;
use DecodeLabs\Imprint\Options;
use DecodeLabs\Imprint\Options\PageSize;
use DecodeLabs\Monarch;

$imprint = Monarch::getService(Imprint::class);

$options = new Options(
    pageSize: PageSize::A5,
    marginTop: 10,
    marginBottom: 10
);

$file = $imprint->urlToLocalFile(
    'https://example.com/document.html',
    '/path/to/save/document.pdf',
    $options
);
```

For different input/output combinations:

```php
// URL to local file
$file = $imprint->urlToLocalFile($url, $path, $options);

// File to temporary file
$tempFile = $imprint->fileToTempFile($file, 'document.pdf', $options);

// String to temporary URL
$url = $imprint->stringToTempUrl($html, 'document.pdf', $options);
```

---

## 4. Dependencies

### 4.1 Direct Decode Labs Dependencies

From `composer.json`:

- `decodelabs/atlas`
  File handling for reading HTML source files and writing PDF output files.

- `decodelabs/exceptional`
  Enhanced exception handling throughout the package.

- `decodelabs/hydro`
  HTTP client for making requests to PDF generation service APIs.

- `decodelabs/kingdom`
  Service container integration for service resolution and dependency injection.

### 4.2 External Dependencies

None required for runtime operation.

See `composer.json` for supported PHP versions.

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- All conversion methods return appropriate types (LocalFile, MemoryFile, or string URL)
- Options are always applied, but unsupported options are silently ignored by adapters
- Source content (URL, file, or string) is always valid HTML
- Output targets are always created or returned as specified
- Adapters handle service-specific API differences transparently
- Temporary files are created as MemoryFile instances
- Local files are saved to disk and returned as LocalFile instances
- Temporary URLs are only supported by adapters that provide this capability

### 5.2 Input & Output Contracts

**Imprint::urlToLocalFile(string url, string|LocalFile target, ?Options options = null):**
- **Input:** URL to HTML document, destination path or LocalFile, optional options
- **Output:** LocalFile containing generated PDF
- **Preconditions:** URL must be accessible, target path must be writable
- **Postconditions:** PDF file is saved to disk and returned

**Imprint::stringToTempFile(string content, ?string fileName = null, ?Options options = null):**
- **Input:** HTML string, optional filename, optional options
- **Output:** MemoryFile containing generated PDF
- **Preconditions:** Content must be valid HTML
- **Postconditions:** PDF is in memory, ready for use or saving

**Imprint::urlToTempUrl(string url, ?string fileName = null, ?Options options = null):**
- **Input:** URL to HTML document, optional filename, optional options
- **Output:** String URL to temporary PDF (only if adapter supports it)
- **Preconditions:** URL must be accessible, adapter must support TempUrl target
- **Postconditions:** Returns URL string or throws exception if unsupported

**Adapter::convert(Request request):**
- **Input:** Request object with source, target, and options
- **Output:** Target instance with value populated
- **Preconditions:** Adapter must be properly configured with API credentials
- **Postconditions:** PDF is generated according to request, target value is set

---

## 6. Error Handling

### 6.1 Exception Types

Imprint throws Exceptional exceptions:

- `Exceptional::Runtime`: When PDF generation fails (service errors, invalid responses)
- `Exceptional::ComponentUnavailable`: When adapter doesn't support requested target type
- `Exceptional::UnexpectedValue`: When adapter returns unexpected target type
- `Exceptional::Setup`: When adapter configuration is invalid (e.g., secret key with file input in PdfLayer)

All exceptions use the Exceptional pattern for enhanced stack traces and context. Service-specific error messages are preserved in exception data.

### 6.2 Error Strategy

Imprint uses a fail-fast error strategy. Service errors, invalid responses, or unsupported operations result in exceptions being thrown immediately. The package does not attempt to retry failed requests or provide fallback behavior. Error messages from PDF services are preserved and included in exception data for debugging.

---

## 7. Configuration & Extensibility

### 7.1 Configuration

Configuration is done through:

- **Adapter selection:** Choose which PDF service adapter to use (Doppio, PdfLayer, DocRaptor)
- **Adapter credentials:** Provide API keys and service-specific configuration to adapter constructors
- **Options object:** Configure PDF generation parameters (page size, margins, quality, encryption, etc.)

Options that are not supported by a particular adapter are silently ignored, allowing the same Options object to be used with different adapters.

### 7.2 Extension Points

Imprint supports extension via:

- **Custom Adapter implementations:** Implement `DecodeLabs\Imprint\Adapter` interface to add support for additional PDF generation services
- **Custom Target implementations:** Implement `DecodeLabs\Imprint\Request\Target` interface for custom output destinations (e.g., S3, cloud storage)
- **Options configuration:** Extend Options class or add new option enums for additional configuration parameters

---

## 8. Interactions with Other Packages

Imprint is designed to integrate with:

- **`decodelabs/hydro`**
  Uses Hydro for making HTTP requests to PDF generation service APIs. All adapters use Hydro to communicate with their respective services.

- **`decodelabs/atlas`**
  Uses Atlas for file operations. Source files are read via Atlas, and output files are written using Atlas LocalFile and MemoryFile instances.

- **`decodelabs/kingdom`**
  Uses Kingdom for service resolution. Imprint implements Service interface and adapters are registered via Kingdom container.

Design assumptions:

- Hydro is available for HTTP requests to PDF services
- Atlas is available for file operations
- Kingdom service container is available for service resolution
- PDF generation services are accessible via HTTP and require API authentication

---

## 9. Usage Examples

### 9.1 Basic URL to PDF

```php
use DecodeLabs\Imprint;
use DecodeLabs\Monarch;

$imprint = Monarch::getService(Imprint::class);

$file = $imprint->urlToLocalFile(
    'https://example.com/report.html',
    '/path/to/report.pdf'
);
```

### 9.2 HTML String to PDF with Options

```php
use DecodeLabs\Imprint;
use DecodeLabs\Imprint\Options;
use DecodeLabs\Imprint\Options\PageSize;
use DecodeLabs\Imprint\Options\Orientation;
use DecodeLabs\Monarch;

$imprint = Monarch::getService(Imprint::class);

$options = new Options(
    pageSize: PageSize::A4,
    orientation: Orientation::Landscape,
    marginTop: 20,
    marginBottom: 20,
    marginLeft: 15,
    marginRight: 15,
    title: 'My Document',
    author: 'John Doe'
);

$tempFile = $imprint->stringToTempFile(
    '<h1>Hello, World!</h1><p>This is a PDF.</p>',
    'document.pdf',
    $options
);
```

### 9.3 File to Temporary URL

```php
use DecodeLabs\Imprint;
use DecodeLabs\Monarch;

$imprint = Monarch::getService(Imprint::class);

$url = $imprint->fileToTempUrl(
    '/path/to/document.html',
    'document.pdf'
);

// URL points to temporary PDF on service
```

### 9.4 Adapter Configuration

```php
use DecodeLabs\Dovetail\Env;
use DecodeLabs\Hydro;
use DecodeLabs\Imprint\Adapter;
use DecodeLabs\Imprint\Adapter\Doppio;
use DecodeLabs\Pandora;

$pandora->setFactory(
    Adapter::class,
    fn () => new Doppio(
        $pandora->get(Hydro::class),
        Env::asString('DOPPIO_API_KEY')
    )
);
```

---

## 10. Implementation Notes (For Contributors)

### 10.1 Internal Architecture

At a high level, Imprint:

- Uses adapter pattern to abstract different PDF service APIs
- Provides convenience methods that construct Request objects and handle target conversion
- Uses Source and Target interfaces with generic types for type safety
- Converts between different target types when adapters return different formats (e.g., downloading TempUrl to LocalFile)
- Maps Options to service-specific API parameters in each adapter
- Uses Hydro for all HTTP communication with PDF services

Contributors should:

- Maintain adapter interface consistency across implementations
- Preserve service-specific error messages in exceptions
- Handle unsupported options gracefully (ignore rather than error)
- Ensure proper file handling via Atlas
- Keep convenience methods simple and delegate to adapters
- Document which options are supported by each adapter

### 10.2 Performance Considerations

- PDF generation is inherently slow (network requests to external services)
- Large HTML documents may take significant time to convert
- Temporary file operations use memory files to avoid disk I/O when possible
- DocRaptor adapter supports async operations for TempUrl targets with polling
- No local caching is performed; each request generates a new PDF

### 10.3 Gotchas & Historical Decisions

- **Option support:** Not all options are supported by all adapters. Unsupported options are silently ignored, which may lead to unexpected results if developers assume all options work with all adapters.
- **TempUrl support:** Only some adapters support temporary URL targets. The main Imprint service throws exceptions if an adapter doesn't support the requested target type.
- **Service differences:** Doppio uses headless Chromium for exact browser rendering, while PdfLayer and DocRaptor use different rendering engines. This can lead to visual differences in output.
- **File vs URL input:** Some adapters have different capabilities or requirements for file vs URL input (e.g., PdfLayer secret key only works with URLs).
- **Async operations:** DocRaptor supports async operations for TempUrl targets, which requires polling the status endpoint. This is handled internally but adds complexity.

---

## 11. Testing & Quality

### 11.1 Testing Strategy

Tests should cover:

- All convenience methods (urlToLocalFile, fileToTempFile, stringToTempUrl, etc.)
- All adapter implementations (Doppio, PdfLayer, DocRaptor)
- Options mapping to service-specific parameters
- Target type conversion (TempUrl to LocalFile, etc.)
- Error handling for service failures
- Unsupported option handling
- File and URL source handling
- Temporary file and URL generation
- Async job polling (DocRaptor)
- Edge cases (empty HTML, very large documents, invalid URLs)

### 11.2 Quality Signals

From the Decode Labs package index (at time of writing):

- **Code:** 4.5
- **Readme:** 3.5
- **Docs:** 0
- **Tests:** 0

Imprint is a mature, production-ready package with high code quality. The README provides good usage examples and adapter information, though comprehensive documentation (this spec) was not present at indexing time. Test coverage is planned but not yet implemented. The package demonstrates solid abstraction over multiple PDF services with a clean, consistent API.

---

## 12. Roadmap & Future Ideas

Non-binding ideas:

- Comprehensive test suite covering all adapters and conversion scenarios
- Additional adapter implementations for other PDF services
- Local PDF generation option (using headless browser libraries)
- PDF template system for consistent document structure
- Batch conversion support for multiple documents
- Progress tracking for long-running conversions
- Caching layer for repeated conversions
- Enhanced error messages with service-specific guidance
- Support for additional output formats (images, other document types)

---

## 13. References

- **Chorus docs:**
  - Architecture principles
  - Package taxonomy & clusters
  - Backwards compatibility strategy (once published)

- **Related packages:**
  - `decodelabs/hydro` (HTTP client for service communication)
  - `decodelabs/atlas` (file handling)
  - `decodelabs/kingdom` (service resolution)

- **External services:**
  - Doppio: https://doppio.sh/
  - PdfLayer: https://pdflayer.com/
  - DocRaptor: https://docraptor.com/

- **Repository:**
  - `https://github.com/decodelabs/imprint`

---

> This spec is intended to stay in sync with the **actual behaviour** of the package.
> When you make significant changes to the public surface or semantics, please update this document and, where applicable, add or update ADRs in Chorus.

