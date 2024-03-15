<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
class HelpCopy extends \Illuminate\Database\Eloquent\Model {

//    protected $dateFormat = 'Y-m-d H:i:s';

//    const UPDATED_AT = 'updated';
//
//    const CREATED_AT = 'created';

    public  $timestamps = false;

    protected $table = 'help_copy';

//    protected $hidden = ['lang'];

    /**
     * @param \DateTime|int $value
     * @return false|int
     * @author dividez
     */
//    public function fromDateTime($value){
//        return strtotime(parent::fromDateTime($value));
//    }

    public static function getById($id){

        return self::find($id);

    }


}