<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/2
 * Time: 15:08
 * 获取投注列表（重新序列化数据）
 */

use Utils\Www\Action;

return new class extends Action
{
    const TITLE = '投注列表信息';
    const DESCRIPTION = '获取详细的投注列表序列化信息';
    const TAGS = "彩票";
    const PARAMS = [
        'data' =>[
            'id' => 'string(required)   #彩票大类id',
            'play_id' => 'string(required)    #玩法id ',
            'num' => 'string(required) #投注值 格式num=0|1,2 如果一组数据有一个单元没有值的情况，用,号占位如1|3,,1|9'
        ]
    ];
    const STATEs = [
//        \Las\Utils\ErrorCode::UNREALIZED_OR_UNABLE => '开奖结果未出炉'
    ];
    const SCHEMAS = [
        [
            ["title"=>"万位", "value"=>["2"]],
            ["title"=>"千位", "value"=>["6"]]
        ],
    ];

    protected $ipProtect = false;

    public function run()
    {
        $req = $this->request->getParams();
        if (!isset($req['data'])) {
            return $this->lang->set(10);
        }
        $data = $req['data'];
        $logic = new \LotteryPlay\Logic();
        $result = [];
        foreach ($data as $key => $value) {
            $res = $logic->getPretty($value['id'], $value['play_id'], $value['num']);
            /*
             * 测试数据
             * */
           /* if (!empty($res)){
                $res[0]['value'][0] = str_replace('#','',$res[0]['value'][0]);
            }*/
            $result[] = $res;
        }
        return $result;


    }
};