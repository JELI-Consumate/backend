<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ModuleCompleted;
use App\Services\Progress\ProgressService;

final readonly class RecalculateJourneyProgress
{
    public function __construct(private ProgressService $progress) {}

    public function handle(ModuleCompleted $event): void
    {
        $this->progress->recalculateJourney($event->user, $event->page->module->journey);
    }
}
