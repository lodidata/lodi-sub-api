<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/4/3
 * Time: 16:52
 */

namespace Logic\Admin;

use Model\Admin\Advert as AdvertModel;
use Illuminate\Database\Capsule\Manager as Capsule;
/**
 * json web token
 * 保证web api验证信息不被篡改
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/5/3 8:59
 */
class Advert extends \Logic\Logic{

    protected $Db;

    protected $advertModel;

    public function __construct($ci)
    {
        parent::__construct($ci);
        $this->Db = new Capsule();
        $this->Db->setFetchMode(\PDO::FETCH_ASSOC);
        $this->advertModel = new AdvertModel();
    }
    
    public function getAdvert($data=[])
    {
        $query = AdvertModel::from('advert as ad')
                            ->leftJoin('active as a','ad.link','=','a.id')
                            ->selectRaw('ad.*,a.title as link_name,type_id')
                            ->where('ad.pf',$data['pf'])
                            ->where('ad.type','banner');
        //多语言
        if($data['language_id']){
            $query->where('ad.language_id', $data['language_id']);
        }
        if($data['id']){
            $res = $query->find($data['id'])->toArray();
            $res[0]['picture'] = showImageUrl($res[0]['picture']);
            $attributes['total'] = 1;
        }else{
            $attributes['total'] = $query->count();
            $res = $query->orderBy('ad.created','desc')->forPage($data['page'],$data['page_size'])->get()->toArray();
            $attributes['num'] = $data['page'];
            $attributes['size'] = $data['page_size'];
            $attributes['current_page'] = $data['page'];
            //前端显示数据
            foreach($res as $key => $val){
                if($val['link_type'] == 4){
                    //查询游戏名
                    $link_arr = explode(',', $val['link']);
                    if(count($link_arr) == 3){
                        $link_name = '';
                        $game_ids = [$link_arr[0],$link_arr[1]];
                        $game_3th_id = $link_arr[2];
                        $game_menu = \DB::table('game_menu')->select(['id','name'])->whereIn('id',$game_ids)->get()->toArray();
                        if(!empty($game_menu)){
                            $game_menu = array_column($game_menu, 'name', 'id');
                            if(isset($game_menu[$link_arr[0]])){
                                $link_name .= $game_menu[$link_arr[0]].'|';
                            }
                            if(isset($game_menu[$link_arr[1]])){
                                $link_name .= $game_menu[$link_arr[1]].'|';
                            }
                        }
                        $game_3th = \DB::table('game_3th')->select('game_name')->where('id','=', $game_3th_id)->first();
                        if(!empty($game_3th)){
                            $link_name .= $game_3th->game_name;
                        }
                        $res[$key]['link_name'] = $link_name;
                    }
                }
                $res[$key]['picture'] = showImageUrl($res[$key]['picture']);
            }
//            $res = $query->paginate($data['page_size'],['*'],'page',$data['page'])->toArray();
        }
        return $this->lang->set(0,[],$res,$attributes);
    }

    public function updateAdvertById($id,$params){

        $res = AdvertModel::getById($id);
        if(!$res){
            return $this->lang->set(10015);
        }

        //参数筛选
        $upData = [];

            $upData['name']    =$params['name'];
            $upData['pf']      = $params['pf'];
            $upData['status']  = $params['status'];
            $upData['position']   = $params['position'];
            $upData['link_type']   = $params['link_type'];
            $upData['link']   =  isset($params['link']) ? $params['link'] : '' ;
            $upData['sort']   = $params['sort'];
        if(!empty($params['picture'])){
            $upData['picture']   = $params['picture'];
        }

        $res = AdvertModel::where('id',$id)->update($upData);

        if(!$res){
            return $this->lang->set(-2);
        }

        return $this->lang->set(0,[],$res,'');

    }

    public function updateAdvertStatusById($id,$params){

        $res = AdvertModel::getById($id);
        if(!$res){
            return $this->lang->set(10015);
        }

        $res->status = $params['status'];

        $res = $res->save();

        if(!$res){
            return $this->lang->set(-2);
        }

        return $this->lang->set(0,[],$res,'');

    }

    public function delAdvertById($id){
        $res = AdvertModel::getById($id);
        if(!$res){
            return $this->lang->set(10015);
        }
        $res = AdvertModel::where('id',$id)->delete();
        if(!$res){
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }
    
    public function createAdvert($data){
        $this->advertModel->name        = $data['name'];
        $this->advertModel->pf          = $data['pf'];
        $this->advertModel->status      = $data['status'];
        $this->advertModel->approve     = 'pass';
        $this->advertModel->type        = 'banner';
        $this->advertModel->position    = $data['position'];
        $this->advertModel->picture     = replaceImageUrl($data['picture']);
        $this->advertModel->link_type   = $data['link_type'];
        $this->advertModel->link        =  $data['link'] ;
        $this->advertModel->sort        = $data['sort'];
        $this->advertModel->language_id = isset($data['language_id']) ? $data['language_id'] : 1;

        $res = $this->advertModel->save();
        if(!$res){
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }
   
}
