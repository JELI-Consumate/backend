<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Sector;
use App\Models\SectorProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Survei eksternal (Google Form) pretest/posttest per sektor -- terpisah
 * dari kuis in-app QuizContent kind pretest/posttest (lihat QuizAttemptTest).
 * Link diisi admin lewat Filament (SectorForm), user menandai selesai lewat
 * endpoint self-report di sini (SectorSurveyService).
 */
final class SectorSurveyTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_pretest_survey_records_a_timestamp(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create(['pretest_survey_link' => 'https://forms.gle/pretest-abc']);

        $response = $this->actingAs($user)->postJson("/api/v1/sectors/{$sector->slug}/pretest-survey/complete");

        $response->assertOk()
            ->assertJsonPath('data.surveys.pretest.link', 'https://forms.gle/pretest-abc')
            ->assertJsonPath('data.surveys.posttest.link', null);

        $this->assertNotNull($response->json('data.surveys.pretest.completed_at'));
        $this->assertNull($response->json('data.surveys.posttest.completed_at'));

        $this->assertDatabaseHas('sector_progress', [
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        $progress = SectorProgress::query()->where('user_id', $user->id)->where('sector_id', $sector->id)->firstOrFail();
        $this->assertNotNull($progress->pretest_survey_completed_at);
        $this->assertNull($progress->posttest_survey_completed_at);
    }

    public function test_completing_posttest_survey_records_a_timestamp(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create(['posttest_survey_link' => 'https://forms.gle/posttest-abc']);

        $response = $this->actingAs($user)->postJson("/api/v1/sectors/{$sector->slug}/posttest-survey/complete");

        $response->assertOk()->assertJsonPath('data.surveys.posttest.link', 'https://forms.gle/posttest-abc');
        $this->assertNotNull($response->json('data.surveys.posttest.completed_at'));
    }

    /**
     * Menandai selesai tidak butuh SectorProgress yang sudah ada (mis. user
     * belum pernah menyentuh journey mana pun di sektor itu) -- baris dibuat
     * on-demand, bukan cuma diupdate.
     */
    public function test_completing_survey_works_even_without_existing_journey_progress(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create(['pretest_survey_link' => 'https://forms.gle/pretest-abc']);

        $this->assertDatabaseMissing('sector_progress', ['user_id' => $user->id, 'sector_id' => $sector->id]);

        $this->actingAs($user)->postJson("/api/v1/sectors/{$sector->slug}/pretest-survey/complete")->assertOk();

        $this->assertDatabaseHas('sector_progress', ['user_id' => $user->id, 'sector_id' => $sector->id]);
    }

    /**
     * Idempotent: menandai ulang tidak menggeser completed_at yang sudah ada.
     */
    public function test_completing_survey_twice_keeps_the_first_timestamp(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create(['pretest_survey_link' => 'https://forms.gle/pretest-abc']);

        $this->actingAs($user)->postJson("/api/v1/sectors/{$sector->slug}/pretest-survey/complete")->assertOk();
        $first = SectorProgress::query()->where('user_id', $user->id)->where('sector_id', $sector->id)
            ->firstOrFail()->pretest_survey_completed_at;

        $this->travel(1)->hour();
        $this->actingAs($user)->postJson("/api/v1/sectors/{$sector->slug}/pretest-survey/complete")->assertOk();
        $second = SectorProgress::query()->where('user_id', $user->id)->where('sector_id', $sector->id)
            ->firstOrFail()->pretest_survey_completed_at;

        $this->assertTrue($first->equalTo($second));
    }

    public function test_completing_survey_fails_when_link_not_configured(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create(['pretest_survey_link' => null]);

        $response = $this->actingAs($user)->postJson("/api/v1/sectors/{$sector->slug}/pretest-survey/complete");

        $response->assertNotFound()->assertJsonPath('code', 'SURVEY_NOT_CONFIGURED');
    }

    public function test_completing_survey_requires_authentication(): void
    {
        $sector = Sector::factory()->create(['pretest_survey_link' => 'https://forms.gle/pretest-abc']);

        $this->postJson("/api/v1/sectors/{$sector->slug}/pretest-survey/complete")->assertUnauthorized();
    }

    public function test_completing_survey_for_unknown_sector_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/sectors/tidak-ada/pretest-survey/complete')->assertNotFound();
    }

    public function test_sector_show_exposes_survey_links_and_completion_status(): void
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create([
            'is_active' => true,
            'pretest_survey_link' => 'https://forms.gle/pretest-abc',
            'posttest_survey_link' => 'https://forms.gle/posttest-abc',
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/sectors/{$sector->slug}");

        $response->assertOk()
            ->assertJsonPath('data.surveys.pretest.link', 'https://forms.gle/pretest-abc')
            ->assertJsonPath('data.surveys.pretest.completed_at', null)
            ->assertJsonPath('data.surveys.posttest.link', 'https://forms.gle/posttest-abc')
            ->assertJsonPath('data.surveys.posttest.completed_at', null);
    }
}
