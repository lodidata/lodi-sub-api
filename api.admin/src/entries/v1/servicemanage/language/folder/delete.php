<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/7
 * Time: 17:11
 */


use Logic\Admin\BaseController;

/*
 * 删除文件夹
 *
 * */
return new class extends BaseController
{

    const TITLE = 'delete 删除文件夹';
    const DESCRIPTION = '删除文件夹';
    
    const QUERY = [

    ];
    
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run($id)
    {

        //$params['uid'] = $this->playLoad['uid'];
        $folderModel = new \Model\Admin\ServiceFolder();
        $anaModel = new \Model\Admin\ServiceAna();
        DB::beginTransaction();
        try {
            $anaModel::where('service_folder_id', $id)->delete();
            $folderModel::findOrFail($id)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return $this->lang->set(-2);
        }


        return $this->lang->set(0);


    }
};