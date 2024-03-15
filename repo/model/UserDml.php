<?php

namespace Model;

use DB;

class UserDml extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'user_dml';

    public $timestamps = false;

    protected $primaryKey = 'user_id';
}

