<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
class CurrencyExchangeRate extends \Illuminate\Database\Eloquent\Model {

//    protected $dateFormat = 'Y-m-d H:i:s';

    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    protected $table = 'currency_exchange_rate';


    /**
     * @param \DateTime|int $value
     * @return false|int
     * @author dividez
     */

    public static function getById($id){

        return self::find($id);

    }



}