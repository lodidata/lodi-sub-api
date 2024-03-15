<?php

namespace Logic\GameApi;

use Logic\Define\Cache3thGameKey;
use Logic\Funds\DealLog;
use Logic\User\Quota;
use Model\Funds;
use Model\FundsChild;
use Model\FundsDealLog;
use Model\GameMenu;
use Model\OrdersReport;
use Model\User;

/**
 *  获取对应的第三方游戏实例
 * @author lwx
 *
 */
class GameApi {

    protected static $api;
    static $LOG =  LOG_PATH.'/game/';

    /*
     * 第三方订单回调过来需要执行的操作  只传递结算的订单
     * 传递的参数
     * [
     *     user_id => '用户ID'
     *     game => '第三方游戏大类'
     *     game_type => '第三方游戏TYPE'
     *     order_number => '第三方游戏注单  -- 唯一性'
     *     game_id => '第三方游戏GameID'
     *     server_id => '第三方游戏ServerID'
     *     account => '第三方Accounts'
     *     bet => '有效下注'
     *     profit => '输赢'
     *     date => '游戏结算时间'
     * ]
     */
    public static $synchronousOrderCallback = "synchronousOrderCallback";  //订单拉取时每条数据消息通知做相应的逻辑处理
    public static $synchronousOrderCallback110 = "synchronousOrderCallback_110";
    public static $synchronousOrderCallback111 = "synchronousOrderCallback_111";
    public static $synchronousOrderCallback112 = "synchronousOrderCallback_112";
    public static $synchronousOrderCallback113 = "synchronousOrderCallback_113";
    public static function redisGameInfo(){
        global $app;
        $ci = $app->getContainer();
        $ci->redis->del(Cache3thGameKey::$perfix['gameList']);
        $data = \DB::table('game_menu')->where('switch', '=', 'enabled')->where('pid', '!=', 0)->get()->toArray();
        $re = [];
        foreach ($data as $val) {
            $val->type = strtoupper($val->type);
            $re[$val->type] = "\Logic\GameApi\Game\\" . $val->type;
        }
        $ci->redis->hMset(Cache3thGameKey::$perfix['gameList'], $re);
        $ci->redis->expire(Cache3thGameKey::$perfix['gameList'], 5*60);
    }
    /**
     * 获取api对象
     *
     * @param string $gameType
     * @param int $uid
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getApi(string $gameType, int $uid = 0) {
        global $app;
        $ci = $app->getContainer();
        try {
            $gameType = strtoupper($gameType);
            self::$api = $ci->redis->hGet(Cache3thGameKey::$perfix['gameList'], $gameType);
            if (!self::$api || !class_exists(self::$api)) {
                self::redisGameInfo();
                self::$api = $ci->redis->hGet(Cache3thGameKey::$perfix['gameList'], $gameType);
            }
            if (!isset(self::$api) && empty(self::$api)) {
                throw new \Exception($ci->lang->text("There is no defined game type") . $gameType);
            }
            if (!class_exists(self::$api)) {
                throw new \Exception($ci->lang->text("There is no defined game type") . $gameType);
            }
            $obj = new self::$api($ci);
            $obj->initGameType($gameType, $uid);
            return $obj;
        } catch (\Exception $e) {
            $ci->logger->error("【getApi】" . $e->getMessage());
            throw new \Exception($ci->lang->text("Contact technical personnel, no third party of {%s} or wrong code of {%s}", [$gameType,$gameType]));
        }
    }

    /**
     * 生成用户游戏账号  (所有第三方平台同用游戏账号)
     * 账号名规则
     * [平台ID]gametes[十六进制(用户ID + 999)]
     * 例如 平台 22, 用户ID 628 的 sbbench 游戏平台的游戏账号为 [22]gametes[十六进制(628 + 999)], 最终账号如下
     * 22gametes65b
     */
    public static function createGameUname(int $tid, int $uid) {
        return 'game' . $tid . 'tes' . dechex($uid + 999);
    }

    /**
     * 生成用户游戏密码
     *
     * @param int $uid
     *
     * @return string
     */
    public static function createGameUpasswd(int $uid) {
        return md5(md5($uid + 999));
    }

    /**
     * 生成流水号
     * @param int $rand
     * @param int $length
     * @return string
     */
    public static function generateOrderNumber(int $rand = 99999, int $length = 5) {
        return date('YmdHis') . str_pad(mt_rand(1, $rand), $length, '0', STR_PAD_LEFT);
        /*global $app;
        $ci = $app->getContainer();
        $tid = $ci->get('settings')['app']['tid'];
        $key = date('ymdHis');
        $value = $ci->redis->incr($key);
        $ci->redis->expire($key, 10);
        return $key.$tid . str_pad($value, $length, '0', STR_PAD_LEFT);*/
    }

