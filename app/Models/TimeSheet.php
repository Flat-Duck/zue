<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimeSheet extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'value',
        'day',
        'employee_id',
        'revised_at',
        'old_value',
        'user_id',
        'over_time'
    ];

    protected $searchableFields = ['*'];

    protected $table = 'time_sheets';

    protected $casts = [
        'day' => 'date',
        'revised_at' => 'datetime',
    ];

    public function revisedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function time_keeper()
    {
        return $this->belongsTo(User::class, 'timekeeper_id');
    }
    
    public function super_intendent()
    {
        return $this->belongsTo(User::class, 'superintendent_id');
    }
    
    public function super_visor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
