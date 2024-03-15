<?php
use Utils\Www\Action;
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "提问类型列表";
    const DESCRIPTION = "提问类型列表";
    const TAGS = '客服';
    const SCHEMAS = [
           [
               "id" => "int() #对应id",
               "name" => "string() #问题名称"
           ]
   ];

    public function run() {
        $result = [
            ["id" => 1, "name" => $this->lang->text("Recharge problem")],
            ["id" => 2, "name" => $this->lang->text("Game problems")],
            ["id" => 3, "name" => $this->lang->text("Quota conversion problem")],
            ["id" => 4, "name" => $this->lang->text("Raise questions")],
            ["id" => 5, "name" => $this->lang->text("Feedback")],
            ["id" => 6, "name" => $this->lang->text("Other")],

        ];
        return $result;
        /*return json_decode('[{
                            "id": 1,
                            "name": "充值问题"
                        }, {
                            "id": 2,
                            "name": "游戏问题"
                        }, {
                            "id": 3,
                            "name": "额度转换问题"
                        }, {
                            "id": 4,
                            "name": "提现问题"
                        }, {
                            "id": 5,
                            "name": "反馈"
                        }, {
                            "id": 6,
                            "name": "其他"
                        }]');*/
    }
};