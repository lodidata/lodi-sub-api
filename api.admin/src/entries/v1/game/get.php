<?php

use Logic\Admin\BaseController;

/**
 * 获取当前菜单的排序信息
 *
 *  接收type类型数据：p：父菜单；c：二级菜单；cs:三級菜单
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
        $type=$this->request->getParam('type','p');
        if($type=='p'){
            $data= DB::table('game_menu')
                ->select(['id','type','alias','name','rename','sort','pid','status','img'])
                ->where('switch', 'enabled')
                ->get()
                ->toArray();

            $newArr = array();
            $num = 0;
            foreach ($data as $item) {
                $item = (array)$item;
                $item['img'] = showImageUrl($item['img']);
                if ($item['pid'] == 0) {
                    $newArr[$item['id']] = $item;
                } else {
                    if (isset($newArr[$item['pid']])) {
                        $newArr[$item['pid']]['childrens'][$num] = $item;
                        $num++;
                        $newArr[$item['pid']]['childrens'] = array_values($newArr[$item['pid']]['childrens']);
                        array_multisort(array_column($newArr[$item['pid']]['childrens'],'sort'),SORT_ASC,$newArr[$item['pid']]['childrens']);
                    }
                }

            }

            //以sort对数组排序
            //原因：直接sql查询以sort排序会出现子菜单和父级菜单的排序相同时，子菜单的数据排到了父菜单前面，所以就不能将子菜单的值赋值到父菜单里面去
            array_multisort(array_column($newArr,'sort'),SORT_ASC,$newArr);

        }else if ($type == 'c'){
            $pid=$this->request->getParam('pid',0);
            $game_3th = DB::table('game_3th')
                ->select(['id','game_id', 'game_name as name','rename', 'sort', 'status','game_img'])
                ->orderBy('sort')
                ->get()
                ->toArray();
            if($pid){
                $newArr=DB::table('game_menu')
                    ->select(['id','name','rename','sort','status','img','m_start_time','m_end_time'])
                    ->where('pid','=',$pid)
                    ->where('switch', 'enabled')
                    ->orderBy('sort', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->toArray();
            }else{
                $newArr=DB::table('game_menu')
                    ->select(['id','name','rename','sort','status','img','m_start_time','m_end_time'])
                    ->where('pid','<>',0)
                    ->where('switch', 'enabled')
                    ->orderBy('sort', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->toArray();
            }
            foreach ($newArr as $key => $item) {
                $item = (array)$item;
                $item['img'] = showImageUrl($item['img']);
                $num = 0;
                foreach ($game_3th as $item_3th) {
                    $item_3th = (array)$item_3th;
                    $item_3th['game_img'] = showImageUrl($item_3th['game_img']);
                    if ($item['id'] == $item_3th['game_id']) {
                        $item['childrens'][$num] = $item_3th;
                        $newArr[$key] = $item;
                        $num++;
                    }
                }
            }
        } else {
            $game_id = $this->request->getParam('game_id', 0);
            $newArr = DB::table('game_3th')
                ->select(['id', 'game_name as name','rename', 'sort', 'status','game_img'])
                ->where('game_id', '=', $game_id)
                ->orderBy('sort', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->toArray();
            foreach ($newArr as $key => $item) {
                $item = (array)$item;
                $newArr[$key]->game_img = showImageUrl($item['game_img']);
            }
        }
        return $newArr;
    }
};
