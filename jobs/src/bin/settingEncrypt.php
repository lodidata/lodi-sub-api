<?php
    if(defined("ENCRYPTMODE") && ENCRYPTMODE) {
        die('已加密过，请务重复加密，以免失误'.PHP_EOL);
    }
    $str = '<?php
            if(!defined("RUNMODE")) {
                define("RUNMODE", "dev");
            }
            if(!defined("ENCRYPTMODE")) {
                define("ENCRYPTMODE", true);
            }
            return ';
    $file = __DIR__ . '/../../../config/settings.php';
    $settings = require_once $file;
    $settings['settings'] = \Utils\Utils::settleCrypt($settings['settings']);
    file_put_contents($file,$str);
    file_put_contents($file,var_export($settings,true).';'.PHP_EOL,FILE_APPEND);
    $settings = require_once $file;
    print_r($settings['settings']['db']['default']);
