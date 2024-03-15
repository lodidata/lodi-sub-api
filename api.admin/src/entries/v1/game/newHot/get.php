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
            $newArr = DB::table('game_menu')
                ->select(['id','name','alias','img','hot_status as status','hot_sort as sort'])
                ->where('alias', 'HOT')
                ->orderBy('hot_sort', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->toArray();
            foreach ($newArr as &$val) {
                $val->img = showImageUrl($val->img);
            }
            unset($val);
        }else if ($type == 'c'){
            $data = DB::table('game_menu')
                ->select(['id','id as menu_id','alias','hot_sort','hot_status','img'])
                ->where('switch', 'enabled')
                ->where('pid','>',0)
                ->orderBy('hot_sort', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->toArray();
            $item = [];
            foreach ($data as $key => $val) {
                $val = (array)$val;
                if(isset($item[$val['alias']])){
                    $item[$val['alias']]['id'][] = $val['id'];
                }else{
                    $item[$val['alias']] = [
                        'menu_id' => $val['menu_id'],
                        'id'      => [$val['id']],
                        'alias'   => $val['alias'],
                        'rename'  => $val['alias'],
                        'name'    => $val['alias'],
                        'sort'    => $val['hot_sort'],
                        'status'  => $val['hot_status'],
                        'img'     => showImageUrl($val['img']),
                    ];
                }
            }
            $newArr = array_values($item);
        } else {
            $game_id = $this->request->getParam('game_id', '');
            $game_id_arr = explode(',', $game_id);
            $newArr = DB::table('game_3th')
                ->select(['id', 'game_name as name','rename', 'hot_sort', 'hot_status','game_img'])
                ->whereIn('game_id', $game_id_arr)
                ->orderBy('hot_sort', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->toArray();
            foreach ($newArr as $key => $val) {
                $val = (array)$val;
                $item = [
                    'id'       => $val['id'],
                    'rename'   => $val['rename'],
                    'name'     => $val['name'],
                    'sort'     => $val['hot_sort'],
                    'status'   => $val['hot_status'],
                    'game_img' => showImageUrl($val['game_img'])
                ];
                $newArr[$key] = $item;
            }
        }
        return $newArr;
    }
};
