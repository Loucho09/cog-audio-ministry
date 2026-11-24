<?php

namespace App\Models;

use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(FaqFactory::class)]
class Faq extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'answer', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_published' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }
}
