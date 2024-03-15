<?php
/**
 * 第三方大厅配置信息
 */

namespace Model;

use \Illuminate\Database\Eloquent\Model;
use DB;

class Hall3th extends Model
{
    protected $table = 'hall_3th';

    public static function getHallByType($type)
    {
        $types = (array)$type;
        return DB::connection()->table('hall_3th')
                    ->whereIn('3th_name', $types)
                    ->get()->toArray();
    }
}