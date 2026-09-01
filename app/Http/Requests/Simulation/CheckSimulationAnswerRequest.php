<?php

declare(strict_types=1);

namespace App\Http\Requests\Simulation;

use App\Data\SimulationAnswerCheckData;
use App\Enums\SimulationType;
use App\Models\SimulationAttempt;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

final class CheckSimulationAnswerRequest extends FormRequest
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
            'type' => ['required', 'in:matching,ordering'],
            'simulation_matching_pair_id' => ['required_if:type,matching', 'string', 'ulid'],
            'submitted_right_pair_id' => ['required_if:type,matching', 'string', 'ulid'],
            'simulation_ordering_step_id' => ['required_if:type,ordering', 'string', 'ulid'],
            'submitted_position' => ['required_if:type,ordering', 'integer', 'min:1'],
        ];
    }

    /**
     * Pastikan pair/step id yang dikirim memang milik simulasi attempt ini
     * (bukan milik simulasi lain) — dicek sekali di memori, bukan `exists` query.
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

            if ($this->input('type') === 'matching') {
                $validPairIds = $attempt->simulationContent->matchingPairs->pluck('id')->all();

                if (! in_array($this->input('simulation_matching_pair_id'), $validPairIds, true)
                    || ! in_array($this->input('submitted_right_pair_id'), $validPairIds, true)) {
                    $validator->errors()->add('simulation_matching_pair_id', 'Pasangan tidak valid untuk simulasi ini.');
                }

                return;
            }

            $validStepIds = $attempt->simulationContent->orderingSteps->pluck('id')->all();

            if (! in_array($this->input('simulation_ordering_step_id'), $validStepIds, true)) {
                $validator->errors()->add('simulation_ordering_step_id', 'Langkah tidak valid untuk simulasi ini.');
            }
        });
    }

    public function toData(): SimulationAnswerCheckData
    {
        return new SimulationAnswerCheckData(
            type: SimulationType::from($this->validated('type')),
            simulationMatchingPairId: $this->validated('simulation_matching_pair_id'),
            submittedRightPairId: $this->validated('submitted_right_pair_id'),
            simulationOrderingStepId: $this->validated('simulation_ordering_step_id'),
            submittedPosition: $this->validated('submitted_position'),
        );
    }
}
