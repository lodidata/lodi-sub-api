<?php
namespace Logic\Recharge;

use Logic\Recharge\Traits\RechargeAutoTopUpPay;
use Logic\Recharge\Traits\RechargeDeposit;
use Logic\Recharge\Traits\RechargeLog;
use Logic\Recharge\Traits\RechargePay;
use Logic\Recharge\Traits\RechargeExchange;
use Logic\Recharge\Traits\RechargePayWebsite;
use Logic\Recharge\Traits\RechargeShareManual;
use Logic\Recharge\Traits\RechargeTzHandRecharge;
use Logic\Recharge\Traits\RechargeHandoutActivity;
use Logic\Recharge\Traits\RechargeHandDeposit;
use Logic\Recharge\Traits\RechargeOnlinePay;
use Logic\Recharge\Traits\RechargeAddActivity;
use Logic\Recharge\Traits\RechargeHandApply;
use Logic\Recharge\Traits\RechargeCallback;
use Logic\Recharge\Traits\RechargeDirectManual;

use Logic\Recharge\Traits\RechargeAdminOptMoney;
class Recharge extends \Logic\Logic {
    use RechargePay;
    use RechargeDeposit;
    use RechargeTzHandRecharge;
    use RechargeHandoutActivity;
    use RechargeHandDeposit;
    use RechargeOnlinePay;
    use RechargePayWebsite;
    use RechargeAddActivity;
    use RechargeAdminOptMoney;
    use RechargeHandApply;
    use RechargeLog;
    use RechargeCallback;
    use RechargeAutoTopUpPay;
    use RechargeShareManual;
    use RechargeDirectManual;

    private $next = false;

    public function getThirdClass($third_code)
    {
        $className = "Logic\Recharge\Pay\\" . strtoupper($third_code);
        if (class_exists($className)) {
            $obj = new $className;   //初始化类
            return $obj;
        } else
            return false;
    }

    public function existThirdClass($third_code)
    {
        $className = "Logic\Recharge\Pay\\" . strtoupper($third_code);
        if (class_exists($className)) {
            return true;
        } else
            return false;
    }
}