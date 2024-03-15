<?php
use Utils\Www\Action;
/**
 * 返回包括可参加活动、支持的银行列表、最低、最高充值额度、提示等信息
 */
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "获取银行列表，及存款方式";
    const TAGS = "充值提现";
    const SCHEMAS = [
        "type" => [
            "name" => "string(required) #名称",
            "id"   => "string(required) #id"
        ],
        "list" => [
            "id" => "id(required) #id",
            "bank_name" => "string(required) #银行名称",
            "name" => "string(required) #银行名称",
            "code" => "string(required) #银行英文代码",
            "logo" => "string(required) #银行图标",
        ]
    ];

    public function run()
    {
        $customerData = [
            'type' => [
                ['name' => $this->lang->text("Online bank transfer"), 'id' => '1'],
                ['name' => $this->lang->text("ATM teller machine"), 'id' => '2'],
                ['name' => $this->lang->text("ATM cash deposit"), 'id' => '3'],
                ['name' => $this->lang->text("Bank counter"), 'id' => '4'],
                ['name' => $this->lang->text("Mobile transfer"), 'id' => '5'],
                ['name' => $this->lang->text("Alipay transfer"), 'id' => '6'],
                ['name' => $this->lang->text("Wechat transfer"), 'id' => '7'],
                ['name' => $this->lang->text("TenPay transfer"), 'id' => '8'],
                ['name' => $this->lang->text("Jingdong transfer"), 'id' => '9'],
            ]
        ];
//        print_r($this->request->getParam('page'));exit;
//        FIND_IN_SET('chat', lottery.state)
        $list= \Model\Bank::where('type', '1')->whereRaw('FIND_IN_SET("enabled",status)')
                        ->select(['id','code','h5_logo as logo'])
                        ->paginate(999)
                        ->toArray()['data'];
        foreach ($list as &$v){
            $v['bank_name'] = $this->lang->text($v['code']);
            $v['name'] = $v['bank_name'];
            $v['logo'] = showImageUrl($v['logo']);
        }
        unset($v);
        $customerData['list'] = $list;
        return $customerData;

    }
};
