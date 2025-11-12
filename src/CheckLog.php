<?php

namespace DShovchko\ImagesChecker;

use Flarum\Post\CommentPost;

class CheckLog
{
    protected const EMOJI_OK = '\u2705';
    protected const EMOJI_WARNING = '\u26a0\ufe0f';
    
    protected static $lastBatch = [];
    protected static $records = [];
    protected static $baseUrl = null;

    public static function reset()
    {
        self::$lastBatch = [];
        self::$records = [];
        self::$baseUrl = null;
    }

    public static function addInfo(CommentPost $post, int $resultCode, bool $hasImages = false)
    {
        $postID = $post->id;
        $discussionID = $post->discussion_id;
        $record =& self::getRecordFrom(self::$lastBatch, $discussionID);
        if ($resultCode & 2) {
            array_push($record['fixed'], $postID);
        } elseif ($resultCode & 1) {
            array_push($record['wrong'], $postID);
        } elseif ($hasImages) {
            array_push($record['checked'], $postID);
        }
    }

    public static function addError(CommentPost $post, string $errorMsg)
    {
        $postID = $post->id;
        $discussionID = $post->discussion_id;
        $record =& self::getRecordFrom(self::$lastBatch, $discussionID);
        array_push($record['invalid'], $postID);
        array_push($record['errors'], $errorMsg);
    }

    protected static function createEmptyRecord(int $discussionID)
    {
        return array(
            'id' => $discussionID,
            'fixed' => array(),
            'wrong' => array(),
            'checked' => array(),
            'invalid' => array(),
            'errors' => array()
        );
    }
    
    protected static function & getRecordFrom(array & $from, int $discussionID)
    {
        if (!isset($from[$discussionID])) {
            $from[$discussionID] = self::createEmptyRecord($discussionID);
        }
        return $from[$discussionID];
    }
    
    public static function mergeLastBatch()
    {
        foreach (self::$lastBatch as $discussionID => $record) {
            if (!isset(self::$records[$discussionID])) {
                self::$records[$discussionID] = $record;
                continue;
            }

            self::$records[$discussionID]['fixed'] = array_values(array_unique(array_merge(
                self::$records[$discussionID]['fixed'],
                $record['fixed']
            )));

            self::$records[$discussionID]['wrong'] = array_values(array_unique(array_merge(
                self::$records[$discussionID]['wrong'],
                $record['wrong']
            )));

            self::$records[$discussionID]['invalid'] = array_values(array_unique(array_merge(
                self::$records[$discussionID]['invalid'],
                $record['invalid']
            )));

            self::$records[$discussionID]['checked'] = array_values(array_unique(array_merge(
                self::$records[$discussionID]['checked'],
                $record['checked']
            )));

            self::$records[$discussionID]['errors'] = array_values(array_merge(
                self::$records[$discussionID]['errors'],
                $record['errors']
            ));
        }

        self::$lastBatch = [];
    }

    public static function getLastMessages()
    {
        return self::$lastBatch;
    }

    public static function getMessages()
    {
        return self::$records;
    }

    public static function setBaseUrl(string $url)
    {
        self::$baseUrl = rtrim($url, '/');
    }

    public static function sprintf(array $record, bool $formatted = true)
    {
        $id = $record['id'];
        $hasIssues = !empty($record['wrong']) || !empty($record['invalid']) || !empty($record['errors']);
        $emoji = $hasIssues ? self::EMOJI_WARNING : self::EMOJI_OK;
        
        if (!$formatted && self::$baseUrl) {
            $message = sprintf("\n%s Discussion %s\n%s/d/%s\n", $emoji, $id, self::$baseUrl, $id);
        } else {
            $message = sprintf('discussion %s: ', $id);
        }
        if (empty($record['fixed']) && empty($record['wrong']) && empty($record['invalid']) && empty($record['checked'])) {
            $message .= ' There are no images in posts.';
        }
        else {
            if (count($record['fixed'])) {
                $message .= sprintf(' fixed images in posts (%s)', implode(' ', $record['fixed']));
            }
            if (count($record['wrong'])) {
                $text = sprintf(' wrong images in posts (%s)', implode(' ', $record['wrong']));
                $message .= sprintf($formatted ? '<comment>%s</comment>' : '%s', $text);
            }
            if (count($record['checked'])) {
                $message .= sprintf(' images with size attributes (%s)', implode(' ', $record['checked']));
            }
            if (count($record['invalid'])) {
                $text = sprintf(' invalid images in posts (%s)', implode(' ', $record['invalid']));
                $message .= sprintf($formatted ? '<error>%s</error>' : '%s', $text);
            }
        }
        foreach($record['errors'] as $errorMsg) {
            $message .= sprintf(PHP_EOL.($formatted ? ' <error>%s</error>' : '%s'), $errorMsg);
        }
        return $message.PHP_EOL;
    }

    public static function sprint(array $record)
    {
        return self::sprintf($record, false);
    }
}
