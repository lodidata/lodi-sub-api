<?php
$captcha = new \Logic\Captcha\Captcha($app->getContainer());
$res = $captcha->sendTextCodeByEmail(1, '418796717@qq.com');

// $res = $captcha->sendTextCodeByEmail(2, 'firebellqq@163.com');
print_r($res->get());