<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;

    protected $fillable = ['first_name', 'middle_name', 'last_name', 'gender', 'birth_date', 'location', 'address', 'mobile', 'emergency_contact', 'status', 'registered_at'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'registered_at' => 'date'];
    }

    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]));
    }

    public function getAgeAttribute(): int
    {
        return $this->birth_date->age;
    }

    public function getInitialsAttribute(): string
    {
        return mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1);
    }
}
