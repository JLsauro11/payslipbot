<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $fillable = [
        'employee_id',
         'name',
        'payslip',
        'payslip_date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    // Use this computed name everywhere instead of DB `name`
    public function getDisplayNameAttribute()
    {
        $employee = $this->employee;

        if (!$employee) {
            return '-';
        }

        $fname   = $employee->first_name ?? '';
        $lname   = $employee->last_name ?? '';
        $middle  = $employee->middle_initial ? ' ' . $employee->middle_initial : '';
        $suffix  = $employee->suffix ? ' ' . trim($employee->suffix) : '';

        if ($suffix) {
            return trim("{$lname}, {$fname}{$suffix}{$middle}") ?: '-';
        }

        return trim("{$lname}, {$fname}{$middle}") ?: '-';
    }
}
