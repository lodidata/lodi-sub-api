<?php

namespace Utils;

use DB;
use Model\Game3th;
use Utils\Encrypt;

class Utils {

    /**
     * @return array
     */
    public static function NeedFilterKeywords() {
        return ['email','wechat', 'mobile', 'qq', 'weixin', 'skype', 'idcard', 'telephone', 'card'];
    }

    /**
     * 判断夏令时
     * @return boolean [description]
     */
    public static function isDst($date = '')
    {
        $timezone = date('e'); //获取当前使用的时区
        @date_default_timezone_set('US/Pacific-New'); //强制设置时区
        $dst = empty($date) ? date('I') : date('I', strtotime($date)); //判断是否夏令时
        date_default_timezone_set($timezone); //还原时区
        return $dst; //返回结果
    }

    /**
     * openssl 双向加密
     *
     * @return Encrypt|null
     */
    public static function Xmcrypt() {
        global $app;
        static $mt = null;
        if (is_null($mt)) {
            $key = $app->getContainer()
                       ->get('settings')['app']['app_key'];
            $mt = new Encrypt($key);
        }

        return $mt;
    }

    /**
     * openssl 双向加密
     *
     * @return Encrypt|null
     */
    public static function XmcryptSetting() {
        global $app;
        static $mts = null;
        if (is_null($mts)) {
            $key = 'kQYOdswcm9I5elv2wdJucg==';
            $mts = new Encrypt($key);
        }
        return $mts;
    }

    /**
     * 加密
     *
     * @param string $data
     *
     * @return string
     */
    public static function settleCrypt($data,$encrypt = true) {
        if (empty($data)||is_bool($data)||is_null($data)) {
            return $data;
        }elseif(!is_array($data)){
            return self::XmcryptSetting()
                    ->settleCrypt($data,$encrypt) ?? $data;
        }
        $res = [];
        foreach ($data as $key=>$val){
            $res[$key] = self::settleCrypt($val,$encrypt);
        }
        return $res;
    }

    /**
     * 加密
     *
     * @param string $data
     *
     * @return string
     */
    public static function RSAEncrypt(string $data = null) {
        if (!strlen($data)) {
            return $data;
        }

        return self::Xmcrypt()
                   ->encrypt($data) ?? $data;
    }

    /**
     * 个人信息加、解密补丁
     *
     * @param array $data 多维数组
     * @param int $handler Enc 加密 Dec 解密
     *
     * @return array
     */
    public static function RSAPatch(array &$data = null, int $handler = Encrypt::DECRYPT,bool $show = true) {
        if (!$data) {
            return $data;
        }
        foreach ($data as $key => &$datum) {
            $datum = is_object($datum) ? ((array)$datum) : $datum;
            if (is_array($datum)) {
                $datum = self::RSAPatch($datum, $handler);
            } else {
                if (in_array($key, self::NeedFilterKeywords(), true)) {
                    if($handler == Encrypt::DECRYPT) {
                        $datum = self::RSADecrypt($datum);
                        //隐藏中间的三分之一
                        if(!$show) {
                            $l = intval(strlen($datum)/3);
                            $datum = str_replace(substr($datum,$l,$l),'****',$datum);
                        }
                    }else {
                        $datum = self::RSAEncrypt($datum);
                    }

                }
            }
        }

        return $data;
    }

    /**
     * 解密
     *
     * @param string $data
     *
     * @return null|string
     */
    public static function RSADecrypt(string $data = null) {
        if (!strlen($data)) {
            return $data;
        }

        return self::Xmcrypt()
                   ->decrypt($data) ?? $data;
    }


