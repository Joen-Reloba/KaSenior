<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;

    protected $fillable = ['first_name', 'middle_name', 'last_name', 'name_extension', 'gender', 'birth_date', 'location', 'barangay', 'district', 'senior_citizen_id', 'id_issued_at', 'address', 'mobile', 'emergency_contact', 'status', 'registered_at', 'deceased_at'];

    protected $attributes = ['location' => '7C', 'barangay' => 'Baliok', 'district' => 'Talomo'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'registered_at' => 'date', 'deceased_at' => 'date', 'id_issued_at' => 'date'];
    }

    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name, $this->name_extension]));
    }

    public function getAgeAttribute(): int
    {
        $asOf = $this->status === 'Deceased' ? ($this->deceased_at ?? $this->updated_at ?? today()) : today();

        return (int) $this->birth_date->diffInYears($asOf, true);
    }

    public function getInitialsAttribute(): string
    {
        return mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1);
    }
}
