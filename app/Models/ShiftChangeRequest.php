<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_date',
        'current_shift_id',
        'requested_shift_id',
        'reason',
        'status',
        'pj_note',
        'pj_approved_by',
        'pj_approved_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentShift()
    {
        return $this->belongsTo(Shift::class, 'current_shift_id');
    }

    public function requestedShift()
    {
        return $this->belongsTo(Shift::class, 'requested_shift_id');
    }

    public function pjApprover()
    {
        return $this->belongsTo(User::class, 'pj_approved_by');
    }
}
