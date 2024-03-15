<?php
namespace LotteryPlay\pk10;
use LotteryPlay\BasePretty;
class PK10Pretty extends BasePretty{

    public static function prettyDwd($playId,$playNumber) {
        $config =  require __DIR__.'/'.'/StructConfig.php';
        $ch = array_column($config[$playId]['tags'],'nm');
        $vv = array_column($config[$playId]['tags'],'vv');
        $type = array_values($config[$playId]['name']);
        $is_kuaijie = false;
        if ($type[0] == '快捷') $is_kuaijie = true;

        $output =  self::getPretty($ch,$playNumber,$is_kuaijie,$vv);
        return $output;
    }
}