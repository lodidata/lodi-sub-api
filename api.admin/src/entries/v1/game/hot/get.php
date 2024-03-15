<?php

use Logic\Admin\BaseController;

/**
 * 获取当前竖版菜单的热门游戏
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
        $newArr = DB::table('game_menu')
            ->select(['id', 'name', 'rename', 'sort', 'status'])
            ->whereNotIn('pid', [0,23])
            ->where('switch', 'enabled')
            //->where('pid', '=', 17)
            ->orderBy('sort')
            ->get()
            ->toArray();


        $type = $this->request->getParam('type');
        $sql = DB::table('game_3th')
            ->select(['id', 'game_id', 'game_name','rename', 'sort', 'status','is_hot']);
        if($type){
            $sql->where('is_hot', '=', 1);
        }
//        else{
//            $sql->where('is_hot', '=', 0);
//        }
        $game_3th = $sql->orderBy('sort')
            ->get()
            ->toArray();
        if($type!='hot'){
            foreach ($newArr as $key => $item) {
                $item = (array)$item;
                $num = 0;
                foreach ($game_3th as $item_3th) {
                    $item_3th = (array)$item_3th;
                    if ($item['id'] == $item_3th['game_id']) {
                        $item['childrens'][$num] = $item_3th;
                        $newArr[$key] = $item;
                        $num++;
                    }
                }
            }
            return $newArr;
        }else {
//            $newArr = array_column($newArr,'name','id');
//            foreach($game_3th as &$val){
//                $val = (array)$val;
//                $val['game_name'] = $newArr[$val['game_id']].'-'.$val['game_name'];
//            }
        }

        return $game_3th;

    }
};
