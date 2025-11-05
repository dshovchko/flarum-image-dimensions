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
        if ($el->hasAttribute('src')) return TRUE;
        throw new \Exception('The src attribute is absent');
    }

    protected function isValidImageUrl(string $url)
    {
        $context = stream_context_create([
            'http' => array(
                'method' => 'HEAD'
                )
            ]);
        $headers = get_headers($url, true, $context);
        if ($headers === FALSE) throw new \Exception(sprintf('The image URL (%s) is invalid', $url));
        $status = explode(' ', $headers[0])[1];
        if ($status >= 400) throw new \Exception(sprintf('The image URL (%s) is invalid', $url));
        return TRUE;
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

    public function checkContent(string $content, bool $strictMode) {
        $dom = new DOMDocument();
        $dom->loadXML($content);
        $nodes = $dom->getElementsByTagName('IMG');
        $result = TRUE;
        foreach($nodes as $node) {
            $result = $result && ($strictMode ? $this->checkImageStrict($node) : $this->checkImage($node));
        }
        return $result;
    }

    protected function checkImage(DOMElement $el) {
        if ($this->hasSrc($el) && $this->hasHeight($el) && $this->hasWidth($el)) return TRUE;
        return $this->isValidImageUrl($el->getAttribute('src')) && FALSE;
    }

    protected function checkImageStrict(DOMElement $el) {
        return $this->hasSrc($el)
            && $this->isValidImageUrl($el->getAttribute('src'))
            && $this->hasExactHeight($el)
            && $this->hasExactWidth($el);
    }
}
