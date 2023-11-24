<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    protected $with = ['center','department','location','department.administration'];
    protected $fillable = [
        'number',
        'job',
        'english_name',
        'id_card',
        'id_card_issue_date',
        'passport',
        'passport_issue_date',
        'address',
        'phone',
        'email',
        'user_id',
        'location_id',
        'department_id',
        'center_id',
        'transfered_balance',
        'schedule',
        'start_date',
        'last_date',
        'total_balance',
        'archived_at',
    ];
    
    protected $appends = [
        'administration_name',
        'department_name',
        'location_name',
        'center_name',
        'start_date',
        'last_date',
        'balance',
    ];
    
    protected $searchableFields = ['*'];

    protected $casts = [
        'id_card_issue_date' => 'date',
        'passport_issue_date' => 'date',
        'start_date' => 'date',
        'last_date' => 'date',
        'archived_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timeSheets()
    {
        return $this->hasMany(TimeSheet::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class);
    }

    public function flights()
    {
        return $this->belongsToMany(Flight::class);
    }

    public function getAdministrationNameAttribute()
    {
        return optional($this->department->administration)->name ?? '-';
    }
    
    public function getDepartmentNameAttribute()
    { 
        return optional($this->department)->name ?? '-';
    }

    public function getLocationNameAttribute()
    {
        return is_null($this->location)? '' : $this->location->name;
    }
    public function getCenterNameAttribute()
    {
        return is_null($this->center)? '' :  $this->center->name;
    }

    public function getStartDateAttribute($date)
    {
         return date('Y/m/d',strtotime($date));
    }
    public function getLastDateAttribute($date)
    {
        return date('Y/m/d',strtotime($date));
    }

    public function getBalanceAttribute()
    {
        return isset($this)? 10 : '-';
    }
}
