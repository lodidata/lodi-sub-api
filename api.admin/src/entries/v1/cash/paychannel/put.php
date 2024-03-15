<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\FundsDealLog;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '修改设置';
    const DESCRIPTION = '修改设置';

    const QUERY = [
        'id' => 'int(required) #需要修改的 id',
    ];

    const PARAMS = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id) {
        $this->checkID($id);

        $param    = $this->request->getParams();
        $validate = new \lib\validate\BaseValidate([
            'min_money'         => 'number',
            'max_money'         => 'number',
            'money_day_stop'    => 'number',
            'money_stop'        => 'number',
            'give_protion'      => 'float',
            'give_recharge_dml' => 'float',
            'give_lottery_dml'  => 'float',
            'name'              => 'require|max:100'
        ]);
        $validate->paramsCheck('', $this->request, $this->response);

        $type = $param['type'];
        if($type == 'channel') {
            $table    = 'pay_channel';
            $fun_name = '支付渠道';
        } elseif($type == 'payment') {
            $table    = 'payment_channel';
            $fun_name = '支付通道';
            if($param['currency_type'] == 2 && empty($param['coin_type'])){
                return $this->lang->set(10013);
            }
        } else {
            return $this->lang->set(10013);
        }

        $check = DB::table($table)->where('id', $id)->first();
        if(!$check) {
            return $this->lang->set(10014);
        }

        $str = $fun_name . 'id:' . $id;
        if($check->name != $param['name']) {
            $str .= "更改[名称]，[{$check->name}]更改为[{$param['name']}]/";
        }
        if($check->min_money != $param['min_money']) {
            $str .= "更改[最小支付金额]，[" . ($check->min_money / 100) . "]更改为[" . ($param['min_money'] / 100) . "]/";
        }
        if($check->max_money != $param['max_money']) {
            $str .= "更改[最大支付金额]，[" . ($check->max_money / 100) . "]更改为[" . ($param['max_money'] / 100) . "]/";
        }
        if($check->money_day_stop != $param['money_day_stop']) {
            $str .= "更改[日停用金额]，[" . ($check->money_day_stop / 100) . "]更改为[" . ($param['money_day_stop'] / 100) . "]/";
        }
        if($check->money_stop != $param['money_stop']) {
            $str .= "更改[累计停用金额]，[" . ($check->money_stop / 100) . "]更改为[" . ($param['money_stop'] / 100) . "]/";
        }
        if($check->rechage_money != $param['rechage_money']) {
            $str .= "更改[快捷充值]，[{$check->rechage_money}]更改为[{$param['rechage_money']}]/";
        }
        if($check->give != $param['give']) {
            if($check->give == 0) {
                $new_give = '是';
                $old_give = '否';
            } else {
                $new_give = '否';
                $old_give = '是';
            }
            $str .= "更改[是否赠送]，[{$old_give}]更改为[{$new_give}]/";
        }

        if($check->give_protion != $param['give_protion']) {
            $str .= "更改[赠送百分比]，[{$check->give_protion}]更改为[{$param['give_protion']}]/";
        }
        if($check->give_recharge_dml != $param['give_recharge_dml']) {
            $str .= "更改[充值赠送打码量]，[{$check->give_recharge_dml}]更改为[{$param['give_recharge_dml']}]/";
        }
        if($check->give_lottery_dml != $param['give_lottery_dml']) {
            $str .= "更改[彩金赠送打码量]，[{$check->give_lottery_dml}]更改为[{$param['give_lottery_dml']}]/";
        }
        if ($check->text != $param['text']) {
            $str .= "更改[文本]，[{$check->text}]改为[{$param['text']}]";
        }
        if ($check->img != $param['img']) {
            $str .= "更改[img]，更改图片[{$param['img']}]";
        }
        if ($check->logo != $param['logo']) {
            $str .= "更改[logo]，更改图片[{$param['logo']}]";
        }
        if ($check->remark != $param['remark']) {
            $str .= "更改[remark]，[{$check->remark}]改为[{$param['remark']}]";
        }

        if($check->sort != $param['sort']) {
            $str .= "排序:[{$check->sort}]更改为[{$param['sort']}]/";
        }
        $old_level=DB::table('level_payment')->where('payment_id',$id)->pluck('level_id')->toArray();
        if(isset($param['level']) && !empty($param['level'])){

            $old_level_str=implode(',',$old_level);
            $new_level_str=implode(',',$param['level']);
            if($old_level_str != $new_level_str){
                $str .="等级配置:[{$old_level_str}]更改为[{$new_level_str}]/";
            }
        }
        $update = [
            'name'              => $param['name'],
            'img'               => replaceImageUrl($param['img']),
            'text'              => $param['text'] ?? '',
            'remark'            => $param['remark'] ?? '',
            'min_money'         => $param['min_money'] ?? 0,
            'max_money'         => $param['max_money'] ?? 0,
            'money_day_stop'    => $param['money_day_stop'] ?? 0,
            'money_stop'        => $param['money_stop'] ?? 0,
            'logo'              => replaceImageUrl($param['logo']),
            'give'              => $param['give'],
            'give_protion'      => $param['give_protion'] ?? 0,
            'give_recharge_dml' => $param['give_recharge_dml'] ?? 0,
            'give_lottery_dml'  => $param['give_lottery_dml'] ?? 0,
            'rechage_money'     => $param['rechage_money'] ?? '',
            'sort'              => $param['sort'] ?? 0,
            'update_time'       => date('Y-m-d H:i:s', time())
        ];
        if($type == 'channel') {
            $update['guide_url'] = $param['guide_url'];
        }
        if(isset($param['guide_url']) && !empty($param['guide_url'])){
            $update['guide_url'] = replaceImageUrl($param['guide_url']);
        }
        if(isset($param['coin_type']) && !empty($param['coin_type'])){
            $update['coin_type']=$param['coin_type'];
        }

        DB::beginTransaction();
        try {
            if(isset($param['level']) && !empty($param['level'])){
                if (is_array($param['level']) && count($param['level'])) {
                    $levelData = [];
                    foreach ($param['level'] ?? [] as $k => $v) {
                        $levelData[] = ['payment_id' => $id, 'level_id' => $v];
                    }
                    DB::table('level_payment')->where('payment_id', $id)->delete();
                    DB::table('level_payment')->insert($levelData);
                } else {
                    //必须先删除之前线上支付的数据
                    DB::table('level_payment')->where('payment_id', $id)->delete();
                }
            }
            DB::table($table)->where('id', $id)->update($update);

            (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '第三方支付', $fun_name, '编辑', 1, $str);
            DB::commit();
            return $this->lang->set(0);
        } catch(Exception $exception) {
            DB::rollback();
            $this->logger->error('修改支付通道失败' . $exception->getMessage());
            return $this->lang->set(-2);
        }
    }
};
