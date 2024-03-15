<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/4/10
 * Time: 14:46
 */

namespace Logic\Admin;

use Model\Admin\Message as MessageModel;
use Illuminate\Database\Capsule\Manager as DB;

class Message extends \Logic\Logic
{
    protected $messageModel;
    const ADMIN_UID = '0';//筛选发布人0 = 系统 否则个人

    public function __construct($ci)
    {
        parent::__construct($ci);

        $this->messageModel = new MessageModel();
    }

    /**
     * 消息列表
     * @param int $page
     * @param int $size
     * @return mixed
     */
    public function getMesList($params, $page = 1, $size = 10)
    {

//        if ($size > 20) $size = 20;
        $query = \DB::connection('slave')->table('message as m')
            ->leftJoin('admin_user as a', 'm.admin_uid', '=', 'a.id');
//            ->where('m.status','<>','3');

        if (isset($params['title']) && !empty($params['title'])) {

            $query = $query->where('m.title', $params['title']);
        }
        if (isset($params['type']) && !empty($params['type'])) {

            $query = $query->where('m.type', $params['type']);
        }

        if (isset($params['admin_uid'])) {
            if ($params['admin_uid'] == self::ADMIN_UID) {
                $query = $query->where('m.admin_uid', '=', $params['admin_uid']);
            } else {
                $query = $query->where('m.admin_uid', '>', $params['admin_uid']);
            }
        }
        if (isset($params['admin_name']) && !empty($params['admin_name'])) {

            $query = $query->where('m.admin_name', $params['admin_name']);
        }
        if (isset($params['recipient']) && !empty($params['recipient'])) {
            $recipient_list = explode(',', $params['recipient']);
            $query = $query->whereIn('m.recipient', $recipient_list);
        }
        if (isset($params['start_time']) && !empty($params['start_time'])) {

            $query = $query->where('m.created', '>=', strtotime($params['start_time']));
        }

        if (isset($params['end_time']) && !empty($params['end_time'])) {

            $query = $query->where('m.created', '<=', strtotime($params['end_time'] . " 23:59:59"));
        }

        $sum = clone $query;
        $res = $query->orderBy('m.id', 'DESC')->forPage($page, $size)->get(['m.*', 'a.username'])->toArray();

        if (empty($res)) {
            return [];
        }
        foreach ($res as &$value) {
            $value->title = $this->translateMsg($value->title);
            $value->content = $this->translateMsg($value->content);
            $value->created = date('Y-m-d H:i:s', $value->created);
            $value->updated = date('Y-m-d H:i:s', $value->updated);
        }
        $attributes['total'] = $sum->count();
        $attributes['number'] = $page;
        $attributes['size'] = $size;
        return $this->lang->set(0, [], $res, $attributes);
    }

    /**
     * 新增消息
     * @param $params
     * @return mixed
     */
    public function createMessage($params)
    {
        $params['recipient'] = $params['send_type_value'];//接收对象
        if ($params['send_type'] == 1) {//会员等级
            if (empty($params['recipient'])) {
                return $this->lang->set(10012);
            }
        } elseif ($params['send_type'] == 2) {//代理
            $params['recipient'] = '';//all agent
        } elseif ($params['send_type'] == 3) {//自定义
            $user_names = explode(',', $params['send_type_value']);
            foreach ($user_names as $v) {
                $user = (new \Model\User())->where('name', $v)->get()->toArray();
                if (!$user) {
                    $msg = "发送人中含有不存在用户 $v ";
                    return createRsponse($this->ci->response, 200, 10, $msg);
                }
            }
        }

        $this->messageModel->title = $params['title'];//发送标题
        $this->messageModel->content = $params['desc'];//发送内容
        //1 重要消息（import） 2 一般消息
        $this->messageModel->type = isset($params['message_type']) && !empty($params['message_type']) ? $params['message_type'] : 1;
        $this->messageModel->send_type = $params['send_type'];//发送类型（1：会员层级，2：代理，3：自定义）
        if ($params['send_type'] == 3 || $params['send_type'] == 1) {
            $this->messageModel->recipient = $params['recipient'];
        }
        $this->messageModel->admin_uid = $params['admin_uid'];
        $this->messageModel->admin_name = $params['admin_name'];

        $res = $this->messageModel->save();
        if (!$res) {
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }

    /**
     * 发布公告(同步，旧)
     *
     * @param int $id
     * @return array
     */
    public function messagePublishBak($id)
    {

        $message = DB::table('message')->where('status', '=', 0)->lockForUpdate()->find($id);

        if (!$message) {
            return $this->lang->set(10015);
        }
        $message = (array)$message;
        $where = [];
        if ($message['send_type'] == 1) {         //会员层级
            $levels = DB::table('level')->get()->toArray();
            $levels = array_column($levels, 'id', 'name');


            $filed = 'ranting';

            $where = array_map(function ($name) use ($levels) {
                return isset($levels[$name]) ? $levels[$name] : null;
            }, explode(',', $message['recipient']));

        } elseif ($message['send_type'] == 3) {  //自定义名称
            $filed = 'name';
            $where = explode(',', trim($message['recipient'], ','));
        }

        if ($message['send_type'] == 2) {
            $table = 'agent';
            $type = 2;
        } else {
            $table = 'user';
            $type = 1;
        }

        $paramArr = [
            'type' => $type,
            'm_id' => $id,
            'created' => time(),
            'updated' => time(),
        ];


        DB::table($table)
            ->selectRaw('id')
            ->whereIN($filed, $where)->orderByDesc('id')->chunk(5000, function ($users) use ($paramArr) {
                foreach ($users as $user) {
                    $message = array_merge(['uid' => $user->id], $paramArr);

//                    $this->redis->rpush('messageQuene', json_encode($message));

                    DB::table('message_pub')->insert($message);
                }
            });

        $res = MessageModel::where(['id' => $id])->update(['status' => 1]);
        if (!$res) {
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);

    }

    /**
     * 发布公告（异步，rabbitmq）
     * @param int $id 消息id
     * @return array
     */
    public function messagePublish($id)
    {
        $message = DB::table('message')->find($id);
        if (!$message) {
            return $this->lang->set(10015);
        }
        $message = (array)$message;
        if ($message['send_type'] == 1) {//会员层级
            $levels = DB::table('user_level')->get()->toArray();
            $levels = array_column($levels, 'level', 'name');
            $filed = 'ranting';

            $InArray = array_map(function ($name) use ($levels) {
                return isset($levels[$name]) ? $levels[$name] : null;
            }, explode(',', $message['recipient']));
        } elseif ($message['send_type'] == 3) {  //自定义名称
            $filed = 'name';
            $InArray = explode(',', trim($message['recipient'], ','));
        }
        //插入消息队列
        \Utils\MQServer::send('user_message', ['filed' => $filed, 'inArray' => $InArray, 'type' => 1, 'm_id' => $id]);
        $res = MessageModel::where(['id' => $id])->update(['status' => 1]);
        if (!$res) {
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }

    //消息删除
    public function delMessageById($id)
    {
        $res = MessageModel::getById($id);
        if (!$res) {
            return $this->lang->set(10015);
        }
        $res = MessageModel::where('id', $id)->delete();
        if (!$res) {
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }

    /**
     * 翻译消息
     * @param $msg
     * @return mixed
     */
    public function translateMsg($msg)
    {
        if ($title = json_decode($msg, true)) {
            if (is_array($title)) {
                return $this->lang->text(array_shift($title), $title);
            } else {
                return $this->lang->text($title);
            }

        } else {
            return $this->lang->text($msg);
        }

    }
}