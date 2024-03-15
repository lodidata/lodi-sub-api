<?php
use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '批量赠送彩金活动';
    const DESCRIPTION = '创建批量赠送彩金活动';
    const QUERY = [
        'title' => 'string() #活动名称',
        'template_id' => 'integer() #模板ID',
        'status' => 'string() #enabled（启用），disabled（停用）',
    ];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $params = $this->request->getParams();
        $params['begin_time'] = date('Y-m-d H:i:s', strtotime($params['begin_time']));
        $params['end_time'] = date('Y-m-d H:i:s', strtotime($params['end_time']));
        $title = $params['title'] ?? '';
        $start_time = $params['begin_time'] ?? '';
        $end_time = $params['end_time'] ?? '';
        $language_id = isset($params['language_id']) ? intval($params['language_id']) : 1;
        if ($start_time > $end_time) {
            return $this->lang->set(10032);
        }
        $type_id = $params['type_id'] ?? 0;
        if (!$type_id) {
            return $this->lang->set(886, ['活动类型id不能为空']);
        }
        $template_id = $params['template_id'] ?? 0;
        $msg_title = $params['msg_title'] ?? '';     //消息标题
        $msg_content = $params['msg_content'] ?? '';     //消息内容
        $give_away = $params['give_away'] ?? 0;     //赠送条件：1-指定用户，2-指定等级，3-批量赠送
        $phone_list = isset($params['phone_list']) ? trim($params['phone_list']) : '';     //指定手机号列表
        $user_level = isset($params['user_level']) ? trim($params['user_level']) : '';     //指定用户等级列表
        $batch_url = $params['batch_url'] ?? '';     //批量上传时的文件路径
        $give_amount = $params['give_amount'] ?? 0;     //赠送的彩金数量
        $dm_num = $params['dm_num'] ?? 0;     //打码量
        $notice_type = $params['notice_type'] ?? '';     //通知类型：1-短信，2-邮箱，3-站内信息通知
        $is_now_give = $params['is_now_give'] ?? 0;     //是否立即赠送: 1-是，0-否
        $give_amount_time = $params['give_amount_time'] ?? "2000-01-01 00:00:00";     //赠送彩金时间
        $limit_game = $params['limit_game'] ?? '';    //指定的游戏分类，多个游戏id之间逗号隔开
        $recharge_num = $params['recharge_num'] ?? 0;   //充值金额
        $valid_time = $params['valid_time'] ?? "";   //有效时间
        $unfixed_url = $params['unfixed_url'] ?? '';     //非固定方式赠送文件url
        $receive_way = $params['receive_way'] ?? 1;     //赠送彩金方式
        $recharge_limit = intval($params['recharge_limit']) ?? 1;    //是否需要充值金币才能领取彩金：1-是，0-否
        $recharge_type = $params['recharge_type'] ?? 1;      //充值金币类型：1-单笔，2-累计
        $recharge_coin = intval($params['recharge_coin']) ?? 0;        //需要充值金币的数量
        $uid_list = isset($params['uid_list']) ? trim($params['uid_list']) : '';     //指定用户ID列表

        if ($give_amount >= 5000000) {
            return createRsponse($this->response, 200, -2, '赠送彩金不能大于等于50000');
        }

        if ($recharge_limit && $recharge_coin <= 0) {
            return createRsponse($this->response, 200, -2, '需要充值的金币不能小于0');
        }

        if (empty($give_away)) {
            return createRsponse($this->response, 200, -2, '彩金赠送方式不能为空');
        }
        //这里只允许创建批量彩金活动
        if (!isset($template_id) ||empty($template_id)) {
            return createRsponse($this->response, 200, -2, '只能创建批量彩金活动');
        }
        //如果是批量赠送彩金，判读一些参数
        if (empty($msg_title) || empty($msg_content)) {
            return createRsponse($this->response, 200, -2, '缺少必要参数');
        }
        if ($is_now_give == 1) {
            $give_amount_time = date("Y-m-d H:i:s");
        } elseif ($is_now_give != 1) {
            if (empty($give_amount_time)) {
                return createRsponse($this->response, 200, -2, '赠送时间不能为空');
            }
            if ($give_amount_time <= date("Y-m-d H:i:s")) {
                return createRsponse($this->response, 200, -2, '定时赠送时间不能小于当前时间');
            }
        }
        if ($give_away != 4) {
            if ($give_away <= 0) {
                return createRsponse($this->response, 200, -2, '赠送彩金数量必须大于0');
            }
        }
        $item_list = [];    //要发放的用户列表
        if ($give_away == 1) {
            if (empty($phone_list)) {
                return createRsponse($this->response, 200, -2, '指定的用户不能为空');
            }
            if (mb_strlen($phone_list) > 550000) {
                return createRsponse($this->response, 200, -2, '指定手机号太多，当前最大支持5万个');
            }
            //获取手机号对应的用户id
            $tag_phone = explode(',', $phone_list);
            $fmt_phone_list = [];
            foreach ($tag_phone as $p) {
                if (empty($p)) {
                    continue;
                }
                $parse_p = \Utils\Utils::RSAEncrypt($p);
                if (empty($parse_p)) {
                    continue;
                }
                array_push($fmt_phone_list, $parse_p);
            }
            $userIds = \DB::table("user")->select(['id','name'])->whereIn('mobile', $fmt_phone_list)->get()->toArray();
            if (!empty($userIds)) {
                foreach ($userIds as $item) {
                    $tmp = $item->id.":".$item->name;    //将用户id与用户名用冒号拼接起来，后续发放彩金时候就不用再读取数据库了
                    array_push($item_list, $tmp);
                }
            }
        }
        if ($give_away == 2) {
            if (empty($user_level)) {
                return createRsponse($this->response, 200, -2, '指定的等级列表不能为空');
            }
            if (mb_strlen($user_level) > 550000) {
                return createRsponse($this->response, 200, -2, '指定手机号太多，当前最大支持5万个');
            }
            $level_list = explode(',', $user_level);
            $userIds = \DB::table("user")->select(['id','name'])->whereIn('ranting', $level_list)->get()->toArray();
            if (!empty($userIds)) {
                foreach ($userIds as $item) {
                    $tmp = $item->id.":".$item->name;    //将用户id与用户名用冒号拼接起来，后续发放彩金时候就不用再读取数据库了
                    array_push($item_list, $tmp);
                }
            }
        }
        if ($give_away == 3) {
            if (empty($batch_url)) {
                return createRsponse($this->response, 200, -2, '批量上传文件地址不能为空');
            }
            $batchData = file_get_contents($batch_url);
            $batchData = str_replace(array("\r\n", "\r", "\n"), ",", $batchData);
            $batchData = ltrim($batchData, 'mobile');    //excel模板表格第一行是mobile字符要去掉
            $batchData = trim($batchData,',');           //字符串前后的逗号要去掉
            if (mb_strlen($batchData) > 550000) {
                return createRsponse($this->response, 200, -2, '指定手机号太多，当前最大支持5万个');
            }
            //获取手机号对应的用户id
            $tag_phone = explode(',', $batchData);
            $fmt_phone_list = [];
            foreach ($tag_phone as $p) {
                array_push($fmt_phone_list, \Utils\Utils::RSAEncrypt($p));
            }
            $userIds = \DB::table("user")->select(['id','name'])->whereIn('mobile', $fmt_phone_list)->get()->toArray();
            if (!empty($userIds)) {
                foreach ($userIds as $item) {
                    $tmp = $item->id.":".$item->name;    //将用户id与用户名用冒号拼接起来，后续发放彩金时候就不用再读取数据库了
                    array_push($item_list, $tmp);
                }
            }
        }
        //非固定方式赠送彩金
        if ($give_away == 4) {
            if (empty($unfixed_url)) {
                return createRsponse($this->response, 200, -2, '非固定方式上传文件地址不能为空');
            }
            //解析一下上传文件内容
            $cont = file_get_contents($unfixed_url);
//            $fixData = str_replace(array("\r\n", "\r", "\n"), ",", $cont);
//            $fixData = explode(',', $fixData);
            $fixData = str_replace(array("\r\n", "\r", "\n"), ";", $cont);    //.csv格式解析用分号分割每条数据
            $fixData = explode(';', $fixData);    //.csv格式分号解析
            array_shift($fixData);
            $uid_maps = [];   //上传文件中每个用户映射发放彩金内容
            foreach ($fixData as $itm) {
                if (empty($itm) || strlen($itm) < 10) {
                    continue;
                }
//                $ex = explode("\t", $itm);
                $ex = explode(",", $itm);    //.csv格式中每条数据用的是逗号隔开
                $cur_uid = $ex[0] ?? 0;         //uid
                $cur_uname = $ex[1] ?? '';      //用户名
                $cur_amount = $ex[3] ? $ex[3]*100 : 0;      //赠送彩金
                $cur_dm = $ex[4] ? $ex[4]*100 : 0;          //打码量
                if (empty($cur_uid) || empty($cur_amount) || empty($cur_dm)) {
                    continue;
                }
                $uid_maps[$cur_uid] = ['name'=>$cur_uname,'give_amount'=>$cur_amount,'dml'=>$cur_dm];
            }
            if (!empty($uid_maps)) {
                if (count(array_keys($uid_maps)) > 50000) {
                    return createRsponse($this->response, 200, -2, '批量赠送当前最大支持5万个');
                }
                $userIds = \DB::table("user")->select(['id','name'])->whereIn('id', array_keys($uid_maps))->get()->toArray();
                if (!empty($userIds)) {
                    foreach ($userIds as $item) {
                        if (isset($uid_maps[$item->id]) && $uid_maps[$item->id]['name'] == $item->name) {
                            array_push($item_list, $item->id.":".$item->name.":".$uid_maps[$item->id]['give_amount'].":".$uid_maps[$item->id]['dml']);
                        }
                    }
                }
            }
        }
        //指定用户ID
        if ($give_away == 5) {
            if (empty($uid_list)) {
                return createRsponse($this->response, 200, -2, '指定的用户不能为空');
            }
            $exp_uid_list = explode(',', $uid_list);
            $arr_uid_list = [];
            foreach ($exp_uid_list as $i) {
                if (empty(intval($i))) {
                    continue;
                }
                array_push($arr_uid_list, intval($i));
            }
            if (count($arr_uid_list) > 50000) {
                return createRsponse($this->response, 200, -2, '指定的用户太多，当前最大支持5万个');
            }
            $userIds = \DB::table("user")->select(['id','name'])->whereIn('id', $arr_uid_list)->get()->toArray();
            if (!empty($userIds)) {
                foreach ($userIds as $item) {
                    $tmp = $item->id.":".$item->name;    //将用户id与用户名用冒号拼接起来，后续发放彩金时候就不用再读取数据库了
                    array_push($item_list, $tmp);
                }
            }
        }

        //如果rpush时候 $item_list 为空会报错
        if (empty($item_list)) {
            return createRsponse($this->response, 200, -2, '解析出的用户列表为空');
        }
        //必须指定有效时间
        if (empty($valid_time)) {
            return createRsponse($this->response, 200, -2, '必须指定有效时间');
        }

        //添加活动表数据
        $userId   = $this->playLoad['uid'];
        $userName = $this->playLoad['nick'];
        $lastId   = DB::table('active')->insertGetId([
            'language_id' => $language_id, 'state'=>'manual',
            'end_time' => $end_time, 'begin_time' => $start_time, 'type_id' => $template_id, 'title' => $msg_title,
            'name' => $msg_title, 'created_uid' => $userId, 'created_user' => $userName, 'active_type_id' => $type_id
        ]);

        if (isset($GLOBALS['playLoad'])) {
            $admin_id = $GLOBALS['playLoad']['uid'];
            $admin_name = $GLOBALS['playLoad']['nick'];
        } else {
            $admin_id = 0;
            $admin_name = '';
        }

        //如果是批量发放彩金活动，根据发放类型将活动id和发放的用户列表存入redis
        $handsel_id = DB::table("active_handsel")->insertGetId([
            'active_id' => $lastId,
            'msg_title' => $msg_title,
            'msg_content' => $msg_content,
            'give_away' => $give_away ?? '',
            'phone_list' => $phone_list ?? '',
            'user_level' => $user_level ?? '',
            'batch' => $batchData ?? '',
            'batch_url' => replaceImageUrl($batch_url),
            'give_amount' => intval($give_amount),
            'dm_num' => intval($dm_num),
            'notice_type' => $notice_type,
            'is_now_give' => $is_now_give,
            'give_amount_time' => $give_amount_time,
            'state' => 0,
            'create_time' => date("Y-m-d H:i:s"),
            'limit_game' => $limit_game,
            'recharge_num' => $recharge_num,
            'valid_time' => $valid_time,
            'unfixed_url' => replaceImageUrl($unfixed_url),
            'receive_way' => $receive_way,
            'recharge_limit' => $recharge_limit,
            'recharge_type' => $recharge_type,
            'recharge_coin' => $recharge_coin,
            'uid_list' => $uid_list,
            'admin_id' => $admin_id,
            'admin_user' => $admin_name
        ]);

        if ($is_now_give == 1) {    //立即赠送的活动
            if ($give_away == 4) {    //非固定模式
                $this->redis->sadd("immediately_unfixed_keys", [$handsel_id]);              //集合中保存每个需要立即发放的彩金活动id
                $this->redis->rpush("immediately_unfixed_list:".$handsel_id, $item_list);   //该条活动中需要立即发放的用户信息存入队列
            } else {    //普通模式
                $this->redis->sadd("immediately_send_handsel", [$handsel_id]);
                $this->redis->rpush("immediately_send_handsel_item:".$handsel_id, $item_list);
            }
        } else {    //定时赠送的活动
            if ($give_away == 4) {    //非固定模式
                $this->redis->sadd("timer_unfixed_keys", [$handsel_id]);
                $this->redis->rpush("timer_unfixed_list:".$handsel_id, $item_list);
                $exp_time = (strtotime($give_amount_time) - time()) + 86400;
                $this->redis->expire("timer_unfixed_list:".$handsel_id, $exp_time);
            } else {     //普通模式
                $this->redis->sadd("timer_send_handsel", [$handsel_id]);                    //添加当前活动id到定时发放的集合中
                $this->redis->rpush("timer_send_handsel_item:".$handsel_id, $item_list);    //将要发放的用户列表添加到redis队列中
                $exp_time = (strtotime($give_amount_time) - time()) + 86400;
                $this->redis->expire("timer_send_handsel_item:".$handsel_id, $exp_time);    //设置过期时间比定时发送时间多1天
            }
        }

        $type_info = DB::table('active_template')->find($template_id);
        $type = "新增活动";
        $str  = "活动名称:{$title}/活动类型:{$type_info->name}";
        (new Log($this->ci))->create(null, null, Log::MODULE_ACTIVE, '活动列表', '活动列表', $type, 1, $str);
        return [];
    }
};