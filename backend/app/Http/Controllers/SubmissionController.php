<?php

namespace App\Http\Controllers;

use App\Models\FormField;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmissionController extends Controller
{
    /**
     * Validate the submitted answers against the form definition and store them.
     *
     * Expected body: { "answers": { "<field_id>": <value>, ... } }
     *  - radio_button: value is a single option id (string)
     *  - checkbox:     value is an array of option ids
     *  - text/long_text: value is a string (date sub_type must be a valid date)
     */
    public function store(Request $request)
    {
        $answers = $request->input('answers', []);

        if (! is_array($answers)) {
            throw ValidationException::withMessages(['answers' => 'answers must be an object.']);
        }

        // Load only the fields referenced in the payload (+ their options).
        $fields = FormField::with('options')
            ->whereIn('id', array_keys($answers))
            ->get()
            ->keyBy('id');

        $validated = [];

        foreach ($answers as $fieldId => $value) {
            /** @var FormField|null $field */
            $field = $fields->get($fieldId);

            if (! $field) {
                throw ValidationException::withMessages([
                    "answers.$fieldId" => "Unknown field id: {$fieldId}",
                ]);
            }

            $validated[$fieldId] = $this->validateValue($field, $value);
        }

        $submission = DB::transaction(function () use ($validated) {
            $submission = Submission::create();

            $rows = [];
            $now = now();
            foreach ($validated as $fieldId => $value) {
                $rows[] = [
                    'submission_id' => $submission->id,
                    'field_id' => $fieldId,
                    'value' => json_encode($value),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows) {
                $submission->values()->getRelated()->insert($rows);
            }

            return $submission;
        });

        return response()->json([
            'id' => $submission->id,
            'message' => 'Submission saved.',
        ], 201);
    }

    public function show(Submission $submission)
    {
        $submission->load('values');

        return response()->json([
            'id' => $submission->id,
            'created_at' => $submission->created_at,
            'answers' => $submission->values
                ->mapWithKeys(fn ($v) => [$v->field_id => $v->value]),
        ]);
    }

    /**
     * Validate one answer against its field definition and return the
     * normalised value to store.
     */
    protected function validateValue(FormField $field, mixed $value): mixed
    {
        $key = $field->id;

        if ($field->hasOptions()) {
            $validOptionIds = $field->options->pluck('id')->all();

            if ($field->isMultiValue()) {
                $value = $value ?? [];
                Validator::make(
                    ['v' => $value],
                    ['v' => 'array', 'v.*' => ['string', \Illuminate\Validation\Rule::in($validOptionIds)]],
                    [],
                    ['v' => $field->label, 'v.*' => $field->label]
                )->validate();

                return array_values($value);
            }

            // radio_button: single option id (empty allowed = no answer).
            if ($value === null || $value === '') {
                return null;
            }
            Validator::make(
                ['v' => $value],
                ['v' => ['string', \Illuminate\Validation\Rule::in($validOptionIds)]],
                [],
                ['v' => $field->label]
            )->validate();

            return $value;
        }

        // Free-text fields.
        $rules = ['string', 'nullable'];
        if ($field->sub_type === 'date' && $value !== null && $value !== '') {
            $rules[] = 'date';
        }

        Validator::make(
            ['v' => $value],
            ['v' => $rules],
            [],
            ['v' => $field->label]
        )->validate();

        return $value;
    }
}
