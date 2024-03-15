<?php
use Utils\Www\Action;
use Respect\Validation\Validator;
use Logic\Define\Lang;
return new class extends Action {
    const TITLE = "文档编写中";
    const HINT = "开发的技术提示信息";
    const DESCRIPTION = "文档编写中";



    public function run() {
        $user = new \Logic\User\User($this->ci);
        $datas = new \Logic\Set\Datas($this->ci);
        $username = $this->generate_username(6);
//        $password = $this->create_password();
//        $res = $user->register($username, 123456, '');


        while(true){
            $res = $user->register($username, 123456, '');
            $TxtFileName = "test.txt";
//以读写方式打写指定文件，如果文件不存则创建
            if( ($TxtRes=fopen ($TxtFileName,"w+")) === FALSE){
//                echo("创建可写文件：".$TxtFileName."失败");
//                exit();
            }
//            echo ("创建可写文件".$TxtFileName."成功！</br>");
            $StrConents = "Welcome To ItCodeWorld!";//要 写进文件的内容
            if(!fwrite ($TxtRes,$StrConents)){ //将信息写入文件
                echo ("尝试向文件".$TxtFileName."写入".$StrConents."失败！");
                fclose($TxtRes);
                exit();
            }
            echo ("尝试向文件".$TxtFileName."写入".$StrConents."成功！");
            fclose ($TxtRes); //关闭指针
            sleep(1);
        }

    }

    public function test(){

        echo 'start.';
        while(!file_exists('close.txt')){
            $fp = fopen('test.txt','a+');
            fwrite($fp,date("Y-m-d H:i:s") . " 成功了！rn");
            fclose($fp);
            sleep(10);
        }
        echo 'end.';
        $mobile = $this->request->getParam('mobile');
        $username = $this->request->getParam('user_name');
        $telphoneCode = $this->request->getParam('telphone_code');
        $telCode = $this->request->getParam('tel_code');
        $invitCode = $this->request->getParam('invit_code');
        $password = $this->request->getParam('password');

        $user = new \Logic\User\User($this->ci);
        $mobileRegister = false;
        $datas = new \Logic\Set\Datas($this->ci);

        ignore_user_abort(); // 后台运行
        set_time_limit(0); // 取消脚本运行时间的超时上限
        echo 'start.';
        while(!file_exists('close.txt')){
            $fp = fopen('test.txt','a+');
            fwrite($fp,date("Y-m-d H:i:s") . " 成功了！rn");
            fclose($fp);
            sleep(10);
        }
        echo 'end.';
        exit;
        $res = $user->register($username, $password, $invitCode);
        // 自动登录
        $res = $this->auth->login($username, $password);
        (new \Logic\Activity\Activity($this->ci))->bindInfo($this->auth->getUserId(), "");
    }

    //自动为用户随机生成用户名(长度6-13)
    function create_password($pw_length = 4){
        $randpwd = '';
        for ($i = 0; $i < $pw_length; $i++){
            $randpwd .= chr(mt_rand(33, 126));
        }
        return $randpwd;
    }
    function generate_username( $length = 6 ) {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ( $i = 0; $i < $length; $i++ )
        { // www.jbxue.com
            // 这里提供两种字符获取方式
            // 第一种是使用substr 截取$chars中的任意一位字符；
            // 第二种是取字符数组$chars 的任意元素
            // $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $password;
    }
};