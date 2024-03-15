<?php
use Utils\Www\Action;
use Model\Admin\ActiveBkge as ActiveBkgeModel;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "下级列表";
    const DESCRIPTION = "直属会员列表";
    const TAGS = "代理返佣";
    const QUERY = [
        "page"          => 'int()',
        "page_size"     => "int()",
        "start_time"    => "date() #开始日期 2021-08-12",
        "end_time"      => "date() #结束日期 2021-08-20",
    ];

    const SCHEMAS = [
        [
            "name"                      => "string(required) #用户名",
            "balance"                   => "float(required) #钱包余额",
            "register_time"             => "string(required) #注册时间",
            "winloss"                   => "float(required) #盈亏金额",
            "deposit_amount"            => "float(required) #充值金额",
            "withdraw_amount"         => "float(required) #取款金额",
            "bet_amount"                => "float(required) #投注金额",
            "active_amount"             => "float(required) #活动金额",
        ]
    ];


    public function run(){
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid        = $this->auth->getUserId();
        $page       = $this->request->getParam('page',1);
        $page_size  = $this->request->getParam('page_size',20);
        $beginDate  = $this->request->getParam('start_time') ? : date('Y-m-d',strtotime("-1 day"));
        $endDate    = $this->request->getParam('end_time') ? : date('Y-m-d');

        if(strtotime($endDate) - strtotime($beginDate) > 3600*24*31){
            return $this->lang->set(886, ['The query time cannot exceed 31 days']);
        }

        //关于金额的统计
        $query = \DB::table('user_agent as ua')
            ->join('rpt_user as ru','ua.user_id','=','ru.user_id','inner')
            ->leftJoin('user as u','ua.user_id','=','u.id')
            ->leftJoin('funds as f','u.wallet_id','=','f.id')
            ->where('ua.uid_agent',$uid)
            ->where('ru.count_date', '>=', $beginDate)
            ->where('ru.count_date','<=', $endDate);

        $total = $query->count();

        $sub_data = $query->groupBy('ru.user_id')
                        ->forPage($page, $page_size)
                        ->get([
                            'u.name',
                            'f.balance',
                            'ru.register_time',
                            \DB::raw('ifnull(sum(prize_user_amount-bet_user_amount),0) winloss'),
                            \DB::raw('ifnull(sum(deposit_user_amount),0) deposit_amount'),
                            \DB::raw('ifnull(sum(withdrawal_user_amount),0) withdraw_amount'),
                            \DB::raw('ifnull(sum(bet_user_amount),0) bet_amount'),
                            \DB::raw('ifnull(sum(coupon_user_amount+return_user_amount+turn_card_user_winnings+promotion_user_winnings),0) active_amount'),
                        ])->toArray();

        if($sub_data){
            foreach ($sub_data as &$v){
                $v->balance = bcdiv($v->balance,100,2);
            }
            unset($v);
        }
        $attributes['total']  = $total;
        $attributes['number'] = $page;
        $attributes['size']   = $page_size;
        return $this->lang->set(0, [], $sub_data, $attributes);
    }
};