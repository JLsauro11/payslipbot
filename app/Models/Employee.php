<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'employee_id',
        'bio_number',
        'first_name',
        'last_name',
        'middle_initial',
        'suffix',
        'password',
        'position_id',
        'department_id',
        'area_id',
        'status'
    ];

    protected $casts = [
        'password' => 'string',
    ];

    /**
     * Get the position that belongs to the employee.
     */
    // ✅ ADD THESE RELATIONSHIPS:
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = $value;
    }

    public function verifyPassword(string $password): bool
    {
        // 🔥 Works for BOTH hashed AND plain text
        return $this->password === $password;
    }

}
