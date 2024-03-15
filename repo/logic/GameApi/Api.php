<?php

namespace Logic\GameApi;

use Logic\Define\Cache3thGameKey;
use Model\Funds;
use Model\FundsChild;
use Model\Game3th;
use Model\GameMenu;
use Model\User;
use Model\SuperGameOrderExec;
use Utils\Curl;

/**
 * 第三方游戏接口 对外的服务接口
 * @author lwx
 *
 */
abstract class Api extends \Logic\Logic {

    protected $origin;  //当前平台 pc,h5,ios,android;
    protected $config;  //当前第 三方游戏type
    protected $game_type;  //当前第 三方游戏type
    protected $game_alias;  //当前第 三方游戏type
    protected $uid;  //这边用户id
    protected $wid;  //这边用户钱包id
    protected $game_config_apis;
    protected $jumpCurl;

    public function initConfigMsg() {
        if(!$this->game_alias){
            $this->game_alias = GameMenu::where('type', $this->game_type)->value('alias');
        }
        $this->config = json_decode($this->redis->hget(Cache3thGameKey::$perfix['gameJumpUrl'], $this->game_alias), true);

        if (is_null($this->config)) {
            $api_verify_token = $this->ci->get('settings')['app']['api_verify_token'];
            $token = $api_verify_token . date("Ymd");
            $url = array_random($this->game_config_apis) . "/config?type={$this->game_alias}";
            $req = \Requests::get($url, ["token" => $token], ["timeout" => 10]);
            if ($req->status_code != 200) {
                $this->logger->error('游戏获取配置错误'. $this->game_type, ["token" => $token]);
                return ['status' => false, 'message' => $this->game_type.$this->ci->lang->text("api configuration error, please try again later or contact technical personnel")];
            }

            $tmp = json_decode($req->body, true);
            $this->config = json_decode(base64_decode($tmp['data']), true);
            if ($this->config && is_array($this->config)) {
                $this->redis->hset(Cache3thGameKey::$perfix['gameJumpUrl'], $this->game_alias, json_encode($this->config));
            }
        }
    }

    /*
     * 拼接用户跳转链接
     * return   mix    正确直接跳转地址， 无 直接false
     */
    abstract function getJumpUrl(array $data = []);

    public function getAvailableBalance(bool $rollIn = false) {

    }

    /**
     * 获取超管订单
     * @param string $table 表名
     * @param string $game_type 游戏厂商类型
     * @param int $lastId 最大ID，主要用户于cq9,jdb
     * @return bool
     */
    public function getSupperOrder($game_type, $lastId = 0)
    {
        if(is_null($game_type)){
            return false;
        }

        if($lastId == 0){
            $res    = $this->getSuperGameOrderExec($game_type);
            $lastId = $res['last_id'];
        }
        //该游戏停止拉单
        if($res['stop']) false;

        $tid = $this->ci->get('settings')['app']['tid'];
        $api_verify_token = $this->ci->get('settings')['app']['api_verify_token'];
        $token = $api_verify_token . date("Ymd");
        $url = array_random($this->game_config_apis) . "/order?type={$game_type}&tid={$tid}&max_id=$lastId";
        $req = \Requests::get($url, ["token" => $token], ["timeout" => 5]);
        $data = json_decode($req->body, true);
        GameApi::addElkLog(['url' => $url, 'game_type' => $game_type, 'return' => $data], $game_type);

        if (!(isset($data['data']) && isset($data['attributes']) && isset($data['attributes']['total']) && isset($data['attributes']['lastId'])) || $data['attributes']['lastId'] == 0 ){
            return false;
        }

        //更新拉取进度
        if($data['attributes']['lastId'] >= $lastId){
            $this->updateSuperGameOrderExec($game_type, $data['attributes']['lastId']);
        }

        if($data['attributes']['total'] > 0){
            return $data['data'];
        }else{
            return false;
        }
    }

