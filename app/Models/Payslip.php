<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $fillable = ['employee_id', 'name', 'payslip', 'payslip_date'];

    public function employee()
    {
        // ✅ FIXED: Match correct columns!
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

}
