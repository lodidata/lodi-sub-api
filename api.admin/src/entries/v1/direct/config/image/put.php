<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class () extends BaseController {
    const TITLE = '更新推广图片配置';
    const QUERY = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $id = $this->request->getParam('id', '');
        $type = $this->request->getParam('type', '');
        $name = $this->request->getParam('name', '');
        $desc = $this->request->getParam('desc', '');
        $img = $this->request->getParam('img', '') ? replaceImageUrl($this->request->getParam('img')) : '';
        $reward = $this->request->getParam('reward', 0);

        if (empty($id) || empty($type)) {
            return $this->lang->set(-2);
        }

        $res = DB::table('direct_imgs')
            ->where('id', $id)
            ->update(
                [
                    'desc' => $desc,
                    'img' => $img,
                    'name' => $name,
                    'type' => $type,
                    'reward' => $reward,
                ]
            );

        if ($res !== false) {
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }
};