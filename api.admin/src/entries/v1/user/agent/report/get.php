<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '总报表';
    const DESCRIPTION = '';

    const QUERY       = [
        'date'    => 'date() #时间',
        'type'    => 'enum[day,week,month]() #类型 day,week,month',
        'page'       => 'int(required)   #页码',
        'page_size'  => 'int(required)    #每页大小',
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    private $reportDB;
    public function run()
    {
        $this->reportDB = \DB::connection('default');

        (new \lib\validate\BaseValidate([
            'date' => 'require',
            'type' => 'require',
        ]))->paramsCheck('',$this->request,$this->response);
        $date = $this->request->getParam('date');
        $type = $this->request->getParam('type');
        $page = $this->request->getParam('page',1);
        $size= $this->request->getParam('page_size',20);
        $timestr = strtotime($date);
        switch ($type){
            case 'week':
                $now_day = (date('w', $timestr) ? : 7) - 1; //当前是周几  注意周日为一周的开始所以为0  当为周日应该返回的是7
                //获取一周的第一天：周一
                $date = date('Y-m-d', strtotime("-{$now_day} day",$timestr));break;
            case 'month':
                $date = date('Y-m-01', $timestr);break;
            default:
                $type = 'day';
        }

        $sql = <<<SQL
SELECT
    IFNULL(t2.`name`, '彩票') AS plat_name,
    (bet_money - send_money) AS lose_earn,
    IFNULL(bet_money, 0) AS bet_money,
    IFNULL(send_money, 0) AS send_money,
    IFNULL(bet_num, 0) AS bet_num,
    IFNULL(bet_user, 0) AS bet_user,
    IFNULL(avg_betnum, 0) AS avg_betnum,
    IFNULL(avg_betmoney, 0) AS avg_betmoney,
    IFNULL(in_money, 0) AS in_money,
    IFNULL(avg_inmoney, 0) AS avg_inmoney,
    IFNULL(out_money, 0) AS out_money,
    IFNULL(avg_outmoney, 0) AS avg_outmoney
FROM
    rpt_plat_earnlose_{$type} t1
LEFT JOIN
    partner t2 ON t1.plat_id = t2.id
WHERE
    t1.count_date ='{$date}'
SQL;

        $data =  $this->reportDB->select($sql);
        $re = [];
        foreach ($data as $v) {
            $v = (array)$v;
            $tmp = [
                'lottery_name' => $v['plat_name'],  //游戏平台
                'earn_money' => $v['bet_money'] - $v['send_money'],  //盈亏
                'pay_money' => $v['bet_money'],  //投注金额
                'send_money' => $v['send_money'],  //派奖
                'pay_count' => $v['bet_num'],  //投注笔数
                'pay_player' => $v['bet_user'],  //投注人数
                'pay_avg_count' => $v['avg_betnum'], //人均投注笔数
                'pay_avg_money' => $v['avg_betmoney'], //人均投注金额
                'change_deposit' => $v['in_money'],  //转入总额   子钱包转主钱包
                'change_withdraw' => $v['out_money'],  //转出总额   主钱包转子钱包
                'change_deposit_count' => 0,  //子钱包转主钱包人数
                'change_withdraw_count' => 0,  //主钱包转子钱包人数
                'change_avg_deposit' => $v['avg_inmoney'],  //人均转入金额
                'change_avg_withdraw' => $v['avg_outmoney'],  //人均转出金额
            ];
            $re[] = $tmp;
        }
        $attributes['total'] = count($re);
        $attributes['size'] = $size;
        $attributes['number'] = $page;
        return $this->lang->set(0,[],$re,$attributes);
    }
};
