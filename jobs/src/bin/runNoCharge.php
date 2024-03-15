<?php
/**
 * dba定时任务导出
 * @author Taylor 2018-11-12
 */
date_default_timezone_set('PRC');

$db = $app->getContainer()->db;

//$time = strtotime('-1 day');
$time = time();
$yesterday = date('Y-m-d', strtotime('-1 day', $time));//昨天
$before_yesterday = date('Y-m-d', strtotime('-2 day', $time));//前天

$sql = "SELECT u.id, u.name, p.name real_name, u.mobile, f.balance / 100
  FROM `user` u INNER JOIN funds f ON u.wallet_id = f.id
  INNER JOIN `profile` p ON p.user_id = u.id
  WHERE u.tags NOT IN (4, 7)
  And u.created>= '{$before_yesterday}'
  AND u.created < '{$yesterday}'
  AND NOT EXISTS (SELECT 1 FROM funds_deposit t WHERE created >= '{$before_yesterday}'
         AND `status` = 'paid' AND money > 0 AND u.id = t.user_id) GROUP BY u.id";

$user_data = $db->getConnection()->select($sql);

$csv_str = '';
foreach ($user_data as $data) {
    $data = json_decode(json_encode($data),true);
    if(!empty($data['mobile'])){
        $data['mobile'] = \Utils\Utils::RSADecrypt($data['mobile']);
    }
    $str = join(",",$data);
    $csv_str .= "'".str_replace(",","','",$str)."'"."\r\n";
}
$csv_file = __DIR__.'/nochangelog/'.date('Ymd', $time).'.csv';
file_put_contents($csv_file, $csv_str);

$mail_data = [
    'title'   => "凤凰{$yesterday}创建,并且之后未充值的用户数据",
//    'content' => '安全中心邮箱验证码:',
    'email'   => 'fenghuangylc@gmail.com',
];

try {
    $website = $app->getContainer()->get('settings')['website'];
    $mailer = new \PHPMailer();
    $mailer->CharSet = 'UTF-8';
    // todo: 替换成真正的配置
    $setting = \Model\MailConfig::first();
    if (empty($setting)) {
        throw new \Exception("系统没有配置邮件发送功能");
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
    $mailer->addAddress($mail_data['email'], null);
    $mailer->isHTML(0);

    $mailer->Subject = $mail_data['title'];
    $mailer->AllowEmpty = true;
//    $mailer->Body = '';
//    $mailer->AltBody = strip_tags($mail_data['content']);
    $mailer->addAttachment($csv_file);

    if (!$mailer->send()) {
        $logger->error('sendTextCodeByEmail:' . $mailer->ErrorInfo);
    }
} catch (\Exception $e) {
    $logger->error('sendTextCodeByEmail:' . $e->getMessage());
}
