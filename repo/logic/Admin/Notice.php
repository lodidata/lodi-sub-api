<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/4/10
 * Time: 14:46
 */

namespace Logic\Admin;

use Model\Admin\Notice as NoticeModel;
use Model\Admin\RoomNotice as RoomNoticeModel;
use Illuminate\Database\Capsule\Manager as DB;
class Notice extends \Logic\Logic
{

    protected $noticeModel;

    protected $roomNoticeModel;
    const POPUP_TYPE       = [
        '1'     => '登录弹出',
        '2'     => '首页弹出',
        '3'     => '滚动公告',
        '4'     => '登录页面',
    ];
    public function __construct($ci)
    {
        parent::__construct($ci);

        $this->noticeModel = new NoticeModel();
        $this->roomNoticeModel = new RoomNoticeModel();
    }

    /**
     * 公告列表
     * @param int $page
     * @param int $size
     * @param int $language_id
     * @return mixed
     */
    public function getNoticeList($params)
    {
        $page        = $params['page'];
        $size        = $params['page_size'];
        $language_id = $params['language_id'];
        if ($size > 20) $size = 20;

        $query = \DB::table('notice as n')
            ->leftJoin('admin_user as a', 'n.admin_uid', '=', 'a.id')
            ->select('n.*', 'a.username')
            ->where('n.status','<>','3');
        ;
        if (isset($params['status'])){
            $query->where('n.status', '=', $params['status']);
        }
        if (isset($params['popup_type'])){
            $query->where('n.popup_type', '=', $params['popup_type']);
        }
        $attributes['total'] = $query->count();
        if(!$attributes['total']){
            return [];
        }
        $res = $query->orderBy('n.sort','asc')->orderBy('n.updated','DESC')->forPage($page,$size)->get()->toArray();
        foreach($res as &$val){
            $val->start_time = date('Y-m-d H:i:s',$val->start_time);
            $val->end_time = date('Y-m-d H:i:s',$val->end_time);
            $val->created = date('Y-m-d H:i:s',$val->created);
            $val->updated = date('Y-m-d H:i:s',$val->updated);
            $val->popup_type = self::POPUP_TYPE[$val->popup_type] ?? '';
            $val->recipient =  $val->recipient ?: '所有用户';
            $val->imgs = showImageUrl($val->imgs);
        }
        $attributes['number'] = $page;
        $attributes['size'] = $size;
        return $this->lang->set(0,[],$res,$attributes);
    }

    /**
     * 公告列表-查询单个
     * @param int $page
     * @param int $size
     * @return mixed
     */
    public function getNoticeById($id)
    {
        $data = DB::table('notice as n')
            ->leftJoin('admin_user as a', 'n.admin_uid', '=', 'a.id')
            ->select('n.*', 'a.username')
            ->where('n.status', '<>', '3')
            ->where('n.id', $id)->first();

        if(empty($data)){
            return [];
        }
        $data->start_time = date('Y-m-d H:i:s',$data->start_time);
        $data->end_time = date('Y-m-d H:i:s',$data->end_time);
        $attributes['data'] = $data;
        $data->imgs = showImageUrl($data->imgs);
        return $this->lang->set(0,[],$data);
    }

    /**
     * 房间公告列表
     * @param int $page
     * @param int $size
     * @return mixed
     */
    public function getRoomNoticeList($page = 1, $size = 10)
    {
//        if ($size > 20) $size = 20;

        $query = RoomNoticeModel::from('room_notice as n')
            ->leftJoin('lottery as l', 'n.lottery_id', '=', 'l.id')
            ->leftJoin('admin_user as a', 'n.creator', '=', 'a.id')
            ->select('n.id','n.lottery_id','n.title','n.content','l.name','n.hall_id','n.sleep_time','a.username','n.created','n.status', 'a.username');

        $attributes['total'] = $query->count();
        $attributes['number'] = $page;
        $attributes['size'] = $size;
        $res = $query->orderBy('n.id','DESC')->forPage($page,$size)->get()->toArray();

        if(empty($res)){
            return $this->lang->set(0,[],[],$attributes);
        }

        $hallData = DB::table('hall')->get(['id','hall_name'])
            ->pluck('hall_name','id')
            ->toArray();


        foreach ($res as $k=>&$v){
            $hallIds=$v['hall_id'];
            $hallIdArr = explode(',',$hallIds);
            $hallNameStr = '';
            foreach ($hallIdArr as $hallId){
                $hallNameStr .=$hallData[$hallId].",";
            }
            $hallNameStr = trim($hallNameStr,',');
            $v['hall_name']  = $hallNameStr;
        }

        return $this->lang->set(0,[],$res,$attributes);
    }

    /**
     * 创建公告
     * @param $data
     * @return mixed
     */
    /**
     * 创建公告
     * @param $data
     * @return mixed
     */
    public function createNotice($data){

        if(isset($data['id'])){
            unset($data['language_id']);
            unset($data['username']);
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time'].'23:59:59');
            $res =  \Model\Admin\Notice::where('id', $data['id'])->update($data);

        }else{

            $this->noticeModel->type            = $data['type'];
            $this->noticeModel->content         = $data['content'];
            $this->noticeModel->title           = $data['title'];
            $this->noticeModel->popup_type      = $data['popup_type'];
            $this->noticeModel->send_type       = $data['send_type'];
            if($data['send_type'] == 3||$data['send_type'] == 1){
                $this->noticeModel->recipient   = $data['recipient'];
            }
            $this->noticeModel->start_time      =  strtotime($data['start_time']);
            $this->noticeModel->end_time        = strtotime($data['end_time'].'23:59:59');
            $this->noticeModel->admin_uid       = $data['admin_uid'];
            $this->noticeModel->admin_name      = $data['admin_name'];
            $this->noticeModel->language_id     = $data['language_id'];
            $this->noticeModel->sort            = $data['sort'];

            if(empty($data['content']))
            {
                if(!empty($data['imgs'])) $this->noticeModel->imgs = replaceImageUrl($data['imgs']);
                
            }
            
            
            
            $res = $this->noticeModel->save();
        }

        if($res === false){
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }


    public function delNoticeById($id){

        $res = NoticeModel::getById($id);
        if(!$res){
            return $this->lang->set(10015);
        }
        $res = NoticeModel::where('id',$id)->delete();
        if(!$res){
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }

}