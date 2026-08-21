<?php

namespace App\Providers;

use App\Models\ArticleContent;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\QuizContent;
use App\Models\ReflectionContent;
use App\Models\SimulationContent;
use App\Models\VideoContent;
use App\Observers\ContentModuleCacheObserver;
use App\Observers\ModuleObserver;
use App\Observers\ModulePageObserver;
use App\Observers\QuizContentObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'video' => VideoContent::class,
            'article' => ArticleContent::class,
            'quiz' => QuizContent::class,
            'simulation' => SimulationContent::class,
            'reflection' => ReflectionContent::class,
        ]);

        Model::preventLazyLoading(! app()->isProduction());

        Module::observe(ModuleObserver::class);
        QuizContent::observe(QuizContentObserver::class);

        ModulePage::observe(ModulePageObserver::class);
        VideoContent::observe(ContentModuleCacheObserver::class);
        ArticleContent::observe(ContentModuleCacheObserver::class);
        QuizContent::observe(ContentModuleCacheObserver::class);
        SimulationContent::observe(ContentModuleCacheObserver::class);
        ReflectionContent::observe(ContentModuleCacheObserver::class);
    }
}
