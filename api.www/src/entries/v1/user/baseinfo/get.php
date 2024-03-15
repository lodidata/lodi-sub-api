<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "个人中心-个人资料-完善资料操作项";
    const TAGS = "个人中心";
    const QUERY = [
       //"type" => "string() #(type=h5) "
   ];
    const SCHEMAS = [
       "name"       => "string() #姓名",
       "user_name"  => "string() #用户名称",
       "gender"     => "int() #性别 (1:男,2:女,3:保密)",
       "city"       => "int() #城市",
       "address"    => "string() #详细地址",
       "nationality" => "int() #国籍",
       "birth_place" => "int() #出生地",
       "currency"   => "int() #货币",
       "first_account" => "int() #首选账户",
       "qq"         => "string() #qq",
       "wechat"     => "string() #wechat",
       "skype"      => "string() #skype",
       "id_card"    => "string() #type=app时 身份证号码",
       "mobile"     => "string() #type=app时 手机号码",
       "email"      => "string() #type=app时 邮箱"
   ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $type = $this->request->getQueryParam('type');
        $user = new \Logic\User\User($this->ci);
        $userId = $this->auth->getUserId();
        $info = $user->getInfo($userId);
        $area = \Model\Area::getArea();
        $countryList = \Model\Area::getCountryList(1);


        //$info['avatar'] = $info['avatar'] - 1;
        if (is_null($info['avatar'])) {
            $info['avatar'] = 0;
        }
//        if ($type == 'app' || $type == 'h5') {
            $var = file_get_contents(__DIR__ . '/get2.json');
            $var = json_decode($var);
            if (!empty($info['true_name'])) {
                $var[1]->value = $info['true_name'];
                $var[1]->edit = 0;
            }
            $var[0]->value = '/static/tctest/default.jpg';
            // $var[0]->info[$info['avatar']]->check = 1;
            $var[2]->value = $info['user_name'];
            $var[3]->value = $info['gender'];
            $var[4]->info = $area;
            $var[4]->value = $info['region_id'];
            $var[5]->value = $info['address'];
            $var[6]->info = [];
            $var[7]->info = [];
            $var[8]->info = [];
            $var[8]->value = '';
            $var[9]->info = [];
            $var[9]->value = '';
            $var[10]->value = !empty($info['email']) ? $info['email'] : '';
            $var[11]->value = !empty($info['idcard']) ? $info['idcard'] : '';
            $var[12]->value = !empty($info['mobile']) ? $info['mobile'] : '';
            $var[13]->value = $info['nickname'] ? $info['nickname'] : '';
            $var[14]->value = $info['birth'] ? $info['birth'] : '';
            $var[15]->value = $info['qq'] ? $info['qq'] : '';
            $var[16]->value = $info['weixin'] ? $info['weixin'] : '';
            $var[17]->value = $info['skype'] ? $info['skype'] : '';
//        }  else {
//            $var = file_get_contents(__DIR__ . '/get.json');
//            $var = json_decode($var);
//            if (!empty($info['true_name'])) {
//                $var[0]->value = $info['true_name'];
//                $var[0]->edit = 0;
//            }
//            // $var[0]->value = 'http://szfungame.oss-cn-shenzhen.aliyuncs.com/tctest/default.jpg';
//            $var[1]->value = $info['name'];
//            $var[2]->value = $info['gender'];
//            $var[3]->info = $area;
//            $var[3]->value = $info['region_id'];
//            $var[4]->value = $info['address'];
//            $var[5]->info = $countryList;
//            $var[6]->info = $countryList;
//            $var[7]->info = [];
//            $var[7]->value = '';
//            $var[8]->info = [];
//            $var[8]->value = '';
//            $var[9]->value = $info['qq'] ? $info['qq'] : '';
//            $var[10]->value = $info['weixin'] ? $info['weixin'] : '';
//            $var[11]->value = $info['skype'] ? $info['skype'] : '';
//            $var[12]->value = $info['nickname'] ? $info['nickname'] : '';
//            $var[13]->value = $info['birth'] ? $info['birth'] : '';
//            $var[14]->value = $info['email'] ? $info['email'] : '';
//        }
        return $var;
    }
};