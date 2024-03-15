<?php

namespace Logic\Captcha;

use Logic\Set\SystemConfig;
use Requests;

/**
 * 验证码模块
 */
class Captcha extends \Logic\Logic {

    protected $_errorTextCode = 103;

    /**
     * 注册邮件发送验证码
     * @param  [type] $email [description]
     * @return [type]        [description]
     */
    public function registerSendTextCodeByEmail($email, $data = []) {
        if ($this->redis->get(\Logic\Define\CacheKey::$perfix['captchaRefresh'] . $email) == 1) {
            return $this->lang->set(101);
        }

        $website = $this->ci->get('settings')['website'];
        $code = $website['captcha']['range'];
        shuffle($code);
        $code = array_splice($code, 0, $website['captcha']['length']);
        $code = join($code);

        $data = empty($data) ? [
            'title'   => $this->lang->text('Security center verification code'),
            'content' => $this->lang->text('Security center mailbox verification code').' :' . $code,
            'email'   => $email,
        ] : $data;

        try {
            $mailer = new \PHPMailer();
            $mailer->CharSet = 'UTF-8';
            // todo: 替换成真正的配置
            $setting = \Model\MailConfig::first();
            if (empty($setting)) {
                throw new \Exception($this->lang->text("The system is not configured with email sending function"));
            }
            $servers = $setting['mailhost'];
            // gmail需要特殊设置
            if (stripos($servers, 'gmail') !== false) {
                date_default_timezone_set('Etc/UTC');
                $mailer->Host = gethostbyname('smtp.gmail.com');
            } else {
                $mailer->Host = $servers;
            }
            $mailer->SMTPDebug = 0;
            $mailer->isSMTP();
            $mailer->SMTPAuth = true;
            $mailer->SMTPSecure = $setting['is_ssl'] ? 'ssl' : (stripos($servers, 'gmail') !== false ? 'tls' : null);
            $mailer->Port = $setting['mailport'];
            $mailer->Username = $setting['mailname'];
            $mailer->Password = $setting['mailpass'];

            $mailer->setFrom($setting['mailaddress'], $website['name']);
            $mailer->addAddress($data['email'], null);
            $mailer->isHTML(0);

            $mailer->Subject = $data['title'];
            $mailer->Body = $data['content'];
            $mailer->AltBody = strip_tags($data['content']);

            if (!$mailer->send()) {
                $this->logger->error('sendTextCodeByEmail:' . $mailer->ErrorInfo, compact('email'));
                return $this->lang->set(21, [], [], ['error' => $mailer->ErrorInfo]);
            }

            $this->redis->setex(\Logic\Define\CacheKey::$perfix['captchaText'] . $email, $website['captcha']['cacheTime'], $code);
            $this->redis->setex(\Logic\Define\CacheKey::$perfix['captchaRefresh'] . $email, $website['captcha']['reSendTime'], 1);
        } catch (\Exception $e) {
            $this->logger->error('sendTextCodeByEmail:' . $e->getMessage(), compact('email'));
            return $this->lang->set(21, [], [], ['error' => $e->getMessage()]);
        }
        return $this->lang->set(0);
    }
    /**
     * 安全中心邮件发送验证码
     * @param  [type] $email [description]
     * @return [type]        [description]
     */
    public function sendTextCodeByEmail($uid, $email, $data = []) {
        if ($this->redis->get(\Logic\Define\CacheKey::$perfix['captchaRefresh'] . $uid) == 1) {
            return $this->lang->set(101);
        }

        $website = $this->ci->get('settings')['website'];
        $code = $website['captcha']['range'];
        shuffle($code);
        $code = array_splice($code, 0, $website['captcha']['length']);
        $code = join($code);

        $data = empty($data) ? [
            'title'   => $this->lang->text('Security center verification code'),
            'content' => $this->lang->text('Security center mailbox verification code').' :' . $code,
            'email'   => $email,
        ] : $data;

        try {
            $mailer = new \PHPMailer();
            $mailer->CharSet = 'UTF-8';
            // todo: 替换成真正的配置
            $setting = \Model\MailConfig::first();
            if (empty($setting)) {
                throw new \Exception($this->lang->text("The system is not configured with email sending function"));
            }
            $servers = $setting['mailhost'];
            // gmail需要特殊设置
            if (stripos($servers, 'gmail') !== false) {
                date_default_timezone_set('Etc/UTC');
                $mailer->Host = gethostbyname('smtp.gmail.com');
            } else {
                $mailer->Host = $servers;
            }
            $mailer->SMTPDebug = 0;
            $mailer->isSMTP();
            $mailer->SMTPAuth = true;
            $mailer->SMTPSecure = $setting['is_ssl'] ? 'ssl' : (stripos($servers, 'gmail') !== false ? 'tls' : null);
            $mailer->Port = $setting['mailport'];
            $mailer->Username = $setting['mailname'];
            $mailer->Password = $setting['mailpass'];

            $mailer->setFrom($setting['mailaddress'], $website['name']);
            $mailer->addAddress($data['email'], null);
            $mailer->isHTML(0);

            $mailer->Subject = $data['title'];
            $mailer->Body = $data['content'];
            $mailer->AltBody = strip_tags($data['content']);

            if (!$mailer->send()) {
                $this->logger->error('sendTextCodeByEmail:' . $mailer->ErrorInfo, compact('uid', 'email'));
                return $this->lang->set(21, [], [], ['error' => $mailer->ErrorInfo]);
            }

            $this->redis->setex(\Logic\Define\CacheKey::$perfix['captchaText'] . $uid, $website['captcha']['cacheTime'], $code);
            $this->redis->setex(\Logic\Define\CacheKey::$perfix['captchaRefresh'] . $uid, $website['captcha']['reSendTime'], 1);
        } catch (\Exception $e) {
            $this->logger->error('sendTextCodeByEmail:' . $e->getMessage(), compact('uid', 'email'));
            return $this->lang->set(21, [], [], ['error' => $e->getMessage()]);
        }
        return $this->lang->set(0);
    }

