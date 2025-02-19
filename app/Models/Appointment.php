<?php

namespace App\Models;

use App\Enums\AppointmentStatusEnum;
use App\Enums\PaymentStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'payment_id',
        'date_time',
        'status',
    ];

    protected $casts = [
        'date_time' => 'datetime:Y-m-d H:i',
    ];

    /**
     * Relationships
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'id', 'payment_id');
    }

    /**
     * Accesors and Mutators
     */
    public function canConfirm(): Attribute
    {
        return Attribute::make(
            get: function () {
                $now = Carbon::now();
                $dateTime = Carbon::parse($this->date_time);

                $validDateTime = $dateTime->isAfter($now);
                $validPaymentStatus = $this->payment?->status === PaymentStatusEnum::Completed->value();


                return $validDateTime && $validPaymentStatus;
            }
        );
    }

    public function confirmed(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === AppointmentStatusEnum::Confirmed->value(),
        );
    }
}
