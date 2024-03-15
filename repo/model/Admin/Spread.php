<?php

namespace Model\Admin;

class Spread extends \Illuminate\Database\Eloquent\Model {

    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    protected $table = 'spread';

    public static function getOne($id) {
        return self::find($id);
    }

}