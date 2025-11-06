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
    // protected $settings;

    // public function __construct(SettingsRepositoryInterface $settings)
    // {
    //     $this->settings = $settings;
    // }

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
                'method' => 'HEAD'
            ]
        ]);
        $headers = get_headers($url, true, $context);
        if ($headers === false) {
            throw new \Exception(sprintf('The image URL (%s) is invalid', $url));
        }
        $statusParts = explode(' ', $headers[0]);
        if (count($statusParts) < 2 || !is_numeric($statusParts[1])) {
            throw new \Exception(sprintf('Invalid HTTP status line for image URL (%s): %s', $url, $headers[0]));
        }
        $status = (int)$statusParts[1];
        if ($status >= 400) {
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
        return $this->hasHeight($el) && $el->getAttribute('height') == $height;
    }

    public function hasWidth(DOMElement $el)
    {
        return $el->hasAttribute('width');
    }

    protected function hasExactWidth(DOMElement $el)
    {
        $width = ImageSizeDetector::getWidth($el->getAttribute('src'));
        return $this->hasWidth($el) && $el->getAttribute('width') == $width;
    }

    public function checkContent(string $content, bool $strictMode)
    {
        $dom = new DOMDocument();
        $dom->loadXML($content);
        $nodes = $dom->getElementsByTagName('img');
        $result = true;
        foreach ($nodes as $node) {
            $result = $result && ($strictMode ? $this->checkImageStrict($node) : $this->checkImage($node));
        }
        return $result;
    }

    protected function checkImage(DOMElement $el)
    {
        if ($this->hasSrc($el) && $this->hasHeight($el) && $this->hasWidth($el)) {
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
}
