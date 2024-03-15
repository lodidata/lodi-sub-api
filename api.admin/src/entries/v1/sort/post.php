<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;

return new class() extends BaseController {
    const TITLE       = '排序';
    const DESCRIPTION = '代付、支付、游戏排序';

    const QUERY       = [

    ];

    const PARAMS      = [];
    const SCHEMAS     = [
        [

        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $ids = $this->request->getParam('ids');
        $sort = $this->request->getParam('sort');
        $type = $this->request->getParam('type');
        $pid = $this->request->getParam('pid') ?? 0;
        $game_id = $this->request->getParam('game_id') ?? 0;

        (new BaseValidate([
            'sort' => 'require|isPositiveInteger'
        ]))->paramsCheck('', $this->request, $this->response);

        if($type == 'transfer') {
            $query = \DB::table('transfer_config');
        } elseif($type == 'pay') {
            $query = \DB::table('pay_config');
        } elseif($type == 'game3th') {                      //竖版游戏一级菜单
            $query = DB::table('game_menu')->where('switch', 'enabled')->where('pid', 0);
        } elseif($type == 'game3th_c' && $pid != 0) {       //竖版游戏二级菜单
            $query = DB::table('game_menu')->where('switch', 'enabled')->where('pid','=',$pid);
        } elseif($type == 'game3th_cs' && $game_id != 0) {  //竖版游戏三级菜单
            $query = DB::table('game_3th')->where('game_id', '=', $game_id);
        } elseif($type == 'hotgame') {
            $query = DB::table('game_menu')->where('alias', 'HOT');
        } elseif($type == 'hotgame_c') {
            $query = DB::table('game_menu')
                ->where('pid','>',0)
                ->where('switch', 'enabled')
                ->groupBy(['alias']);
        } elseif($type == 'hotgame_cs' && $game_id != 0) {
            $game_id_arr = explode(',', $game_id);
            $query = DB::table('game_3th')
                ->whereIn('game_id', $game_id_arr);
        } else {
            return $this->lang->set(-2);
        }

        $field = 'sort';
        if (in_array($type, ['hotgame', 'hotgame_c', 'hotgame_cs'])) {
            $field = 'hot_sort';
        }
        $query->orderBy($field, 'ASC')
            ->orderBy('id', 'ASC')
            ->select(['id']);
        if($query->groups) {
            $query->select([
                DB::raw('GROUP_CONCAT(id ORDER BY id ASC) as id')
            ]);
        }
        $data = $query->pluck('id')->toArray();
        $updateData = [];
        $tmpSort = 1;
        foreach ($data as $item) {
            $items = explode(',', $item);
            $item = current($items);
            if ($tmpSort == $sort) {
                foreach ($ids as $id) {
                    $updateData[$id] = $tmpSort;
                    $tmpSort++;
                }
            }
            if (!in_array($item, $ids)) {
                $updateData[$item] = $tmpSort;
                $tmpSort++;
            }
        }
        foreach ($data as $item) {
            $items = explode(',', $item);
            $item = current($items);
            foreach ($items as $tmpItem) {
                if (isset($updateData[$item])) {
                    $updateData[$tmpItem] = $updateData[$item];
                }
            }
        }
        foreach ($ids as $id) {
            if (!isset($updateData[$id])) {
                $updateData[$id] = $tmpSort;
                $tmpSort++;
            }
        }
        $updateFields = [];
        foreach ($updateData as $id => $tmpSort) {
            $updateFields[] = "when id='{$id}' then {$tmpSort}";
        }
        $tmpWhenThen = implode(' ', $updateFields);
        $res = $query->update([
            $field => DB::raw("(case {$tmpWhenThen} end)")
        ]);

        if ($res !== false) {
            \Model\GameMenu::delMenuRedis();
            \Model\GameMenu::delVerticalMenuRedis();
            if(!empty($game_id)) {
                $this->redis->del(\Logic\Define\CacheKey::$perfix['verticalMenuList'].':'.$game_id);
            }

            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }
};
