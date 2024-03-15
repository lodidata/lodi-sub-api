<?php
/**
 * Created by PhpStorm.
 * User: 95684
 * Date: 2019/1/15
 * Time: 11:20
 */

namespace Logic\GameApi;


use Logic\Funds\DealLog;
use Model\Funds;
use Model\User;
use Model\Orders;

class Common extends \Logic\Logic {

    public function transferOrder($order = []){

        if(empty($order) || empty($order['game_type']) || empty($order['user_id'])){
            $this->logger->error('transferOrder orders empty', $order);
            return ;
        }
//        if($order['bet'] <= 0) return;
        $game = $order['game'];
        $auditSetting = \Logic\Set\SystemConfig::getModuleSystemConfig('audit');
        $gameAduitSetting = isset($auditSetting[$game]) && $auditSetting[$game] ? bcdiv($auditSetting[$game],100,2) : 1;//游戏类型打码量设置，如果不存在则为1
        $dml = $order['bet'] * $gameAduitSetting;
        $date = $order['date'] ? substr($order['date'],0,10) : date('Y-m-d');
        $data = [];
        $data['user_id'] = $order['user_id'];
        $data['order_number'] = $order['order_number'];
        $data['game'] = $order['game'];
        $data['game_type'] = $order['game_type'];
        $data['type_name'] = $order['type_name'];
        $data['play_id'] = $order['game_id'];
        $data['bet'] = $order['bet'];
        $data['profit'] = $order['profit'];
        $data['send_money'] = $order['bet'] + $order['profit'];
        $data['dml'] = $dml;
        $data['date'] = $date;
        $data['order_time'] = $order['date'] ? $order['date'] : date('Y-m-d H:i:s');

        try{

           $res = Orders::insert($data);

                //更新的时候 下面就不执行了
                /*if($res){
                    if ($data['game'] != 'CP') {  //彩票写流水时已加过一次
                        $user = $this->getUserInfo($order['user_id']);
                        $balance = Funds::where('id', $user['wallet_id'])->value('balance');
                        DealLog::addOrderDealLog(
                            (int)$order['user_id'],
                            (string)$user['name'],
                            (int)$balance,
                            (string)$order['order_number'],
                            (int)$order['bet'],
                            (int)\Model\FundsDealLog::TYPE_THIRD_SETTLE,
                            $order['game_type'] . '=' . $order['type_name'],
                            (int)$order['bet'],
                            $order['game']
                        );
                        \DB::table('user_dml')->where('user_id', $order['user_id'])->increment($order['game_type'], $order['bet']);
                    }
                    \DB::table('user_data')
                        ->where('user_id', $order['user_id'])
                        ->increment('order_amount', $order['bet'], [
                            'order_num' => \DB::raw('order_num + 1'),
//                            'total_bet'=>\DB::raw("total_bet + {$dml}"),  //写流水加过一次
                            'send_amount' => \DB::raw("send_amount + {$data['send_money']}"),
                        ]);
                }*/

            echo '统计成功！';
            echo 666;
            echo PHP_EOL;
        }catch (\Exception $e){
            $this->logger->error('transferOrder ' . $e->getMessage());
            //\DB::rollback();
            \DB::table('orders_temp')->insert(['data'=>json_encode($order)]);
            print_r($e->getMessage());
        }

    }

    public function getUserInfo($userId){
        $redis_key = "user:info:{$userId}";
        $res = $this->redis->get($redis_key);
        if($res){
            return json_decode($res,true);
        }
        $user = User::select(['wallet_id','name'])->where('id', $userId)->first()->toArray();
        if($user){
            $this->redis->setex($redis_key,86400,json_encode($user));
        }
        return $user;
    }

    public function handleDml(){
        $last_id = \DB::table('order_id')->where('id',1)->value('last_order_id');
        $max_id =  \DB::table('orders')->max('id');
        $add   = 100000;
        //每次最多计算10万条
        $start = $last_id??0;
        $end   = $last_id + $add;

        if($last_id >= $max_id){
            echo '没有可以同步的数据';
            return;
        }

        while(1){
            //不能大于最大id
            $end > $max_id && $end = $max_id;
            $result = \DB::table('order_id')->where('id',1)->update(['last_order_id' => $end]);
            if(!$result){
                echo '修改last_order_id出错';
                break;
            }

            //本来应该把彩票的过滤掉 但因为现在彩票也没开 所以就没加过滤彩票的条件
            $sql = "SELECT count(1) num,sum(bet) bet,sum(send_money) send_money, game_type, game, user_id,type_name from orders where id > {$start} and id <= {$end} group by user_id,game_type";

            $res = \Db::select($sql);

            if(!$res){
                echo '暂时没有数据';
                break;
            }

            $res_num = count($res);
            for($i=0; $i<$res_num; $i++){
                $v = (array)$res[$i];
                try{
                    $this->logger->info('handleDmlInfo ' . json_encode($v));
                    $this->CountDml($v);
                }catch (\Exception $e){
                    $this->logger->error('handleDml ' . $e->getMessage());
                    $this->logger->error('handleDmlInfoError' . json_encode($v));
                }
                unset($res[$i]);
            }

            //统计完了
            if($end >= $max_id){
                break;
            }else{
                $start = $end;
                $end   = $end + $add;
            }
        }

    }

    public function CountDml($order){
        $user = $this->getUserInfo($order['user_id']);
        $balance = Funds::where('id', $user['wallet_id'])->value('balance');
        DealLog::addOrderDealLog(
            (int)$order['user_id'],
            (string)$user['name'],
            (int)$balance,
            '',
            (int)$order['bet'],
            (int)\Model\FundsDealLog::TYPE_THIRD_SETTLE,
            $order['game_type'] . '=' . $order['type_name'],
            (int)$order['bet'],
            $order['game']
        );
        \DB::table('user_dml')->where('user_id', $order['user_id'])->increment($order['game_type'], $order['bet']);
        \DB::table('user_data')
            ->where('user_id', $order['user_id'])
            ->increment('order_amount', $order['bet'], [
                'order_num' => \DB::raw("order_num + {$order['num']}"),
//                            'total_bet'=>\DB::raw("total_bet + {$dml}"),  //写流水加过一次
                'send_amount' => \DB::raw("send_amount + {$order['send_money']}"),
            ]);
    }
}