    /**
     * 更新super_game_order_exec
     * @param $gameType
     * @param $lastId
     * @return mixed
     */
    public function updateSuperGameOrderExec($gameType, $lastId){
        return SuperGameOrderExec::where('game_type', $gameType)->update(['last_id' => $lastId]);
    }
    /**
     * 获取超管更新到子站的进度
     * @param $gameType
     * @return mixed
     */
    public function getSuperGameOrderExec($gameType){
        $order_exec = SuperGameOrderExec::select('last_id','stop')->where('game_type', $gameType)->first();
        if(!$order_exec){
            $data = [
                'game_type' => $gameType,
                'last_id'   => 0,
                'stop'      => 0
            ];
            SuperGameOrderExec::insert($data);
            $order_exec = SuperGameOrderExec::select('last_id','stop')->where('game_type', $gameType)->first();
        }

        return $order_exec->toArray();
    }

    public function getGameAccount() {
        $create_account = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('create_account');

        $account = (array)\DB::table('game_user_account')->where('user_id', $this->uid)->first();
        if ($create_account === NULL) {
            //$name = \DB::table('game_menu')->where('type', $this->game_alias)->value('name');
            $fundsChildData = ['pid' => $this->wid, 'game_type' => $this->game_alias, 'name' => $this->game_alias];
            \Model\FundsChild::create($fundsChildData);
        }
        if ($create_account) {
            $account['user_password'] = md5($account['user_password'] . $this->game_alias);
        } else {
            if ($account) {
                $account['user_password'] = md5($account['user_password'] . $this->game_alias);
                $res = $this->childCreateAccount($account['user_account'], $account['user_password']);
            } else {
                $tid = $this->ci->get('settings')['app']['tid'];
                $account['user_account'] = GameApi::createGameUname($tid, $this->uid);
                $account['user_password'] = GameApi::createGameUpasswd($this->uid);
                $res = $this->childCreateAccount($account['user_account'], md5($account['user_password'] . $this->game_alias));
                $insert = true;
            }
            if ($res) {
                FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->update(['create_account' => '1']);
            } else {
                return false;
            }
            if ($res && isset($insert)) {
                $name = User::where('id', $this->uid)->value('name');
                \DB::table('game_user_account')->insert([
                    'user_id' => $this->uid,
                    'user_name' => $name,
                    'user_account' => $account['user_account'],
                    'user_password' => $account['user_password'],
                    'game_type' => $this->game_alias,
                ]);
            }
        }
        return ['account' => $account['user_account'], 'password' => $account['user_password']];
    }

    //retur  bool  true  or  false
    abstract function childCreateAccount(string $account, string $password);

    /*
     * 用户ID
     */
    public function initUserData(int $uid) {
        $this->uid = $uid;
        //$this->wid = User::where('id', $uid)->value('wallet_id');
        $this->wid = (new Common($this->ci))->getUserInfo($uid)['wallet_id'];
        $this->game_alias = GameMenu::where('type', $this->game_type)->value('alias');
        if (!$this->uid || !$this->wid) {
            throw new \Exception($this->ci->lang->text("The user is illegal - no information about the user's wallet"));
        }
    }

    public function initGameType(string $game_type, int $uid = 0) {
        $this->origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
        $this->game_type = $game_type;
        $uid && $this->initUserData($uid);
        $this->game_config_apis = $this->ci->get('settings')['website']['api_game_url'];
        $this->jumpCurl = rtrim(array_random($this->game_config_apis),'/');
        $this->jumpCurl .= '/curl.php';
        $this->initConfigMsg();
        $uid && $this->getGameAccount();
    }

    /**
     * 获取第三方钱包信息 (分为单位)
     * @return array[可下分金额，总金额]
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getThirdBalance() {
        $re = $this->getThirdWalletBalance();
        return $re;
    }

    /**
     * 获取第三方钱包信息
     * @return array[可下分金额，总金额]
     * @throws \Interop\Container\Exception\ContainerException
     */
    abstract function getThirdWalletBalance();

    /**
     * 同步数据  一般同步游戏订单数据 ，若有其它数据一并在子类实现  ，定时器会定时执行调用数据同步
     *
     * @param $gameType
     *
     * @return mixed
     * @throws \Exception
     */
    public function synchronousData() {
        return $this->synchronousChildData();
    }

    abstract function synchronousChildData();

    /**
     * 获取用户余额
     *
     * @param $gameType
     *
     * @return mixed
     * @throws \Exception
     */
    public function quitGame() {
        $balance = $this->quitChildGame();
        return $balance;
    }

