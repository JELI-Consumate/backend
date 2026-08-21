<?php

declare(strict_types=1);

namespace App\Http\Requests\Simulation;

use App\Data\SimulationSubmissionData;
use App\Models\SimulationAttempt;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

final class SubmitSimulationAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'matching_answers' => ['array'],
            'matching_answers.*.simulation_matching_pair_id' => ['required', 'integer'],
            'matching_answers.*.submitted_right_pair_id' => ['required', 'integer'],
            'ordering_answers' => ['array'],
            'ordering_answers.*.simulation_ordering_step_id' => ['required', 'integer'],
            'ordering_answers.*.submitted_position' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Custom "whereIn" validation: pair/step ids di-preload SEKALI, lalu dicek
     * keanggotaannya di memori — bukan `exists` per baris jawaban.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator): void {
            $attempt = SimulationAttempt::query()
                ->with(['simulationContent.matchingPairs', 'simulationContent.orderingSteps'])
                ->find($this->route('id'));

            if ($attempt === null) {
                return;
            }

            $validPairIds = $attempt->simulationContent->matchingPairs->pluck('id')->all();
            $validStepIds = $attempt->simulationContent->orderingSteps->pluck('id')->all();

            foreach ($this->input('matching_answers', []) as $index => $answer) {
                if (! in_array($answer['simulation_matching_pair_id'] ?? null, $validPairIds, true)
                    || ! in_array($answer['submitted_right_pair_id'] ?? null, $validPairIds, true)) {
                    $validator->errors()->add("matching_answers.{$index}", 'Pasangan tidak valid untuk simulasi ini.');
                }
            }

            foreach ($this->input('ordering_answers', []) as $index => $answer) {
                if (! in_array($answer['simulation_ordering_step_id'] ?? null, $validStepIds, true)) {
                    $validator->errors()->add("ordering_answers.{$index}", 'Langkah tidak valid untuk simulasi ini.');
                }
            }
        });
    }

    public function toData(): SimulationSubmissionData
    {
        return new SimulationSubmissionData(
            $this->validated('matching_answers') ?? [],
            $this->validated('ordering_answers') ?? [],
        );
    }
}
