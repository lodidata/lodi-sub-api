<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/15 15:25
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '给用户绑定/打一个标签';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'id' => 'int',
    ];
    
    const PARAMS      = [
        'tag' => 'int #标签id',
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id)
    {

        $this->checkID($id);

        (new BaseValidate([
            'tag'=>'require|integer|egt:0'
        ]))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();

        $data = DB::table('user')->find($id);

        if(!$data){
            return $this->lang->set(10014);
        }
        $data = (array) $data;
        $tags = $this->getIdByTags('测试');
        if($tags == $data[0]['tags']){
            return false;
        }

        $res = DB::table('user')->where('id',$id)->update(['tags'=>$params['tag']]);
        if($res !== false){
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);

    }

    /**
     * 取标识ID
     * @param $tagname
     * @return array|int
     */
    protected function getIdByTags($tagname) {

        $result = DB::table('label')->where('title',$tagname)->value('id');
        return empty($result) ? 0 : $result;
    }
};
