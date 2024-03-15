<?php

namespace Model;

class RebetCacheConfig extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'rebet_cache_config';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'count_date',
        'content',
    ];
}


