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
use Logic\Admin\Log;

return new class() extends BaseController {
//    const STATE       = \API::DRAFT;
    const TITLE = '给用户绑定/打一个标签';
    const DESCRIPTION = '';
    
    const QUERY = [
        'id' => 'int',
    ];
    
    const PARAMS = [
        'tag' => 'int #标签id',
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id) {

        $this->checkID($id);
        $params = $this->request->getParams();

        (new BaseValidate(
            ['tag' => 'require|integer'],
            ['tag' => '标签']
        ))->paramsCheck('', $this->request, $this->response);
        $user = \Model\Admin\User::find($id);
        if($user->tags !=4 && $params['tag'] == 4) {
            return $this->lang->set(10021);
        }
        if($user->tags ==4 && $params['tag'] != 4) {
            return $this->lang->set(10022);
        }
        if (!$user) {
            return $this->lang->set(10014);
        }

//        $user->tags = $params['tag'];
//        $user->setTarget($id,$user->name);
//        $res = $user->save();

        $preTag = $user->tags;
        $res = DB::table('user')->where('id', $id)->update(['tags'=>$params['tag']]);
        if ($res) {
            DB::table('label')->where('id', $params['tag'])->update(['sum'=>DB::raw('sum+1')]);
        }

        if($preTag != 0) {
            DB::table('label')->where('id', $preTag)->update(['sum'=>DB::raw('sum-1')]);
        }

        return $this->lang->set(0);
    }

};
