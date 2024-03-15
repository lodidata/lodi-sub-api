<?php
use Utils\Www\Action;
use Model\Advert;
use Model\AppBag;
use Model\User;

return new class extends Action {
    const TITLE = "启动后配置参数";
    const DESCRIPTION = "启动后配置参数";
    const TAGS = '公共分类';
    const SCHEMAS = [
        "code"                  => "string() #第三方客服URL",
        "withdraw_need_mobile"  => "boolean() #提现是否需要手机验证",
        'pusherio_server'       => "string() #pusherio socketio服务器地址",
        'WeChat_login'          => "boolean() #微信快登开关 1开，0关",
        'maintaining'           => "boolean() #维护开关 1开，0关",
        'site_pc_logo'          => "string() #PC端logo",
        'site_h5_logo'          => "string() #h5端logo",
        'first_WeChat_binding'  => "boolean() #微信首次快登绑定手机 1开，0关",
        'register_type'         => "int() #注册方式  1. 仅账号密码 2. 账号密码和账号密码手机  默认开3. 仅手机号密码4. 仅账号密码手机",
        'no_login_trial_service' => "boolean() #未登录和试玩登录可联系客服  1开，0关",
        'xima'                  => "boolean() #洗码开关 1开，0关",
        'withdraw_need_idcard'  => "boolean() #设置提现实名证 1开，0关",
        'min_money'             => "float() #最小充值金额",
        'max_money'             => "float() #最高充值金额",
        'recharge_money_set'    => "boolean() #快捷金额 1开，0关",
        'recharge_money_value'  => "string() #快捷充值设置 数组[5000,2000,8000,10000]",
        'recharge_money_value_list' => "string() #快捷充值设置 数组[5000,2000,8000,10000]",
        'stop_withdraw'         => "boolean() #暂停提现开关   1开，0关",
        'stop_deposit'          => "boolean() #暂停充值   1开，0关",
        'down_url'              => "string() #APP下载地址",
        'app_name'              => "string() #APP下载名称",
        'app_desc'              => "string() #APP下载描述",
        'h5_url'                => "string() #H5推广地址",
        'pc_url'                => "string() #PC推广地址",
        'spread_url'            => "string() #推广页下载APP地址",
        'certificate_url'       => "string() #APP修复地址",
        'certificate_switch'    => "boolean() #APP修复开关  1开，0关",
        'app_spead_url'         => "string() #APP推广地址",
        'recharge_autotopup'    => "boolean() #AutoTopup支付页显示  1开，0关",
        'recharge_qrcode'       => "boolean() #QRCode支付页显示  1开，0关",
        'recharge_offline'      => "boolean() #线下入款支付页显示  1开，0关",
        'kefu_code'             => "string() #客服代码 html",
        'landingpage_img'       => "string() #落地页图片地址",
        'landingpage_url'       => "string() #落地页跳转地址",
        'landingpage_video'     => "string() #落地页视频地址",
        'landingpage_button'    => "string() #落地页按钮跳转地址",
        'agent_desc_img'        => "string() #代理说明图片地址",
   ];
    //这个接口不检查ip黑名单 为了返回客服连接
    protected $noCheckBlackIp = true;

    public function run() {
        $this->auth->verfiyToken();

        $data=(new \Logic\Set\SystemConfig($this->ci))->getStartGlobal();
        $app_list = AppBag::getList();
        if($app_list){
            $app_list = array_column($app_list,'url','type');
        }

        $data['ios_url']     = $app_list[1] ?? '';
        $data['android_url'] = $app_list[3] ?? '';
        return $data;
    }
};