<?php

namespace App\Models\Appraisals;

use Illuminate\Database\Eloquent\Model;

class AppraisalPeriod extends Model
{
    protected $fillable = [
        'year',
        'type',
        'quarter',
        'window_open_from',
        'window_open_to',
        'status'
    ];

    protected $casts = [
        'window_open_from' => 'date',
        'window_open_to' => 'date',
    ];

    public function getLabelAttribute(): string
    {
        if ($this->type === 'yearly')
            return "Yearly {$this->year}";
        return "Q{$this->quarter} {$this->year}";
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
