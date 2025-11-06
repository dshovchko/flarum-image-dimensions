<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace DShovchko\ImagesChecker\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Mail\Job\SendRawEmailJob;
use Flarum\Post\CommentPost;
use Flarum\Discussion\Discussion;
use Illuminate\Contracts\Queue\Queue;
use Symfony\Component\Console\Input\InputOption;
use DShovchko\ImagesChecker\Validators\ImageSizeValidator;
use DShovchko\ImagesChecker\CheckLog;

class ImagesCheckCommand extends AbstractCommand
{
    protected $queue;
    protected $validator;
    /**
     * @param ImageSizeValidator $validator
     * @param Queue $queue
     */
    public function __construct(ImageSizeValidator $validator, Queue $queue)
    {
        $this->queue = $queue;
        $this->validator = $validator;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('images:check')
            ->setDescription('Checks images in posts for a valid source and set size attributes')
            ->addOption('discussion', null, InputOption::VALUE_REQUIRED, 'Process only discussion with the specified ID')
            ->addOption('post', null, InputOption::VALUE_REQUIRED, 'Process only comment post with the specified ID')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Should fix images without size attributes')
            ->addOption('mailto', null, InputOption::VALUE_REQUIRED, 'Send the checking log to the specified email')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Should perform strict checking of image sizes');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->process();
    }

    protected function process()
    {
        if ($this->input->getOption('post')) {
            $id = $this->input->getOption('post');
            $post = CommentPost::find($id);
            if ($post === null) {
                $this->error(sprintf('comment post with id=%s not found', $id));
                return;
            }
            $this->info(sprintf('<comment>processing only one post %s from the discussion %s</comment>'.PHP_EOL, $id, $post->discussion_id));
            $this->processCommentPost($post);
            $this->consoleReport();
            CheckLog::mergeLastBatch();
        }
        elseif ($this->input->getOption('discussion')) {
            $id = $this->input->getOption('discussion');
            $discussion = Discussion::find($id);
            if ($discussion === null) {
                $this->error(sprintf('discussion with id=%s not found', $id));
                return;
            }
            $this->processDiscussion($discussion);
        } else {
            $discussions = Discussion::all();
            foreach($discussions as $discussion) {
                $this->processDiscussion($discussion);
            }
        }

        $this->mailReport();
    }

    protected function processDiscussion(Discussion $discussion)
    {
        if ($discussion === null) {
            return;
        }
        foreach ($discussion->posts as $post) {
            if ($post->type !== 'comment') {
                continue;
            }
            $this->processCommentPost($post);
        }
        $this->consoleReport();
        CheckLog::mergeLastBatch();
    }
    
    protected function processCommentPost(CommentPost $post)
    {
        $result = 0;
        try {
            if (!$this->checkPost($post)) {
                $result |= 1;
                if ($this->fixPost($post)) {
                    $result |= 2;
                }
            }
            CheckLog::addInfo($post, $result);
        } catch (\Exception $e) {
            CheckLog::addError($post, $e->getMessage());
        }
    }

    protected function checkPost(CommentPost $post)
    {
        $strict = $this->input->getOption('strict');
        return $this->validator->checkContent($post->getParsedContentAttribute(), $strict);
    }

    protected function fixPost(CommentPost $post)
    {
        if (!$this->shouldFix()) {
            return false;
        }
        // TODO: Implement actual content fixing logic
        // Currently this method doesn't modify content
        // Need to parse XML and add missing width/height attributes
        $content = $post->content;
        $post->content = $content;
        $post->updateQuietly();
        return true;
    }

    protected function shouldFix()
    {
        return $this->input->getOption('fix');
    }

    protected function consoleReport()
    {
        foreach (CheckLog::getLastMessages() as $message) {
            $this->info(CheckLog::sprintf($message));
        }
    }

    protected function mailReport()
    {
        if (!$this->input->getOption('mailto')) {
            return;
        }
        $email = $this->input->getOption('mailto');

        $body = '';
        foreach (CheckLog::getMessages() as $message) {
            $body .= CheckLog::sprint($message);
        }

        $subject = 'Images checker report';
        $this->queue->push(new SendRawEmailJob($email, $subject, $body));
    }
}
