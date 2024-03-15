<?php

namespace Model\Admin;

class Profile extends LogicModel {

    protected $table = 'profile';

    public $timestamps = false;

    protected $primaryKey = 'user_id';

    public $title_param = 'name';

    public $desc = [
        'idcard' => [
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'decrypt' => true,
            'key' => '身份证号',
        ],
    ];

}