    abstract function quitChildGame();

    abstract function checkMoney();

    abstract function getJackpot();

    public function generateOrderNumber(){
        return $this->generateChildOrderNumber();
    }

    public function updateThirdBalance($thirdBalance){
        isset($thirdBalance) && FundsChild::where('pid',$this->wid)->where('game_type',$this->game_alias)->update(['balance' => $thirdBalance]);
    }

    /**
     * 转出第三方余额 子转主
     * @param int $balance
     * @param string $tradeNo
     * @return array ['status'=> bool,'msg'=>'ok']
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function rollOutThird(int $balance = 0, string $tradeNo = null, int $is_gameing = 0) {
        $this->logger->info('api::rollOutThird uid:'.$this->uid.' start');
        //按序转入转出加锁  记得处理完之后要删除锁，以防产生死锁
        $lockRequest = \Logic\Define\Cache3thGameKey::$perfix['gameBalanceLockUser'] . $this->uid;
        $lock = $this->redis->setnx($lockRequest,1);
        $this->redis->expire($lockRequest,60);
        if(!$lock) {
            return ['status' => false, 'money' => 0, 'msg' => $this->ci->lang->text('in fund consolidation, please wait')];
        }

        // 订单不存在生成订单(SA特殊处理)
        if($tradeNo === null && in_array($this->game_type, ['SA','ALLBET'])){
            $tradeNo = $this->generateOrderNumber();
        }else{
            $tradeNo = $tradeNo === null ? GameApi::generateOrderNumber() : $tradeNo;
        }

        if ($balance == 0) {
            $this->logger->info('api::rollOutThird  uid:'.$this->uid.' 获取第三方余额');
            list($freeMoney, $thirdBalance) = $this->getThirdBalance();
            $this->logger->info('api::rollOutThird  uid:'.$this->uid.' 获取第三方余额', ['freeMoney' => $freeMoney, 'thirdBalance' => $thirdBalance]);
            //第三方余额为0无须处理
            if($freeMoney == 0 && $thirdBalance == 0){
                $this->redis->del($lockRequest); //删除锁
                return ['status' => true, 'thirdBalance' => $thirdBalance, 'money' => $balance, 'msg' => $this->ci->lang->text('third party transfer out succeeded')];
            }
            // 下分金额不足
            if ($freeMoney <= 0) {
                $this->redis->del($lockRequest); //删除锁
                $this->updateThirdBalance($thirdBalance);
                if($is_gameing){
                    return ['status' => true, 'thirdBalance' => $thirdBalance, 'money' => 0, 'msg' => $this->ci->lang->text('third party api error')];
                }
                return ['status' => false, 'thirdBalance' => $thirdBalance, 'money' => 0, 'msg' => $this->ci->lang->text('divisible amount is 0, please refresh balance')];
            } else {
                $balance = $freeMoney;
            }
        }
        //每次转出记录一下最后一次的转出金额
        $redisData = [ 'user_id' => $this->uid, 'wid' => $this->wid, 'balance' => $balance, 'game_type' => $this->game_alias, 'tradeNo' => $tradeNo, 'msg' => '转出成功，更新钱包失败', 'status' => 0, 'transfer_type' => 'out'];
        list($re,$thirdBalance) = $this->rollOutChildThird($balance, $tradeNo);
        $this->logger->info('api::rollOutThird  uid:'.$this->uid.' 转出第三余额', ['balance' => $balance, 'tradeNo' => $tradeNo, 're' => $re, 'thirdBalance' => $thirdBalance]);

        if ($re === true) {
            // 转出成功  更新第三方金额
            try {
                $this->db->getConnection()->beginTransaction();
                    \Model\Funds::where('id', $this->wid)->lockForUpdate();
                    //更新可用余额
                    $res = \Model\Funds::where('id', $this->wid)
                        ->update(['balance' => \DB::raw("balance + {$thirdBalance}")]);
                    if(!$res){
                        \DB::table('game_money_error')->insert($redisData);
                        GameApi::addElkLog($redisData,'money', 'error');
                        $this->logger->error('api::rollOutThird  uid:'.$this->uid.' 更新主钱包失败', ['wid:' => $this->wid, 'thirdBalance' => $thirdBalance]);
                        throw new \Exception('update balance error');
                    }
                    $this->updateThirdBalance(0);
                    $this->db->getConnection()->commit();
            } catch (\Exception $e) {
                $this->redis->del($lockRequest); //删除锁
                $this->db->getConnection()->rollback();
                return ['status' => false, 'thirdBalance' => $thirdBalance, 'money' => 0, 'msg' => $this->ci->lang->text('update balance error')];
            }

            GameApi::gameTransferLog($this->uid, $this->game_type, $balance, 0, $tradeNo, true, 0, $this->ci->lang->text('success to transfer out %s', [$this->game_type]));
            $this->redis->del($lockRequest); //删除锁
            return ['status' => true, 'thirdBalance' => $thirdBalance, 'money' => $balance, 'msg' => $this->ci->lang->text("Third party transfer out succeeded, refresh amount failed, please refresh amount")];
        }else{
            //第三方转出失败
            \DB::table('game_money_error')->insert($redisData);
            GameApi::addElkLog($redisData, 'money', 'error');
        }
        $this->logger->info('api::rollOutThird  uid:'.$this->uid.' 转出失败', ['balance' => $balance, 'tradeNo' => $tradeNo, 're' => $re, 'thirdBalance' => $thirdBalance]);
        // 转出失败
        GameApi::gameTransferLog($this->uid, $this->game_type, $balance, 0, $tradeNo, false, 0, $this->ci->lang->text('failed to transfer out %s', [$this->game_type]));
        $this->redis->del($lockRequest); //删除锁
        return ['status' => false, 'thirdBalance' => $thirdBalance, 'money' => 0, 'msg' => $this->ci->lang->text('third party transfer out failed, try again later'), 'res' => $re];
    }

    public function issueOutBalance() {
        $this->redis->del(\Logic\Define\Cache3thGameKey::$perfix['gameBalanceRollOut'] . $this->uid);
        return true;
    }

    /**
     * 退出第三方
     * @param int $balance
     * @param string $tradeNo
     * @return array(是否成功,转出后第三方剩余金额)
     */
    abstract function rollOutChildThird(int $balance, string $tradeNo);

