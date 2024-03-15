<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/21
 * Time: 17:54
 */

namespace Model;

class Label extends \Illuminate\Database\Eloquent\Model{
    protected $table = 'label';
    public function getIdByTags($tagname) {
        $data = \DB::table('label')
                ->select('id')
                ->where('title',$tagname)
                ->get()->toArray();
        return $data[0]->id;

    }
}