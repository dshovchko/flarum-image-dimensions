# Flarum Image Dimensions

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/dshovchko/flarum-image-dimensions/blob/main/LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/dshovchko/flarum-image-dimensions.svg)](https://packagist.org/packages/dshovchko/flarum-image-dimensions)
[![Total Downloads](https://img.shields.io/packagist/dt/dshovchko/flarum-image-dimensions.svg)](https://packagist.org/packages/dshovchko/flarum-image-dimensions)

A Flarum extension that automatically adds `width` and `height` attributes to images in posts, improving page load performance and preventing layout shifts.

## Features

- 🚀 Automatically detects and adds image dimensions
- ⚡ Adds `loading="lazy"` attribute for better performance
- 🔍 Console command to check and fix existing posts
- 📧 Email reports for batch operations
- ✅ Supports BBCode, Markdown, and auto-linked images

## Installation

```bash
composer require dshovchko/flarum-image-dimensions
```

## Usage

### Automatic Processing

Once enabled, the extension automatically adds dimensions to all new images posted.

### Console Command

Check and fix existing posts:

```bash
# Check all discussions
php flarum images:check

# Fix images (add missing dimensions)
php flarum images:check --fix

# Check specific discussion
php flarum images:check --discussion=123

# Check specific post
php flarum images:check --post=456

# Strict mode (verify exact dimensions)
php flarum images:check --strict

# Send report via email
php flarum images:check --mailto=admin@example.com
```

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

## License

[MIT](https://github.com/dshovchko/flarum-image-dimensions/blob/main/LICENSE)
