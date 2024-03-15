<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/5
 * Time: 11:14
 */

namespace LotteryPlay\lhc;
use LotteryPlay\BasePretty;

class LHCPretty extends BasePretty
{
    public static function prettyDwd($playId,$playNumber) {
        $config =  require __DIR__.'/'.'/StructConfig.php';
        $ch = array_column($config[$playId]['tags'],'nm');
        $vv = array_column($config[$playId]['tags'],'vv');
        $is_kuaijie = true;
        $output =  self::getPretty($ch,$playNumber,$is_kuaijie,$vv);
        return $output;
    }

}