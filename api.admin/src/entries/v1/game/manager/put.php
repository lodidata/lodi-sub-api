<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 2018/7/27
 * Time: 16:23
 */

use Logic\Admin\BaseController;

/**
 * 设置游戏维护----->超管平台
 */
return new class extends BaseController
{

    protected $beforeActionList = [
       // 'verifyToken', 'authorize',
    ];

    public function run()
    {
        $api_token = $this->ci->request->getHeaderLine('api-token');
        if(!$api_token){
            die('token error');
        }
        $api_verify_token = $this->ci->get('settings')['app']['api_verify_token'];
        $token = $api_verify_token . date("Ymd");
        if($token != $api_token){
            die('token unequal');
        }

        $params = $this->request->getParams();
        //$this->checkID($id);

        $m_start_time = isset($params['m_start_time']) ? $params['m_start_time'] : '';
        $m_end_time = isset($params['m_end_time']) ? $params['m_end_time'] : '';

        $alias = $params['type'];
        if(!$alias){
            return $this->lang->set(25);
        }
        //开始时间和结束时间验证
        if ($m_start_time > $m_end_time) {
            return $this->lang->set(25);
        }

        //$alias = \DB::table('game_menu')->where('id', '=', $id)->value('alias');
        $result = DB::table('game_menu')
            ->where('alias', '=', $alias)
            ->update(['m_start_time' => $m_start_time, 'm_end_time' => $m_end_time]);

        if ($result !== false) {
            $this->redis->del(\Logic\Define\CacheKey::$perfix['verticalMenuList']);
            return 'success';
        }
        return 'fail';

    }
};