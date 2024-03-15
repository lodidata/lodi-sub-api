<?php


namespace Model;
use DB;

class PayConfig  extends \Illuminate\Database\Eloquent\Model
{
    protected $table    = 'pay_config';
    public $timestamps  = false;

    /**
     * 获取在线充值类型 type
     * @return array
     */
    public static function getOnlinePayType(){
        $list = self::select('type')->orderBy('sort', 'asc')->get()->toArray();
        $list && $list = array_column($list, 'type');
        return $list ? $list : [];
    }
}