<?php

namespace App\Providers;

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
        Relation::enforceMorphMap([
            'video' => \App\Models\VideoContent::class,
            'article' => \App\Models\ArticleContent::class,
            'quiz' => \App\Models\QuizContent::class,
            'simulation' => \App\Models\SimulationContent::class,
            'reflection' => \App\Models\ReflectionContent::class,
        ]);

        Model::preventLazyLoading(! app()->isProduction());
    }
}
