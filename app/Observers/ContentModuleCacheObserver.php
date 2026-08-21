<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ModulePage;
use Illuminate\Database\Eloquent\Model;

/**
 * Menyentuh module pemilik saat root content (video/article/quiz/simulation/reflection)
 * disimpan/dihapus, supaya cache key `content:module:{id}:v{updated_at}` di
 * ContentTreeService berubah dan konten lama tidak lagi terlayani dari cache.
 *
 * @template TModel of Model
 */
final class ContentModuleCacheObserver
{
    public function saved(Model $content): void
    {
        $this->touchModule($content);
    }

    public function deleted(Model $content): void
    {
        $this->touchModule($content);
    }

    private function touchModule(Model $content): void
    {
        /** @var ModulePage|null $modulePage */
        $modulePage = $content->modulePage()->first();

        $modulePage?->module?->touch();
    }
}
