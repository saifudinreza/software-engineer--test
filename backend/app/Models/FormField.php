<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'section_id', 'label', 'type', 'sub_type', 'description', 'orm_only', 'position',
    ];

    protected $casts = [
        'orm_only' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'section_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(FieldOption::class, 'field_id')->orderBy('position');
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, ['radio_button', 'checkbox'], true);
    }

    public function isMultiValue(): bool
    {
        return $this->type === 'checkbox';
    }
}
