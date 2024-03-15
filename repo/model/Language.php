<?php


namespace Model;


use Logic\Define\CacheKey;

class Language extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'language';

    /**
     * 语言列表
     * @return mixed
     */
    public function getList()
    {
        global $app;
        $redis = $app->getContainer()->redis;
        //多语言取当前语言ID
        $languageList = $redis->get(CacheKey::$perfix['language_list']);
        if($languageList){
            $languageList = json_decode($languageList, true);
        }else{
            $result = Language::where('status',1)->orderby('sort')->get()->toArray();
            $languageList = [];
            foreach($result as $k => $v)
            {
                $languageList[$v['code']] = $v;
            }
            $redis->set(CacheKey::$perfix['language_list'], json_encode($languageList, JSON_UNESCAPED_UNICODE));
        }

        return $languageList;
    }

    /**
     * 获取当前语言ID及名称
     * @return array
     */
    public function getCurrentIdName()
    {
        $languageList = $this->getList();
        return [
            'language_id' => $languageList[LANG]['id'],
            'language_name' => $languageList[LANG]['name']
        ];
    }

    /**
     * 更新语言
     * @param array $data 字段键值对
     * @param int $id ID
     * @return int|boolean
     */
    public function updateLang($data, $id = 0)
    {
        if($id){
            if(!empty($data)){
                $result = $this->where('id', '=', $id)->update($data);
            }else{
                $result = $this->where('id', '=', $id)->delete();
            }
        }else{
            $result = $this->insertGetId($data);
        }
        if($result){
            global $app;
            $redis = $app->getContainer()->redis;
            $redis->del(CacheKey::$perfix['language_list']);
            $redis->del(CacheKey::$perfix['language_dir']);
        }
        return $result;
    }
}