<?php

declare(strict_types=1);

namespace Tests\Feature\ComplaintAgency;

use App\Models\ComplaintAgency;
use App\Models\ComplaintAgencyChannel;
use App\Models\ComplaintCategory;
use Database\Seeders\ComplaintAgencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ComplaintAgencySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_agencies_and_categories(): void
    {
        $this->seed(ComplaintAgencySeeder::class);

        $this->assertSame(16, ComplaintAgency::query()->count());
        $this->assertSame(13, ComplaintCategory::query()->count());
        $this->assertSame('bpsk', ComplaintAgency::query()->orderBy('order')->first()->slug);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ComplaintAgencySeeder::class);
        $channels = ComplaintAgencyChannel::query()->count();

        $this->seed(ComplaintAgencySeeder::class);

        $this->assertSame(16, ComplaintAgency::query()->count());
        $this->assertSame(13, ComplaintCategory::query()->count());
        $this->assertSame($channels, ComplaintAgencyChannel::query()->count());
    }

    public function test_every_channel_with_a_button_has_a_usable_url(): void
    {
        $this->seed(ComplaintAgencySeeder::class);

        ComplaintAgencyChannel::query()->whereNotNull('button_label')->get()->each(
            fn (ComplaintAgencyChannel $channel) => $this->assertNotNull($channel->url(), "{$channel->label} ({$channel->value})")
        );
    }

    public function test_every_category_points_to_at_least_one_agency(): void
    {
        $this->seed(ComplaintAgencySeeder::class);

        ComplaintCategory::query()->withCount('agencies')->get()->each(
            fn (ComplaintCategory $category) => $this->assertGreaterThan(0, $category->agencies_count, $category->problem)
        );
    }
}
