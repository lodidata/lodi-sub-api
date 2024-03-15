<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 2018/7/27
 * Time: 16:23
 */

use Logic\Admin\BaseController;

/**
 * 设置游戏维护
 */
return new class extends BaseController
{

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id = null)
    {
        $this->checkID($id);
        $params = $this->request->getParams();

        $m_start_time = isset($params['m_start_time']) ? $params['m_start_time'] : '';
        $m_end_time   = isset($params['m_end_time']) ? $params['m_end_time'] : '';

        //开始时间和结束时间验证
        if ($m_start_time > $m_end_time) {
            return $this->lang->set(25);
        }

        $result = DB::table('game_menu')
            ->where('id', '=', $id)
            ->update(['m_start_time' => $m_start_time, 'm_end_time' => $m_end_time]);

        if ($result !== false) {
            \Model\GameMenu::delMenuRedis();
            \Model\GameMenu::delVerticalMenuRedis();
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }
};