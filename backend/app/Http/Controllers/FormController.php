<?php

namespace App\Http\Controllers;

use App\Models\FormSection;

class FormController extends Controller
{
    /**
     * Return the full form definition (sections -> fields -> options)
     * for the frontend to render.
     */
    public function show()
    {
        $sections = FormSection::with(['fields.options'])
            ->orderBy('position')
            ->get()
            ->map(fn (FormSection $section) => [
                'id' => $section->id,
                'name' => $section->name,
                'fields' => $section->fields->map(fn ($field) => [
                    'id' => $field->id,
                    'label' => $field->label,
                    'type' => $field->type,
                    'sub_type' => $field->sub_type,
                    'description' => $field->description,
                    'options' => $field->options->map(fn ($o) => [
                        'id' => $o->id,
                        'label' => $o->label,
                        'value' => $o->value,
                    ])->values(),
                ])->values(),
            ])->values();

        return response()->json(['sections' => $sections]);
    }
}
