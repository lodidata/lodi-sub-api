<?php
/**
 * Workerman相关的服务代码
 * @author Taylor 2019-03-01
 */
namespace Logic\Service;

/**
 * workerman服务模块
 */
class Workerman extends \Logic\Logic
{
    //群消息发送
    public function send_group_msg($params){
        \DB::table('user')->selectRaw('id')->whereIN($params['filed'], $params['inArray'])
            ->orderByDesc('id')->chunk(2000, function ($users) use ($params) {
                $insertArr = [];
                foreach ($users as $user) {
                    $message = [
                        'uid'     => $user->id,
                        'type'    => $params['type'],
                        'm_id'    => $params['m_id'],
                        'created' => time(),
                        'updated' => time(),
                    ];
                    array_push($insertArr, $message);
                }
                \DB::table('message_pub')->insert($insertArr);
            });
    }

    //私发消息
    public function send_private_msg($params){
        if($params['user_id']) {
            $params['send_type'] = isset($params['send_type']) ? $params['send_type'] : 3;
            $params['type'] = isset($params['type']) ? $params['type'] : 2;
            $params['user_name'] = isset($params['user_name']) ? $params['user_name'] : \Model\User::where('id', $params['user_id'])->value('name');
            $message = [
                'send_type' => $params['send_type'],//发送类型（1：会员层级，2：代理，3：自定义）
                'title' => $params['title'],
                'admin_uid' => 0,
                'admin_name' => 0,
                'recipient' => $params['user_name'],//用户名
                'user_id' => $params['user_id'],//用户id
                'type' => $params['type'],//1 重要消息（import）, 2 一般消息
                'status' => 1,//0未发布，1，已发布
                'content' => $params['content'],
                'active_type'=>$params['active_type'] ?? 0,
                'active_id'=>$params['active_id'] ??0,
                'created' => time(),
                'updated' => time(),
            ];
            $mid = \Model\Message::insertGetId($message);
            $message_pub = [
                'type' => 1,//1,会员，2代理
                'uid' => $params['user_id'],//用户id
                'm_id' => $mid,//消息id
                'created' => time(),
                'updated' => time(),
            ];
            \Model\MessagePub::insert($message_pub);
        }
    }

    //线下充值更新对应的充值次数
    public function offline_times(){
        $start = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $end = date('Y-m-d',strtotime($now) - 5*60);
        $data1 = \Model\FundsDeposit::where('status','paid')
            ->where('updated','>=',$start)
            ->where('money','>',0)
            ->whereRaw('!FIND_IN_SET("online",state)')
            ->groupBy('receive_bank_account_id','money')
            ->get(['receive_bank_account_id','money',\DB::raw('count(id) AS times')])->toArray();
        $data2 = \Model\FundsDeposit::where('status','pending')
            ->where('created','>=',$end)
            ->where('money','>',0)
            ->whereRaw('!FIND_IN_SET("online",state)')
            ->groupBy('receive_bank_account_id','money')
            ->get(['receive_bank_account_id','money',\DB::raw('count(id) AS times')])->toArray();

        $re = [];
        $this->redis->del(\Logic\Define\CacheKey::$perfix['rechargeOffline']);
        $this->redis->del(\Logic\Define\CacheKey::$perfix['rechargeOfflineMoney']);
        foreach ($data1 as $val) {
            $val = (array)$val;
            $re[$val['receive_bank_account_id']] = isset($re[$val['receive_bank_account_id']]) ? $re[$val['receive_bank_account_id']] + $val['times'] : $val['times'];
            $this->redis->hSet(\Logic\Define\CacheKey::$perfix['rechargeOfflineMoney'], 'offline_' . $val['receive_bank_account_id'] . '_' . $val['money'],$val['times']);
        }
        foreach ($data2 as $val) {
            $val = (array)$val;
            $re[$val['receive_bank_account_id']] = isset($re[$val['receive_bank_account_id']]) ? $re[$val['receive_bank_account_id']] + $val['times'] : $val['times'];
            $times = $this->redis->hGet(\Logic\Define\CacheKey::$perfix['rechargeOfflineMoney'], 'offline_' . $val['receive_bank_account_id'] . '_' . $val['money']);
            $this->redis->hSet(\Logic\Define\CacheKey::$perfix['rechargeOfflineMoney'], 'offline_' . $val['receive_bank_account_id'] . '_' . $val['money'],$times + $val['times']);
        }
        if($re) {
            foreach ($re as $key=>$val) {
                $this->redis->hSet(\Logic\Define\CacheKey::$perfix['rechargeOffline'], 'offline_' . $key,$val);
            }
        }
    }
}