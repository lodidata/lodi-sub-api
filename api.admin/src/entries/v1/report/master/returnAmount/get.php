<?php
use Logic\Admin\BaseController;

return new  class() extends BaseController {
    const TITLE = '查询回水详情';
    const DESCRIPTION = '查询回水详情：日回水、周回水、月回水、其他回水';

    const QUERY = [
        'day_begin'   => 'datetime(required) #开始日期',
        'day_end'     => 'datetime(required) #结束日期',

    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'day'=>'日回水 ',
            'week'=>'周回水 ',
            'month'=>'月回水',
            'other'=> '其他 ',
            "people" => '人数',
            'amount' => '回水金额',
            'num' => '回水笔数',
        ],
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $date_start = $this->request->getParam('date_start',date('Y-m-d'));
        $date_end = $this->request->getParam('date_end',date('Y-m-d'));

        $res = [
            "day" => ["people"=>0,"amount"=>0,"num"=>0],
            "day_deduct" => ["people"=>0,"amount"=>0,"num"=>0],
            "week" => ["people"=>0,"amount"=>0,"num"=>0],
            "week_deduct" => ["people"=>0,"amount"=>0,"num"=>0],
            "month" => ["people"=>0,"amount"=>0,"num"=>0],
            "month_deduct" => ["people"=>0,"amount"=>0,"num"=>0],
            "other" => ["people"=>0,"amount"=>0,"num"=>0],
        ];
        $deductQuery = DB::table('rebet_deduct')
            ->selectRaw('1')
            ->whereRaw('rebet_deduct.user_id = l2.user_id')
            ->whereRaw('rebet_deduct.order_number = l2.order_number')
            ->whereRaw('rebet_deduct.type = l2.deal_type')
            ->whereRaw('rebet_deduct.deduct_rebet > 0')
            ->toSql();
        $dealQuery = DB::table('funds_deal_log as l2')
            ->whereRaw('funds_deal_log.id = l2.id')
            ->whereRaw("exists({$deductQuery})");
        $cntQuery = (clone $dealQuery)->selectRaw('count(order_number)')->toSql();
        $amountQuery = DB::table('rebet_deduct')
            ->whereRaw('rebet_deduct.user_id = funds_deal_log.user_id')
            ->whereRaw('rebet_deduct.order_number = funds_deal_log.order_number')
            ->whereRaw('rebet_deduct.type = funds_deal_log.deal_type')
            ->whereRaw('rebet_deduct.deduct_rebet > 0')
            ->selectRaw('cast(sum(deduct_rebet)/100 as decimal(18,2))')
            ->toSql();
        $peopleQuery = (clone $dealQuery)->selectRaw('count(DISTINCT  user_id)')->toSql();

        //deal_type: 701-日回水，702-周回水，703-月回水, 其他回水-(109,113), 旧的回水统计：701
        $deal_types = [701,702,703,107,109,113];
        $query = DB::table('funds_deal_log')
            ->whereIn('deal_type', $deal_types)
            ->whereRaw("created>=? and created<=?", [$date_start . " 00:00:00", $date_end . " 23:59:59"])
            ->selectRaw("deal_type, cast(sum(deal_money)/100 as decimal(18,2)) as return_amount, count(DISTINCT funds_deal_log.id) as return_cnt, COUNT(DISTINCT funds_deal_log.user_id) people")
            ->addSelect([
                DB::raw("sum(($cntQuery)) as count_deduct_return"),
                DB::raw("cast(sum(($amountQuery)) as decimal(18,2)) as rebet_amount"),
                DB::raw("sum(($peopleQuery)) as count_deduct_people"),
            ])
            ->groupBy(['deal_type']);

        $data = $query->get()->toArray();
        if ($data) {
            foreach ($data as $item) {
                switch ($item->deal_type) {
                    case 701:
                        $index = "day";
                        break;
                    case 702:
                        $index = "week";
                        break;
                    case 703:
                        $index = "month";
                        break;
                    default:
                        $index = "other";
                        break;
                }
                $res[$index]['people'] += $item->people;
                $res[$index]['amount'] += $item->return_amount;
                $res[$index]['num'] += $item->return_cnt;
                if (in_array($item->deal_type, [701, 702, 703])) {
                    $res[$index . '_deduct']['people'] += $item->count_deduct_return;
                    $res[$index . '_deduct']['amount'] += $item->rebet_amount;
                    $res[$index . '_deduct']['num'] += $item->count_deduct_people;
                }
            }
        }
        return $this->lang->set(0, [], $res, []);
    }
};
