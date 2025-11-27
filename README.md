# Flarum Image Dimensions

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/dshovchko/flarum-image-dimensions/blob/main/LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/dshovchko/flarum-image-dimensions.svg)](https://packagist.org/packages/dshovchko/flarum-image-dimensions)
[![Total Downloads](https://img.shields.io/packagist/dt/dshovchko/flarum-image-dimensions.svg)](https://packagist.org/packages/dshovchko/flarum-image-dimensions)

A Flarum extension that automatically adds `width` and `height` attributes to images in posts, improving page load performance and preventing layout shifts.

## Features

- 🚀 Automatically detects and adds image dimensions
- ⚡ Adds `loading="lazy"` attribute for better performance
- 🔍 Console command to audit existing posts with flexible modes
- 📧 Email reports for batch operations
- ⏰ Scheduled automatic checks (daily/weekly/monthly)
- ⚙️ Configurable check modes (fast/default/full)
- ✅ Supports BBCode, Markdown, and auto-linked images

## Installation

```bash
composer require dshovchko/flarum-image-dimensions
```

## Usage

### Automatic Processing

Once enabled, the extension automatically adds dimensions to all new images posted.

### Scheduled Checks

Configure automatic checks via the admin panel:

1. Go to **Admin → Extensions → Image Dimensions**
2. Enable **Scheduled Checks**
3. Set **Check Frequency** (daily/weekly/monthly)
4. Choose **Check Mode**:
   - **Fast**: Only verify attributes exist
   - **Default**: Verify attributes + URL validity
   - **Full**: Verify exact dimensions
5. Set **Batch Size** (number of discussions per run)
6. Add **Email Recipients** (comma-separated)

> ⚠️ Requires Flarum scheduler to be configured. Add to your crontab:
> ```bash
> * * * * * cd /path/to/flarum && php flarum schedule:run >> /dev/null 2>&1
> ```

### Manual Console Command

Audit existing posts using the `image-dimensions:check` console command:

```bash
# Check a single discussion
php flarum image-dimensions:check --discussion=123

# Check a specific post
php flarum image-dimensions:check --post=456

# Scan all discussions in batches of 250
php flarum image-dimensions:check --all --chunk=250

# Fast mode (verifies only width/height attributes)
php flarum image-dimensions:check --discussion=123 --fast

# Full mode (verifies URLs and actual image dimensions)
php flarum image-dimensions:check --discussion=123 --full

# Email the report
php flarum image-dimensions:check --all --mailto=admin@example.com

# Automatically fix images without dimensions
php flarum image-dimensions:check --discussion=123 --fix
php flarum image-dimensions:check --post=456 --fix
php flarum image-dimensions:check --all --fix --chunk=100
```

> ℹ️  The command requires one of `--discussion=<id>`, `--post=<id>`, or `--all`.

## Supported Image Formats

- JPG/JPEG
- PNG
- GIF
- WebP
- SVG/SVGZ

**For AVIF support:** Install [dshovchko/flarum-avif-support](https://packagist.org/packages/dshovchko/flarum-avif-support) extension (requires PHP 8.2+ for dimensions)

## Why Image Dimensions Matter

Adding `width` and `height` attributes to images:
- Prevents Cumulative Layout Shift (CLS)
- Improves Core Web Vitals scores
- Enhances SEO rankings
- Provides better user experience

## Requirements

- Flarum ^1.0
- PHP 7.4+

## Links

- [GitHub Repository](https://github.com/dshovchko/flarum-image-dimensions)
- [Packagist](https://packagist.org/packages/dshovchko/flarum-image-dimensions)
- [Flarum Community](https://discuss.flarum.org)

## Release Checklist

1. `cd js && npm ci`
2. `npm run build`
3. `git add js/dist` to include the compiled admin/forum bundles
4. Update `CHANGELOG.md` with the version notes
5. Commit, tag (e.g. `v1.5.1`), and push branch + tag to GitHub
6. Publish the GitHub release and ensure Packagist receives the tag

## License

[MIT](https://github.com/dshovchko/flarum-image-dimensions/blob/main/LICENSE)
