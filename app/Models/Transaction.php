<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pp_id', 'reference_id', 'event_code', 'initiation_date', 'currency', 'value',
        'description',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'initiation_date' => 'datetime',
        'firefly_id'      => 'int',
    ];

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Payer::class);
    }

    public function getIsPaymentAttribute(): bool
    {
        return 'T00' === substr($this->event_code, 0, 3) && $this->value < 0;
    }

    public function getIsRevenueAttribute(): bool
    {
        return 'T00' === substr($this->event_code, 0, 3) && $this->value > 0;
    }

    public function getIsRefundAttribute(): bool
    {
        return in_array($this->event_code, ['T1106', 'T1107']);
    }
}
