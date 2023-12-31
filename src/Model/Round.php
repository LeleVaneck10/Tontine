<?php

namespace Siak\Tontine\Model;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    /**
     * @const
     */
    const STATUS_PENDING = 0;

    /**
     * @const
     */
    const STATUS_OPENED = 1;

    /**
     * @const
     */
    const STATUS_CLOSED = 2;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'status',
        'notes',
        'start_at',
        'end_at',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'start_at',
        'end_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_at' => 'datetime:Y-m-d',
        'end_at' => 'datetime:Y-m-d',
    ];

    public function getStartAttribute()
    {
        return $this->start_at->toFormattedDateString();
    }

    public function getEndAttribute()
    {
        return $this->end_at->toFormattedDateString();
    }

    public function getPendingAttribute()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getOpenedAttribute()
    {
        return $this->status === self::STATUS_OPENED;
    }

    public function getClosedAttribute()
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function tontine()
    {
        return $this->belongsTo(Tontine::class);
    }

    public function pools()
    {
        return $this->hasMany(Pool::class)->orderBy('id', 'asc');
    }

    public function sessions()
    {
        return $this->hasMany(Session::class)->orderBy('sessions.start_at', 'asc');
    }

    public function bills()
    {
        return $this->hasMany(RoundBill::class);
    }
}
