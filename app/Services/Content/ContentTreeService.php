<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\ArticleContent;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\QuizContent;
use App\Models\ReflectionContent;
use App\Models\SimulationContent;
use App\Models\VideoContent;
use App\Support\CacheKey;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;

final readonly class ContentTreeService
{
    /**
     * Muat module + module_pages + konten polimorfik ter-resolve.
     *
     * Cache key mengandung `updated_at` module (bertambah tiap module sendiri
     * ATAU salah satu konten/halamannya berubah — lihat ContentModuleCacheObserver
     * & ModulePageObserver), jadi 1 query murah selalu dijalankan untuk cek versi
     * sebelum menyentuh cache.
     */
    public function loadModuleTree(int $moduleId): Module
    {
        $module = Module::query()->with('journey')->findOrFail($moduleId);

        $cacheKey = CacheKey::moduleTree($module->id, (string) $module->updated_at?->timestamp);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($module): Module {
            $module->load([
                'pages' => fn ($query) => $query->orderBy('order'),
                'pages.contentable' => fn (MorphTo $morphTo) => $morphTo->morphWith($this->contentMorphMap()),
            ]);

            return $module;
        });
    }

    /**
     * Muat satu module_page + konten polimorfik ter-resolve, untuk lazy load per halaman.
     * Tidak di-cache (spesifikasi cache hanya untuk module tree penuh).
     */
    public function loadPage(int $modulePageId): ModulePage
    {
        return ModulePage::query()
            ->with([
                'module.journey',
                'contentable' => fn (MorphTo $morphTo) => $morphTo->morphWith($this->contentMorphMap()),
            ])
            ->findOrFail($modulePageId);
    }

    /**
     * @return array<class-string, array<int, string>>
     */
    private function contentMorphMap(): array
    {
        return [
            VideoContent::class => [],
            ArticleContent::class => ['blocks'],
            QuizContent::class => ['segments.questions.choiceOptions', 'segments.likertScaleOptions'],
            SimulationContent::class => ['matchingPairs', 'orderingSteps'],
            ReflectionContent::class => ['sections.questions'],
        ];
    }
}
