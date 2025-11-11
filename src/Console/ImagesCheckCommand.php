<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace DShovchko\ImagesChecker\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Foundation\Config;
use Flarum\Mail\Job\SendRawEmailJob;
use Flarum\Post\CommentPost;
use Flarum\Discussion\Discussion;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Queue\Queue;
use Symfony\Component\Console\Input\InputOption;
use DShovchko\ImagesChecker\Validators\ImageSizeValidator;
use DShovchko\ImagesChecker\CheckLog;

class ImagesCheckCommand extends AbstractCommand
{
    protected $queue;
    protected $validator;
    protected $settings;
    protected $config;

    public function __construct(ImageSizeValidator $validator, Queue $queue, SettingsRepositoryInterface $settings, Config $config)
    {
        $this->queue = $queue;
        $this->validator = $validator;
        $this->settings = $settings;
        $this->config = $config;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('image-dimensions:check')
            ->setDescription('Checks post images for missing/invalid dimensions and sources')
            ->addOption('discussion', null, InputOption::VALUE_REQUIRED, 'Process only discussion with the specified ID')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Process all discussions (use with caution)')
            ->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'How many discussions to process per batch when using --all (default: 100)')
            ->addOption('post', null, InputOption::VALUE_REQUIRED, 'Process only comment post with the specified ID')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Should fix images without size attributes')
            ->addOption('mailto', null, InputOption::VALUE_REQUIRED, 'Send the checking log to the specified email')
            ->addOption('fast', null, InputOption::VALUE_NONE, 'Skip remote size checks, only ensure attributes exist')
            ->addOption('full', null, InputOption::VALUE_NONE, 'Validate remote images and match exact dimensions');
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
        CheckLog::reset();
        $url = $this->config->url();
        if ($url) {
            CheckLog::setBaseUrl((string) $url);
        }

        $this->configureValidatorMode();

        try {
            $chunkSize = $this->resolveChunkSize();
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return;
        }

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
            $this->mailReport();
            return;
        }

        $discussionId = $this->input->getOption('discussion');
        $runAll = (bool) $this->input->getOption('all');

        if ($discussionId && $runAll) {
            $this->error('Please provide either --discussion=<id> or --all, not both.');
            return;
        }

        if ($discussionId) {
            $discussion = Discussion::find($discussionId);
            if ($discussion === null) {
                $this->error(sprintf('discussion with id=%s not found', $discussionId));
                return;
            }
            $this->processDiscussion($discussion);
            $this->mailReport();
            return;
        }

        if (!$runAll) {
            $this->error('You must specify either --discussion=<id>, --post=<id>, or --all to run this command.');
            return;
        }

        Discussion::chunk($chunkSize, function ($discussions) {
            foreach ($discussions as $discussion) {
                $this->processDiscussion($discussion);
            }
        });

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
            $postIsValid = $this->checkPost($post);
            $hasImages = $this->validator->lastCheckHadImages();
            if (!$postIsValid) {
                $result |= 1;
                if ($this->fixPost($post)) {
                    $result |= 2;
                }
            }
            CheckLog::addInfo($post, $result, $hasImages);
        } catch (\Exception $e) {
            CheckLog::addError($post, $e->getMessage());
        }
    }

    protected function checkPost(CommentPost $post)
    {
        return $this->validator->checkContent($post->getParsedContentAttribute());
    }

    protected function fixPost(CommentPost $post)
    {
        if (!$this->shouldFix()) {
            return false;
        }
        // TODO: Implement actual content fixing logic
        // Currently this method doesn't modify content
        // Need to parse XML and add missing width/height attributes
        // Returning false until implemented
        return false;
    }

    protected function shouldFix()
    {
        return $this->input->getOption('fix');
    }

    protected function configureValidatorMode(): void
    {
        if ($this->input->getOption('full')) {
            $this->validator->setMode(ImageSizeValidator::MODE_FULL);
            return;
        }

        if ($this->input->getOption('fast')) {
            $this->validator->setMode(ImageSizeValidator::MODE_FAST);
            return;
        }

        $this->validator->setMode(ImageSizeValidator::MODE_DEFAULT);
    }

    protected function resolveChunkSize(): int
    {
        $chunkOption = $this->input->getOption('chunk');
        if ($chunkOption === null) {
            return 100;
        }

        $validatedChunk = filter_var($chunkOption, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($validatedChunk === false) {
            throw new \InvalidArgumentException('--chunk must be a positive integer');
        }

        return $validatedChunk;
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
        $emails = $this->input->getOption('mailto');

        $messages = CheckLog::getMessages();
        $total = count($messages);
        $withIssues = 0;
        $ok = 0;
        
        foreach ($messages as $record) {
            $hasIssues = !empty($record['wrong']) || !empty($record['invalid']) || !empty($record['errors']);
            if ($hasIssues) {
                $withIssues++;
            } else {
                $ok++;
            }
        }
        
        $summary = sprintf(
            "Image Dimensions Check Report\n\nTotal discussions: %d\n✅ OK: %d\n⚠️ Issues: %d\n%s\n",
            $total,
            $ok,
            $withIssues,
            str_repeat('=', 50)
        );
        
        $body = $summary;
        foreach ($messages as $message) {
            $body .= CheckLog::sprint($message);
        }

        $subject = sprintf('Images checker report (%d issues)', $withIssues);
        
        foreach (array_map('trim', explode(',', $emails)) as $email) {
            if (!empty($email)) {
                $this->queue->push(new SendRawEmailJob($email, $subject, $body));
            }
        }
    }
}
