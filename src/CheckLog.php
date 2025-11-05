<?php

namespace DShovchko\ImagesChecker;

use Flarum\Post\CommentPost;

class CheckLog
{
    protected static $lastBatch = [];
    protected static $records = [];

    public static function addInfo(CommentPost $post, int $resultCode)
    {
        $postID = $post->id;
        $discussionID = $post->discussion_id;
        $record =& self::getRecordFrom(self::$lastBatch, $discussionID);
        if ($resultCode & 2) {
            array_push($record['fixed'], $postID);
        } elseif ($resultCode & 1) {
            array_push($record['wrong'], $postID);
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
        self::$records = array_merge_recursive(self::$records, self::$lastBatch);
    }

    public static function getLastMessages()
    {
        return self::$lastBatch;
    }

    public static function getMessages()
    {
        return self::$records;
    }

    public static function sprintf(array $record, bool $formatted = TRUE)
    {
        $message = sprintf('discussion %s: ', $record['id']);
        if (!count($record['fixed']) && !count($record['wrong']) && !count($record['invalid'])) $message .= ' There are no images in posts or all images have size attributes.';
        else {
            if (count($record['fixed'])) {
                $message .= sprintf(' fixed images in posts (%s)', implode(' ', $record['fixed']));
            }
            if (count($record['wrong'])) {
                $text = sprintf(' wrong images in posts (%s)', implode(' ', $record['wrong']));
                $message .= sprintf($formatted ? '<comment>%s</comment>' : '%s', $text);
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
        return self::sprintf($record, FALSE);
    }
}
