<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '新增H5推广链接';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        //不需要新增功能
        return $this->lang->set(-1);

        $data = $this->request->getParams();

        if (!isset($data['link']) || empty($data['link'])) {
            return $this->lang->set(-1);
        }
        $exp = explode('?', trim($data['link']));
        $link = $exp[0];   //H5推广链接

        $desc = '';
        if (isset($data['desc']) && !empty($data['desc'])) {
            if (mb_strlen($data['desc']) >= 200) {
                return $this->lang->set(-1);
            }
            $desc = $data['desc'];
        }

        $state = 'disabled';
        if (isset($data['state']) && $data['state'] == 1) {
            $state = 'enabled';
        }

        $name = 'H5推广';  //H5推广名称默认为 H5推广N （N表示一个序号）名称长度最大为10
        if (isset($data['name']) && !empty($data['name'])) {
            $name = trim($data['name']);
        }

        $tmp_key = "h5_url_".time();
        $insertId = DB::table('system_config')->insertGetId([
            'module' => 'market',
            'name' => $name,
            'key' => $tmp_key,
            'type' => 'string',
            'value' => $link,
            'desc' => $desc,
            'state' => $state
        ]);
        if (!$insertId) {
            return $this->lang->set(-1);
        }
        //修改key
        $new_key = "h5_url_".intval($insertId);
        DB::table('system_config')->where('id','=', intval($insertId))->update(['key'=>$new_key]);

        //更新name字段
        if ($name == "H5推广") {
            $newName = $name.intval($insertId);
            DB::table('system_config')->where('id','=', intval($insertId))->update(['name'=>$newName]);
        }
        $this->redis->del(\Logic\Set\SystemConfig::SET_GLOBAL);
        return $this->lang->set(0);
    }
};