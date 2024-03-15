<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 2018/7/27
 * Time: 16:23
 */

use Logic\Admin\BaseController;

/**
 * 游戏维护开关----->超管平台
 */
return new class extends BaseController
{

    protected $beforeActionList = [
        //'verifyToken', 'authorize',
    ];

    public function run($id = null)
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
        $switch = $this->request->getParam('switch', '');
        $this->checkID($id);
        $result = DB::table('game_menu')
            ->where('id', '=', $id)
            ->update(['switch' => $switch]);

        if ($result !== false) {
            $this->redis->del(\Logic\Define\CacheKey::$perfix['verticalMenuList']);
            return 'success';
        }
        return 'fail';

    }
};