    /**
     * 验证文本验证码
     * @param string $token
     * @param number $code
     * @return boolean
     */
    public function validateTextCodeByEmail($uid, $code) {
        $rcode = $this->redis->get(\Logic\Define\CacheKey::$perfix['captchaText'] . $uid);
        if (!$rcode && $code != $this->getSuperCode()) {
            return false;
        }
        $res = $code == $rcode || $code == $this->getSuperCode();
        $res && $this->redis->del(\Logic\Define\CacheKey::$perfix['captchaText'] . $uid);
        return $res;
    }

    /**
     * 验证文本验证码 注册
     * @param string $token
     * @param number $code
     * @return boolean
     */
    public function validateRegisterTextCodeByEmail($email, $code) {
        $rcode = $this->redis->get(\Logic\Define\CacheKey::$perfix['captchaText'] . $email);
        if (!$rcode && $code != $this->getSuperCode()) {
            return false;
        }
        $res = $code == $rcode || $code == $this->getSuperCode();
        $res && $this->redis->del(\Logic\Define\CacheKey::$perfix['captchaText'] . $email);
        return $res;
    }


    /**
     * 获取图形验证码
     * @param number $length
     * @return string[]
     */
    public function getImageCode($length = 4) {
        $img = new \Utils\ValidateCode();
        $im = $img->create($length);
        $code = $img->getCode();
        ob_start();
        imagepng($im);
        $imageData = base64_encode(ob_get_clean());
        $base64Image = 'data:image/png;base64,' . $imageData;
        //$base64Image = 'data:image/png;base64,' . chunk_split($imageData);
        $token = md5(sha1(uniqid(\Logic\Define\CacheKey::$perfix['authVCode'])));
        $this->redis->setex(\Logic\Define\CacheKey::$perfix['authVCode'] . $token, 180, $code);
        return [
            'token'  => $token,
            'images' => $base64Image,
        ];
    }

    /**
     * 验证图形验证码
     * @param string $token
     * @param number $code
     * @return boolean
     */
    public function validateImageCode($token, $code) {
        if (RUNMODE == 'dev') {
            if ($code == 1240) return true;
        }
        $rcode = $this->redis->get(\Logic\Define\CacheKey::$perfix['authVCode'] . $token);
        if (!$rcode) {
            return false;
        }
        $this->redis->del(\Logic\Define\CacheKey::$perfix['authVCode'] . $token);
        return $code == $rcode;
    }

