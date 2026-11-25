<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceDutySlot extends Model
{
    public $timestamps = false;

    protected $fillable = ['service_id', 'duty_role_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function dutyRole(): BelongsTo
    {
        return $this->belongsTo(DutyRole::class);
    }
}
