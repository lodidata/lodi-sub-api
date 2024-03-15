<?php
/**
 *
 * {"id": "113", "sort": 111, "tags": [{
 * "nm": "u5927u5c0fu5355u53cc",
 * "tp": ["1", "2", "3", "4"]
 * }]}
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE = '玩法数据设定--开关';
    const DESCRIPTION = '';

    const PARAMS = [
        'id' => 'int(required) # 结构ID',
        'open' => 'int() # 状态，1 开启，0 关闭',
        'sort' => 'int() # 排序值，值大靠前',
        // 'tags' => [
        //     0 => ['nm' => 'string() 玩法名称', 'tp' => "array #提示语[1, 2, 3]"],
        // ],
    ];

//前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        (new BaseValidate([
            ['id', 'require|isPositiveInteger'],
            ['open_play_num', 'isPositiveInteger|egt:1'],
            ['open', 'in:0,1'],
        ],
            [],
            ['open_play_num.egt' => '修改失败，至少保留一个玩法']
        ))->paramsCheck('', $this->request, $this->response);

        $params = $this->request->getParams();
        $id=$params['id'];

        $vals = [];
        if (isset($params['buy_ball_num'])){
            $struct = DB::table('lottery_play_struct')
                ->select('is_ball_num')
                ->where('id', '=', $id)
                ->first();
            if ($struct->is_ball_num == 1){
                return $this->lang->set(10013,['该玩法购球数不允许修改']);
            }
        }
        if (isset($params['buy_ball_num']) && $params['buy_ball_num'] == "") $params['buy_ball_num'] = 0;
        foreach ($params as $field => $val) {
            if (in_array($field, ['open', 'sort', 'tags', 'play_text1', 'play_text2', 'buy_ball_num'])) {
                $field = "`$field`";
            } else {
                continue;
            }

            if ($field == '`tags`') {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE);
            }

            $vals[] = "$field = '{$val}'";
        }
        $vals = join(',', $vals);
        if (empty($vals)) {
            return $this->lang->set(10010);
        }

//        $res = DB::update("UPDATE lottery_play_struct SET $vals WHERE id = {$params['id']}");


        $struct=\Model\Admin\LotteryPlayStruct::find($id);
        unset($params['id']);
        foreach ($params as $key=>$value) {
            $struct->$key=$value;
        }
        $struct->logs_type='关闭/开启';
        $res=$struct->save();


        if ($res !== false) {
            $pid = DB::table('lottery_play_struct')->where('id', $id)->value('lottery_pid');
            $this->_removeCache($pid);
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }


    protected function _removeCache($lotteryPid)
    {

        $redis = $this->redis;
        $lottery = DB::table('lottery')->where('pid', $lotteryPid)->get(['id'])->toArray();
        $lottery = array_map('get_object_vars', $lottery);
        foreach ($lottery as $k => $v) {

            $hall = DB::table('hall')->where('lottery_id', $v['id'])->get(['lottery_id', 'id'])->toArray();
            $hall = array_map('get_object_vars', $hall);

            foreach ($hall as $k2 => $v2) {
                $res = $redis->del('lottery_play_struct_ver_' . $v2['lottery_id'] . '_' . $v2['id']);
                $res = $redis->incr('lottery_play_struct_ver_' . $v2['lottery_id'] . '_' . $v2['id']);
            }
        }
    }

};