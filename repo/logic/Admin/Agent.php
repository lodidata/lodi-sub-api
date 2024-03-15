<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/4/10
 * Time: 14:46
 */

namespace Logic\Admin;

use Model\Admin\HelpCopy as HelpCopyModel;
use Illuminate\Database\Capsule\Manager as DB;
class Agent extends \Logic\Logic
{
    protected $helpCopyModel;

    public function __construct($ci)
    {
        parent::__construct($ci);

        $this->helpCopyModel = new HelpCopyModel();
    }

    /**
     * 公告列表
     * @param int $page
     * @param int $size
     * @return mixed
     */
    public function getProxyDoc($params,$page = 1, $size = 15)
    {

        if ($size > 20) $size = 20;

        $query = helpCopyModel::where('type','agent');

        if(isset($params['pf'])&&!empty($params['pf'])){

            $query = $query->where('pf',$params['pf']);
        }
        if(isset($params['id'])&&!empty($params['id'])){
            try{
                return $query->findorfail($params['id'])->toArray();
            }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
                return $this->lang->set(10015);
            }
        }

        $attributes['total'] = $query->count();
        $attributes['number'] = $page;
        $attributes['size'] = $size;
        $res = $query->orderBy('id','DESC')->forPage($page,$size)->get()->toArray();

        if(empty($res)){
            return $this->lang->set(0,[],[],$attributes);
        }
        return $this->lang->set(0,[],$res,$attributes);
    }


    public function createProxyDoc($params){

        $this->helpCopyModel->content      = $params['content'];
        $this->helpCopyModel->name         = $params['name'];
        $this->helpCopyModel->pf            = $params['pf'];
        $this->helpCopyModel->type          = 'agent';
        $this->helpCopyModel->language_id  = $params['language_id'] ?? null;
        $this->helpCopyModel->language_name  = $params['language_name'] ?? null;
        $this->helpCopyModel->approve_status  = $params['approve_status'] ?? 'pending';

        $res = $this->helpCopyModel->save();
        if(!$res){
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }

    /**
     * 发布公告
     *
     * @param int $id
     * @return array
     */
    public function messagePublish($id)
    {

        $message = DB::table('message')->where('status','=',0)->find($id);
        $message = (array) $message;
        if(!$message){
            return $this->lang->set(10015);
        }

        $where = [];
        if ($message['send_type'] == 1) {         //会员层级
            $where = ['ranting' => ['in' => explode(',', trim($message['recipient'], ','))]];
        } elseif ($message['send_type'] == 3) {  //自定义名称
            $where = [
                'name' => [
                    'in' => explode(',', trim($message[0]['recipient'], ','))
                ]
            ];
        }

        if ($message[0]['send_type'] == 2) {
            $table = 'agent';
            $type  = 2;
        } else {
            $table = 'user';
            $type  = 1;
        }

        $re  = Db::table($table)->where($where)->get(['id'])->toArray();
        if ($re) {
            $insertArr = [];
            foreach ($re as  $item) {
                $paramArr = [
                    'type' => $type,
                    'uid'  => $item->id,
                    'm_id' => $id,
                ];
                array_push($insertArr,$paramArr);
            }
            DB::table('message_pub')->insert($paramArr);
        }

        $res = MessageModel::where(['id'=>$id])->update(['status'=>1]);

        if(!$res){
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);


    }

    public function delMessageById($id){

        $res = MessageModel::getById($id);
        if(!$res){
            return $this->lang->set(10015);
        }
        $res = MessageModel::where('id',$id)->delete();
        if(!$res){
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }




    public function test(){
//        DB::table('')->orderBy('n.id','DESC')->get()->forPage($page,$size)->toArray();
    }
}