<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = ['name', 'event_date', 'description', 'amount_cents'];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'amount_cents' => 'integer'];
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function summary(): array
    {
        $rows = $this->contributions;
        $count = $rows->count();
        $paid = $rows->where('amount_cents', '>=', $this->amount_cents)->count();

        return [
            'count' => $count,
            'expected' => $count * $this->amount_cents,
            'collected' => $rows->sum('amount_cents'),
            'outstanding' => $rows->sum(fn ($row) => max(0, $this->amount_cents - $row->amount_cents)),
            'paid' => $paid,
            'unpaid' => $count - $paid,
            'percent' => $count ? (int) round($paid / $count * 100) : 0,
        ];
    }
}
