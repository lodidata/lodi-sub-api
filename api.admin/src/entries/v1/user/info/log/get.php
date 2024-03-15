<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/16 16:23
 */

use Logic\Admin\BaseController;
use Model\UserLogs;

return new class() extends BaseController {

//    const STATE = \API::DRAFT;

    const TITLE = '会员操作日志';

    const DESCRIPTION = '';

    const QUERY = [
        'username' => 'string #用户名',
        'ip' => 'string',
        'date_begin' => 'datetime #查询日期，起始',
        'date_end' => 'datetime #查询日期，结束',
        'domain' => 'domain() #登录域名',
        'type' => 'int #操作类型，日志类型(1登陆,2取款申请,3充值申请,4申请活动奖励,5修改登录密码,6修改取款密码,7修改个人信息,8会员注册,9代理注册,10转账,11修改银行卡信息,12投注)',
        'page' => 'int #第几页',
        'page_size' => 'int #每页多少条',
        'result' => 'enum[success,fail,error] # 成功 success，失败 fail，错误 error',
        'status' => 'int # 状态(1成功,0失败)',
    ];

    const PARAMS = [];

    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id = '') {
        $this->checkID($id);
        $params = $this->request->getParams();

        $query = UserLogs::from('user_logs')
                         ->where('user_id', $id)
                         ->where('log_type', 1)
                         ->selectRaw('id,name,log_ip,platform,MAX(created) AS created,COUNT(platform) AS times,MAX(version) AS version');

        $query = isset($params['date_begin']) && !empty($params['date_begin']) ? $query->where('created', '>=', $params['date_begin']) : $query;
        $query = isset($params['end_begin']) && !empty($params['end_begin']) ? $query->where('created', '<=', $params['end_begin'] . '23:59:59') : $query;

        $attributes = [];
//        $attributes['total'] = $query->count();

//        $res = $query->forPage($params['page'], $params['page_size'])
        $res = $query->orderBy('id', 'desc')
                     ->groupBy(['platform','log_ip'])
                     ->get()
                     ->toArray();

        if (!$res) {
            return [];
        }
        
        foreach($res as $key => $val){
            //获取同IP人数
            $user_num = UserLogs::from('user_logs')->where('log_type',1)
                                ->where('log_ip', $val['log_ip'])
                                ->distinct()
                                ->count('user_id');
            $res[$key]['user_num'] = $user_num;
        }

//        $attributes['size'] = $params['page_size'];
//        $attributes['number'] = $params['page'];

        return $this->lang->set(0, [], $res, $attributes);
    }
};
