<?php
use Logic\Admin\BaseController;
use Utils\Curl;

return new class() extends BaseController {
    const TITLE       = '电访后台';
    const DESCRIPTION = '电访后台';
    const PARAMS       = [
    ];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];
    public function run() {
        $id = $this->request->getParam('kefu_id', 0);
        $name = $this->request->getParam('name', '');
        $telecom_name = $this->request->getParam('telecom_name', '');
        $batch_url = $this->request->getParam('batch_url', '');

        if($id <= 0){
            if(empty($name)){
                return createRsponse($this->response, 200, -2, '客服名称不能为空');
            }
            $count = \DB::table("kefu_user")->where('name', $name)->count();
            if($count > 0){
                return createRsponse($this->response, 200, -2, '客服名称已存在');
            }
        }
        if(empty($telecom_name)){
            return createRsponse($this->response, 200, -2, '名单名称名称不能为空');
        }
        if($id > 0){
            $count = \DB::table("kefu_telecom")->where('name', $telecom_name)->where('kefu_id', $id)->count();
            if($count > 0){
                return createRsponse($this->response, 200, -2, '名单名称已存在');
            }
        }

        if (empty($batch_url)) {
            return createRsponse($this->response, 200, -2, '批量上传文件地址不能为空');
        }
        $batchData = Curl::get($batch_url);
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
        $userList = \DB::table("user")->select(['id','name','mobile','created'])->whereIn('mobile', $fmt_phone_list)->groupBy('mobile')->get()->toArray();
//        $count = count($userList);
//        //去掉一个手机号多个用户问题
//        foreach($userList as $key => $val){
//            for($j = $key+1;$j < $count;$j++){
//                if(isset($userList[$j]) && $val->mobile == $userList[$j]->mobile){
//                    unset($userList[$key], $userList[$j]);
//                }
//            }
//        }

        $userids = array_column($userList, 'id');

        //开始插入数据
        \DB::beginTransaction();
        //先建立主数据
        if($id > 0){
            $kefu_id = $id;
        }else{
            try {
                $kefu_id = \DB::table("kefu_user")->insertGetId(['name'=>$name,'put_time'=>date('Y-m-d H:i:s',time())]);
            } catch (\Exception $e) {
                \DB::rollback();
                return createRsponse($this->response, 200, -2, '插入数据库失败,请联系管理员'.$e->getMessage());
            }
        }
        try {
            $telecom_data = [
                'kefu_id'         => $kefu_id,
                'name'            => $telecom_name,
                'roll_num'        => count($fmt_phone_list),
                'not_register'    => 0,
                'register_num'    => count($userids),
                'recharge_num'    => 0,
                'recharge_amount' => 0,
                'recharge_mean'   => 0,
            ];
            $pid = \DB::table("kefu_telecom")->insertGetId($telecom_data);
        } catch (\Exception $e) {
            \DB::rollback();
            return createRsponse($this->response, 200, -2, '插入数据库失败,请联系管理员2'.$e->getMessage());
        }

        if($pid <= 0){
            \DB::rollback();
            return createRsponse($this->response, 200, -2, '未知错误,请联系管理员');
        }

        //获取充值信息
        //分页查询 1000条一次
        $all_page = ceil(count($userids) / 1000);
        $rechargeList = [];
        $current_id = 0;
        for($i=0;$i<$all_page;$i++){
            $one_uids = array_slice($userids,$current_id,1000);
            $one_list = \DB::connection('slave')->table("funds_deposit")
                ->whereIn('user_id', $one_uids)
                ->where('status', 'paid')
                ->whereRaw(\DB::raw("FIND_IN_SET('new',state)"))
                ->where('money', '>', 0)
                ->get(['user_id','money','recharge_time'])->toArray();
            $rechargeList = array_merge($rechargeList,$one_list);
            $current_id += 1000;
        }

        $item = [];
        $is_data = false;
        foreach ($userList as $key => $user_v){
            $item[$key] = [
                'pid'             => $pid,
                'user_id'         => $user_v->id,
                'mobile'          => $user_v->mobile,
                'username'        => $user_v->name,
                'register_time'   => $user_v->created,
                'recharge_amount' => 0,
                'recharge_time' => null,
            ];
            foreach($rechargeList as $recharge_info){
                if($recharge_info->user_id == $user_v->id){
                    $item[$key]['recharge_amount'] = $recharge_info->money;
                    $item[$key]['recharge_time'] = $recharge_info->recharge_time;
                    break;
                }
            }
            //分批插入
            if(count($item) >= 5000){
                $is_data = true;
                try {
                    \DB::table("kefu_telecom_item")->insert($item);
                } catch (\Exception $e) {
                    \DB::rollback();
                    return createRsponse($this->response, 200, -2, '插入数据库失败,请联系管理员3'.$e->getMessage());
                }
                $item = [];
            }
        }
        if(!empty($item)){
            $is_data = true;
            try {
                \DB::table("kefu_telecom_item")->insert($item);
            } catch (\Exception $e) {
                \DB::rollback();
                return createRsponse($this->response, 200, -2, '插入数据库失败,请联系管理员3'.$e->getMessage());
            }
        }

        if(!$is_data){
            \DB::rollback();
            return createRsponse($this->response, 200, -2, '未填写手机号或者无效手机号未找到用户');
        }
        \DB::commit();

        //统计客服数量
        $all_count = count($fmt_phone_list);
        $recharge_num = \DB::table("kefu_telecom_item")->where('pid', $pid)->where('recharge_amount', '>', 0)->count();
        if($recharge_num == 0){
            $recharge_mean = 0;
            $recharge_amount = 0;
        }else{
            $recharge_amount = \DB::table("kefu_telecom_item")->where('pid', $pid)->where('recharge_amount', '>', 0)->sum('recharge_amount');
            $recharge_mean = round($recharge_amount/$recharge_num);
        }

        $telecom_update = [
            'roll_num'        => $all_count,
            'recharge_num'    => $recharge_num,
            'recharge_amount' => $recharge_amount,
            'recharge_mean'   => $recharge_mean,
        ];
        \DB::table("kefu_telecom")->where('id', $pid)->update($telecom_update);

        return $this->lang->set(0, '', [], []);
    }
};