    /**
     * 银行卡、提款、取款补丁
     * 处理表fund_deposit里面的银行卡信息(解密)
     * 数组：支持一维['pay_bank_info'=>,'receive_bank_info'=>]
     * 或二维数组[
     *  0=>['pay_bank_info'=>,'receive_bank_info'=>],
     *  1=>['pay_bank_info'=>,'receive_bank_info'=>]
     * ]
     *
     * @param \Data\Paged|\Row|\Rowset|array $result
     * @param int $handler
     *
     * @return mixed
     */
    public static function DepositPatch(&$result, int $handler = Encrypt::DECRYPT) {
        if ($result instanceof \Data\Paged) {
            $data = $result->data();
        } else if (is_array($result) || $result instanceof \Traversable) {
            $data = &$result;
        } else {
            return $result;
        }
        // 二维数组
        if (isset($data[0])) {
            foreach ($data as &$datum) {
                $datum = self::DepositPatch($datum, $handler);
            }
        } else {
            // 一维
            foreach ($data as $key => &$datum) {
                if (in_array($key, ['pay_bank_info', 'receive_bank_info'])) {
                    if (is_string($datum)) {
                        $datum = strlen($datum) ? json_decode($datum, true) : [];
                    }
                    if (is_array($datum)) {
                        $datum = json_encode(self::RSAPatch($datum, $handler), JSON_UNESCAPED_UNICODE);
                    }
                }
            }
        }

        if (is_object($result) && method_exists($result, 'setData')) {
            $result->setData($data);
        }

        return $result;
    }

    public static function exportExcel($file, $title, $data) {
        header('Content-type:application/vnd.ms-excel');
        header('Content-Disposition:attachment;filename=' . $file . '.xls');
        $content = '';
        foreach ($title as $tval) {
            $content .= $tval . "\t";
        }
        $content .= "\n";
        $keys = array_keys($title);
        if ($data) {
            foreach ($data as $ke => $val) {
                if ($ke > 49999) {
                    break;
                }
                $val = (array)$val;
                foreach ($keys as $k) {
                    $content .= rtrim(ltrim($val[$k],'"')) . "\t";
                }
                $content .= "\n";
                echo mb_convert_encoding($content, "UTF-8", "UTF-8");
                $content = '';
            }
        }
        exit;
    }

    /**
     * 随机字符串。
     *
     * @param int $length 字符串长度。
     * @param string $chars 可选，字符表，默认为 a-zA-Z0-9 区分大小写的集合。
     *
     * @return string 注意：返回的长度为 $length 和和字符表长度中的最小值。
     */
    public static function randStr(int $length = 16, string $chars = 'stOcdWpuFUVw9Eb4eYfgSZ0ykln3jTGa2xKB5zqrPTv7NDXCoMLRh8HIJiQA1m6') {
        $chars_length = strlen($chars);

        $string = '';
        while ($length--) {
            $string .= substr($chars, mt_rand(0, $chars_length - 1), 1);
        }

        return $string;
    }

    /**
     * 返回带*号的账号
     */
    public static function randUsername(){
        $account = self::randStr(2,'abcdefghijklmnopqrstuvwxyz').'***'.self::randStr(2,'0123456789');
        return $account;
    }

    public static function creatUsername($charLen = 2, $numLen = 12){
        $account = self::randStr($charLen,'abcdefghijklmnopqrstuvwxyz').self::randStr($numLen,'0123456789');
        return $account;
    }

