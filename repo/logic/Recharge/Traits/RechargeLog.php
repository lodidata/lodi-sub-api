<?php
namespace Logic\Recharge\Traits;

use Utils\Telegram;

trait RechargeLog{

    private static $logDir =  LOG_PATH.'/pay';
    //添加数据库日志
    public static function addLogBySql($data,$table){
        if($table == 'pay_callback_failed'){
            //回调时间过滤报警
            if(strpos($data['error'], '回调时间') === false && $data['error'] != 'unpaid' && strpos($data['error'], 'special_suggestion') === false){
                global $app;
                $website_name = $app->getContainer()->get('settings')['website']['name'];
                $content = "【".$website_name."】支付回调失败：".PHP_EOL;
                $content .= '支付类型：' . $data['pay_type'] . PHP_EOL;
                $content .= '      方式：' . $data['method'] . PHP_EOL;
                $content .= ' 错误提示：' . $data['error'] . PHP_EOL;
                $content .= '      参数：' . $data['content'] . PHP_EOL;
                $content .= '警告时间：' . date('Y-m-d H:i:s');
                Telegram::sendMiddleOrdersMsg($content);
            }
        }
        \DB::table($table)->insert($data);
    }
    //添加文本日志
    public static function addLogByTxt($data, $table){
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0777, true);
        }
        $file = self::$logDir.'/'.date('Y-m-d').'.log';
        $stream = @fopen($file, "aw+");
        $data['json'] = !empty($data['json']) ? json_decode($data['json'], true) : [];
        $data['response'] = !empty($data['response']) ? json_decode($data['response'], true) : [];
        $str = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $str .= "\r\n";
        @fwrite($stream, $str);
        @fclose($stream);
    }

    public static function addLog($data,$table){
        self::addLogBySql($data,$table);
        self::addLogByTxt($data,$table);
    }

    public static function logger($obj,$data,$table){
        $obj->logger->info($table, $data);
    }
}