<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/5 16:46
 */
use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '删除标签';
    const DESCRIPTION = '会员标签';
    
    const QUERY       = [
        'id' => 'int(required) #标签id',
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id)
    {
        $this->checkID($id);

        if($id == 2) {
            return createRsponse($this->response,200,'测试用户不能删除');
        }
        /*============================日志操作代码================================*/
        $info = DB::table('label')->where('id',$id)->find($id);
        /*============================================================*/
        $res = DB::table('label')->delete($id);

        if(!$res){
            return $this->lang->set(-2);
        }
        /*============================日志操作代码================================*/
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_USER, '会员标签', '会员标签', '删除', $sta, "标签名称:{$info->title}");
        /*============================================================*/

        return $this->lang->set(0);
    }
};
