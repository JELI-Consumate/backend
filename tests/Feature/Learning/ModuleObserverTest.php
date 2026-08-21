<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Enums\PublishStatus;
use App\Models\Journey;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ModuleObserverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * BR-13: journeys.estimated_minutes = SUM(modules.estimated_minutes) untuk
     * module published, direkalkulasi otomatis saat module disimpan/diubah/dihapus.
     */
    public function test_br13_journey_estimated_minutes_resyncs_on_module_saved(): void
    {
        $journey = Journey::factory()->create();

        $moduleA = Module::factory()->create([
            'journey_id' => $journey->id,
            'estimated_minutes' => 10,
            'status' => PublishStatus::Published,
        ]);
        Module::factory()->create([
            'journey_id' => $journey->id,
            'estimated_minutes' => 5,
            'status' => PublishStatus::Published,
        ]);

        $this->assertSame(15, $journey->fresh()->estimated_minutes);

        $moduleA->update(['estimated_minutes' => 20]);

        $this->assertSame(25, $journey->fresh()->estimated_minutes);
    }

    public function test_br13_draft_modules_excluded_from_resync(): void
    {
        $journey = Journey::factory()->create();

        Module::factory()->create([
            'journey_id' => $journey->id,
            'estimated_minutes' => 10,
            'status' => PublishStatus::Published,
        ]);
        Module::factory()->create([
            'journey_id' => $journey->id,
            'estimated_minutes' => 99,
            'status' => PublishStatus::Draft,
        ]);

        $this->assertSame(10, $journey->fresh()->estimated_minutes);
    }

    public function test_br13_journey_estimated_minutes_resyncs_on_module_deleted(): void
    {
        $journey = Journey::factory()->create();

        $moduleA = Module::factory()->create([
            'journey_id' => $journey->id,
            'estimated_minutes' => 10,
            'status' => PublishStatus::Published,
        ]);
        Module::factory()->create([
            'journey_id' => $journey->id,
            'estimated_minutes' => 5,
            'status' => PublishStatus::Published,
        ]);

        $this->assertSame(15, $journey->fresh()->estimated_minutes);

        $moduleA->delete();

        $this->assertSame(5, $journey->fresh()->estimated_minutes);
    }
}
