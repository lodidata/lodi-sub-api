<?php
namespace LotteryPlay\xy28;
use LotteryPlay\BasePretty;
class Xy28Pretty extends BasePretty{

    public static function prettyDwd($playId,$playNumber) {
        $config =  require __DIR__.'/'.'/StructConfig.php';
        $ch = array_column($config[$playId]['tags'],'nm');
        $vv = array_column($config[$playId]['tags'],'vv');
        $is_kuaijie = true;
        $output =  self::getPretty($ch,$playNumber,$is_kuaijie,$vv);
        return $output;
    }
}