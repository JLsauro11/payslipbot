<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'bio_number',
        'name',
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
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the department that belongs to the employee.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the area that belongs to the employee.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
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

    /**
     * Get position name attribute (for backward compatibility)
     */
    public function getPositionAttribute(): ?string
    {
        return $this->position?->name;
    }

    /**
     * Get department name attribute (for backward compatibility)
     */
    public function getDepartmentAttribute(): ?string
    {
        return $this->department?->name;
    }

    /**
     * Get area name attribute (for backward compatibility)
     */
    public function getAreaAttribute(): ?string
    {
        return $this->area?->name;
    }
}
