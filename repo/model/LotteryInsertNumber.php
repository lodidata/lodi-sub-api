<?php
/**
 * 泰国彩票插入数字
 */

namespace Model;


use Logic\Define\CacheKey;
use Illuminate\Contracts\Redis;

class LotteryInsertNumber extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'lottery_insert_number';
    public $timestamps = false;

    public static function getNumber($lotteryId, $lotteryNumber){
        $where = [
            'lottery_id'        => $lotteryId,
            'lottery_number'    => $lotteryNumber,
        ];
        return self::select(['uid',\DB::raw("if(uid=0,0,1) true_user"), 'user_account','number','sort', 'created'])->where($where)->get()->toArray();
    }

    public static function insertNumber($params){
        $data = [
            'uid'               => $params['uid'],
            'user_account'      => $params['user_account'],
            'number'            => $params['number'],
            'lottery_id'        => $params['lottery_id'],
            'lottery_number'    => $params['lottery_number'],
            'sort'              => $params['sort'],
            'created'           => $params['time'] ? date('Y-m-d H:i:s',strtotime($params['time'])) : date('Y-m-d H:i:s', time()),
        ];
        return self::insert($data);
    }

    /**
     * 插入号码生成开奖号
     * @param int $lottery_id 彩票ID
     * @param string $lottery_number 彩票期号
     * @return bool|string
     */
    public static function openCode($lottery_id, $lottery_number)
    {
        $where = [
            'lottery_id' => $lottery_id,
            'lottery_number' => $lottery_number,
        ];

        $res = self::where($where)->selectRaw('count(0) as num_count,sum(number) as number_sum')->first()->toArray();
        if(!$res){
            return false;
        }

        $num_count = $res['num_count'];
        $number_sum = $res['number_sum'];

        //4千万<=随机总和<=9千万
        $rand_sum_number = mt_rand(40000000,90000000);
        //echo 'rand_sum_num:'.$rand_sum_number.PHP_EOL;

        //获取第十六位
        $info16_number = 0;
        if($num_count >= 16){
            $info16_number = self::where($where)->orderBy('id','ASC')->forPage(16,1)->value('number');
        }
        try{
            //随机生成插入号码
            while (1){
                if(bccomp($number_sum, $rand_sum_number, 0) > 0){
                    break;
                }
                $number = mt_rand(0,9).mt_rand(0,9).mt_rand(0,9).mt_rand(0,9).mt_rand(0,9);
                $data = [
                    'uid'               => 0,
                    'user_account'      => str_random(6),
                    'number'            => $number,
                    'lottery_id'        => $lottery_id,
                    'lottery_number'    => $lottery_number,
                ];

                self::insertNumber($data);
                //获取第十六位
                if($num_count == 16){
                    $info16_number = $number;
                }
                $num_count++;
                //总和累计
                $number_sum += (int)$number;
                //echo $num_count.'----'.$number.'---'.$number_sum.PHP_EOL;
            }

            //总和-第16位值=开奖号
            $open_number = bcsub($number_sum, $info16_number, 0);
            //echo 'open_number:'.$open_number.PHP_EOL;
            //return
            //更新lottery_info 开奖6-8位
            $period_code_part = substr($open_number, 0,3);
            //print_r($period_code_part);
            $period_code = join(',', str_split(substr($open_number, 3,5)));
            //print_r($period_code);
            \Model\LotteryInfo::where('lottery_number', $lottery_number)
                ->where('lottery_type', $lottery_id)
                ->update(['period_code_part' => $period_code_part]);

        }catch (\Exception $e){
            print_r($e->getMessage());
        }
        return $period_code;

    }
}