<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventDutySlot extends Model
{
    public $timestamps = false;

    protected $fillable = ['event_id', 'duty_role_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function dutyRole(): BelongsTo
    {
        return $this->belongsTo(DutyRole::class);
    }
}
