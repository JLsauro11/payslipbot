<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;  // 🔥 ADD THIS LINE (line 6)

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'password',
        'position',
        'department',
        'status'
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value;
    }

    public function verifyPassword(string $password): bool
    {
        // 🔥 Works for BOTH hashed AND plain text
        return $this->password === $password;
    }
}