    public static function updateBatch($multipleData = [],$table)
    {
        try {
            if (empty($multipleData)) {
                throw new \Exception("数据不能为空");
            }
            $tableName = DB::getTablePrefix() . $table; // 表名
            $firstRow  = current($multipleData);
            if (empty($firstRow)) {
                throw new \Exception("数据格式错误！");
            }
            //初始化 先把所热门的都置为0
            if(!in_array($table,['pay_channel','payment_channel'])){
                Game3th::where('is_hot',1)->update(['is_hot' => 0]);
            }
            $updateColumn = array_keys($firstRow);
            // 默认以id为条件更新，如果没有ID则以第一个字段为条件

            $referenceColumn = isset($firstRow['id']) ? 'id' : current($updateColumn);
            unset($updateColumn[0]);
            // 拼接sql语句
            $updateSql = "UPDATE " . $tableName . " SET ";
            $sets      = [];
            $bindings  = [];
            foreach ($updateColumn as $uColumn) {
                $setSql = "`" . $uColumn . "` = CASE ";
                foreach ($multipleData as $data) {
                    $setSql .= "WHEN `" . $referenceColumn . "` = ? THEN ? ";
                    $bindings[] = $data[$referenceColumn];
                    $bindings[] = $data[$uColumn];
                }
                $setSql .= "ELSE `" . $uColumn . "` END ";
                $sets[] = $setSql;
            }
            $updateSql .= implode(', ', $sets);
            $whereIn   = collect($multipleData)->pluck($referenceColumn)->values()->all();
            $bindings  = array_merge($bindings, $whereIn);
            $whereIn   = rtrim(str_repeat('?,', count($whereIn)), ',');
            $updateSql = rtrim($updateSql, ", ") . " WHERE `" . $referenceColumn . "` IN (" . $whereIn . ")";
            // 传入预处理sql语句和对应绑定数据
            return DB::update($updateSql, $bindings);
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * XML解析成数组
     */
    public static function parseXML($xmlSrc){
        if(empty($xmlSrc)){
            return false;
        }
        $array = array();
        $xml = simplexml_load_string($xmlSrc);
        $encode = self::getXmlEncode($xmlSrc);
        if($xml && $xml->children()) {
            foreach ($xml->children() as $node){
                //有子节点
                if($node->children()) {
                    $k = $node->getName();
                    $nodeXml = $node->asXML();
                    $v = self::parseXML($nodeXml);
                } else {
                    $k = $node->getName();
                    $v = (string)$node;
                }
                if($encode!="" && strpos($encode,"UTF-8") === FALSE ) {
                    $k = iconv("UTF-8", $encode, $k);
                    $v = iconv("UTF-8", $encode, $v);
                }
                $array[$k] = $v;
            }
        }
        return $array;
    }

    public static function to_xml(array $data){
        $xml='<?xml version="1.0" encoding="UTF-8" ?>';
        $xmlStr=self::data_to_xml($data);

        return $xml.$xmlStr;
    }

    public static function data_to_xml(array $data) {
        $xml = '';
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = "item id=\"$key\"";
            $xml    .=  "<$key>";
            $xml    .=  ( is_array($val) || is_object($val)) ? self::data_to_xml($val) : $val;
            list($key, ) = explode(' ', $key);
            $xml    .=  "</$key>";
        }
        return $xml;
    }

    //获取xml编码
    public static function getXmlEncode($xml) {
        $ret = preg_match ("/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr);
        if($ret) {
            return strtoupper ( $arr[1] );
        } else {
            return "";
        }
    }

    public static function subtext($text, $length)
    {
        if (mb_strlen($text, 'utf8') > $length) {
            return mb_substr($text, 0, $length, 'utf8') . '...';
        } else {
            return $text;
        }
    }

    public static function fileDebug($massage)
    {
        if(is_object($massage) || is_array($massage)){
            $massage = json_encode($massage, JSON_UNESCAPED_UNICODE);
        }
        file_put_contents(ROOT_PATH.'/logs/debug.log', $massage.PHP_EOL,FILE_APPEND);
    }


    public static function matchChinese($chars, $encoding = 'utf8')
    {
        $pattern = ($encoding == 'utf8') ? '/[\x{4e00}-\x{9fa5}a-zA-Z0-9]/u' : '/[\x80-\xFF]/';
        preg_match_all($pattern, $chars, $result);
        return join('', $result[0]);
    }

    //获取数组某个值 组成新的数组
    public static function arrayChangeKey($arr,$key)
    {
        $processedArr = array();
        if(is_array($arr) && !empty($arr)){

            foreach ($arr as $item)
            {
                $processedArr[$item->$key] = $item->user_id;
            }
        }
        return $processedArr;
    }    //获取数组某个值 组成新的数组
        public static function GetWeekDay($target_weekday)
    {
        // 获取当前时间戳
        $current_time = time();
        // 获取当前周几（0表示周日，1表示周一，以此类推）
        $current_weekday = date('w', $current_time);
        if ($current_weekday == 0) $current_weekday =7;
        // 获取指定周几的时间戳（假设指定周三，即weekday=3）
//            $target_weekday = 3; // 周三
        $days_diff = ($target_weekday >= $current_weekday) ? ($target_weekday - $current_weekday) : @($target_weekday + 7 - $current_weekday);
        $target_time = $current_time + $days_diff * 86400;
        // 计算距离指定周几还有多少天
        $days_left = ceil(($target_time - $current_time) / 86400);
        // 输出结果
        if($days_left <= 0)  $days_left = 0;
        return $days_left;
    }

};