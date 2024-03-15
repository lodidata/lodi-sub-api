<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/15 18:08
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE = '修改用户所属等级';
    const DESCRIPTION = '会员管理--给用户一个新等级';
    const HINT = '示例：user/info/level';
    const QUERY = [];
    
    const PARAMS = [
        'list' => 'rows(required) #格式：[用户id,等级id]。eg: [[1,2],[2,3]]',
    ];
    const SCHEMAS = [
        [
                'success' => 'rows #数组，成功的uid',
                'fail'    => 'rows #数组，失败的uid',
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        return $this->lang->set(5002);
        $post = $this->request->getParams();

        foreach ($post['list'] as $item) {
            $uid = $item[0];
            $lid = $item[1];
            $user = DB::table('user')
                      ->find($uid);

            if (!$user) {
                return $this->lang->set(10006);
            }
            /*================================日志操作代码=================================*/
            $user = DB::table('user')
                ->select('user.name as user_name','ranting')
                ->where('id', $uid)
                ->get()
                ->first();
            $user=(array)$user;

            $level=DB::table('user_level')
                ->get()
                ->toArray();
            foreach ($level as $item) {
                $item=(array)$item;
                if($item['id']==$user['ranting']){
                    $old=$item['name'];
                }
                if($item['id']==$lid){
                    $new=$item['name'];
                }
            }
            /*==================================================================================*/

            $rs = DB::table('user')
                    ->where('id', $uid)
                    ->update(['ranting' => $lid]);

            // 更新层级统计人数
            \Model\UserLevel::whereRaw('1')
                        ->update(['user_count' => DB::raw("(SELECT COUNT(*) FROM `user` WHERE `ranting` = `user_level`.`id`)")]);

            if ($rs === false) {
                return $this->lang->set(-2);
            }
            /*================================日志操作代码=================================*/
            $sta = $rs === false ? 0 : 1;
            $old = isset($old) ? $old : '';
            $new = isset($new) ? $new : '';
            (new Log($this->ci))->create($uid, $user['user_name'], Log::MODULE_USER, '会员管理', '基本信息', '调级', $sta, "会员分层 [$old]更改为[$new]");
            /*==================================================================================*/

        }
        return $this->lang->set(0);
    }
};