    /**
     * 转出所有第三方余额入口
     *
     * @param $uid
     * @param $balance
     * @param $tradeNo
     * @param $changeType
     *
     * @return mixed
     * @throws \Exception
     */
    public static function rollOutAllThird(int $uid) {
        global $app;
        $ci = $app->getContainer();
        try {
            $gamesLogin = $ci->redis->hGetAll(Cache3thGameKey::$perfix['gameList']);
            if(!$gamesLogin) {
                self::redisGameInfo();
                $gamesLogin = $ci->redis->hGetAll(Cache3thGameKey::$perfix['gameList']);
            }
            //判断当前子游戏钱包是否有钱与是否创建账号
            //$wid = User::where('id', $uid)->value('wallet_id');
            $wid = (new Common($ci))->getUserInfo($uid)['wallet_id'];
            if (!$wid) {
                return;
            }
            foreach ($gamesLogin as $type => $objGame) {
                if (!class_exists($objGame)) {
                    continue;
                }
                $cur = FundsChild::where('pid', $wid)->where('game_type', $type)->where('create_account', '1')->first();
                if (!$cur) {
                    continue;
                }
                if ($cur['balance'] <= 0) {
                    continue;
                }
                // 创建对象
                $obj = new $objGame($ci);
                $obj->initGameType($type, $uid);
                // 下分
                $obj->rollOutThird();
            }
        } catch (\Exception $e) {
            $ci->logger->error("【rollOutAllThird】" . $e->getMessage());
        }
    }

    /**
     * 第三方同步订单
     *
     * @param $uid
     * @param $balance
     * @param $tradeNo
     * @param $changeType
     *
     * @return mixed
     * @throws \Exception
     */
    public static function synchronousData() {
        global $app;
        $ci = $app->getContainer();
        try {
            $gamesLogin = $ci->redis->hGetAll(Cache3thGameKey::$perfix['gameList']);
            if(!$gamesLogin) {
                self::redisGameInfo();
                $gamesLogin = $ci->redis->hGetAll(Cache3thGameKey::$perfix['gameList']);
            }
            foreach ($gamesLogin as $type => $objGame) {
                if (!class_exists($objGame)) {
                    continue;
                }
                $obj = new $objGame($ci);
                $obj->initGameType($type);
                $obj->synchronousData();
            }
        } catch (\Exception $e) {
            $ci->logger->error("【synchronousData】" . $e->getMessage());
        }
    }

    /**
     * 第三方同步订单
     *
     * @param $uid
     * @param $balance
     * @param $tradeNo
     * @param $changeType
     *
     * @return mixed
     * @throws \Exception
     */
    public static function synchronousData2($games) {
        global $app;
        $ci = $app->getContainer();
        try {
            foreach ($games as $val) {
                $val = (array)$val;
                $objGame = "\Logic\GameApi\Game\\" . strtoupper($val['type']);
                $ci->logger->info(" 【拉单】 ". $val['type'] . ' start ' . $objGame);
                if (!class_exists($objGame)) {
                    continue;
                }
                $obj = new $objGame($ci);
                $obj->initGameType($val['type']);
                $obj->synchronousData();
            }
        } catch (\Exception $e) {
            $ci->logger->error("【synchronousData2】" . $e->getMessage());
        }
    }

    /**
     * 第三方游戏订单与orders表同步
     */
    public static function order_repair() {
        global $app;
        $ci = $app->getContainer();
        try {
            $today = date('Ymd');
            $key = \Logic\Define\CacheKey::$perfix['egame'].'-repair-'.$today;
            $today_is_run = $ci->redis->get($key);
            if(!$today_is_run){//今天未执行
                $game_menu = \DB::table('game_menu')->where('status', '=', 'enabled')
                    ->where('pid', '!=', 0)
                    ->where('alias', '!=', 'ZYCP')->get()->toArray();
                foreach ($game_menu as $val){
                    $game_class = "\Logic\GameApi\Order\\" . strtoupper($val->type);
                    if (!class_exists($game_class)) {
                        continue;
                    }
                    $game_obj = new $game_class($ci);
                    if(!method_exists($game_obj, 'OrderRepair')){
                        continue;
                    }
                    $game_obj->OrderRepair();
                }
                $ci->redis->setex($key, 24*3600, 1);//今天已执行
            }
            $ci->logger->debug("【游戏补单】" . $key);
        } catch (\Exception $e) {
            $ci->logger->error("【游戏补单错误】".$e->getMessage());
            print_r($e->getMessage());
        }
    }