    /**
     * 进入第三方前，准备工作
     * @param int $balance
     * @param string $tradeNo
     * @param bool $callChild 是否执行抓如第三方余额操作
     * @return mixed
     */
    public function rollInThird(int $balance = 0, string $tradeNo = null, bool $callChild = true) {
        //按序转入转出加锁  记得处理完之后要删除锁，以防产生死锁
        $lockRequest = \Logic\Define\Cache3thGameKey::$perfix['gameBalanceLockUser'] . $this->uid;
        $lock = $this->redis->setnx($lockRequest,1);
        $this->redis->expire($lockRequest,60);
        if(!$lock) {
            return ['status' => false, 'money' => 0, 'msg' => $this->ci->lang->text('in fund consolidation, please wait')];
        }
        $this->logger->info('api::rollInThird  uid:'.$this->uid.' start');
        // 订单不存在生成订单(SA特殊处理)
        if($tradeNo === null && in_array($this->game_type, ['SA','ALLBET'])){
            $tradeNo = $this->generateOrderNumber();
        }else{
            $tradeNo = $tradeNo === null ? GameApi::generateOrderNumber() : $tradeNo;
        }

        $this->logger->info('api::rollInThird  uid:'.$this->uid.' tradeNo : '. $tradeNo);

        try {
            $this->db->getConnection()->beginTransaction();
            if (!$this->db->getConnection()->transactionLevel()) {
                GameApi::gameTransferLog($this->uid, $this->game_type, $balance, 1, $tradeNo, false, 0, $this->ci->lang->text('Transfer to %s failed', [$this->game_type]));
                $this->redis->del($lockRequest); //删除锁
                return ['status' => false, 'money' => $balance, 'msg' => $this->ci->lang->text('third party transfer out failed, try again later')];
            }
            $balance = $balance ? $balance : Funds::where('id', $this->wid)->lockForUpdate()->value('balance');
            $this->logger->info('api::rollInThird  uid:'.$this->uid.' $balance : ' . $balance);
            if ($balance <= 0) {
                $this->db->getConnection()->rollback();
                $this->redis->del($lockRequest); //删除锁
                return ['status' => true, 'money' => 0, 'msg' => 'success'];
                //return ['status' => false, 'money' => 0, 'msg' => 'Insufficient balance'];
            }

            //更新可用余额
            Funds::where('id', $this->wid)->where(\DB::raw("balance - {$balance}"), ">=", 0)->update(['balance' => \DB::raw("balance - {$balance}")]);

            FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->update(['balance' => \DB::raw("balance + {$balance}")]);
            $this->logger->info('api::rollInThird  uid:'.$this->uid.' 更新可用余额 : ' . $balance);
            //GameApi::gameTransferLog($this->uid, $this->game_type, $balance, 1, $tradeNo, true, 0,  $this->ci->lang->text('Before transferring to %s, the balance is reduced successfully', [$this->game_type]) , 0);
            // 提交事务
            $this->db->getConnection()->commit();
            if ($callChild) {
                $this->logger->info('api::rollInThird  uid:'.$this->uid.' rollInChildThird start ');
                // 转入第三方余额
                $re = $this->rollInChildThird($balance, $tradeNo);
                $redisData = ['user_id' => $this->uid, 'wid' => $this->wid, 'balance' => $balance, 'game_type' => $this->game_alias, 'tradeNo' => $tradeNo, 'msg' => '转入第三方失败', 'status' => 0, 'transfer_type' => 'in'];
                $this->logger->info('api::rollInThird  uid:'.$this->uid.' rollInChildThird result ', ['re' => $re]);
                if ($re === true) {
                    GameApi::gameTransferLog($this->uid, $this->game_type, $balance, 1, $tradeNo, true, 0, $this->ci->lang->text('Transfer to %s successed', [$this->game_type]));
                }else{
                    //第三方转入失败  不在这里直接还原  先记下来
                    \DB::table('game_money_error')->insert($redisData);
                    GameApi::addElkLog($redisData, 'money', 'error');
                    GameApi::gameTransferLog($this->uid, $this->game_type, $balance, 1, $tradeNo, false, 0, $this->ci->lang->text('Transfer to %s failed', [$this->game_type]));
                    // 提交事务
                    $this->logger->error('api::rollInThird  转入失败', ['uid' => $this->uid, 'wid' => $this->wid, 'balance' => $balance, 'game_alias' => $this->game_alias, 'tradeNo' => $tradeNo]);
                    throw new \Exception('third party transfer in failed');
                }
            }
            $this->redis->del($lockRequest); //删除锁
            return ['status' => true, 'money' => $balance, 'msg' => 'success'];
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            $this->logger->error('rollInThird  uid:'.$this->uid.' ' . $e->getMessage());
            $this->redis->del($lockRequest); //删除锁
            return ['status' => false, 'money' => 0, 'msg' => $this->ci->lang->text("Third party transfer in failed, things failed to open")];
        }
    }

