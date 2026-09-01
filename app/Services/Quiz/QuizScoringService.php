<?php

declare(strict_types=1);

namespace App\Services\Quiz;

use App\Data\QuizAnswerCheckData;
use App\Data\QuizAnswerCheckResult;
use App\Data\QuizSubmissionData;
use App\Enums\QuizSegmentType;
use App\Events\QuizAttemptCompleted;
use App\Exceptions\InvalidSubmissionException;
use App\Models\LikertScaleOption;
use App\Models\QuizAttempt;
use App\Models\QuizChoiceAnswer;
use App\Models\QuizChoiceOption;
use App\Models\QuizLikertAnswer;
use App\Models\QuizQuestion;
use App\Services\Progress\ProgressService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class QuizScoringService
{
    public function __construct(private ProgressService $progress) {}

    public function submit(QuizAttempt $attempt, QuizSubmissionData $data): QuizAttempt
    {
        if ($attempt->completed_at !== null) {
            throw new InvalidSubmissionException('Attempt sudah pernah diselesaikan.'); // BR-08
        }

        return DB::transaction(function () use ($attempt, $data): QuizAttempt {
            $now = Date::now();

            // Preload peta jawaban benar — 1 query (pluck), bukan query per soal.
            $correctOptionIdByQuestion = QuizChoiceOption::query()
                ->where('is_correct', true)
                ->whereIn('quiz_question_id', array_column($data->choiceAnswers, 'quiz_question_id'))
                ->pluck('id', 'quiz_question_id');

            $choiceRows = [];
            $choiceScore = 0;

            foreach ($data->choiceAnswers as $answer) {
                $isCorrect = ($correctOptionIdByQuestion[$answer['quiz_question_id']] ?? null) === $answer['quiz_choice_option_id'];
                $choiceScore += $isCorrect ? 1 : 0;

                $choiceRows[] = [
                    'id' => (string) Str::ulid(),
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $answer['quiz_question_id'],
                    'quiz_choice_option_id' => $answer['quiz_choice_option_id'],
                    'is_correct' => $isCorrect,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $likertRows = [];

            foreach ($data->likertAnswers as $answer) {
                $likertRows[] = [
                    'id' => (string) Str::ulid(),
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $answer['quiz_question_id'],
                    'likert_scale_option_id' => $answer['likert_scale_option_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($choiceRows !== []) {
                QuizChoiceAnswer::insert($choiceRows); // satu panggilan untuk seluruh baris
            }

            if ($likertRows !== []) {
                QuizLikertAnswer::insert($likertRows); // satu panggilan untuk seluruh baris
            }

            $choiceMaxScore = count($data->choiceAnswers);
            $percentage = $choiceMaxScore > 0 ? intdiv($choiceScore * 100, $choiceMaxScore) : 0;

            $likertAverage = null;

            if ($data->likertAnswers !== []) {
                $likertValues = LikertScaleOption::query()
                    ->whereIn('id', array_column($data->likertAnswers, 'likert_scale_option_id'))
                    ->pluck('value');

                $likertAverage = round((float) $likertValues->avg(), 2);
            }

            $attempt->update([
                'choice_score' => $choiceScore,
                'choice_max_score' => $choiceMaxScore,
                'passed' => $percentage >= $attempt->quizContent->passing_score,
                'likert_average' => $likertAverage,
                'completed_at' => $now,
            ]);

            $attempt->quizContent->loadMissing('modulePage');

            if ($attempt->quizContent->modulePage !== null) {
                $this->progress->markPageCompleted($attempt->user, $attempt->quizContent->modulePage);
            }

            event(new QuizAttemptCompleted($attempt));

            return $attempt->fresh();
        });
    }

    /**
     * Cek SATU pertanyaan per panggilan (gaya ujian, bukan Duolingo-style
     * `SimulationScoringService::checkAnswer` yang menolak jawaban salah
     * tanpa menyimpannya): jawaban SALAH tetap disimpan permanen begitu
     * dicek — soal itu langsung terkunci untuk attempt ini, tidak ada
     * "coba lagi sampai benar" seperti simulasi. Pertanyaan yang SUDAH
     * pernah dicek (idempotent lewat `firstOrCreate`) mengembalikan hasil
     * PERTAMA kali tersimpan, mengabaikan jawaban baru yang dikirim.
     *
     * Attempt otomatis completed begitu SELURUH pertanyaan (choice + likert)
     * sudah pernah dicek — lihat `completeIfAllAnswered`.
     */
    public function checkAnswer(QuizAttempt $attempt, QuizAnswerCheckData $data): QuizAnswerCheckResult
    {
        if ($attempt->completed_at !== null) {
            throw new InvalidSubmissionException('Attempt sudah pernah diselesaikan.'); // BR-08
        }

        return DB::transaction(function () use ($attempt, $data): QuizAnswerCheckResult {
            if ($data->type === QuizSegmentType::Likert) {
                QuizLikertAnswer::query()->firstOrCreate(
                    ['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $data->quizQuestionId],
                    ['likert_scale_option_id' => $data->likertScaleOptionId],
                );

                $this->completeIfAllAnswered($attempt);

                return new QuizAnswerCheckResult(
                    correct: null,
                    correctOptionId: null,
                    explanation: null,
                    attempt: $attempt->fresh(),
                );
            }

            $question = QuizQuestion::query()->with('choiceOptions')->findOrFail($data->quizQuestionId);
            $correctOption = $question->choiceOptions->firstWhere('is_correct', true);

            $answer = QuizChoiceAnswer::query()->firstOrCreate(
                ['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $data->quizQuestionId],
                [
                    'quiz_choice_option_id' => $data->quizChoiceOptionId,
                    'is_correct' => $correctOption?->id === $data->quizChoiceOptionId,
                ],
            );

            $this->completeIfAllAnswered($attempt);

            return new QuizAnswerCheckResult(
                correct: $answer->is_correct,
                correctOptionId: $correctOption?->id,
                explanation: $question->explanation,
                attempt: $attempt->fresh(),
            );
        });
    }

    /**
     * Paralel sengaja dibiarkan terpisah dari agregasi di `submit()` (bukan
     * di-refactor jadi satu method bersama) — `submit()` sudah punya kontrak
     * jumlah query yang dikunci test (`test_submit_query_count_does_not_scale_with_question_count`),
     * jadi tidak disentuh sama sekali di sini.
     */
    private function completeIfAllAnswered(QuizAttempt $attempt): void
    {
        $totalQuestions = QuizQuestion::query()
            ->whereHas('quizSegment', fn ($query) => $query->where('quiz_content_id', $attempt->quiz_content_id))
            ->count();

        $attempt->loadCount(['choiceAnswers', 'likertAnswers']);
        $answeredCount = $attempt->choice_answers_count + $attempt->likert_answers_count;

        if ($totalQuestions === 0 || $answeredCount < $totalQuestions) {
            return;
        }

        $choiceScore = (int) $attempt->choiceAnswers()->where('is_correct', true)->count();
        $choiceMaxScore = $attempt->choice_answers_count;
        $percentage = $choiceMaxScore > 0 ? intdiv($choiceScore * 100, $choiceMaxScore) : 0;

        $likertAverage = null;

        if ($attempt->likert_answers_count > 0) {
            $likertAverage = round((float) $attempt->likertAnswers()
                ->join('likert_scale_options', 'likert_scale_options.id', '=', 'quiz_likert_answers.likert_scale_option_id')
                ->avg('likert_scale_options.value'), 2);
        }

        $attempt->update([
            'choice_score' => $choiceScore,
            'choice_max_score' => $choiceMaxScore,
            'passed' => $percentage >= $attempt->quizContent->passing_score,
            'likert_average' => $likertAverage,
            'completed_at' => Date::now(),
        ]);

        $attempt->quizContent->loadMissing('modulePage');

        if ($attempt->quizContent->modulePage !== null) {
            $this->progress->markPageCompleted($attempt->user, $attempt->quizContent->modulePage);
        }

        event(new QuizAttemptCompleted($attempt));
    }
}
