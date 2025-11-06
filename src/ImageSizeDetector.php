<?php

namespace DShovchko\ImagesChecker;

/**
 * Detects image dimensions with caching and timeout support
 */
class ImageSizeDetector
{
    /**
     * Cache for image dimensions
     * @var array<string, array{int|null, int|null}>
     */
    protected static $cache = [];
    
    /**
     * Timeout in seconds for HTTP/HTTPS requests
     * @var int
     */
    protected static $timeout = 3;

    public static function getSizes(string $src): array
    {
        if (!isset(self::$cache[$src])) {
            $width = null;
            $height = null;
            
            try {
                // Create context with timeout for both HTTP and HTTPS
                $opts = [
                    'http' => [
                        'timeout' => self::$timeout,
                        'user_agent' => 'Flarum Image Dimensions Extension'
                    ],
                    'https' => [
                        'timeout' => self::$timeout,
                        'user_agent' => 'Flarum Image Dimensions Extension'
                    ]
                ];
                
                // Set default context for HTTP/HTTPS requests
                if (str_starts_with($src, 'http')) {
                    stream_context_set_default($opts);
                }
                
                // Get image size from header only (memory efficient)
                $result = getimagesize($src);
                
                if ($result !== false) {
                    $width = $result[0];
                    $height = $result[1];
                }
            } catch (\Throwable $e) {
                // Log error for debugging
                error_log('ImageSizeDetector error for ' . $src . ': ' . $e->getMessage());
            }
        
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
