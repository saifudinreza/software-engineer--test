<?php

namespace Tests\Feature;

use App\Models\FieldOption;
use App\Models\FormField;
use App\Models\FormSection;
use App\Services\FormImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormApiTest extends TestCase
{
    use RefreshDatabase;

    protected function importFeed(): void
    {
        app(FormImporter::class)->importFromFile(database_path('data/submission.json'));
    }

    public function test_import_populates_normalised_tables(): void
    {
        $counts = app(FormImporter::class)->importFromFile(database_path('data/submission.json'));

        $this->assertSame(2, $counts['sections']);
        $this->assertSame(11, $counts['fields']);
        $this->assertSame(40, $counts['options']);

        $this->assertSame(2, FormSection::count());
        $this->assertSame(11, FormField::count());
        $this->assertSame(40, FieldOption::count());
    }

    public function test_import_is_idempotent(): void
    {
        $this->importFeed();
        $this->importFeed(); // re-run should not duplicate

        $this->assertSame(11, FormField::count());
    }

    public function test_form_endpoint_returns_definition(): void
    {
        $this->importFeed();

        $this->getJson('/api/form')
            ->assertOk()
            ->assertJsonCount(2, 'sections')
            ->assertJsonPath('sections.0.fields.0.label', 'Bulan Pelaporan')
            ->assertJsonCount(12, 'sections.0.fields.0.options');
    }

    public function test_valid_submission_is_stored(): void
    {
        $this->importFeed();

        $radio = FormField::where('type', 'radio_button')->with('options')->first();
        $checkbox = FormField::where('type', 'checkbox')->with('options')->first();
        $text = FormField::where('sub_type', 'date')->first();

        $payload = [
            'answers' => [
                $radio->id => $radio->options->first()->id,
                $checkbox->id => $checkbox->options->take(2)->pluck('id')->all(),
                $text->id => '2026-06-18',
            ],
        ];

        $response = $this->postJson('/api/submissions', $payload)
            ->assertCreated()
            ->assertJsonStructure(['id', 'message']);

        $id = $response->json('id');

        $this->assertDatabaseHas('submissions', ['id' => $id]);
        $this->assertDatabaseCount('submission_values', 3);

        $this->getJson("/api/submissions/{$id}")
            ->assertOk()
            ->assertJsonPath("answers.{$radio->id}", $radio->options->first()->id);
    }

    public function test_invalid_option_id_is_rejected(): void
    {
        $this->importFeed();

        $radio = FormField::where('type', 'radio_button')->first();

        $this->postJson('/api/submissions', [
            'answers' => [$radio->id => 'does-not-exist'],
        ])->assertStatus(422);
    }

    public function test_unknown_field_id_is_rejected(): void
    {
        $this->importFeed();

        $this->postJson('/api/submissions', [
            'answers' => ['not-a-real-field' => 'x'],
        ])->assertStatus(422);
    }

    public function test_invalid_date_is_rejected(): void
    {
        $this->importFeed();

        $dateField = FormField::where('sub_type', 'date')->first();

        $this->postJson('/api/submissions', [
            'answers' => [$dateField->id => 'not-a-date'],
        ])->assertStatus(422);
    }
}
