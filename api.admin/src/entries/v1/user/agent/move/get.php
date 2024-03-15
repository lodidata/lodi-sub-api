<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/9/17
 * Time: 16:24
 */

use Logic\Admin\BaseController;


return new class() extends BaseController
{

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run(){

        $params = $this->request->getParams();

        $query = DB::table('user')->selectRaw('id,name');

        $query = isset($params['name']) && !empty($params['name']) ? $query->where('name', 'like',$params['name']) : $query;

        $result = $query->orderByDesc('id')->limit(10)->get()->toArray();
        array_unshift($result, ['id' => 999999999, 'name' => '无上级']);
        return $result;

    }


};