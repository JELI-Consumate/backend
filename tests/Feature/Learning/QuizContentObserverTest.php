<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Enums\QuizKind;
use App\Enums\QuizSegmentType;
use App\Exceptions\InvalidQuizContentException;
use App\Models\Journey;
use App\Models\QuizContent;
use App\Models\QuizSegment;
use App\Models\Sector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class QuizContentObserverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * BR-04: kind=quiz wajib journey_id terisi & sector_id null.
     */
    public function test_br04_quiz_kind_requires_journey_id_and_rejects_sector_id(): void
    {
        $this->expectException(InvalidQuizContentException::class);

        QuizContent::create([
            'kind' => QuizKind::Quiz,
            'journey_id' => null,
            'sector_id' => Sector::factory()->create()->id,
        ]);
    }

    /**
     * BR-04: kind pretest/posttest wajib sector_id terisi & journey_id null.
     */
    public function test_br04_pretest_requires_sector_id_and_rejects_journey_id(): void
    {
        $this->expectException(InvalidQuizContentException::class);

        QuizContent::create([
            'kind' => QuizKind::Pretest,
            'journey_id' => Journey::factory()->create()->id,
            'sector_id' => null,
        ]);
    }

    public function test_br04_valid_quiz_and_pretest_are_accepted(): void
    {
        $quiz = QuizContent::create([
            'kind' => QuizKind::Quiz,
            'journey_id' => Journey::factory()->create()->id,
            'sector_id' => null,
        ]);
        $this->assertNotNull($quiz->id);

        $pretest = QuizContent::create([
            'kind' => QuizKind::Pretest,
            'journey_id' => null,
            'sector_id' => Sector::factory()->create()->id,
        ]);
        $this->assertNotNull($pretest->id);
    }

    /**
     * BR-09: pretest/posttest wajib minimal 2 segment (multiple_choice + likert).
     */
    public function test_br09_pretest_with_only_one_segment_is_rejected_on_resave(): void
    {
        $pretest = QuizContent::create([
            'kind' => QuizKind::Pretest,
            'journey_id' => null,
            'sector_id' => Sector::factory()->create()->id,
        ]);

        QuizSegment::factory()->create([
            'quiz_content_id' => $pretest->id,
            'segment_type' => QuizSegmentType::MultipleChoice,
        ]);

        $this->expectException(InvalidQuizContentException::class);

        $pretest->update(['passing_score' => $pretest->passing_score + 1]);
    }

    public function test_br09_pretest_with_both_segment_types_passes(): void
    {
        $pretest = QuizContent::create([
            'kind' => QuizKind::Pretest,
            'journey_id' => null,
            'sector_id' => Sector::factory()->create()->id,
        ]);

        QuizSegment::factory()->create([
            'quiz_content_id' => $pretest->id,
            'segment_type' => QuizSegmentType::MultipleChoice,
        ]);
        QuizSegment::factory()->likert()->create([
            'quiz_content_id' => $pretest->id,
        ]);

        $pretest->update(['passing_score' => $pretest->passing_score + 1]);

        $this->assertNotNull($pretest->fresh());
    }

    public function test_br09_does_not_apply_to_regular_quiz(): void
    {
        $quiz = QuizContent::create([
            'kind' => QuizKind::Quiz,
            'journey_id' => Journey::factory()->create()->id,
            'sector_id' => null,
        ]);

        $quiz->update(['passing_score' => $quiz->passing_score + 1]);

        $this->assertNotNull($quiz->fresh());
    }
}
