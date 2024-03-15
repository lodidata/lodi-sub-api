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

return new class() extends BaseController {

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
        'result' => 'string # 成功 success，失败 fail，错误 error',
        'status' => 'int # 状态(1成功,0失败)',
    ];

    const PARAMS = [];

    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $params = $this->request->getParams();
        if(!isset($params['date_begin']) || empty($params['date_begin'])){
            $params['date_begin'] = date('Y-m-d', time());
        }
        if(!isset($params['date_end']) || empty($params['date_end'])){
            $params['date_end'] = date('Y-m-d', time());
        }

        $query = DB::connection('slave')->table('user_logs')->orderBy('created', 'desc');
        !empty($params['id']) && $query->where('id', "{$params['id']}");
        isset($params['status']) && is_numeric($params['status']) && $query->where('status', $params['status']);
        !empty($params['username']) && $query->where('name', 'like', "%{$params['username']}%");
        !empty($params['type']) && $query->where('log_type', $params['type']);
        !empty($params['ip']) && $query->whereRaw('trim(log_ip)=?', $params['ip']);
        !empty($params['result']) && $query->where('log_value', 'like', "%{$params['result']}%");
        !empty($params['domain']) && $query->where('domain', 'like', "%{$params['domain']}%");
        !empty($params['date_begin']) && $query->where('created', '>=', $params['date_begin']);
        !empty($params['date_end']) && $query->where('created', '<=', $params['date_end'] . ' 23:59:59');
        !empty($params['platform']) && $query->where('platform', $params['platform']);

        $attributes['total'] = $query->count();

        $res = $query->forPage($params['page'], $params['page_size'])
                     ->get()
                     ->toArray();

        if (!$res) {
            return [];
        }

        $attributes['number'] = $params['page'];

        return $this->lang->set(0, [], $res, $attributes);
    }
};
