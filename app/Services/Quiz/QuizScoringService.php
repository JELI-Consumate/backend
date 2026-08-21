<?php

declare(strict_types=1);

namespace App\Services\Quiz;

use App\Data\QuizSubmissionData;
use App\Events\QuizAttemptCompleted;
use App\Exceptions\InvalidSubmissionException;
use App\Models\LikertScaleOption;
use App\Models\QuizAttempt;
use App\Models\QuizChoiceAnswer;
use App\Models\QuizChoiceOption;
use App\Models\QuizLikertAnswer;
use App\Services\Progress\ProgressService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

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
}