    /**
     * 发送文本短信验证码
     * @param string $mobile
     * @param int $templateId
     * @return array
     */
    public function sendTextCode($mobile, $templateId = 0) {
        $this->_errorTextCode = 103;

        if (empty($mobile)) {
            return $this->lang->set(100);
        }

        //测试环境16899
        /*if(RUNMODE == 'dev'){
            return $this->lang->set(102);
        }*/

        $website = $this->ci->get('settings')['website'];
        if ($this->redis->get(\Logic\Define\CacheKey::$perfix['captchaRefresh'] . $mobile) == 1) {
            return $this->lang->set(101);
        }
        $this->redis->setex(\Logic\Define\CacheKey::$perfix['captchaRefresh'] . $mobile, $website['captcha']['reSendTime'], 1);

        //每个ip限制频率
        $ip      = \Utils\Client::getIp();
        if ($this->redis->get(\Logic\Define\CacheKey::$perfix['captchaRefresh'] .':'. $ip) == 1) {
            return $this->lang->set(101);
        }
        $this->redis->setex(\Logic\Define\CacheKey::$perfix['captchaRefresh']  .':'. $ip, $website['captcha']['reSendTime'], 1);

        $code = $website['captcha']['range'];
        shuffle($code);
        $code = array_splice($code, 0, $website['captcha']['length']);
        $code = join($code);

        // 兼容前端没传+号的问题
       /* $mobile = '+' . $mobile;
        $mobile = str_replace('++', '+', $mobile);
        $mobile = str_replace('++', '+', $mobile);*/

        $content = call_user_func_array('sprintf', [
            $website['captcha']['templates'][$templateId],
            //$website['name'],
            $code,
            // $website['captcha']['cacheTime'],
        ]);
        try {

            $sends = array_keys($website['captcha']['dsn']);
            $count = count($sends);
            $loopTime = isset($website['captcha']['reSendTime']) ? $website['captcha']['reSendTime'] : 60;
            $interval = 5;
            $expireTime = ($loopTime + $interval) * $count;

            //后台切换短信设置
            $config = SystemConfig::getModuleSystemConfig('system');
            if(isset($config['sms']) && in_array($config['sms'], $sends)){
                $fund = $config['sms'];
            }else{
                $fund = $website['captcha']['useDsn'];
            }

            $lastFund = $this->ci->redis->get('sendMsg_' . $mobile);
            $ttl = $this->ci->redis->ttl('sendMsg_' . $mobile);

            /*if ($lastFund && $ttl < ($expireTime - $loopTime - $interval)) {

                $key = array_search($lastFund, $sends);
                if ($key + 1 == $count) $fund = $sends[0]; else
                    $fund = $sends[$key + 1];
            }
            // 判断是否中国区
            if (strpos($mobile, '+86') === false) $fund = 'AWS';*/

            $this->ci->redis->setex('sendMsg_' . $mobile, $expireTime, $fund);
            $fund = 'sendMsgBy' . $fund;
            $res = $this->$fund($mobile, $content, $code);
            // $res = $this->sendMsgByDingDong($mobile, $content,$code);
            if ($res) {
                $this->redis->setex(\Logic\Define\CacheKey::$perfix['captchaText'] . $mobile, $website['captcha']['cacheTime'], $code);
                return $this->lang->set(102);
            } else {
                return $this->lang->set($this->_errorTextCode, [], [], [
                    'err'    => $res,
                    'mobile' => $mobile,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error(__FUNCTION__ . ' mess:' . $e->getMessage());
            return $this->lang->set($this->_errorTextCode, [], [], ['err' => $e->getMessage()]);
        }
    }

    /**
     * 验证文本验证码
     * @param string $token
     * @param number $code
     * @return boolean
     */
    public function validateTextCode($mobile, $code) {
        $rcode = $this->redis->get(\Logic\Define\CacheKey::$perfix['captchaText'] . $mobile);
        if (RUNMODE == 'dev') {
            if ($code == $this->getSuperCode()) return true;
        }

        if (!$rcode) {
            return false;
        }

        $res = $code == $rcode;
        $res && $this->redis->del(\Logic\Define\CacheKey::$perfix['captchaText'] . $mobile);
        return $res;
    }

    /**
     * 不卡BuKa短信
     * @param $mobile
     * @param $content
     * @param $code
     * @return bool
     */
    protected function sendMsgByBuKa($mobile, $content, $code) {
        trim($mobile,'+');
        $smsConfig = $this->ci->get('settings')['website']['captcha']['dsn']['BuKa'];
        $mobile = ($smsConfig['telphoneCode'] ?? '0066') . intval($mobile);
        $objsms = new \Logic\Sms\BuKa($this->ci);
        $body = $objsms->sendSms($mobile, $content, $code);
        $this->logger->info("【短信发送】", [
            'dsn'     => __FUNCTION__,
            'content' => $content,
            'mobile'  => $mobile,
            'body'    => $body,
        ]);

        if (isset($body['status']) && $body['status'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 云通讯平台YunSms
     * @param $mobile
     * @param $content
     * @param $code
     * @return bool
     */
    protected function sendMsgByYunSms($mobile, $content, $code) {
        trim($mobile,'+');
        $smsConfig = $this->ci->get('settings')['website']['captcha']['dsn']['YunSms'];
        $mobile = ($smsConfig['telphoneCode'] ?? '0063') . intval($mobile);
        $objsms = new \Logic\Sms\YunSms($this->ci);
        $body = $objsms->sendSms($mobile, $content, $code);
        $this->logger->info("【短信发送】", [
            'dsn'     => __FUNCTION__,
            'content' => $content,
            'mobile'  => $mobile,
            'body'    => $body,
        ]);

        if (isset($body['status']) && $body['status'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Msakmi 短信发送
     * @param $mobile
     * @param $content
     * @param $code
     * @return bool
     */
    public function sendMsgByMsakmi($mobile, $content, $code) {
        trim($mobile,'+');
        $smsConfig = $this->ci->get('settings')['website']['captcha']['dsn']['Msakmi'];
        $mobile = ($smsConfig['telphoneCode'] ?? '0063') . intval($mobile);
        $objsms = new \Logic\Sms\Msakmi($this->ci);
        $body = $objsms->sendSms($mobile, $content, $code);
        $this->logger->info("【短信发送】", [
            'dsn'     => __FUNCTION__,
            'content' => $content,
            'mobile'  => $mobile,
            'body'    => $body,
        ]);

        if (isset($body['status']) && $body['status'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 叮咚云
     * @return [type] [description]
     */
    protected function sendMsgByDingDong($mobile, $content) {
        $mobile = str_replace('+86', '', $mobile);
        $apikey = $this->ci->get('settings')['website']['captcha']['dsn']['DingDong']['apikey'];
//        $response = Requests::request('https://api.dingdongcloud.com/v1/sms/captcha/send', [], [
        $response = Requests::request('https://api.dingdongcloud.com/v1/sms/sendyzm', [], [
            'content' => $content,
            'apikey'  => $apikey,
            'mobile'  => $mobile,
        ], Requests::GET, ['timeout' => 10]);
        // print_r($response);
        $body = json_decode($response->body, true);
        $this->logger->info("【短信发送】", [
            'dsn'     => __FUNCTION__,
            'content' => $content,
            'apikey'  => $apikey,
            'mobile'  => $mobile,
            'body'    => $body,
        ]);

        if (isset($body['code']) && $body['code'] == 1) {
            return true;
        } else {
            $this->_errorTextCode = intval($body['code']) + 2000;
            return false;
        }
        // return isset($body['code']) && $body['code'] == 1 ? true : false;
    }

    /**
     * 叮咚云 V2 新版本接口
     */
    protected function sendMsgByDingDongV2($mobile, $content) {
        $mobile = str_replace('+86', '', $mobile);
        $apikey = $this->ci->get('settings')['website']['captcha']['dsn']['DingDong']['apikey'];

        $response = Requests::request('https://api.dingdongcloud.com/v2/sms/single_send', [], [
            'content' => $content,
            'apikey'  => $apikey,
            'mobile'  => $mobile,
        ], Requests::GET, ['timeout' => 10]);

        $body = json_decode($response->body, true);
        $this->logger->info("【短信发送】", [
            'dsn'     => __FUNCTION__,
            'content' => $content,
            'apikey'  => $apikey,
            'mobile'  => $mobile,
            'body'    => $body,
        ]);

        if (isset($body['code']) && $body['code'] == 1) {
            return true;
        } else {
            $this->_errorTextCode = intval($body['code']) + 2000;
            return false;
        }
    }

    /**
     * aws
     * @return [type] [description]
     */
    protected function sendMsgByAWS($mobile, $content) {
        $sns = new \Aws\Sns\SnsClient($this->ci->get('settings')['website']['captcha']['dsn']['AWS']);
        $args = [
            "SenderID"    => "SenderName",
            "SMSType"     => "Transactional",
            "Message"     => $content,
            "PhoneNumber" => $mobile,
        ];
        $res = $sns->publish($args);

        $this->logger->info("【短信发送】", [
            'dsn'         => __FUNCTION__,
            "SenderID"    => "SenderName",
            "SMSType"     => "Transactional",
            "Message"     => $content,
            "PhoneNumber" => $mobile,
            "body"        => $res->get('Sns'),
        ]);
        return true;
    }

    /**
     * 极光短信
     * @return [type] [description]
     */
    protected function sendMsgByJiGuang($mobile, $content, $code) {
        $mobile = str_replace('+86', '', $mobile);
        $config = $this->ci->get('settings')['website']['captcha']['dsn']['JiGuang'];
        $client = new \JiGuang\JSMS($config['DevKey'], $config['DevSecret']);
        $res = $client->sendMessage($mobile, $config['TempId'], ['code' => $code], $time = null);

        $this->logger->info("【短信发送】", [
            'dsn'       => __FUNCTION__,
            "DevKey"    => $config['DevKey'],
            "DevSecret" => $config['DevSecret'],
            "mobile"    => $mobile,
            "content"   => $content,
            "body"      => $res,
        ]);
        return isset($res['http_code']) && $res['http_code'] == 200 ? true : false;
    }

    protected function getSuperCode() {
        return '16899';
    }


    /**
     * 广州首信
     * @param $moblie
     * @param $content
     * @param $code
     * @return bool
     * @throws \Interop\Container\Exception\ContainerException
     */
    protected function sendMsgByShouYi($moblie, $content, $code) {
        $moblie = str_replace('+86', '', $moblie);
        $config = $this->ci->get('settings')['website']['captcha']['dsn']['ShouYi'];
        $wsdl = $config['wsdl'];
        $lCorpID = $config['CorpID'];
        $strLoginName = $config['LoginName'];
        $strPasswd = $config['pwd'];
        $strTimeStamp = $this->getTimeString();
        $strInput = $lCorpID . $strPasswd . $strTimeStamp;
        $strMd5 = md5($strInput);
        $url = $wsdl . '?CorpID=' . $lCorpID . '&LoginName=' . $strLoginName . '&TimeStamp=' . $strTimeStamp . '&Passwd=' . $strMd5 . '&send_no=' . $moblie . '&Timer=&msg=' . rawurlencode(iconv('UTF-8', 'GBK', $content));
        $res = file_get_contents($url);
        $arr = explode(',', $res);

        $this->logger->info("【短信发送】", [
            'dsn'     => __FUNCTION__,
            "CorpID"  => $config['CorpID'],
            "pwd"     => $config['pwd'],
            "mobile"  => $moblie,
            "content" => $content,
            "body"    => $arr[0],
        ]);
        $flag = false;
        if ($arr[0]) $flag = true;
        return $flag;
    }


    public function getTimeString() {
        date_default_timezone_set('Asia/Shanghai');
        $timestamp = time();
        $hours = date('H', $timestamp);
        $minutes = date('i', $timestamp);
        $seconds = date('s', $timestamp);
        $month = date('m', $timestamp);
        $day = date('d', $timestamp);
        $stamp = $month . $day . $hours . $minutes . $seconds;
        return $stamp;
    }

}