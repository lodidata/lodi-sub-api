<?php

use Logic\Admin\Message as messageLogic;
use Model\Admin\Message as messageModel;
use Logic\Admin\BaseController;

return new class() extends BaseController {
//    const STATE       = \API::DRAFT;
    const TITLE = '本厅消息列表';
    const DESCRIPTION = 'type 1重要消息（import）,2,一般消息';

    const QUERY = [
        'id' => 'int #指定消息id',
        'title' => 'string()',
        'type' => 'int # 1 重要消息,2 一般消息 ',
        'admin_name' => 'string # 发布人',
        'start_time' => 'date()  #开始时间',
        'end_time' => 'date()  #结束时间',
        'page' => 'int() #default 1',
        'page_size' => 'int() #default 20'
    ];

    const PARAMS = [];
    const SCHEMAS = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        return [
            'online_deposit' => 0,
            'offline_deposit' => 0,
            'withdraw' => 0,
            'review' => 0
        ];
        try {
            // 线上
            $date1 = $this->redis->get('admin:UnreadNum1') ?? '1990-01-01 00:00:00';
            // 线下
            $date2 = $this->redis->get('admin:UnreadNum2') ?? '1990-01-01 00:00:00';
            // 提款
            $date3 = $this->redis->get('admin:UnreadNum3') ?? '1990-01-01 00:00:00';
            // 资料审核
            $date4 = $this->redis->get('admin:UnreadNum4') ?? '1990-01-01 00:00:00';

            $output['online_deposit'] = \DB::connection('slave')->table('funds_deposit')->where('created','>=',$date1)->where('money','>',0)->whereRaw("FIND_IN_SET('online',state)")
                ->where('status','=','pending')->count();

            $output['offline_deposit'] = \DB::connection('slave')->table('funds_deposit')->where('created','>=',$date2)->where('money','>',0)->whereRaw(" !FIND_IN_SET('online',state)")->where('pay_type','<>',0)
                ->where('status','=','pending')->count();

            $output['withdraw'] = \DB::connection('slave')->table('funds_deposit')->where('created','>=',$date3)
                ->where('status','=','pending')->count();

            $output['review'] = \Model\UserDataReview::where('created', '<=', $date4)
                ->where('status', '=', 0)->count();

            foreach ($output as &$v) {
                !$v && $v = '0';
            }
            return $output;
        } catch (\Exception $e) {
            return [
                'online_deposit' => 0,
                'offline_deposit' => 0,
                'withdraw' => 0,
                'review' => 0
            ];
        }
    }

};
