<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 06-nonfunctional-ops.md §8: endpoint list tidak memuat isi konten, payload < 50 KB.
 */
final class ContentPayloadSizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_sectors_list_payload_is_under_50kb(): void
    {
        $user = User::factory()->create();
        Sector::factory()->count(20)->create(['is_active' => true]);

        $response = $this->actingAs($user)->getJson('/api/v1/sectors');

        $response->assertOk();
        $this->assertLessThan(50 * 1024, strlen($response->getContent()));
    }
}
