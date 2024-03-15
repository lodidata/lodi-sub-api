<?php
use Utils\Www\Action;
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "客服账号列表";
    const DESCRIPTION = "客服账号列表";
    const TAGS = '客服';
    const SCHEMAS = [
       [
           "type"       => "string() #类型：qq，wechat",
           "name"       => "string() #客服昵称",
           "accouont"   => "string() #客服账号",
           "avatar"     => "string() #客服头像"
       ]
   ];

    public function run() {


//        (new \lib\validate\BaseValidate(
//            [
//                'type'=>'require|in:qq,wechat'
//            ]
//        ))->paramsCheck('',$this->request,$this->response);

//        $params = $this->request->getParams();

        $key = 'ServiceAccounts';
        $cachekey = Logic\Define\CacheKey::$perfix[$key];
        $accounts = $this->redis->get($cachekey);
        if(empty($accounts)){

            $accounts = DB::table('service_account')
                ->selectRaw('type,name,account,avatar')
                ->where('status',1)
//                ->where('type',$params['type'])
                ->get()
                ->toArray();

            $this->redis->setex($cachekey,60,json_encode($accounts));

        }else{
            $accounts = json_decode($accounts,true);
        }

        return $accounts;

    }
};