    public static function addRequestLog(
        string $url,
        string $gameType,
        array $params,
        string $resp,
        string $remark = '',
        bool $db = false
    ) {
        $data = [
            'url' => $url,
            'game_type' => $gameType,
            'request' => $params,
            'response' => $resp,
            'remark' => $remark,
        ];
        if($db) {
            $data['json'] = json_encode($data['json'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            \DB::table('game_request_log')->insert($data);
        }
        self::addElkLog($data, $gameType);
    }

    public static function addElkLog($data, $path = 'game', $file = 'request'){
        if (!is_dir(self::$LOG.$path) && !mkdir(self::$LOG.$path, 0777, true)) {
            $path = '';
        }else{
            $path.='/';
        }
        $file = self::$LOG.$path.$file.'-'.date('Y-m-d').'.log';
        $stream = @fopen($file, "aw+");
        if(isset($data['response']) && self::is_json($data['response'])){
            $data['response'] = json_decode($data['response'], true);
        }
        $date['logTime'] = date('Y-m-d H:i:s');
        $str = urldecode(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).PHP_EOL;
        @fwrite($stream, $str);
        @fclose($stream);
    }

    /*
     * 转账日志
     * $type  转账类型   1  主转第三方
     */
    public static function gameTransferLog(
        int $uid,
        string $gameType,
        int $money,
        int $type,
        string $tradeNo,
        bool $res,
        int $opater_uid = 0,
        string $remark = '',
        int $dealLog = 1
    ) {
        global $app;
        $ci = $app->getContainer();
       // $user = User::where('id', $uid)->first();
        $user = (new Common($ci))->getUserInfo($uid);
        $wallet_type = GameMenu::where('type',$gameType)->value('alias');
        $fundChild = FundsChild::where('pid', $user['wallet_id'])->where('game_type', $wallet_type)->first();
        if ($type == 1) {  //主转子
            $dealType = FundsDealLog::TYPE_MTOC;
            $out = [
                'id' => $user['wallet_id'],
                'name' => $ci->lang->text('Master Wallet'),
                'type' => $ci->lang->text('Master Wallet'),
            ];
            $in = [
                'id' => $fundChild['id'],
                'name' => $fundChild['name'] . $ci->lang->text('Wallet'),
                'type' => $fundChild['game_type'],
            ];
        } else {
            $dealType = FundsDealLog::TYPE_CTOM;
            $in = [
                'id' => $user['wallet_id'],
                'name' => $ci->lang->text('Master Wallet'),
                'type' => $ci->lang->text('Master Wallet'),
            ];
            $out = [
                'id' => $fundChild['id'],
                'name' => $fundChild['name'] . $ci->lang->text('Wallet'),
                'type' => $fundChild['game_type'],
            ];
        }
        $remark = str_replace($gameType, $fundChild['name'], $remark);
        $data = [
            'user_id' => $uid,
            'username' => $user['name'],
            'out_id' => $out['id'],
            'out_name' => $out['name'],
            'out_type' => $out['type'],
            'in_id' => $in['id'],
            'in_name' => $in['name'],
            'in_type' => $in['type'],
            'type' => $type,
            'amount' => $money,
            'no' => $tradeNo,
            'opater_uid' => $opater_uid,
            'status' => $res ? 'success' : 'fail',
            'memo' => $remark,
        ];
        \Model\FundsTransferLog::create($data);
        //操作成功插入流水
        if ($res && $dealLog) {
            $balance = Funds::where('id', $user['wallet_id'])->value('balance');
            DealLog::addDealLog($uid, $user['name'], $balance, $tradeNo, $money, $dealType, $remark);
        }
    }

    public static function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 转入第三方失败  退钱
     */
    public static function gameMoneyErrorRefund(){
        $result = \DB::table('game_money_error')
                    ->where('status',0)
                    ->get(['id','user_id','wid','balance','game_type','tradeNo','transfer_type'])
                    ->toArray();

        if($result){
            foreach ($result as $val){
                $val = (array)$val;
                $objGame = self::getApi(strtoupper($val['game_type']), $val['user_id']);
                if (!is_object($objGame)) {
                    continue;
                }
                $objGame->checkMoney($val);
                unset($objGame);
            }
        }
    }

    public static function handleOrdersReport(){
        $last_id = \DB::table('orders_report_exec')->where('id',1)->value('last_order_id');
        $max_id =  \DB::table('order_game_user_middle')->max('id');
        $add   = 15000;
        //每次最多计算1.5万条
        $start = $last_id??0;
        $end   = $last_id + $add;

        if($last_id >= $max_id){
            echo '没有可以同步的数据';
            return;
        }

        //不能大于最大id
        $end > $max_id && $end = $max_id;
        $result = \DB::table('orders_report_exec')->where('id',1)->where('last_order_id',$last_id)->update(['last_order_id' => $end]);
        if(!$result){
            echo '修改orders_report_exec last_order_id出错';
            return;
        }

        $sql = "SELECT sum(bet) bet,sum(dml) dml,sum(send_money) send_money,count(1) num, user_id,game, date from order_game_user_middle where id > {$start} and id <= {$end} group by user_id,game,date";

        $res = \Db::select($sql);

        if(!$res){
            echo '暂时没有数据';
            return;
        }
        //合并数据
        self::updateOrdersReport($res);

    }

    public static function getDefaultBetAmountList($gameList){
        $bet_amount_list = [];
        foreach ($gameList as $v){
            $bet_amount_list[$v] = 0;
        }

        return $bet_amount_list;
    }

    public  static function updateOrdersReport($order){

        if(empty($order)) return ;
        global $app;
        $ci = $app->getContainer();
        $game_list = GameMenu::getGameTypeList();
        $default_bet_amount_list = self::getDefaultBetAmountList($game_list);

        $orders_num = count($order);
        for($i = 0; $i < $orders_num; $i++){
            $value = (array)$order[$i];

            try{
                $game_name = $value['game'];
                unset($value['game']);

                if(!in_array($game_name,$game_list)){
                    echo "{$game_name} 类型不存在";
                    continue;
                }

                $where = [
                    'date'    => $value['date'],
                    'user_id' => $value['user_id'],
                ];

                $user_data = OrdersReport::where($where)->first(['bet_amount_list','lose_earn_list']);
                $user_data && $user_data = $user_data->toArray();
                //1: 数据已存在,0: 数据不存在
                $exists = 0;
                if($user_data){
                    $exists = 1;
                    $bet_amount_list = json_decode($user_data['bet_amount_list'],true);
                    $lose_earn_list  = json_decode($user_data['lose_earn_list'],true);
                }else{
                    $bet_amount_list = $default_bet_amount_list;
                    $lose_earn_list  = $bet_amount_list;
                }

                $value['bet']                = bcdiv($value['bet'],100,2);
                $value['dml']                = bcdiv($value['dml'],100,2);
                $value['send_money']         = bcdiv($value['send_money'],100,2);
                $lose_earn                   = bcsub($value['bet'],$value['send_money'],2);
                $bet_amount_list[$game_name] = bcadd($bet_amount_list[$game_name]??0, $value['bet'],2);
                $value['bet_amount_list']    = json_encode($bet_amount_list);
                $lose_earn_list[$game_name]  = bcadd($lose_earn_list[$game_name]??0, $lose_earn,2);
                $value['lose_earn_list']     = json_encode($lose_earn_list);

                if($exists){
                    //更新
                    $update_data = [
                        'num'             => \DB::raw("num + {$value['num']}"),
                        'bet'             => \DB::raw("bet + {$value['bet']}"),
                        'dml'             => \DB::raw("dml + {$value['dml']}"),
                        'send_money'      => \DB::raw("send_money + {$value['send_money']}"),
                        'bet_amount_list' => $value['bet_amount_list'],
                        'lose_earn_list'  => $value['lose_earn_list'],
                    ];
                    OrdersReport::where($where)->update($update_data);
                }else{
                    //插入
                    OrdersReport::insert($value);
                }

            }catch (\Exception $e){
                $ci->logger->error('updateOrdersReport ' . $e->getMessage()." data: ".json_encode($value));
                print_r($e->getMessage());
            }
            unset($order[$i]);
        }

    }

    /**
     * 重跑orders_report某一天的数据
     * @param $date
     */
    public static function insertOrdersReportByDay($date){
        if(empty($date)) die('日期不能为空');

        \Db::table('orders_report')->where('date',$date)->delete();
        $sql = "SELECT sum(bet) bet,sum(dml) dml,sum(send_money) send_money,count(1) num, user_id,game, date from order_game_user_middle where date='{$date}' group by user_id,game,date";

        $res = \Db::select($sql);

        if(!$res){
            echo '暂时没有数据';
            return;
        }
        //合并数据
        self::updateOrdersReport($res);

    }

}
