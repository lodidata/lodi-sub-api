<?php
$captcha = new \Logic\Captcha\Captcha($app->getContainer());
$res = $captcha->sendTextCode('+8613928469804');
print_r($res->get());