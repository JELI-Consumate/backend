<?php

declare(strict_types=1);

namespace Tests\Feature\ComplaintAgency;

use App\Models\ComplaintAgency;
use App\Models\ComplaintAgencyChannel;
use App\Models\ComplaintCategory;
use Database\Seeders\ComplaintAgencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ComplaintAgencySeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Disk di-fake untuk seluruh class: seeder meng-upload logo, dan tanpa ini
     * test akan menulis ke disk sungguhan (mis. R2) sesuai .env.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['filament.default_filesystem_disk' => 'public']);
        Storage::fake('public');
    }

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

    public function test_seeder_uploads_a_logo_for_every_agency(): void
    {
        $this->seed(ComplaintAgencySeeder::class);

        ComplaintAgency::query()->get()->each(function (ComplaintAgency $agency): void {
            $this->assertNotNull($agency->logo_url, "{$agency->slug} tanpa logo");
            Storage::disk('public')->assertExists($agency->logo_url);
        });
    }

    public function test_seeder_does_not_overwrite_a_logo_set_by_admin(): void
    {
        $this->seed(ComplaintAgencySeeder::class);
        ComplaintAgency::query()->where('slug', 'ojk')->update(['logo_url' => 'complaint-agencies/logos/custom.png']);

        $this->seed(ComplaintAgencySeeder::class);

        $this->assertSame('complaint-agencies/logos/custom.png', ComplaintAgency::query()->where('slug', 'ojk')->value('logo_url'));
    }

    public function test_rerun_does_not_overwrite_admin_edits(): void
    {
        $this->seed(ComplaintAgencySeeder::class);

        $agency = ComplaintAgency::query()->where('slug', 'ojk')->firstOrFail();
        $agency->update(['summary' => 'Teks hasil edit admin', 'order' => 99, 'is_active' => false]);
        $agency->channels()->delete();
        $category = ComplaintCategory::query()->firstOrFail();
        $category->update(['problem' => $category->problem, 'order' => 77, 'is_active' => false]);
        $category->agencies()->detach();

        $this->seed(ComplaintAgencySeeder::class);

        $agency->refresh();
        $this->assertSame('Teks hasil edit admin', $agency->summary);
        $this->assertSame(99, $agency->order);
        $this->assertFalse($agency->is_active);
        $this->assertSame(0, $agency->channels()->count());
        $category->refresh();
        $this->assertSame(77, $category->order);
        $this->assertFalse($category->is_active);
        $this->assertSame(0, $category->agencies()->count());
    }

    public function test_rerun_does_not_resurrect_rows_deleted_by_admin(): void
    {
        $this->seed(ComplaintAgencySeeder::class);

        ComplaintAgency::query()->where('slug', 'bpom')->firstOrFail()->delete();
        ComplaintCategory::query()->firstOrFail()->delete();

        $this->seed(ComplaintAgencySeeder::class);

        $this->assertSame(15, ComplaintAgency::query()->count());
        $this->assertSame(12, ComplaintCategory::query()->count());
        $this->assertSame(16, ComplaintAgency::withTrashed()->count());
        $this->assertSame(13, ComplaintCategory::withTrashed()->count());
    }

    public function test_rerun_creates_only_the_missing_agency(): void
    {
        $this->seed(ComplaintAgencySeeder::class);
        ComplaintAgency::query()->where('slug', 'iasc')->firstOrFail()->forceDelete();

        $this->seed(ComplaintAgencySeeder::class);

        $iasc = ComplaintAgency::query()->where('slug', 'iasc')->firstOrFail();
        $this->assertGreaterThan(0, $iasc->channels()->count());
        $this->assertSame(16, ComplaintAgency::query()->count());
    }
}
