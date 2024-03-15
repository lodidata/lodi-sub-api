<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model;
use DB;
class LotteryChase extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'lottery_chase';

    public $timestamps = false;
    
    protected $fillable = [  
                        'user_id',
                        'chase_type',
                        'chase_name',
                        'chase_number',
                        'lottery_id',
                        'lottery_number',
                        'multiple',
                        'current_bet',
                        'increment_bet',
                        'reward',
                        'profit',
                        'open_status',
                        'state',
                        'origin',
                        ];

    /**
     * 查询追号记录
     * @param  [type]  $condtion [description]
     * @param  integer $page     [description]
     * @param  integer $pageSize [description]
     * @return [type]            [description]
     */
    public static function getRecords($condtion, $page = 1, $pageSize = 20) {
        global $app;
        $table = DB::table('lottery_chase')->leftjoin('lottery', 'lottery.id', '=', 'lottery_chase.lottery_id');
        isset($condtion['lottery_id']) && $table = $table->where('lottery_chase.lottery_id', $condtion['lottery_id']);
        isset($condtion['user_id']) && $table = $table->where('lottery_chase.user_id', $condtion['user_id']);
        isset($condtion['start_time']) && $table = $table->where('lottery_chase.created', '>=', $condtion['start_time']);
        isset($condtion['end_time']) && $table = $table->where('lottery_chase.created', '<=', $condtion['end_time']);
        if(isset($condtion['state']))
            switch ($condtion['state']){
                case 'complete': $table->havingRaw("sum(IF(FIND_IN_SET(lottery_chase.state,'created,open'),1,0))=COUNT(lottery_chase.id)");break;
                case 'cancel':$table->whereRaw("FIND_IN_SET(lottery_chase.state,'cancel')");break;
                case 'underway':$table->havingRaw("sum(IF(FIND_IN_SET(lottery_chase.state,'created,open'),1,0))!=COUNT(lottery_chase.id)");break;
            }

        $table = isset($condtion['group']) ? $table->groupBy($condtion['group']) : $table->groupBy('lottery_chase.chase_number');
        $table = isset($condtion['order']) ? $table->orderBy($condtion['order'], $condtion['orderBy']) : $table->orderBy('lottery_chase.id', 'desc');

        // 排队测试和试玩用户
        if (isset($condtion['notInTags'])) {
            $notInTags = $app->getContainer()->get('settings')['websize']['notInTags'];
            $table = $table->leftjoin('user', 'user.id', '=', 'lottery_chase.user_id')->whereNotIn('user.tags', $notInTags);
        }

        $select = [
            "lottery_chase.id",
            DB::raw("concat('', lottery_chase.chase_number) as chase_number"), // 长度太长转字符
            "lottery_chase.lottery_id",
            DB::raw("COUNT(lottery_chase.id) AS chase_amount"),
            DB::raw("sum(IF(FIND_IN_SET(lottery_chase.state,'created,open'),1,0)) AS current_amount"),
            "lottery.name",
            "current_bet",
            DB::raw("SUM(lottery_chase.current_bet) AS increment_bet"),
            DB::raw("IF(lottery_chase.chase_type=1,'中奖不停止','中奖停止') AS chase_type"),
            DB::raw("SUM(lottery_chase.reward) AS send_money"),
            "lottery_chase.created",
            DB::raw("(IF((sum(IF(FIND_IN_SET(lottery_chase.state,'created,open'),1,0))=COUNT(lottery_chase.id)),'complete','underway')) AS state"),
            DB::raw("sum(IF(FIND_IN_SET(lottery_chase.state,'cancel'),1,0)) AS cancel"),
            DB::raw("sum(reward) AS send_money"),
            "lottery_chase.open_status"
        ];

        isset($condtion['app']) && $select[] = DB::raw("(SELECT IF(COUNT(DISTINCT play_id) > 1,'多种玩法',CONCAT(play_group,'-',play_name)) FROM lottery_order 
              WHERE user_id = {$condtion['user_id']} AND chase_number = lottery_chase.chase_number) AS play_name");

        $table = $table->select($select);
        return $table->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 详情
     * @param  [type] $chaseNumber [description]
     * @return [type]              [description]
     */
    public static function getOne($chaseNumber, $userId = 0) {
        global $app;
        $table = DB::table('lottery_chase')
                ->leftjoin('lottery_info', function ($join) {
                    $join->on('lottery_info.lottery_type', '=', 'lottery_chase.lottery_id')
                         ->on('lottery_info.lottery_number', '=', 'lottery_chase.lottery_number');
                })->leftJoin('lottery','lottery_chase.lottery_id','=','lottery.id')
                ->where('lottery_chase.chase_number', $chaseNumber)
                ->select([
                    'lottery_chase.id',
                    'lottery_chase.chase_number',
                    'lottery_chase.lottery_number',
                    'lottery_chase.lottery_id',
                    'lottery_info.lottery_name',
                    'lottery_chase.multiple',
                    'lottery_chase.current_bet',
                    'lottery_chase.reward',
                    'lottery_chase.state',
                    'lottery_info.period_code',
                    'lottery_info.pid',
                    'lottery_chase.state',
                    'lottery_chase.created',
                    'lottery_chase.origin',
                    'lottery_chase.chase_type',
                    'lottery.open_img',
                ]);
        $userId > 0 && $table = $table->where('lottery_chase.user_id', $userId);
        return $table->get();
    }

    /** 
     * 获取追号详情
     * @param number $pageIndex
     * @param number $pageSize
     * @param array  $condtion
     */
    public static function getDetail($chaseNumber, $userId = 0) {

        $result = [];
        if($chaseNumber) {
            $chase = self::getOne($chaseNumber, $userId);
            if($chase) {
                $temp = array();
                $i = 0;
                $j = 0;
                $sum = 0;
                $sum_reward = 0;
                $tempLotteryNumber = '';
                $open_img = '';
                foreach ($chase as $val) {
                    $val = (array) $val;
                    $origin = $val['origin'];
                    $open_img = $val['open_img'];
                    $chase_type = $val['chase_type'];
                    $sum += $val['current_bet'];
                    $sum_reward += $val['reward'];
                    $create = $val['created'];
                    $lottery_name = $val['lottery_name'];
                    $pid = $val['pid'];
                    $lotteryId = $val['lottery_id'];
                    $temp['lottery_number'] = $val['lottery_number'];
                    !$tempLotteryNumber && $tempLotteryNumber = $val['lottery_number'];
                    $temp['multiple'] = $val['multiple'];
                    $temp['current_bet'] = $val['current_bet'];
                    $temp['period_code'] = $val['period_code'];
                    if (strstr($val['state'], 'open') && $val['reward'] > 0) {
                        $temp['state'] = '中' . $val['reward']/100 . '元';
                        $temp['winning_money'] = $val['reward'];
                        $temp['state_str'] = 'winning';
                        $i++;
                    } elseif (strstr($val['state'], 'open')) {
                        $temp['state'] = '未中奖';
                        $temp['state_str'] = 'lose';
                        $i++;
                    } elseif (strstr($val['state'], 'created')) {
                        $temp['state'] = '等待开奖';
                        $temp['state_str'] = 'created';
                        $i++;
                    }elseif (strstr($val['state'], 'cancel')) {
                        $temp['state'] = '已撤单';
                        $temp['state_str'] = 'cancel';
                        $j++;
                    } else {
                        $temp['state'] = '等待追号';
                        $temp['state_str'] = 'waiting';
                    }
                    $result['chase_desc']['data'][] = $temp;
                }

                $play = DB::table('lottery_order')
                        ->leftjoin('hall', 'lottery_order.hall_id', '=', 'hall.id')
                        ->leftjoin('room', 'lottery_order.room_id', '=', 'room.id')
                        ->select([
                            'lottery_order.room_id',
                            'lottery_order.hall_id',
                            'lottery_order.play_id',
                            'lottery_order.state',
                            'lottery_order.user_name',
                            'lottery_order.play_group',
                            'lottery_order.play_name',
                            'lottery_order.bet_num as bet_num',
                            //DB::raw('count(lottery_order.bet_num) as bet_num'),
                            'lottery_order.times',
                            'lottery_order.odds',
                            'lottery_order.pay_money',
                            'lottery_order.play_number',
                            'hall.hall_name',
                            'room.room_name'
                        ])
                        ->where('lottery_order.chase_number', $chaseNumber)
                        ->where('lottery_order.lottery_number', $tempLotteryNumber)
                        ->get()
                        ->toArray();
                $temp = 0;
                $hall = '未知';
                $mode = '未知';
                $logic = new \LotteryPlay\Logic();
                foreach ($play as $val) {
                    $val = (array) $val;

                    $val['name'] = $val['play_group'] . '>' . $val['play_name'];
                    $result['chase_msg']['user_name'] = $val['user_name'];
                    $temp += $val['pay_money'];
                    $hall = $val['hall_name'];
                    $hallId = $val['hall_id'];
                    $roomId = $val['room_id'];
                    $roomName = $val['room_name'];
                    $mode = $val['state'];
                    $playId = $val['play_id'];
                    $userName = $val['user_name'];

                    $ii = 0;
                    $temp_odds = [];
                    foreach (json_decode($val['odds'], true) as $k => $v){
                        $temp_odds[$ii]['name'] = $k;
                        $temp_odds[$ii]['num'] = $v;
                        $ii++;
                    }
                    $val['odds_array'] = $temp_odds;
                    if (isset($pid) && isset($playId)){
                        $val['play_numbers'] = $logic->getPretty($pid,$val['play_id'],$val['play_number']);
                    }else{
                        $val['play_numbers'] = [];
                    }
                    unset($val['play_group']);unset($val['play_name']);unset($val['hall_name']);
                    unset($val['user_name']);unset($val['state']);unset($val['play_id']);
                    unset($val['room_id']);unset($val['hall_id']);
                    $result['play_desc']['data'][] = $val;
                    $result['play_desc']['total_money'] = $temp;
                }

                $modes = explode(',', $mode); 
                $define = ['fast' => '快捷', 'chat' => '房间', 'std' => '标准', 'vedio' => '视频'];
                foreach ($define as $k => $val) {
                    if (in_array($k, $modes)) {
                        $mode = $val;
                        break;
                    }
                }
                $result['chase_desc']['num'] = $i . '/' . count($result['chase_desc']['data']);
                $result['chase_desc']['num_c'] = $i;
                $result['chase_desc']['num_s'] = count($result['chase_desc']['data']);
                $result['chase_desc']['chase_type'] = $chase_type;
                $result['chase_desc']['chase_desc'] = $chase_type  == 2 ? '中奖停止追号':'';
                $result['lottery_name'] = $lottery_name;
                $result['pid'] = $pid ?? '未知';
                $result['play_id'] = $playId ?? '';
                $result['lottery_id'] = $lotteryId ?? '未知';
                $result['open_img'] = $open_img;
                $result['status'] = $i == count($result['chase_desc']['data']) ? 'complete' : ($j >0 ? 'cancel' : 'underway');
                $result['status_chinese'] = $i == count($result['chase_desc']['data']) ? '已完成' : ($j >0 ? '已撤单' : '追号中');
                $temp = [];
                $temp['money'] = $sum;
                $temp['money_reward'] = $sum_reward;
                $temp['money_lose'] = $sum_reward - $sum;
                $temp['hall'] = $hall ?? '未知';
                $temp['hall_id'] = $hallId ?? '';
                $temp['room_id'] = $roomId ?? '';
                $temp['room_name'] = $roomName ?? '';
                $temp['mode'] = $mode;
                $temp['user_name'] = $userName ?? '未知';
                $originData = [1 => 'pc',2 => 'h5',3 => '移动端'];
                $temp['origin'] = $originData[$origin] ?? '未知';
                $temp['create'] = $create ?? '';
                $result['chase_msg'] = $temp;
            }
        }
        return $result;
    }
}

