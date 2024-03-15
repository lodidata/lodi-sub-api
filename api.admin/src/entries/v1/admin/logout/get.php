<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/10 9:09
 */
use Logic\Admin\BaseController;
use Logic\Admin\Cache\AdminRedis;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '用户登出';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS     = [

    ];

//前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $header = $this->request->getHeaderLine('Authorization');
        $config = $this->ci->get('settings')['jsonwebtoken'];
        // 判断header是否携带token信息
        $token = substr($header, 7);
        $admin_token = new \Logic\Admin\AdminToken($this->ci);
        $data = $admin_token->decode($token, $config['public_key']);
        $uid = $admin_token->originId($data['uid'], $config['uid_digital']);
        // 从cookie、数据库中删除有效的token
        (new AdminRedis($this->ci))->removeAdminUserCache($uid);
        //退出日志
        (new \Logic\Admin\Log($this->ci))->create($this->playLoad['uid'], $data['nick'], \Logic\Admin\Log::MODULE_USER, '退出系统', '退出系统', $data['nick'].'退出系统',1,'手动退出');
        return $this->lang->set(0);
    }


};
