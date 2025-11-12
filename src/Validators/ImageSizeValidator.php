<?php

/*
 * This file is part of dshovchko/imageschecker.
 *
 * Copyright (c) dshovchko.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DShovchko\ImagesChecker\Validators;

use DOMDocument;
use DOMElement;
use DShovchko\ImagesChecker\ImageSizeDetector;
use Flarum\Settings\SettingsRepositoryInterface;

class ImageSizeValidator
{
    public const MODE_DEFAULT = 'default';
    public const MODE_FAST = 'fast';
    public const MODE_FULL = 'full';

    // protected $settings;

    // public function __construct(SettingsRepositoryInterface $settings)
    // {
    //     $this->settings = $settings;
    // }

    protected $lastCheckHadImages = false;
    protected $mode = self::MODE_DEFAULT;

    public function setMode(string $mode): void
    {
        $allowed = [self::MODE_DEFAULT, self::MODE_FAST, self::MODE_FULL];
        if (!in_array($mode, $allowed, true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported validation mode "%s"', $mode));
        }
        $this->mode = $mode;
    }

    protected function hasSrc(DOMElement $el)
    {
        if ($el->hasAttribute('src')) {
            return true;
        }
        throw new \Exception(sprintf('The src attribute is absent in <%s> element', $el->tagName));
    }

    protected function isValidImageUrl(string $url)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 5
            ]
        ]);
        
        set_error_handler(function() {});
        $headers = get_headers($url, true, $context);
        restore_error_handler();
        
        if ($headers === false) {
            throw new \Exception(sprintf('The image URL (%s) is invalid', $url));
        }
        $statusParts = explode(' ', $headers[0]);
        if (count($statusParts) < 2 || !is_numeric($statusParts[1])) {
            throw new \Exception(sprintf('Invalid HTTP status line for image URL (%s): %s', $url, $headers[0]));
        }
        $status = (int)$statusParts[1];
        if ($status >= 400 && $status !== 403 && $status !== 429) {
            throw new \Exception(sprintf('The image URL (%s) is invalid', $url));
        }
        return true;
    }

    public function hasHeight(DOMElement $el)
    {
        return $el->hasAttribute('height');
    }

    protected function hasExactHeight(DOMElement $el)
    {
        $height = ImageSizeDetector::getHeight($el->getAttribute('src'));
        return $this->hasHeight($el) && (int)$el->getAttribute('height') === $height;
    }

    public function hasWidth(DOMElement $el)
    {
        return $el->hasAttribute('width');
    }

    protected function hasExactWidth(DOMElement $el)
    {
        $width = ImageSizeDetector::getWidth($el->getAttribute('src'));
        return $this->hasWidth($el) && (int)$el->getAttribute('width') === $width;
    }

    public function checkContent(string $content)
    {
        if (trim($content) === '' || stripos($content, '<img') === false) {
            $this->lastCheckHadImages = false;
            return true;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previousState = libxml_use_internal_errors(true);

        $flags = LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET;
        if (defined('LIBXML_HTML_NOIMPLIED')) {
            $flags |= LIBXML_HTML_NOIMPLIED;
        }
        if (defined('LIBXML_HTML_NODEFDTD')) {
            $flags |= LIBXML_HTML_NODEFDTD;
        }

        $loaded = $dom->loadHTML($this->prepareHtmlFragment($content), $flags);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        if (!$loaded) {
            $messages = array_map(static function ($error) {
                return trim($error->message);
            }, $errors);
            $message = implode('; ', $messages);
            throw new \RuntimeException($message !== '' ? $message : 'Unable to parse post content');
        }

        $nodes = $dom->getElementsByTagName('img');
        $this->lastCheckHadImages = $nodes->length > 0;
        $result = true;
        foreach ($nodes as $node) {
            if ($this->mode === self::MODE_FULL) {
                $result = $result && $this->checkImageStrict($node);
                continue;
            }

            if ($this->mode === self::MODE_FAST) {
                $result = $result && $this->checkImageFast($node);
                continue;
            }

            $result = $result && $this->checkImageDefault($node);
        }

        return $result;
    }

    protected function prepareHtmlFragment(string $content): string
    {
        $trimmed = trim($content);

        if (str_starts_with($trimmed, '<?xml')) {
            $trimmed = (string) preg_replace('/^<\?xml[^>]*>\s*/', '', $trimmed); // strip xml declaration
        }

        return '<?xml encoding="UTF-8">'.$trimmed;
    }

    public function lastCheckHadImages(): bool
    {
        return $this->lastCheckHadImages;
    }

    protected function checkImageDefault(DOMElement $el)
    {
        if ($this->checkImageFast($el)) {
            return true;
        }

        // Validate URL and return false
        $this->isValidImageUrl($el->getAttribute('src'));
        return false;
    }

    protected function checkImageStrict(DOMElement $el)
    {
        return $this->hasSrc($el)
            && $this->isValidImageUrl($el->getAttribute('src'))
            && $this->hasExactHeight($el)
            && $this->hasExactWidth($el);
    }

    protected function checkImageFast(DOMElement $el)
    {
        return $this->hasSrc($el)
            && $this->hasHeight($el)
            && $this->hasWidth($el);
    }
}
