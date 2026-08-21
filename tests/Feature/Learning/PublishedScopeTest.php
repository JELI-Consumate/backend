<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Enums\PublishStatus;
use App\Models\Journey;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublishedScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * BR-02: hanya entitas published yang tampil di query publik (default scope).
     */
    public function test_br02_draft_and_archived_journeys_excluded_by_default(): void
    {
        Journey::factory()->create(['status' => PublishStatus::Published]);
        Journey::factory()->create(['status' => PublishStatus::Draft]);
        Journey::factory()->create(['status' => PublishStatus::Archived]);

        $this->assertSame(1, Journey::count());
    }

    public function test_br02_draft_and_archived_modules_excluded_by_default(): void
    {
        $journey = Journey::factory()->create();

        Module::factory()->create(['journey_id' => $journey->id, 'status' => PublishStatus::Published]);
        Module::factory()->create(['journey_id' => $journey->id, 'status' => PublishStatus::Draft]);
        Module::factory()->create(['journey_id' => $journey->id, 'status' => PublishStatus::Archived]);

        $this->assertSame(1, Module::count());
    }

    public function test_br02_admin_can_bypass_scope_to_see_all_statuses(): void
    {
        Journey::factory()->create(['status' => PublishStatus::Published]);
        Journey::factory()->create(['status' => PublishStatus::Draft]);

        $this->assertSame(2, Journey::withoutGlobalScopes()->count());
    }
}
