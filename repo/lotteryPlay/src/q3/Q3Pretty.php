<?php
namespace LotteryPlay\q3;
use LotteryPlay\BasePretty;
class Q3Pretty extends BasePretty{

    public static function prettyDwd($playId,$playNumber) {
        $config =  require __DIR__.'/'.'/StructConfig.php';
        if ($playId == 614){
            $ch = ['大小单双'];
            $is_kuaijie = true;
            $vv = [['大', '小', '单', '双']];
        }else{
            $ch = array_column($config[$playId]['tags'],'nm');
            $vv = array_column($config[$playId]['tags'],'vv');
            $type = array_values($config[$playId]['name']);
            $is_kuaijie = false;
            if ($type[0] == '快捷') $is_kuaijie = true;
        }

        $output =  self::getPretty($ch,$playNumber,$is_kuaijie,$vv);
        return $output;
    }
}