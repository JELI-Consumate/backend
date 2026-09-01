<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Sectors\Pages\CreateSector;
use App\Filament\Resources\Sectors\Pages\EditSector;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Field link survei pretest/posttest (Google Form) di SectorForm --
 * lihat App\Services\Learning\SectorSurveyService untuk sisi tracking-nya.
 */
final class SectorSurveyLinkFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_sector_persists_survey_links(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateSector::class)
            ->fillForm([
                'name' => 'E-Commerce',
                'slug' => 'e-commerce',
                'order' => 1,
                'pretest_survey_link' => 'https://forms.gle/pretest-abc',
                'posttest_survey_link' => 'https://forms.gle/posttest-abc',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $sector = Sector::query()->where('slug', 'e-commerce')->firstOrFail();
        $this->assertSame('https://forms.gle/pretest-abc', $sector->pretest_survey_link);
        $this->assertSame('https://forms.gle/posttest-abc', $sector->posttest_survey_link);
    }

    public function test_survey_links_are_optional(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateSector::class)
            ->fillForm([
                'name' => 'Perumahan',
                'slug' => 'perumahan',
                'order' => 2,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $sector = Sector::query()->where('slug', 'perumahan')->firstOrFail();
        $this->assertNull($sector->pretest_survey_link);
        $this->assertNull($sector->posttest_survey_link);
    }

    public function test_survey_link_must_be_a_valid_url(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateSector::class)
            ->fillForm([
                'name' => 'E-Commerce',
                'slug' => 'e-commerce',
                'order' => 1,
                'pretest_survey_link' => 'bukan-url',
            ])
            ->call('create')
            ->assertHasFormErrors(['pretest_survey_link']);
    }

    public function test_editing_a_sector_updates_survey_links(): void
    {
        $admin = User::factory()->admin()->create();
        $sector = Sector::factory()->create(['pretest_survey_link' => null, 'posttest_survey_link' => null]);

        Livewire::actingAs($admin)
            ->test(EditSector::class, ['record' => $sector->id])
            ->fillForm(['pretest_survey_link' => 'https://forms.gle/pretest-xyz'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('https://forms.gle/pretest-xyz', $sector->refresh()->pretest_survey_link);
    }
}