    /**
     * 退钱 写日志
     * @param $data
     * @return bool
     */
    public function refundAction($data) {
        $res = $this->updateGameMoneyError($data);
        //更新成功 才加钱
        if($res && $data['transfer_type'] == 'in'){
            //更新可用余额 转入第三方失败 主钱包+转入第三方金额  (这个退钱操作也没写事务里)
            $result = \Model\Funds::where('id', $data['wid'])
                ->update(['balance' => \DB::raw("balance + {$data['balance']}")]);
            \Model\FundsChild::where('pid', $data['wid'])
                ->where('game_type', $data['game_type'])
                ->where('balance', '>=', $data['balance'])
                ->update(['balance' => \DB::raw("balance - {$data['balance']}")]);
            $this->logger->error('api::refundAction  转入失败退款日志', $data);
            //写入额度转换记录  不写入交易记录  （只是一个退款操作）
            GameApi::gameTransferLog($data['user_id'], $data['game_type'], $data['balance'], $data['transfer_type']=='in' ? 1 : 0, $data['tradeNo'], true, 0, "refund:{$result}".$this->ci->lang->text('Transfer to %s failed', [$data['game_type']]), 0);

            return true;
        }
        return false;

    }

    /**
     * 更新状态为已处理
     * @param $data
     * @param int $money 余额
     * @return int
     */
    public function updateGameMoneyError($data, $money = 0)
    {
        $res = \DB::table('game_money_error')
            ->where('id',$data['id'])
            ->where('status',0)
            ->update(['status' => 1]);
        
        $money = abs($money);
        //第三方转出成功加余额，但接口报错
        if($res && $data['transfer_type'] == 'out' && $money > 0){
            //更新可用余额
            $result = \Model\Funds::where('id', $data['wid'])
                ->update(['balance' => \DB::raw("balance + {$money}")]);
            \Model\FundsChild::where('pid', $data['wid'])
                ->where('game_type', $data['game_type'])
                ->where('balance', '>=', $data['balance'])
                ->update(['balance' => \DB::raw("balance - {$data['balance']}")]);
            $this->logger->error('api::refundAction  转出失败退款日志', $data);
            //写入额度转换记录  不写入交易记录  （只是一个退款操作）
            GameApi::gameTransferLog($data['user_id'], $data['game_type'], $money, 0, $data['tradeNo'], true, 0, "refund:{$result}".$this->ci->lang->text('success to transfer out %s', [$data['game_type']]), 1);

        }
        return $res;
    }

