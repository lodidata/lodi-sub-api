<?php

use Logic\Admin\Log;
use Logic\Admin\BaseController;
use Logic\Define\CacheKey;
use Logic\Set\SystemConfig;

/**
 * 更新活动
 */
return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = null)
    {
        $this->checkID($id);
        $params               = $this->request->getParams();
        $params['begin_time'] = date('Y-m-d H:i:s', strtotime($params['begin_time']));
        $params['end_time']   = date('Y-m-d H:i:s', strtotime($params['end_time']));
        $title                = isset($params['title']) ? $params['title'] : '';
        $start_time           = isset($params['begin_time']) ? $params['begin_time'] : '';
        $end_time             = isset($params['end_time']) ? $params['end_time'] : '';

        //开始时间和结束时间验证
        if ($start_time > $end_time) {
            return $this->lang->set(10032);
        }

        $type_id = isset($params['type_id']) ? $params['type_id'] : 0;
        if (!$type_id) {
            return $this->lang->set(886, ['活动类型id不能为空']);
        }

        $send_type     = (int)isset($params['send_type']) ? $params['send_type'] : 0;
        $prize_value   = isset($params['rule']) ? replaceImageUrl($params['rule']) : '';
        $prize_max     = isset($params['send_max']) ? $params['send_max'] : '';
        $prize_bet_max = isset($params['send_bet_max']) ? $params['send_bet_max'] : 0;
        $withdraw_bet  = isset($params['withdraw_require_val']) ? $params['withdraw_require_val'] : '';
        $status        = isset($params['status']) ? $params['status'] : '';
        $sort          = isset($params['sort']) ? $params['sort'] : '';
        $img           = isset($params['cover']) ? replaceImageUrl($params['cover']) : '';
        $desc          = isset($params['description']) ? mergeImageUrl($params['description']) : '';
        $issue_mode    = isset($params['issue_mode']) ? $params['issue_mode'] : '';
        $template_id   = isset($params['template_id']) ? (int)$params['template_id'] : '';
        $vender_type   = isset($params['vender_type']) ? $params['vender_type'] : '0';
        $bind_info     = isset($params['bind_info']) ? $params['bind_info'] : '';
        $content       = isset($params['content']) ? $params['content'] : '';
        $content_type  = isset($params['content_type']) ? $params['content_type'] : '';
        $link          = isset($params['link']) ? $params['link'] : '';

//        $give_time = isset($params['give_time']) ? $params['give_time'] : 0;
        $luckydraw_condition = isset($params['luckydraw_condition']) ? $params['luckydraw_condition'] : 0;
        $limit_times         = isset($params['limit_times']) ? $params['limit_times'] : 0;

        $issue_time     = isset($params['issue_time']) ? $params['issue_time'] : '';
        $issue_day      = isset($params['issue_day']) ? $params['issue_day'] : 0;
        $issue_cycle    = isset($params['issue_cycle']) ? $params['issue_cycle'] : '';
        $give_condition = isset($params['give_condition']) ? $params['give_condition'] : '';
        $give_date      = isset($params['give_date']) ? $params['give_date'] : '';
        $state          = isset($params['state']) ? $params['state'] : '';

        $msg_title        = isset($params['msg_title']) ? $params['msg_title'] : '';     //消息标题
        $msg_content      = isset($params['msg_content']) ? $params['msg_content'] : '';     //消息内容
        $give_away        = isset($params['give_away']) ? $params['give_away'] : 0;     //赠送条件：1-指定用户，2-指定等级，3-批量赠送
        $phone_list       = isset($params['phone_list']) ? $params['phone_list'] : '';     //指定手机号列表
        $user_level       = isset($params['user_level']) ? $params['user_level'] : '';     //指定用户等级列表
        $batch_url        = isset($params['batch_url']) ? $params['batch_url'] : '';     //批量上传时的文件路径
        $give_amount      = isset($params['give_amount']) ? $params['give_amount'] : 0;     //赠送的彩金数量
        $dm_num           = isset($params['dm_num']) ? $params['dm_num'] : 0;     //打码量
        $notice_type      = isset($params['notice_type']) ? $params['notice_type'] : '';     //通知类型：1-短信，2-邮箱，3-站内信息通知
        $is_now_give      = isset($params['is_now_give']) ? $params['is_now_give'] : 0;     //是否立即赠送: 1-是，0-否
        $give_amount_time = isset($params['give_amount_time']) ? $params['give_amount_time'] : 0;     //赠送彩金时间
        $game_type        = isset($params['game_type']) ? $params['game_type'] : '';
        $limit_value      = isset($params['limit_value']) ? $params['limit_value'] : '';
        $blacklist_url    = isset($params['blacklist_url']) ? $params['blacklist_url'] : '';

        $send_times           = isset($params['send_times']) ? $params['send_times'] : null;    //活动赠送次数
        $apply_times          = isset($params['apply_times']) ? $params['apply_times'] : null;  //可发起申请次数
        $condition_recharge   = isset($params['condition_recharge']) ? $params['condition_recharge'] : 0;  //申请条件--是否有充值
        $condition_user_level = isset($params['condition_user_level']) ? $params['condition_user_level'] : null;  //申请条件--会员等级

        //做个兼容
        if (empty($prize_bet_max)) {
            $prize_bet_max = 0;
        }

        //做个兼容
        if (empty($prize_bet_max)) {
            $prize_bet_max = 0;
        }

        //如果是批量赠送彩金，判读一些参数
        if ($template_id == 12) {
            return createRsponse($this->response, 200, -2, '彩金活动已经迁至现金管理菜单中');
        }


        $active = new Logic\Activity\Activity($this->ci);

        //幸运轮盘
        if ($template_id == 6) {
            $active->lucky($limit_times, $prize_value);
        }
        if (in_array($template_id, [8, 9])) {
            if (!is_numeric($issue_day) || $issue_day <= 0) {
                return createRsponse($this->response, 200, -2, '反水天数不能为空');
            }
        }
        if ($template_id == 8 && !empty($issue_time)) {
            $this->redis->set(CacheKey::$perfix['week_issue_time'], $issue_time);
            $this->redis->set(CacheKey::$perfix['week_issue_day'], $issue_day);
        }

        if ($template_id == 9 && !empty($issue_time)) {
            $this->redis->set(CacheKey::$perfix['month_issue_time'], $issue_time);
            $this->redis->set(CacheKey::$perfix['month_issue_day'], $issue_day);
        }

        if ($template_id == 11 && !empty($issue_time)) {
            $this->redis->set(CacheKey::$perfix['recharge_week_issue_time'], $issue_time);
            $this->redis->set(CacheKey::$perfix['recharge_week_issue_day'], $issue_day);
        }

        if ($template_id == 4 && empty($link)) {
            $config = SystemConfig::getModuleSystemConfig('market');
            $link = $config['service'] ?? '';
        }

        //更新
        $update_data = [];
        if ($title) {
            $update_data['name']  = $title;
            $update_data['title'] = $title;
        }


        $start_time    && $update_data['begin_time']     = $start_time;
        $start_time    && $update_data['begin_time']     = $start_time;
        $end_time      && $update_data['end_time']       = $end_time;
        $desc          && $update_data['description']    = $desc;
        $update_data['link']                             = $link;
        $img           && $update_data['cover']          = $img;
        $vender_type   && $update_data['vender_type']    = $vender_type;
        $status        && $update_data['status']         = $status;
        $state         && $update_data['state']          = $state;
        $content       && $update_data['content']        = $content;
        $content_type  && $update_data['content_type']   = $content_type;
        $type_id       && $update_data['active_type_id'] = $type_id;
        $blacklist_url && $update_data['blacklist_url']  = replaceImageUrl($blacklist_url);
        
        if(!empty($sort) || $sort == "0") {
            $update_data['sort'] = intval($sort);
        }

        $send_times && $update_data['send_times'] = $send_times;
        $apply_times && $update_data['apply_times'] = $apply_times;
        $update_data['condition_recharge'] = $condition_recharge;
        $condition_user_level && $update_data['condition_user_level'] = $condition_user_level;

        $update_data2 = [];
        $prize_value && $update_data2['rule'] = $prize_value;
        $withdraw_bet && $update_data2['withdraw_require_val'] = $withdraw_bet;
        $issue_mode && $update_data2['issue_mode'] = $issue_mode;
        $prize_max && $update_data2['send_max'] = $prize_max;
        $prize_bet_max && $update_data2['send_bet_max'] = $prize_bet_max;
        $update_data2['bind_info'] = $bind_info;

        $update_data2['luckydraw_condition'] = $luckydraw_condition;
        $update_data2['limit_times']         = $limit_times;
        $give_condition && $update_data2['give_condition'] = $give_condition;
        $give_date && $update_data2['give_date'] = $give_date;
        $issue_time && $update_data2['issue_time'] = $issue_time;
        $issue_day && $update_data2['issue_day'] = $issue_day;
        $issue_cycle && $update_data2['issue_cycle'] = $issue_cycle;
        $game_type && $update_data2['game_type'] = $game_type;
        $limit_value && $update_data2['limit_value'] = $limit_value;
        $send_type && $update_data2['send_type'] = $send_type;


        $params['send_type'] = isset($params['send_type']) && (is_null($params['send_type']) || $params['send_type'] == 'null') ? 0 : $params['send_type'];

        if (isset($params['rebet_multiple']) && in_array($template_id, [8, 9])) {
            $validate = new \lib\validate\BaseValidate([
                'rebet_multiple' => 'integer|egt:0|elt:99',
            ], [], [
                "rebet_multiple" => "流水倍数",
            ]);
            $validate->paramsCheck('', $this->request, $this->response);
        }

        /*============================日志操作代码================================*/
        $strs = $active->activeAdminLog($id, $params, $template_id);
        /*============================================================*/
        DB::beginTransaction();
        try {
            //用户申请限制用户
            if ($state == 'apply' && $blacklist_url) {
                \DB::table('active_apply_blacklist')->where('active_id', $id)->delete();
                $this->inputFile($params['blacklist_url'], $id);
            }
            \Model\Admin\Active::where('id', $id)->update($update_data);
            \Model\Admin\ActiveRule::where('active_id', $id)->update($update_data2);
            $type = "编辑";
            $str  = $strs['str'];
            if (in_array($template_id, [8, 9])) {
                $type = $template_id == 9 ? 'month' : 'week';
                $data['rebet_config'] = $tmp['rebet_config'] = \Logic\Set\SystemConfig::getModuleSystemConfig('rebet_config');
                $data['rebet_config'][$type] = intval($params['rebet_multiple']);
                $data['rebet_config'][$type.'_gt_zero'] = boolval($params['rebet_gt_zero_switch']);
                $confg = new \Logic\Set\SystemConfig($this->ci);
                $confg->updateSystemConfig($data, $tmp);
            }
            $str  = $strs['str']." 活动id：".$id." 活动名称：".$title;

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return $this->lang->set(886, [$e->getMessage()]);
        }
        /*============================日志操作代码================================*/
        (new Log($this->ci))->create(null, null, Log::MODULE_ACTIVE, '活动列表', '活动列表', $type, 1, $str);
        /*============================================================*/

        return [];


    }

    /*
     * @导入用户申请限制用户
     * @http://zentao.wulintop.com:8080/zentao/task-view-1090.html
     * $inputFileName 文件地址
     * $lastId 活动id
     * */
    function inputFile($inputUrl, $lastId)
    {
        //获取文件数据、写入数据表
        $str  = file_get_contents($inputUrl);
        $data = explode("\n", $str);
        unset($data[0]);

        $array = [];
        foreach ($data as $k => $v) {
            if (empty($v)) continue;
            $v = iconv('gb2312', 'utf-8', $v);
            $v = preg_split('/(?:"[^"]*"|)\K\s*(,\s*|$)/', $v);
            if (!is_numeric($v[0])) continue;
//            //已存在跳出本次循环
//            $count =  DB::table('active_apply_blacklist')->where('user_id',$rowData[0])->where('active_id',$lastId)->count();
//            if ($count) continue;
            //组装数据

            $array[$k]['user_id']   = $v[0];
            $array[$k]['user_name'] = $v[1];
            $array[$k]['active_id'] = $lastId;
            $array[$k]['created']   = date('Y-m-d H:i:s', time());
        }
        DB::table('active_apply_blacklist')->insert($array);
        return true;
    }
};
