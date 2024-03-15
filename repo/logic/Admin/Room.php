<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/4/3
 * Time: 16:52
 */

namespace Logic\Admin;

use Model\Room as RoomModel;
use Illuminate\Database\Capsule\Manager as Capsule;

class Room extends \Logic\Logic{

    protected $Db;

    protected $roomModel;

    public function __construct($ci)
    {
        parent::__construct($ci);
       // $this->Db = new Capsule();
       // $this->Db->setFetchMode(\PDO::FETCH_ASSOC);
        $this->roomModel = new RoomModel();
    }




    public function createRoom($data){

        isset($data['id']) && $this->roomModel->id = $data['id'];
        $this->roomModel->lottery_id = $data['lottery_id'];
        $this->roomModel->hall_id = $data['hall_id'];
        $this->roomModel->room_name = $data['room_name'];
        $this->roomModel->room_level = $data['room_level'];
        $this->roomModel->number = $data['number'];
         $res = $this->roomModel->save();
        if(!$res){
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }



}
