<?php

use Logic\Admin\BaseController;
use Logic\Define\CacheKey;

return new class() extends BaseController
{
    const TITLE = '首页统计第五部分';
    const DESCRIPTION = '';
    const TAGS = '首页统计';
    const SCHEMAS = [
        'left' => [
            'total' => [
                'register_total' => 'float #总注册数',
                'register_yesterday_total' => 'int #昨天注册总数',
                'yesterday_login_total' => 'int #昨天总在线数',
            ],
            'list' => [
                'today' => [
                    [
                        'platform' => 'int #平台类型 1:pc,2:h5,3:ios,4:android',
                        'num' => 'int #登录数量',
                        'parent' => 'float #登录数量百分比%'
                    ]
                ],
                'yesterday' => [
                    [
                        'platform' => 'int #平台类型 1:pc,2:h5,3:ios,4:android',
                        'num' => 'int #登录数量',
                        'parent' => 'float #登录数量百分比%'
                    ]
                ],
            ]
        ],
        'right' => [
            'list' => [
                'today' => [
                    'online' => [
                        'money' => 'float #线上充值金额',
                        'parent' => 'float #线上充值百分比%'
                    ],
                    'offline' => [
                        'money' => 'float #线下充值金额',
                        'parent' => 'float #线下充值百分比%'
                    ],
                ],
                'yesterday' => [
                    'online' => [
                        'money' => 'float #线上充值金额',
                        'parent' => 'float #线上充值百分比%'
                    ],
                    'offline' => [
                        'money' => 'float #线下充值金额',
                        'parent' => 'float #线下充值百分比%'
                    ],
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
        $index = new \Logic\Admin\AdminIndex($this->ci);
        return $data = $index->five();
    }

};
