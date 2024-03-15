<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 12:22
 */

namespace Model\Admin;
class UserLevel extends \Illuminate\Database\Eloquent\Model {

    const UPDATED_AT = 'updated';

    const CREATED_AT = 'created';

    protected $table = 'user_level';

}