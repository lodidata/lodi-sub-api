<?php

use Utils\Www\Action;

return new class extends Action
{
    const TITLE = "交易记录列表类型";
    const DESCRIPTION = "交易记录列表类型";
    const TAGS = '交易记录';
    const QUERY = [
        "type" => "int(required,2) #类型 默认为1交易类型 2交易类型分类",
    ];
    const SCHEMAS = [
        [
            "id" => 'int() #ID',
            "name" => "string() #标题",
            "children" => [
                [
                    "id" => 'int() #ID',
                    "name" => "string() #标题",
                ]
            ],
        ],
    ];

    public function run()
    {
        $text1 =
            [
                [
                    "id" => 1,
                    "name" => $this->lang->text('Income'),
                    "children" => [
                        [
                            "id" => 101,
                            "name" => $this->lang->text('Online payment')
                        ],
                        [
                            "id" => 102,
                            "name" => $this->lang->text('Offline payment')
                        ],
                        [
                            "id" => 105,
                            "name" => $this->lang->text('activity')
                        ],
                        [
                            "id" => 106,
                            "name" => $this->lang->text('Manual deposit')
                        ],
                        [
                            "id" => 107,
                            "name" => $this->lang->text('Rebet money')
                        ],
                        [
                            "id" => 108,
                            "name" => $this->lang->text('Agent commission')
                        ],
                        [
                            "id" => 110,
                            "name" => $this->lang->text('Cancel order refund')
                        ],
                        [
                            "id" => 112,
                            "name" => $this->lang->text('Manual addmoney')
                        ],
                        [
                            "id" => 113,
                            "name" => $this->lang->text('Manual rebet money')
                        ],
                        [
                            "id" => 114,
                            "name" => $this->lang->text('Manual discount amount')
                        ],
                        [
                            "id" => 118,
                            "name" => $this->lang->text('Refund for withdrawal failure')
                        ],
                        /*[
                            "id" => 308,
                            "name" => $this->lang->text('Level reward')
                        ],
                        [
                            "id" => 309,
                            "name" => $this->lang->text('Level manual2')
                        ],
                        [
                            "id" => 310,
                            "name" => $this->lang->text('Level monthly reward')
                        ]*/
                    ]
                ],
                [
                    "id" => 2,
                    "name" => $this->lang->text('Pay'),
                    "children" => [
                        [
                            "id" => 118,
                            "name" => $this->lang->text('Withdrawal unfreezing')
                        ], [
                            "id" => 201,
                            "name" => $this->lang->text('Successful withdrawal')
                        ], [
                            "id" => 207,
                            "name" => $this->lang->text('Manual decrease balance')
                        ], [
                            "id" => 208,
                            "name" => $this->lang->text('Withdrawal under review')
                        ]
                    ]
                ],
                [
                    "id" => 3,
                    "name" => $this->lang->text('Exchange'),
                    "children" => [
                        [
                            "id" => 301,
                            "name" => $this->lang->text('Sub to master Wallet')
                        ],
                        [
                            "id" => 302,
                            "name" => $this->lang->text('Master to sub Wallet')
                        ],
                        [
                            "id" => 303,
                            "name" => $this->lang->text('Manual sub to master Wallet')
                        ],
                        [
                            "id" => 304,
                            "name" => $this->lang->text('Manual master to sub Wallet')
                        ]
                    ]
                ]
            ];


        $text2 = [
            "category" => [
                [
                    "name" => $this->lang->text('All'),
                    "id" => 0
                ], [
                    "name" => $this->lang->text('Income'),
                    "id" => 1
                ], [
                    "name" => $this->lang->text('Pay'),
                    "id" => 2
                ], [
                    "name" => $this->lang->text('Exchange'),
                    "id" => 3
                ]
            ],
            "type" => [
                [
                    "name" => $this->lang->text('All'),
                    "id" => 0
                ],
                [
                    "name" => $this->lang->text('payout lottery'),
                    "id" => 104
                ], [
                    "name" => $this->lang->text('Lottery bet'),
                    "id" => 202
                ],
                [
                    "name" => $this->lang->text('Online payment'),
                    "id" => 101
                ], [
                    "name" => $this->lang->text('Offline payment'),
                    "id" => 102
                ], [
                    "name" => $this->lang->text('withdraw'),
                    "id" => 201
                ], [
                    "name" => $this->lang->text('Withdrawal freeze'),
                    "id" => 208
                ], [
                    "name" => $this->lang->text('Withdrawal unfreezing'),
                    "id" => 118
                ],
                [
                    "id" => 110,
                    "name" => $this->lang->text('Cancel order refund')
                ],
                [
                    "name" => $this->lang->text('activity rebet'),
                    "id" => 107
                ],
               /* [
                    "name" => $this->lang->text('Sign in'),
                    "id" => 11
                ], [
                    "name" => $this->lang->text('luck draw'),
                    "id" => 12
                ],*/
               /*[
                    "name" => $this->lang->text('Agent commission'),
                    "id" => 108
                ],*/
                [
                    "id" => 301,
                    "name" => $this->lang->text('Sub to master Wallet')
                ],
                [
                    "id" => 302,
                    "name" => $this->lang->text('Master to sub Wallet')
                ],
            ]
        ];
        //$re = $this->request->getQueryParam('type', 2) == 1 ? json_decode($text2, true) : array_values(\Logic\Funds\DealLog::getDealLogTypes());
        $re = $this->request->getQueryParam('type', 2) == 1 ? $text2 : $text1;

        return $re;
    }

};