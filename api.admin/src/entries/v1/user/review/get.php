<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/5 14:53
 */

use Logic\Admin\BaseController;
use Utils\Utils;
use lib\validate\BaseValidate;

return new class() extends BaseController {
//  const STATE = \API::DRAFT;
    const TITLE = '用户审核列表';
    const DESCRIPTION = '所有用户';

    const QUERY = [
        'status'        => 'enum[0,1,2]() #审核状态,0 审核中，1 通过，2 拒绝',
        'created'       => 'string() #创建时间，起点',
        'created_end'   => 'string() #创建时间，终点',
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            "id"                 => "1",
            "account"            => "string #用户账号",
            "password"           => "string #密码",
            "pin_password"       => "pin密码 #密码",
            'bank_name'          => 'string #银行名称',
            "bank_account_name"  => "string #开户名",
            "bank_card"          => "string #银行卡号",
            "account_bank"       => "string #开户行",
            "status"             => "tinyint #审核状态,0 审核中，1 通过，2 拒绝",
            "created"            => "datetime #eg:2017-08-31 02:56:26",
            "updated"            => "datetime #eg:2017-08-31 03:03:32",
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {

        (new BaseValidate([
            ['status', 'in:0,1,2'],
            ['updated', 'dateFormat:Y-m-d'],
            ['updated', 'dateFormat:Y-m-d'],
        ],
        ))->paramsCheck('', $this->request, $this->response);

        $params = $this->request->getParams();

        $rs = $this->getUserDataReviewList($params, $params['page'], $params['page_size']);

        return $rs;
    }

    protected function getUserDataReviewList($params, $page = 1, $page_size = 15) {
        if ($page == 1) {
            $this->redis->set('admin:UnreadNum4', date('Y-m-d H:i:s'));
        }

        $subQuery = DB::table('user_data_review as ud');
        $subQuery->select(['ud.account','ud.account_bank',
            'ud.created_id','ud.id','ud.name','ud.operator_id',
            'ud.operator_name','ud.password','ud.pin_password','ud.rejection_reason',
            'ud.remarks','ud.salt','ud.status','ud.update_content','ud.updated',
            'ud.bank_account_name','ud.bank_card','ud.bank_id','ud.created','ud.image']);
        $params['created']         = isset($params['created']) ? $params['created'] : '';
        $params['created_end']     = isset($params['created_end']) ? $params['created_end'] . ' 23:59:59' : '';
        $subQuery = isset($params['status']) ? $subQuery->where('status', '=', $params['status']) : $subQuery;
        $subQuery = isset($params['account']) ? $subQuery->where('account', '=', $params['account']) : $subQuery;

        if ($params['created']) {
            $subQuery->whereBetween('ud.created', [
                $params['created'],
                $params['created_end'],
            ]);
        }
        if (isset($params['account'])) {
            $subQuery->where('ud.account', '=', $params['account']);
        }
        if (isset($params['name'])) {
            $subQuery->leftJoin('admin_user','admin_user.id','=','ud.created_id');
            $subQuery->where('admin_user.username', '=', $params['name']);
        }
        $total = $subQuery->count();
        $subQuery->orderBy('ud.status');
        $subQuery->orderBy('ud.created','desc');
        if (!$total) {
            return [];
        }

        $list = $subQuery->forPage($page, $page_size)->get()->toArray();

        $username_arr  = $this->getAdminUsername($list);
        //修改内容字段转化中文
        $cn_content=[];
        foreach ($list as $k => &$v){
            //后台用户username
            if (array_key_exists($v->created_id,$username_arr)){
                $v->created_id  = $username_arr[$v->created_id];
            }
            if (array_key_exists($v->operator_id,$username_arr)){
                $v->operator_id = $username_arr[$v->operator_id];
            }
            //修改内容字段转化中文
            if ($v->update_content){
                $content  = json_decode($v->update_content,true);
                if (is_array($content)){
                    foreach ($content as $key){
                        if (array_key_exists($key,Model\UserDataReview::CN_CONTENT))
                            if (Model\UserDataReview::CN_CONTENT[$key]){
                                $cn_content[$k][] = Model\UserDataReview::CN_CONTENT[$key];
                            }
                    }
                    if (isset($cn_content[$k])) $v->update_content = $cn_content[$k];
                }
            }
            $images = json_decode($v->image,true);
            if(!empty($images)){
                foreach($images as &$val){
                    $val = showImageUrl($val, true);
                }
                unset($val);
                $v->image =  $images;
            }else{
                $v->image = [];
            }

            //银行卡明文展示
            $v->bank_card = \Utils\Utils::RSADecrypt($v->bank_card);
        }
        $attributes['total']  = $total;
        $attributes['number'] = $page;
        $attributes['size']   = $page_size;


        return $this->lang->set(0, [], $list, $attributes);
    }

    //获取后台用户名
    public function getAdminUsername($list)
    {
        $username_arr = [];
        $created_ids = array_unique(array_filter(array_column($list,'created_id')));
        $operator_ids = array_unique(array_filter(array_column($list,'operator_id')));
        $ids =array_merge($created_ids,$operator_ids);

        $admin_username = DB::table('admin_user')->whereIn('id',$ids)->select('id','username')->get()->toArray();
        foreach ($admin_username as $k => $v){
            $username_arr[$v->id] = $v->username;
        }
        return $username_arr;
    }
};
