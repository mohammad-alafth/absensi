<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [

        'name',

        'start_time',

        'end_time',

        'color',

        'work_hours',

        'grace_minutes',

        'category',

        'is_overnight'
    ];

    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }
}
