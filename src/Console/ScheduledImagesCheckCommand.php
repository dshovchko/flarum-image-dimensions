<?php

namespace DShovchko\ImagesChecker\Console;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Scheduling\Event;
use DShovchko\ImagesChecker\Validators\ImageSizeValidator;
use Illuminate\Contracts\Queue\Queue;

class ScheduledImagesCheckCommand extends ImagesCheckCommand
{
    protected $settings;

    public function __construct(ImageSizeValidator $validator, Queue $queue, SettingsRepositoryInterface $settings)
    {
        parent::__construct($validator, $queue);
        $this->settings = $settings;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('image-dimensions:scheduled-check')
             ->setDescription('Scheduled automatic check of post images (configured via admin panel)');
    }

    protected function fire()
    {
        if (!$this->isEnabled()) {
            $this->info('Scheduled image checks are disabled.');
            return;
        }

        $this->info('Running scheduled image dimensions check...');
        
        $this->input->setOption('all', true);
        $this->input->setOption('chunk', $this->getChunkSize());
        
        $emails = $this->getEmailRecipients();
        if ($emails) {
            $this->input->setOption('mailto', $emails);
        }
        
        $mode = $this->getMode();
        if ($mode === 'fast') {
            $this->input->setOption('fast', true);
        } elseif ($mode === 'full') {
            $this->input->setOption('full', true);
        }

        $this->process();
        $this->info('Scheduled check completed.');
    }

    public function isEnabled(): bool
    {
        return (bool) $this->settings->get('dshovchko-image-dimensions.scheduled_enabled', false);
    }

    public function getMode(): string
    {
        return $this->settings->get('dshovchko-image-dimensions.scheduled_mode', 'fast');
    }

    public function getChunkSize(): int
    {
        $chunk = $this->settings->get('dshovchko-image-dimensions.scheduled_chunk', '100');
        return max(1, (int) $chunk);
    }

    public function getEmailRecipients(): string
    {
        return $this->settings->get('dshovchko-image-dimensions.scheduled_emails', '');
    }

    public function schedule(Event $event): void
    {
        $frequency = $this->settings->get('dshovchko-image-dimensions.scheduled_frequency', 'weekly');
        
        switch ($frequency) {
            case 'daily':
                $event->daily();
                break;
            case 'weekly':
                $event->weekly();
                break;
            case 'monthly':
                $event->monthly();
                break;
        }
    }
}
