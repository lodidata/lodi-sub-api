<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = '查询 往来记录';
    const DESCRIPTION = '往来记录列表信息    \r\n返回attributes[number =>\'第几页\', \'size\' => \'记录条数\'， total=>\'总记录数\', sum =>[
            \'depost_sum\' => \'手动存款总金额\',
            \'withdraw_sum\' => \'提现扣款总金额\',
            \'return_sum\' => \'返水优惠总金额\',
            \'active_sum\' => \'活动总金额\',
        ]]';
    const TAGS = "充值提现";
    const QUERY = [
        'type'       => 'int(required) #交易类别（0：全部，1：收入，2：支出）',
        'game_type'  => 'int(required) #交易类型（0：全部，1：充值，....）',
        'start_time' => 'date() #查询开始日期  2019-09-12',
        'end_time'   => 'date() #查询结束日期  2019-09-12',
        'page'       => 'int(,1) #当前第几页',
        'page_size'  => 'int(,30) #每页数目 默认30',
        'pc_or_h5'   => 'int() #(1=h5,其他=pc)',
    ];
    const SCHEMAS = [
        'order_number' => 'string(required) #订单号',
        'time'         => 'int(required) #时间截 1223234',
        'created'      => 'string(required) #时间 2019-09-12 12:12:12',
        'type'         => 'int(required) #交易类别（0：全部，1：支出，2：收入,3:额度转换）',
        'game_type'    => 'int(required) #交易类型（0：全部，1彩票投注,2彩票派彩,3返水,4撤单,5 充值,6 提款,7 提款冻结,8提跨解冻,9优惠返水,10额度转换）',
        'money'        => 'int(required) #交易金额',
        'balance'      => 'int(required) #账户余额',
        'deal_money'   => 'int(required) #交易金额',
        'count'        => 'int(required) #',
        'desc'         => 'string(required) #备注',
        'sum'          => 'int(required) #交易总金额',
        'withdraw_bet' => 'int(required) #打码量收入',
        'coupon_money' => 'int(required) #优惠金额',
        'sum_coupon'   => 'int(required) #优惠总金额',
    ];

    protected $origins = [1 => 'PC', 2 => 'H5', 3 => 'APP'];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();
        $condition = [
            'user_id' => $userId,
        ];

        if ($this->request->getQueryParam('start_time')) {
            $condition['start_time'] = $this->request->getQueryParam('start_time') . ' 00:00:00';
        }

        if ($this->request->getQueryParam('end_time')) {
            $condition['end_time'] = $this->request->getQueryParam('end_time') . ' 59:59:59';
        }

        if ($this->request->getQueryParam('game_type') > 0) {
            $condition['deal_type'] = $this->request->getQueryParam('game_type');
        }

        if (intval($this->request->getQueryParam('type')) > 0) {
            $condition['deal_category'] = intval($this->request->getQueryParam('type'));
        }

        $condition['without_withdraw'] = true;
        $condition['without_free_money'] = true;

        $page = (int)$this->request->getQueryParam('page', 1);
        $pageSize = (int)$this->request->getQueryParam('page_size', 30);

        list($list, $total) = \Model\FundsDealLog::getRecords($condition, $page, $pageSize);

        $deal = new \Logic\Funds\DealLog($this->ci);
        $data = [];

        foreach ($list ?? [] as $k => $v) {
            $v = (array)$v;
            $arr = [];
            $arr['order_number'] = $v['order_number'];
            $arr['time'] = (int)$v['pc_created'];
            $arr['created'] = $v['created'];

            switch ($v['deal_category']) {
                case 1:
                    $v['deal_category'] = $this->lang->text("Income");
                    break;
                case 2:
                    $v['deal_category'] = $this->lang->text("Pay");
                    break;
                case 3:
                    $v['deal_category'] = $this->lang->text("Exchange");
                    break;
                case 4:
                    $v['deal_category'] = $this->lang->text("Change of available balance");
                    break;
            }

            $arr['type'] = $v['deal_category'];

            if (in_array($v['deal_type'], [301, 302, 303, 304])) {
                $arr['game_type'] = $v['memo'];
            } else {
                $arr['game_type'] = $deal->getDealTypeName($v['deal_type']);
            }

            $arr['money'] = intval($v['deal_money']);
            $arr['balance'] = intval($v['balance']);
            $arr['deal_money'] = intval($v['deal_money']);
            $arr['count'] = "";
            $arr['desc'] = $v['memo'];
            $arr['sum'] = intval($v['sum']);
            $arr['coupon_money'] = intval($v['coupon_money']);
            $arr['sum_coupon'] = intval($v['sum_coupon']);
            $arr['deal_type'] = $v['deal_type'];
            $arr['withdraw_bet'] = $v['withdraw_bet'];

            # 新增逻辑，加入是否为收入 与 支出，为横版加入的字段
            # 额度转换的逻辑要修改
            if (in_array($v['deal_type'], [301, 303, 306,120])) {
                $arr['is_income'] = true;
            } else {
                $arr['is_income'] = false;
            }
            $data[] = $arr;
        }
        $t = \Model\FundsDealLog::getRecordsSum($condition);
        $res = [
            'depost_sum' => 0,
            'withdraw_sum' => 0,
            'return_sum' => 0,
            'active_sum' => 0,
        ];
        foreach ($t as $v){
            switch ($v->deal_type) {
                case \Model\FundsDealLog::TYPE_INCOME_ONLINE:
                case \Model\FundsDealLog::TYPE_INCOME_OFFLINE:
                case \Model\FundsDealLog::TYPE_INCOME_MANUAL: $res['depost_sum'] += $v->money;break;
                case \Model\FundsDealLog::TYPE_WITHDRAW_MANUAL:
                case \Model\FundsDealLog::TYPE_WIRTDRAW_CUT: $res['withdraw_sum'] += $v->money;break;
                case \Model\FundsDealLog::TYPE_REBET: $res['return_sum'] += $v->money;break;
                case \Model\FundsDealLog::TYPE_ACTIVITY:
                case \Model\FundsDealLog::TYPE_ACTIVITY_MANUAL:
                case \Model\FundsDealLog::TYPE_LEVEL_MANUAL1:
                case \Model\FundsDealLog::TYPE_LEVEL_MANUAL2:
                case \Model\FundsDealLog::TYPE_LEVEL_MONTHLY:
                    $res['return_sum'] += $v->money;break;
            }

        }
        return $this->lang->set(0, [], $data, [
            'number' => $page, 'size' => $pageSize, 'total' => $total,'sum'=>$res
        ]);
    }
};