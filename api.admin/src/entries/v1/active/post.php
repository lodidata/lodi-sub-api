<?php

use Logic\Admin\Log;
use Logic\Admin\BaseController;
use Logic\Define\CacheKey;
use Logic\Set\SystemConfig;

/**
 * 新增活动
 */
return new class () extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];


    public function run()
    {
        $params = $this->request->getParams();
        $params['begin_time'] = date('Y-m-d H:i:s', strtotime($params['begin_time']));
        $params['end_time'] = date('Y-m-d H:i:s', strtotime($params['end_time']));
        $title = isset($params['title']) ? $params['title'] : '';
        $start_time = isset($params['begin_time']) ? $params['begin_time'] : '';
        $end_time = isset($params['end_time']) ? $params['end_time'] : '';
        $language_id = isset($params['language_id']) ? intval($params['language_id']) : 1;

        //开始时间和结束时间验证
        if ($start_time > $end_time) {
            return $this->lang->set(10032);
        }

        $type_id = isset($params['type_id']) ? $params['type_id'] : 0;
        if (!$type_id) {
            return $this->lang->set(886, ['活动类型id不能为空']);
        }

        $prize_type = (int) isset($params['send_type']) ? $params['send_type'] : 0;
        $prize_value = isset($params['rule']) ? replaceImageUrl($params['rule']) : '';
        $prize_max = isset($params['send_max']) ? $params['send_max'] : "0.00";
        $prize_bet_max = isset($params['send_bet_max']) ? $params['send_bet_max'] : 0;
        $withdraw_bet = isset($params['withdraw_require_val']) ? $params['withdraw_require_val'] : 0;
        $status = isset($params['status']) ? $params['status'] : '';
        $sort = isset($params['sort']) ? $params['sort'] : 0;
        $img = isset($params['cover']) ? replaceImageUrl($params['cover']) : '';
        $desc = isset($params['description']) ? mergeImageUrl($params['description']) : '';
        $issue_mode = isset($params['issue_mode']) ? $params['issue_mode'] : '';
        $template_id = isset($params['template_id']) ? (int) $params['template_id'] : '';
        $vender_type = isset($params['vender_type']) ? $params['vender_type'] : '0';
        $bind_info = isset($params['bind_info']) ? $params['bind_info'] : '';
        $content = isset($params['content']) ? $params['content'] : '';
        $content_type = isset($params['content_type']) ? $params['content_type'] : 1;
        $link = isset($params['link']) ? $params['link'] : '';

        $luckydraw_condition = isset($params['luckydraw_condition']) ? $params['luckydraw_condition'] : 0;
        $limit_times = isset($params['limit_times']) ? $params['limit_times'] : 0;

        $issue_time = isset($params['issue_time']) ? $params['issue_time'] : null;
        $issue_day = isset($params['issue_day']) ? $params['issue_day'] : 0;
        $issue_cycle = isset($params['issue_cycle']) ? $params['issue_cycle'] : null;
        $give_condition = isset($params['give_condition']) ? $params['give_condition'] : null;
        $give_date = isset($params['give_date']) ? $params['give_date'] : null;
        $state = isset($params['state']) ? $params['state'] : '';

        $msg_title = isset($params['msg_title']) ? $params['msg_title'] : ''; //消息标题
        $msg_content = isset($params['msg_content']) ? $params['msg_content'] : ''; //消息内容
        $give_away = isset($params['give_away']) ? $params['give_away'] : 0; //赠送条件：1-指定用户，2-指定等级，3-批量赠送
        $phone_list = isset($params['phone_list']) ? trim($params['phone_list']) : ''; //指定手机号列表
        $user_level = isset($params['user_level']) ? trim($params['user_level']) : ''; //指定用户等级列表
        $batch_url = isset($params['batch_url']) ? $params['batch_url'] : ''; //批量上传时的文件路径
        $give_amount = isset($params['give_amount']) ? $params['give_amount'] : 0; //赠送的彩金数量
        $dm_num = isset($params['dm_num']) ? $params['dm_num'] : 0; //打码量
        $notice_type = isset($params['notice_type']) ? $params['notice_type'] : ''; //通知类型：1-短信，2-邮箱，3-站内信息通知
        $is_now_give = isset($params['is_now_give']) ? $params['is_now_give'] : 0; //是否立即赠送: 1-是，0-否
        $give_amount_time = !empty($params['give_amount_time']) ? $params['give_amount_time'] : "2000-01-01 00:00:00"; //赠送彩金时间
        $game_type = isset($params['game_type']) ? $params['game_type'] : null;
        $limit_value = isset($params['limit_value']) ? $params['limit_value'] : null;
        $blacklist_url = isset($params['blacklist_url']) ? $params['blacklist_url'] : null;

        $send_times = isset($params['send_times']) ? $params['send_times'] : null; //活动赠送次数
        $apply_times = !empty($params['apply_times']) ? $params['apply_times'] : 0; //可发起申请次数
        $condition_recharge = isset($params['condition_recharge']) ? $params['condition_recharge'] : 0; //申请条件--是否有充值
        $condition_user_level = isset($params['condition_user_level']) ? $params['condition_user_level'] : ''; //申请条件--会员等级

        if (empty($prize_bet_max)) {
            $prize_bet_max = 0;
        }

        if (empty($prize_bet_max)) {
            $prize_bet_max = 0;
        }

        //如果是批量赠送彩金，判读一些参数
        if ($template_id == 12) {
            return createRsponse($this->response, 200, -2, '彩金活动已经迁至现金管理菜单中');
        }
        if ($state == 'apply' && $params['blacklist_url']){
            $array     = explode(".",$params['blacklist_url']);
            $file_type = end($array);
            $true_type = ['csv'];
            if (!in_array($file_type,$true_type)) return createRsponse($this->response, 200, -2, '上传文件类型错误！');
        }

        // if (in_array($template_id, [1, 2, 3, 5, 6, 7, 8, 9, 11, 14])) {
        //     if ($vender_type == 3) {
        //         $sql = "select id from active where type_id = $template_id";
        //     } else {
        //         $sql = "select id from active where type_id = $template_id and (vender_type = 3 or vender_type = $vender_type)";
        //     }
        //     if ($template_id == 11 && $give_condition) {
        //         $sql = "select id from active_rule where template_id = {$template_id} and give_condition = $give_condition";
        //     }
        //     if (DB::select($sql)) {
        //         return createRsponse($this->response, 200, -2, '该种类型活动已经存在，请编辑或删除后再新增！');

        //     }
        // }

        // 限制不同类型活动添加数量，对充值类型不进行判断
        $templateCount = 0;
        if (in_array($template_id, [11, 13])) {
            if ($give_condition) { //限制同条件只能一个
                $templateCount = DB::table('active_rule')->where('template_id', $template_id)->where('give_condition', $give_condition)->count();
            }
        } else { //限制只能一个
            if (!in_array($template_id, [4, 10])) {
                $templateCount = DB::table('active_rule')->where('template_id', $template_id)->count();
            }
        }
        if ($templateCount > 0) {
            return createRsponse($this->response, 200, -2, '该种类型活动已经存在，请编辑或删除后再新增！');
        }

        $active = new Logic\Activity\Activity($this->ci);

        //幸运轮盘
        if ($template_id == 6) {
            $active->lucky($limit_times, $prize_value);
        }
        //电子 3次充值
        if ($template_id == 7) {
            try {
                $active->slot($prize_value);
            } catch (\Exception $e) {
                return $this->lang->set(886, [$e->getMessage()]);
            }
        }
        if (in_array($template_id, [8, 9])) {
            if (!is_numeric($issue_day) || $issue_day <= 0) {
                return createRsponse($this->response, 200, -2, '反水天数不能为空');
            }
        }
        if (isset($params['rebet_multiple']) && in_array($template_id, [8, 9])) {
            $validate = new \lib\validate\BaseValidate([
                'rebet_multiple' => 'integer|egt:0|elt:99',
            ], [], [
                "rebet_multiple" => "流水倍数",
            ]);
            $validate->paramsCheck('', $this->request, $this->response);
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

        $userId = $this->playLoad['uid'];
        $userName = $this->playLoad['nick'];
        $lastId = DB::table('active')->insertGetId([
            'vender_type' => $vender_type,
            'language_id' => $language_id,
            'sort' => $sort,
            'status' => $status,
            'state' => $state,
            'cover' => $img,
            'link' => $link,
            'description' => $desc,
            'end_time' => $end_time,
            'begin_time' => $start_time,
            'type_id' => $template_id,
            'title' => $title,
            'name' => $title,
            'created_uid' => $userId,
            'created_user' => $userName,
            'content' => $content,
            'content_type' => $content_type,
            'active_type_id' => $type_id,
            'send_times' => $send_times,
            'apply_times' => $apply_times,
            'condition_recharge' => $condition_recharge,
            'condition_user_level' => $condition_user_level,
            'blacklist_url' => replaceImageUrl($blacklist_url),
        ]);
        //用户申请限制用户
        if ($state == 'apply' && isset($params['blacklist_url']) && $params['blacklist_url']){
            $this->inputFile($params['blacklist_url'],$lastId);
        }

        $ruleData = array(
            'template_id' => $template_id,
            'active_id' => $lastId,
            'rule' => $prize_value,
            'withdraw_require' => "times",
            'withdraw_require_val' => !empty($withdraw_bet) ? $withdraw_bet : 0,
            'send_type' => $prize_type,
            'send_max' => $prize_max,
            'send_bet_max' => $prize_bet_max,
            'issue_mode' => $issue_mode,
            'bind_info' => $bind_info,
            'luckydraw_condition' => $luckydraw_condition,
            'limit_times' => $limit_times,
            'issue_time' => $issue_time,
            'issue_day' => $issue_day,
            'issue_cycle' => $issue_cycle,
            'give_condition' => $give_condition,
            'give_date' => $give_date,
            'game_type' => $game_type,
            'limit_value' => $limit_value,
        );
        //活动为用户申请时需要写入活动规则
        ($template_id != 4) && DB::table('active_rule')->insert($ruleData);

        if (in_array($template_id, [8, 9])) {
            $type = $template_id == 9 ? 'month' : 'week';
            $data['rebet_config'] = $tmp['rebet_config'] = \Logic\Set\SystemConfig::getModuleSystemConfig('rebet_config');
            $data['rebet_config'][$type] = intval($params['rebet_multiple']);
            $data['rebet_config'][$type.'_gt_zero'] = boolval($params['rebet_gt_zero_switch']);
            $confg = new \Logic\Set\SystemConfig($this->ci);
            $confg->updateSystemConfig($data, $tmp);
        }

        /*============================日志操作代码================================*/
        $type_info = DB::table('active_template')
            ->find($template_id);
        $type = "新增活动";
        $str = "活动名称:{$title}/活动类型:{$type_info->name}";
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
    function inputFile($inputUrl,$lastId)
    {
        //获取文件数据、写入数据表
        $str = file_get_contents($inputUrl);
        $data = explode("\n",$str);
        unset($data[0]);
        $array = [];
        foreach ($data as $k=>$v){
            if (empty($v)) continue;
            $v = iconv('gb2312','utf-8',$v);
            $v = preg_split('/(?:"[^"]*"|)\K\s*(,\s*|$)/', $v);
            if (!is_numeric($v[0])) continue;
//            //已存在跳出本次循环
            $count =  DB::table('active_apply_blacklist')->where('user_id',$v[0])->where('active_id',$lastId)->count();
            if ($count) continue;
            //组装数据
            $array[$k]['user_id']    = $v[0];
//            $array[$k]['user_name']  = $v[1];
            $array[$k]['active_id']  = $lastId;
            $array[$k]['created']    = date('Y-m-d H:i:s',time());
        }
        DB::table('active_apply_blacklist')->insert($array);
        return true;
    }

};