<?php

use Logic\Admin\BaseController;
use Model\Service\ServiceSet;

/*
 * 客服统计
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 客服统计';
    const DESCRIPTION = '客服统计';
    
    const QUERY = [
        'page' => '页码',
        'page_size' => '每页大小',
        'name' => 'string()  #名字',
        'status' => 'enum[enable,disable]() #状态 enable:启用,disable:停用 '
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'id' => 'int #id',
            //'name' => 'string  #名字',
        ]
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run()
    {
        $appId = $this->ci->get('settings')['pusherio']['app_id'];
        $appSecret = $this->ci->get('settings')['pusherio']['app_secret'];
        $hashids = new \Hashids\Hashids($appId . $appSecret, 8, 'abcdefghijklmnopqrstuvwxyz');
        var_dump($appId);
        var_dump($appSecret);
        $res = $hashids->encode(1);
        var_dump($res);
        //讲字符串转成十进制
        $res1 = base_convert($res,36,10);
        var_dump((int)$res1);
        //$res1 = 1283540633123;
        $res2 = base_convert($res1,10,36);
        var_dump($res2);
        $id = $hashids->decode($res2);
        var_dump($id);die;
    }




    /**
     * 62进制数转换成十进制数
     *
     * @param string $num
     * @return string
     */
    function from62_to10($num)
    {
        $from = 62;
        $num = strval($num);
        $dict = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len = strlen($num);
        $dec = 0;
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($dict, $num[$i]);
            $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
        }
        return $dec;
    }



    /**
     * 十进制数转换成62进制
     *
     * @param integer $num
     * @return string
     */
    function from10_to62($num)
    {
        $to = 62;
        $dict = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $ret = '';
        do {
            $ret = $dict[bcmod($num, $to)] . $ret;
            $num = bcdiv($num, $to);
        } while ($num > 0);
        return $ret;
    }
    };