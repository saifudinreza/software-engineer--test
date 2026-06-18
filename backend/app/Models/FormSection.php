<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSection extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'position'];

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'section_id')->orderBy('position');
    }
}
