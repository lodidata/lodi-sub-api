<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE = '测试接口';
    const DESCRIPTION = '';

    const QUERY = [];

    const PARAMS = [];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function lately_day($days){

        $daysarr=[];
        for ($i=0;$i<$days;$i++){

            $t = time()+3600*8;//这里和标准时间相差8小时需要补足
            $tget = $t-3600*24*$i;//比如5天前的时间
            $daysarr[$i]=date("Y-m-d",$tget);
        }
        sort($daysarr);
        return $daysarr;
    }

    public function run($days='')
    {

        echo "本地mac ：".Utils\Client::ClientId();
        echo "<pre/>";
        $header = ['alg' => "HS256", 'typ' => "JWT"];
        $header = base64_encode(json_encode($header));
        echo "本地生成header: ".$header;
        echo "<pre/>";
        $jwtconfig = $this->ci->get('settings')['jsonwebtoken'];
        $userData = [
            "uid"=> -116626,
            "role"=> -116626,
            "nick"=>"lebo10",
            "type"=>1,
            "ip"=>"183.15.179.254",
//            "mac"=> "64c7efa230f463888dd965036327a5ae213b72d9"
            "mac"=> "faaa60de6a1a291f0e2e081e76308119911115ec"

//            'ip'=>"183.15.179.254",
//            'mac'=>"64c7efa230f463888dd965036327a5ae213b72d9",
//            'nick'=>"lebo10",
//            'role'=>-116626,
//            'type'=>1,
//            'uid'=>-116626
        ];
        // 通过$uid获取商户的一些基本信息，放入jwt
        // 2、生成payload
        $payload = base64_encode(json_encode(array_merge(["iss" => "lxz", "exp" => time() + $jwtconfig['expire']], $userData)));
//        $payload = base64_encode(json_encode(array_merge(["iss" => "lxz", "exp" => time() + $ext], $userData)));
        echo "payload : ".$payload;
        echo "<pre/>";
//        echo "<pre/>";
        // 3、生成Signature
        $signature = hash_hmac('sha256', $header.'.'.$payload, $jwtconfig['public_key'], false);
        echo "signature : ".$signature;
        echo "<pre/>";
//        $token = $header.'.'.$payload.'.'.$signature;
//        echo "token : ".$token;
//        echo "<pre/>";

        (new \Logic\Admin\AdminToken($this->ci))->verifyToken();
        exit;


    }


    protected function getLotteryRecords(){

        //批量设置在线状态
        $redis = $this->redis;
        $uids = range(1, 5000);

        foreach ($uids as $uid){
            $redis->setBit('online', $uid, $uid % 2);
        }

        //一个一个获取状态
        $uids = range(1,5000);
        $startTime = microtime(true);
        foreach($uids as $uid) {
            echo $redis->getBit('online', $uid) . PHP_EOL;
        }
        $endTime = microtime(true);
        //在我的电脑上，获取50W个用户的状态需要25秒 
        echo "total:" . ($endTime - $startTime) . "s";

    }




};
