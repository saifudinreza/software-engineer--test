<?php

namespace App\Services;

use App\Models\FieldOption;
use App\Models\FormField;
use App\Models\FormSection;
use Illuminate\Support\Facades\DB;
use JsonMachine\Items;

/**
 * Imports the form definition from the JSON feed into the database.
 *
 * Memory consideration: the feed is parsed with a streaming JSON parser
 * (halaxa/json-machine) so the whole file is never materialised into one
 * large PHP array. Sections are processed one at a time, and rows are
 * inserted in bulk per section.
 */
class FormImporter
{
    /**
     * @return array{sections:int, fields:int, options:int}
     */
    public function importFromFile(string $path): array
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("Feed file not found: {$path}");
        }

        $counts = ['sections' => 0, 'fields' => 0, 'options' => 0];

        DB::transaction(function () use ($path, &$counts) {
            // Replace existing definition so re-imports are idempotent.
            FormSection::query()->delete(); // cascades to fields + options

            $sectionPosition = 0;

            // Stream the top-level array one section at a time.
            $sections = Items::fromFile($path);

            foreach ($sections as $section) {
                $sectionId = $section->id;

                FormSection::create([
                    'id' => $sectionId,
                    'name' => $section->name ?? '',
                    'position' => $sectionPosition++,
                ]);
                $counts['sections']++;

                $fieldRows = [];
                $optionRows = [];
                $fieldPosition = 0;

                foreach ($section->payloads ?? [] as $payload) {
                    $now = now();
                    $fieldRows[] = [
                        'id' => $payload->id,
                        'section_id' => $sectionId,
                        'label' => $payload->label ?? '',
                        'type' => $payload->type ?? 'text',
                        'sub_type' => $payload->sub_type ?? null,
                        'description' => $payload->description ?? null,
                        'orm_only' => (($payload->orm_only ?? 'no') === 'yes'),
                        'position' => $fieldPosition++,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $optionPosition = 0;
                    foreach ($payload->options ?? [] as $option) {
                        $optionRows[] = [
                            'id' => $option->id,
                            'field_id' => $payload->id,
                            'label' => $option->label ?? '',
                            'value' => $option->value ?? null,
                            'position' => $optionPosition++,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($fieldRows) {
                    FormField::insert($fieldRows);
                    $counts['fields'] += count($fieldRows);
                }
                if ($optionRows) {
                    FieldOption::insert($optionRows);
                    $counts['options'] += count($optionRows);
                }
            }
        });

        return $counts;
    }
}
