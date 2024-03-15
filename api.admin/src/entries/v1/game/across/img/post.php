<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\Admin\GameMenu;
use Utils\Utils;

/**
 * 编辑橫版热门游戏图标
 *
 */
return new class() extends BaseController
{

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {

        //接收type类型数据：p：父菜单；c：二级菜单；cs:三級菜单
        $params = $this->request->getParams();


        if (!isset($params['id']) || !$params['id']) {
            return $this->lang->set(886, ['id参数不能为空']);
        }

        if (!isset($params['qp_img']) || !$params['qp_img']) {
            return $this->lang->set(886, ['qp_img参数不能为空']);
        }

        if (!isset($params['type']) || !$params['type']) {
            return $this->lang->set(886, ['type参数不能为空']);
        }

        if ($params['type'] == 'p' || $params['type'] == 'c') {
            $tmp = (array)DB::table('game_menu')
                ->where('switch','enabled')
                ->where('id', '=', $params['id'])->first();
            $data['qp_img'] = $params['qp_img'] ? replaceImageUrl($params['qp_img']) : $tmp['qp_img'];
            $data['qp_img2'] = $params['qp_img2'] ? replaceImageUrl($params['qp_img2']) : $tmp['qp_img2'];
            $res = DB::table('game_menu')
                ->where('switch','enabled')
                ->where('id', '=', $params['id'])
                ->update($data);
        } else {
            $tmp = (array)DB::table('game_3th')
                ->where('id', '=', $params['id'])->first();

            $data['qp_img'] = !empty($params['qp_img']) ?  replaceImageUrl($params['qp_img']) : $tmp['qp_img'];
            //$data['game_img'] = isset($params['qp_img2']) && !empty($params['qp_img2']) ? $params['qp_img2']: $tmp['game_img'];
            $res = DB::table('game_3th')
                ->where('id', '=', $params['id'])
                ->update($data);
        }

        if($res!==false){
            \Model\GameMenu::delMenuRedis();
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);
    }

};
