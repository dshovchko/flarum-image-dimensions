<?php

namespace DShovchko\ImagesChecker;

class ImageSizeDetector
{
    protected static $cache = [];

    public static function getSizes(string $src): array
    {
        if (!isset(self::$cache[$src])) {
            $result = getimagesize($src);

            $width = $result === FALSE ? null : $result[0];
            $height = $result === FALSE ? null : $result[1];
        
            self::$cache[$src] = [$width, $height];
        }
        
        return self::$cache[$src];
    }

    public static function getHeight(string $src)
    {
        list($width, $height) = self::getSizes($src);
        return $height;
    }

    public static function getWidth(string $src)
    {
        list($width, $height) = self::getSizes($src);
        return $width;
    }
}