    /**
     * 转入第三方余额
     *
     * @param int $balance
     * @param string $tradeNo
     * @return array ['status'=> true--成功，false失败,'money'=> int,'msg'=>string]
     */
    abstract function rollInChildThird(int $balance, string $tradeNo);

    /**
     * 获取curl
     *
     * @return http
     */
    protected function getHttp() {
        //
        $curl = new http();
        $curl->set(['header' => $this->header]);
        return $curl;
    }


    /**
     * 返回结果
     *
     * @param $res
     *
     * @return array|mixed
     */
    protected function response($res) {
        $res = json_decode($res, true);
        if (isset($res['codeId'])) {
            if (0 === $res['codeId']) {
                return $res;
            } else if (isset($this->error[$res['codeId']]))
                return ['error' => $this->error[$res['codeId']]];
        }
        return ['error' => $this->ci->lang->text('unknown error')];
    }

    /**
     * XML解析成数组
     */
    public function parseXML($xmlSrc){
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser,$xmlSrc,true)){
            xml_parser_free($xml_parser);
            return $xmlSrc;
        }

        $xml = simplexml_load_string($xmlSrc);
        $data = $this->xml2array($xml);
        return $data;
    }

    public function xml2array($xmlobject) {
        if ($xmlobject) {
            foreach ((array)$xmlobject as $k=>$v) {
                $data[$k] = !is_string($v) ? $this->xml2array($v) : $v;
            }
            return $data;
        }
    }

    /**
     * XML解析成数组
     */
    public function parseXML2($xmlSrc){
        if(empty($xmlSrc)){
            return false;
        }
        $array = array();
        $xml = simplexml_load_string($xmlSrc);
        $encode = $this->getXmlEncode($xmlSrc);
        if($xml && $xml->children()) {
            foreach ($xml->children() as $node){
                //有子节点
                if($node->children()) {
                    $k = $node->getName();
                    $nodeXml = $node->asXML();
                    $v = $this->parseXML($nodeXml);
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

    //获取xml编码
    public function getXmlEncode($xml) {
        $ret = preg_match ("/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr);
        if($ret) {
            return strtoupper ( $arr[1] );
        } else {
            return "";
        }
    }

    /**
     * 订单统计错误记录表
     * @param string $now 当前时间
     * @param array $json
     * @param string $startTime
     * @param string $endTime
     * @param string $totalBet
     * @param string $totalWin
     * @param string $orderBet
     * @param string $orderWin
     */
    public function addGameOrderCheckError($now, $json, $startTime, $endTime, $totalBet, $totalWin, $orderBet, $orderWin)
    {
        \DB::table('game_order_check_error')->insert([
            'game_type' => $this->config['type'],
            'now' => date('Y-m-d H:i:s', $now),
            'json' => json_encode($json, JSON_UNESCAPED_UNICODE),
            'error' => json_encode([
                'startTime' => $startTime,
                'endTime' => $endTime,
                'totalBet' => $totalBet,
                'totalWin' => $totalWin,
                'orderBet' => $orderBet,
                'orderWin' => $orderWin
            ], JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * 注单入库
     * @param $game_type
     * @param $table
     * @param $insertData
     * @return bool
     */
    public function addGameOrders($game_type, $table, $insertData)
    {
        if (empty($insertData)) {
            return true;
        }

        //配置CK不更新注单表
        $settings = $this->ci->get('settings')['ClickHouseDB'];
        if(isset($settings)){
            return true;
        }
        $page = 1;
        $pageSize = 100;
        $total_count = count($insertData);
        $page_count = ceil($total_count / 100);
        while (1) {
            if ($page > $page_count) {
                break;
            }
            $page_start = ($page - 1) * $pageSize;
            $subData = array_slice($insertData, $page_start, $pageSize);
            $subDataCount = count($subData);
            try {
                \DB::table($table)->insert($subData);
            } catch (\Exception $e) {
                //总数只有一条插入失败
                if ($subDataCount == 1) {
                    $this->addGameOrderError($game_type, reset($subData), $e->getCode(), $e->getMessage());
                } else {
                    foreach ($subData as $recode) {
                        try {
                            \DB::table($table)->insert($recode);
                        } catch (\Exception $e2) {
                            $this->addGameOrderError($game_type, $recode, $e2->getCode(), $e2->getMessage());
                        }
                    }
                }
            }
            $page++;
        }

        unset($data, $val, $insertData, $subData);
        return true;
    }

    /**
     * 添加游戏订单错误
     * @param $game_type
     * @param $insertData
     * @param $code
     * @param $msg
     */
    public function addGameOrderError($game_type, $insertData, $code, $msg)
    {
        $tmp_err = [
            'game_type' => $game_type,
            'json' => json_encode($insertData, JSON_UNESCAPED_UNICODE),
            'error' => $msg,
        ];
        \DB::table('game_order_error')->insert($tmp_err);
        GameApi::addElkLog(['code' => $code, 'message' => $msg], $game_type);
    }

    /**
     * 批量插入orders表
     * @param $insertData
     * @return bool
     */
    public function addGameToOrdersTable($insertData){
        if (empty($insertData)) {
            return true;
        }
        $page = 1;
        $pageSize = 100;
        $total_count = count($insertData);
        $page_count = ceil($total_count / 100);
        while (1) {
            if ($page > $page_count) {
                break;
            }
            $page_start = ($page - 1) * $pageSize;
            $subData = array_slice($insertData, $page_start, $pageSize);
            $subDataCount = count($subData);
            try {
                \DB::table('orders')->insert($subData);
            } catch (\Exception $e) {
                //总数只有一条插入失败
                if ($subDataCount == 1) {
                    if(strpos($e->getMessage(), '1062 Duplicate entry')){
                        break;
                    }
                    $this->logger->error('addGameToOrdersTable ' . $e->getMessage());
                    \DB::table('orders_temp')->insert(['data'=>json_encode($subData)]);
                } else {
                    foreach ($subData as $recode) {
                        try {
                            \DB::table('orders')->insert($recode);
                        } catch (\Exception $e2) {
                            if(strpos($e2->getMessage(), '1062 Duplicate entry')){
                                continue;
                            }
                            $this->logger->error('addGameToOrdersTable ' . $e->getMessage());
                            \DB::table('orders_temp')->insert(['data'=>json_encode($recode)]);
                        }
                    }
                }
            }
            $page++;
        }

        unset($data, $val, $insertData, $subData);
        return true;
    }

    /**
     * 获取用户头像
     * @return string URL
     */
    public function getUserAvatar() {
        $url  = $this->redis->get('user_avatar-'. $this->uid);
        if(is_null($url)){
            $avatar = \DB::table('profile')->where('user_id', $this->uid)->value('avatar');
            $upload = $this->ci->get('settings')['upload'];
            $oss = $upload['dsn'][$upload['useDsn']];
            $avatar = $avatar ?: 1;
            $url = $oss['domain']."/avatar/{$avatar}.png";
            $this->redis->setex('user_avatar-'. $this->uid, 3600*24, $url);
        }
        return $url;
    }

}
