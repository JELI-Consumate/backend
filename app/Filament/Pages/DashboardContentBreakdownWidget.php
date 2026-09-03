<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\AdminScope;
use App\Models\ArticleContent;
use App\Models\QuizContent;
use App\Models\ReflectionContent;
use App\Models\SimulationContent;
use App\Models\VideoContent;
use Filament\Widgets\ChartWidget;

/**
 * Komposisi konten per jenis (Article/Video/Quiz/Simulation/Reflection) —
 * versi visual dari deskripsi kartu "Konten" di DashboardOverviewWidget,
 * supaya admin bisa lihat sekilas jenis konten mana yang paling banyak
 * digarap tanpa buka tiap resource satu-satu.
 */
class DashboardContentBreakdownWidget extends ChartWidget
{
    protected ?string $heading = 'Komposisi Konten';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = [
            'Article' => AdminScope::scopeSectorContent(ArticleContent::query())->count(),
            'Video' => AdminScope::scopeSectorContent(VideoContent::query())->count(),
            'Quiz' => AdminScope::scopeQuizContentSector(QuizContent::query())->count(),
            'Simulation' => AdminScope::scopeSectorContent(SimulationContent::query())->count(),
            'Reflection' => AdminScope::scopeSectorContent(ReflectionContent::query())->count(),
        ];

        return [
            'datasets' => [
                [
                    'data' => array_values($counts),
                    'backgroundColor' => ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444'],
                ],
            ],
            'labels' => array_keys($counts),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ];
    }
}
