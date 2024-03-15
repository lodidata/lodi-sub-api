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

//    const STATE = \API::DRAFT;

    const TITLE = '同IP登录详情';

    const DESCRIPTION = '';

    

    const QUERY = [
    ];

    

    const PARAMS = [];

    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $params = $this->request->getParams();
        if(empty($params['log_ip'])){
            return $this->lang->set(10010);
        }

        $query = \Model\UserLogs::from('user_logs')
            ->where('log_type',1)
            ->where('log_ip', $params['log_ip'])
            ->selectRaw('DISTINCT(user_id) as user_id');

        $attributes['total'] = $query->count();

        $res = $query->forPage($params['page'], $params['page_size'])
                    ->orderBy('user_id', 'desc')
                    ->get()
                    ->toArray();
        if (!$res) {
            return [];
        }
        $user_list = array_column($res, 'user_id');

        $data = DB::table('user as ur')
            ->leftJoin('user_agent as ua', 'ur.id', '=', 'ua.user_id')
            ->leftJoin('user_level as le', 'ur.ranting', '=', 'le.id')
            ->leftJoin('funds as f', 'ur.wallet_id', '=', 'f.id')
            ->whereIn('ur.id', $user_list)
            ->selectRaw('
                ur.name AS username,
                ua.uid_agent_name AS agent,
                le.name AS level,
                f.balance,
                ur.state
            ')
            ->get()->toArray();

        $attributes['size'] = $params['page_size'];
        $attributes['number'] = $params['page'];

        return $this->lang->set(0, [], $data, $attributes);
    }
};
