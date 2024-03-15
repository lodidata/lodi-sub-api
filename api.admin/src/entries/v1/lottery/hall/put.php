<?php
/*
 * 彩票-厅设置，回水设置移动到lottery/hall/rebate/put.php了
 * @author Taylor 2019-01-12
 */
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;
use Model\Admin\Hall as hallModel;

return new class() extends BaseController {
    const TITLE = '彩票-厅设置';
    //{"type":"base","id":1,"lottery_id":2,"hall_name":"回水厅","min_balance":"2.00","rebot_min":5000,"rebot_max":1000000,"rebot_list":"13156664891,15986624361,15645682122,13566985215,13599487351,15964453855,15633103188,18077342391,13250302165,13056453902,13026653901,18977643625,15060776389,13152056325,13159862022,18025316235,18956503425,13256085679,13133950641,15033690751,15836114208,15980542033,15988322911,15933487620,15844600856,13022956488,13256258920,15642236502,151326565490,18956894071,15634895550,15145163528,15640382310,18923285046,13156054258,15188836559,15056277354","rebet_desc":"最高回水18%","room":[{"id":1,"room_name":"VIP-01房","number":106},{"id":2,"room_name":"VIP-02房","number":103},{"id":3,"room_name":"VIP-03房","number":170},{"id":4,"room_name":"VIP-04房","number":186}]}
    const PARAMS = [
        'id' => 'int(require) #厅id',
        'lottery_id' => 'int(require) #彩种id',
        'hall_name' => 'string(require) #厅名称',
//        'hall_level' => 'int #厅类型1 回水厅，2 保本厅，3 高赔率厅 4 PC房 5 传统 6 直播',
        'min_balance' => 'float(require) #最小余额',
        'rebot_min' => 'int(require) #机器人最小下注',
        'rebot_max' => 'int(require) #机器人最大下注',
        'rebot_list' => 'string() #机器人列表',
        'rebet_desc' => 'string() #厅说明',
        'room' => 'string() #房间列表'
    ];
    const SCHEMAS     = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize',
    ];

    public function run($id)
    {
        $params = $this->request->getParams();
        $levelArray = [
            '1' => '回水厅',
            '2' => '保本厅',
            '3' => '高赔率厅',
            '4' => 'PC房',
            '5' => '传统',
            '6' => '直播',
        ];
        (new BaseValidate([
            'id'          => 'require|isPositiveInteger',
            'lottery_id'  => 'require|isPositiveInteger',
            'hall_name'   => 'require|length:0,64',
            'rebet_desc'  => 'length:0,64',
            'min_balance' => 'require|float',
            'rebot_list'  => 'require|length:0,1024',
            'rebot_min'   => 'require|lt:rebot_max|max:10',
            'rebot_max'   => 'require|gt:rebot_min|max:10',
            'room'        => 'require|array',
        ]))->paramsCheck('', $this->request, $this->response);

        $hall = hallModel::where('lottery_id', $params['lottery_id'])->find($id);
        if (!$hall)
            return $this->lang->set(10015);

        DB::beginTransaction();
        try {
            $hall->hall_name = $params['hall_name'];
            $hall->rebet_desc = isset($params['rebet_desc']) ? $params['rebet_desc'] : '';
            $hall->min_balance = $params['min_balance'];
            $hall->rebot_list = $params['rebot_list'];
            $hall->rebot_min = $params['rebot_min'];
            $hall->rebot_max = $params['rebot_max'];
            $hall->save();

            if (count($params['room'])) {
                foreach ($params['room'] as $v) {
                    $res = DB::table('room')->where('id', $v['id'])->update(['room_name' => $v['room_name']]);
                }
            }
            /*===日志操作代码====*/
            $info = DB::table('lottery')->select('pid', 'name')
                ->where('id', '=', $params['lottery_id'])
                ->get()->first();
            $info = (array)$info;

            $data_type = DB::table('lottery')->select('name')
                ->where('id', '=', $info['pid'])
                ->get()->first();
            $data_type = (array)$data_type;

            $sta = $res !== false ? 1 : 0;

            (new Log($this->ci))->create(
                null, null, Log::MODULE_LOTTERY, '厅设置', '基础设置', '编辑', $sta,
                "彩种类型:{$data_type['name']}/彩种名称:{$info['name']}/厅类型:{$levelArray[$hall->hall_level]}"
            );
            /*****************/
            DB::commit();
            return $this->lang->set(0);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->lang->set(-2);
        }
    }
};
