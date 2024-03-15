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
    const TITLE = '玩法数据设定--编辑信息';
    const DESCRIPTION = '';

    const PARAMS = [
        'id' => 'int(required) # 结构ID',
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
        if (isset($params['tags']) && isset($params['open'])) {
            return $this->lang->set(10012);
        }

        if (isset($params['tags'])) {

            $tags = DB::table('lottery_play_struct')->where('id', $params['id'])->value('tags');
            $tags = json_decode($tags, true);

            foreach ($tags as $k => $v) {
                foreach ($params['tags'] as $k2 => $v2) {
                    if (!isset($v2['nm']) || !isset($v2['tp'])) {
                        return $this->lang->set(10012, ['tags']);
                    }

                    if ($v['nm'] == $v2['nm']) {
                        if (count($v['vv']) != count($v2['tp'])) {
                            return $this->lang->set(10012, ['tags']);
                        }
                        $v['tp'] = $v2['tp'];
                        break;
                    }
                }
                $tags[$k] = $v;
            }
            $params['tags'] = $tags;
        }


        $vals = [];
        if (isset($params['buy_ball_num'])){
            $struct = DB::table('lottery_play_struct')
                ->select('is_ball_num')
                ->where('id', '=', $params['id'])
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

        /*============================================日志操作需要：提取原始数据====================================================*/
        $info = DB::table('lottery_play_struct')
            ->select('lottery_pid', 'model', 'name', 'group', 'buy_ball_num', 'play_text1', 'play_text2', 'tags')
            ->where('id', '=', $params['id'])
            ->get()
            ->first();
        $info = (array)$info;

        $data = DB::table('lottery')
            ->select('name')
            ->where('id', '=', $info['lottery_pid'])
            ->get()
            ->first();
        $data = (array)$data;
        /*================================================================================================*/


        $res = DB::update("UPDATE lottery_play_struct SET $vals WHERE id = {$params['id']}");
        if ($res !== false) {
            $this->logs($params, $data, $info);
            $pid = DB::table('lottery_play_struct')->where('id', $params['id'])->value('lottery_pid');
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
//        foreach ($lottery as $k => $v) {
//            $sql = "SELECT * FROM hall WHERE lottery_id = {$v['id']}";
//            $hall = $db->query($sql);
//            foreach ($hall as $k2 => $v2) {
//                $redis->del('lottery_play_struct_'.$v2['lottery_id'].'_'.$v2['id']);
//                $redis->incr('lottery_play_struct_ver_'.$v2['lottery_id'].'_'.$v2['id']);
//            }
//        }
        foreach ($lottery as $k => $v) {

            $hall = DB::table('hall')->where('lottery_id', $v['id'])->get(['lottery_id', 'id'])->toArray();
            $hall = array_map('get_object_vars', $hall);

            foreach ($hall as $k2 => $v2) {
                $res = $redis->del('lottery_play_struct_' . $v2['lottery_id'] . '_' . $v2['id']);
//                print_r('lottery_play_struct_'.$v2['lottery_id'].'_'.$v2['id'].' : '.$res);
//                echo "<pre/>";
                $res = $redis->incr('lottery_play_struct_ver_' . $v2['lottery_id'] . '_' . $v2['id']);
//                print_r($res);echo "<pre/>";
            }
        }
    }

    /**
     * @param $params 参数
     * @param $data 获取的彩种表的值
     * @param $info 获取的lottery_play_struct表的值
     * 日志操作
     */
    public function logs($params, $data, $info)
    {
        $str = "彩种名称:{$data['name']}/彩种模式:{$info['model']}/玩法:{$info['group']}/子玩法:{$info['name']}";

        if (isset($params['play_text1'])) {
            if (strcmp($params['play_text1'], $info['play_text1']) != 0) {
                (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '玩法开关', '玩法提示', '编辑', 1, "$str/[{$info['play_text1']}]更改为[{$params['play_text1']}]");
            }
            if (strcmp($params['play_text2'], $info['play_text2']) != 0) {
                (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '玩法开关', '中奖提示', '编辑', 1, "$str/[{$info['play_text2']}]更改为[{$params['play_text2']}]");
            }
        }


        if (isset($params['tags'])) {
            $info_info = json_decode($info['tags'], true);
            $sv = $params['tags'][0]['sv'];
            $tp = $params['tags'][0]['tp'];
            $str_xh = '';
            foreach ($tp as $key => $item) {
                if ($info_info[0]['tp'][$key] != $item) {
                    $new = "选号：" . $sv[$key] . "提示：" . $item;
                    $old = "选号：" . $info_info[0]['sv'][$key] . "提示：" . $info_info[0]['tp'][$key];
                    $str_xh .= "/[{$old}]更改为[{$new}]";
                }
            }
            if ($str_xh != '') {
                (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '玩法开关', '选号提示', '编辑', 1, $str . $str_xh);
            }
        }
    }
};