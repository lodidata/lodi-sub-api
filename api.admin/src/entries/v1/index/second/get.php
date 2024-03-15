<?php

use Logic\Admin\BaseController;
use Logic\Define\CacheKey;

return new class() extends BaseController
{
    const TITLE = '首页统计第二部分';
    const DESCRIPTION = '按分钟统计为10分钟一次，返回一天数据，小时返回一天数据，天：返回30天内容数据，不分今天昨天';
    const TAGS = '首页统计';
    const PARAMS = [
        'field' => 'string(required) #统计字段名称 online:实时在线人数，game:在玩人数，recharge:充值金额，withdraw:兑换统计',
        'type' => 'string(required) #统计类型 minute:分钟,hour:小时,day:天'
    ];
    const SCHEMAS = [
        'today' =>[
            'total' => 'flat #总数',
            'list' => [
                [
                    'day' => 'string(required) #时间 分钟、小时、天',
                    'field' => 'string(required) #名称为传入字段 值为对应值'
                ]
            ]
        ],
        'yesterday' =>[
            'total' => 'flat #总数',
            'list' => [
                [
                    'day' => 'string(required) #时间 分钟、小时、天',
                    'field' => 'string(required) #名称为传入字段 值为对应值'
                ]
            ]
        ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {
        $params = $this->request->getParams();
        $field = isset($params['field']) ? trim($params['field']) : 'online';
        $type = isset($params['type']) ? trim($params['type']) : 'minute';
        if(!in_array($field, ['online','game','recharge','withdraw','newRechargePeople','oldRechargePeople'])){
            $field = 'online';
        }
        if(!in_array($type, ['minute', 'hour', 'day'])){
            $type = 'minute';
        }

        $index = new \Logic\Admin\AdminIndex($this->ci);
        return $data = $index->second($field, $type);
    }

};
