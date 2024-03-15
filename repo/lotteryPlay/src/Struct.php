<?php
namespace LotteryPlay;

/**
 * 结构生成类
 * @author ni
 * + 2018-01-27
 */
class Struct {

    /**
     * 处理类映射
     * @var array
     */
    protected $_maps = [
        1 => '\\LotteryPlay\\xy28',
        5 => '\\LotteryPlay\\q3',
        10 => '\\LotteryPlay\\ssc',
        24 => '\\LotteryPlay\\m11n5',
        39 => '\\LotteryPlay\\pk10',
        51 => '\\LotteryPlay\\lhc',
    ];

    protected $_cateList = ['标准', '快捷'];

    protected $_lotteryMaps = [];

    protected $_lotteryStructMaps = [];

    protected $_lotteryChatStructMaps = [];

    protected $_lotteryVedioStructMaps = [];

    protected $_lotterMerge = [];

    protected $_errors = [];

    protected function _checked($cid) {
        $lists = [];
        foreach ($this->_lotteryMaps[$cid] as $key => $val) {
            if ($key == 0) {
                continue;
            }

            if (!isset($this->_lotteryStructMaps[$cid][$key])) {
                continue;
            }
            $merge = array_merge($val, $this->_lotteryStructMaps[$cid][$key]);

            if (!in_array($merge['name'][0], $this->_cateList)) {
                $this->_errors[] = "玩法: $key 没有定义[标准]/[快捷]";
            }

            if (!isset($merge['tags'])) {
                $this->_errors[] = "玩法: $key 没有定义[标签]";
            }
            $lists[$key] = $merge;
        }
        $this->_lotterMerge[$cid] = $lists;
    }

    protected function _loadLotteryConfig2($cid) {
        $dir = explode('\\', $this->_maps[$cid]);
        $dir = end($dir);
        $this->_lotteryMaps[$cid] = require __DIR__.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.'CoreConfig.php';
        $this->_lotteryStructMaps[$cid] = require __DIR__.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.'StructConfig.php';
    }

    protected function _loadLotteryConfig3($cid) {
        $dir = explode('\\', $this->_maps[$cid]);
        $dir = end($dir);
        // $this->_lotteryMaps[$cid] = require __DIR__.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.'CoreConfig.php';
        $this->_lotteryChatStructMaps[$cid] = require __DIR__.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.'ChatStructConfig.php';
    }

    protected function _loadLotteryConfig4($cid) {
        $dir = explode('\\', $this->_maps[$cid]);
        $dir = end($dir);
        // $this->_lotteryMaps[$cid] = require __DIR__.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.'CoreConfig.php';
        $this->_lotteryVedioStructMaps[$cid] = require __DIR__.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.'VedioStructConfig.php';
    }

    public function run($cid) {
        $this->_cid = $cid;
        $this->clear($cid);
        $this->_loadLotteryConfig2($cid);
        $this->_checked($cid);
        // print_r($this->_errors);
        // print_r($this->_lotteryMaps);
        // print_r($this->_lotteryMaps);
        $struct = ['standard' => [], 'fast' => []];
        $maps = ['standard' => [], 'fast' => []];
        // 生成第一层数据
        foreach ($this->_lotterMerge[$cid] as $key => $val) {
            if ($val['name'][0] == '标准') {
                $maps['standard'][] = $val['name'][1];    
            } else {
                $maps['fast'][] = $val['name'][1];    
            }
        }

        foreach (array_unique($maps['standard']) as $val) {
            $struct['standard'][] = ['nm' => $val];
        }

        foreach (array_unique($maps['fast']) as $val) {
            $struct['fast'][] = ['nm' => $val];
        }

        // 生成第二层数据
        foreach ($struct['standard'] as $key => $val) {
            $node = [];
            foreach ($this->_lotterMerge[$cid] as $key2 => $val2) {

                if ($key2 == 0) {
                    continue;
                }
                if ($val2['name'][0] == '标准' && $val2['name'][1] == $val['nm']) {
                    $tags = [];
                    foreach ($val2['tags'] as $key3 => $val3) {
                        $tp = [];
                        foreach ($val3['sv'] as $v) {
                            $tp[] = null;
                        }
                        $val3['tp'] = $tp;
                        $tags[] = $val3;
                    }

                    $node[] = [
                               'nm' => $val2['name'][2],
                               'plid' => $key2,
                               'tags' => $tags,
                               'pltx1' => '玩法提示#1',
                               'valid' => isset($this->_lotteryMaps[$cid][$key2]['valid']) ? $this->_lotteryMaps[$cid][$key2]['valid'] : $this->_lotteryMaps[$cid][0]['valid'],
                               'args' => isset($this->_lotteryMaps[$cid][$key2]['args']) ? $this->_lotteryMaps[$cid][$key2]['args'] : (isset($this->_lotteryMaps[$cid][0]['args']) ? $this->_lotteryMaps[$cid][0]['args'] : []),
                               // 'pltx2' => '玩法提示#2',
                    ];
                }
            }


            $val['child'] = $node;
            $struct['standard'][$key] = $val;
        }

        // exit;
        foreach ($struct['fast'] as $key => $val) {
            $node = [];
            foreach ($this->_lotterMerge[$cid] as $key2 => $val2) {
                if ($key2 == 0) {
                    continue;
                }
                if ($val2['name'][0] == '快捷' && $val2['name'][1] == $val['nm']) {
                    $tags = [];
                    foreach ($val2['tags'] as $key3 => $val3) {
                        $tp = [];
                        foreach ($val3['sv'] as $v) {
                            $tp[] = null;
                        }
                        $val3['tp'] = $tp;
                        $tags[] = $val3;
                    }

                    $node[] = [                             
                                'nm' => $val2['name'][2],
                               'plid' => $key2,
                               'tags' => $tags,
                               'pltx1' => '玩法提示#1',
                               'valid' => isset($this->_lotteryMaps[$cid][$key2]['valid']) ? $this->_lotteryMaps[$cid][$key2]['valid'] : $this->_lotteryMaps[$cid][0]['valid'],
                               'args' => isset($this->_lotteryMaps[$cid][$key2]['args']) ? $this->_lotteryMaps[$cid][$key2]['args'] : (isset($this->_lotteryMaps[$cid][0]['args']) ? $this->_lotteryMaps[$cid][0]['args'] : []),
                               // 'valid' => $val3['valid']
                               // 'pltx2' => '玩法提示#2',
                           ];
                }
            }
            $val['child'] = $node;
            $struct['fast'][$key] = $val;
        }

        $struct['fields'] = [
            'standard' => '标准玩法',
            'fast' => '快捷玩法',
            'child' => 'array 子节点',
            'nm' => 'string 玩法名称',
            'plid' => 'integer 玩法ID',
            'pltx1' => 'string 玩法提示',
            'pltx2' => 'string 玩法提示',
            'valid' => 'string 校验注数方法',
            'tags' => 'array 标签',
            'sv' => 'array 标签选号显示数据(前端显示用)',
            'vv' => 'array 标签选号提交数据与sv对应(提交订单用)',
            'tp' => 'array 标签选号提示与sv对应',
        ];

        if (empty($struct['standard'])) {
            unset($struct['standard']);
        }

        if (empty($struct['fast'])) {
            unset($struct['fast']);
        }
        // file_put_contents('struct_'.$cid.'.json', json_encode($struct));
        return $struct;
    }

    public function chatRun($cid) {
        $this->_cid = $cid;
        $this->clear($cid);
        $this->_loadLotteryConfig3($cid);
        $this->_checked($cid);
        $struct = ['chat' => []];
        $maps = ['chat' => []];
        // 生成第二层数据
        foreach ($this->_lotteryChatStructMaps[$cid] as $key => $val) {
            // $node = [];

        
            $tags = [];
            foreach ($val['tags'] as $key3 => $val3) {
                $tp = [];
                foreach ($val3['sv'] as $v) {
                    $tp[] = null;
                }
                $val3['tp'] = $tp;
                $tags[] = $val3;
            }

            $node = [
                       'nm' => $val['aliasName'],
                       'plid' => $val['playId'],
                       'tags' => $tags,
                       'pltx1' => '玩法提示#1',
                       'valid' => isset($this->_lotteryMaps[$cid][$val['playId']]['valid']) ? $this->_lotteryMaps[$cid][$val['playId']]['valid'] : 'valid',
                       'args' => isset($this->_lotteryMaps[$cid][$val['playId']]['args']) ? $this->_lotteryMaps[$cid][$val['playId']]['args'] : (isset($this->_lotteryMaps[$cid][0]['args']) ? $this->_lotteryMaps[$cid][0]['args'] : []),
                       'pltx2' => '玩法提示#2',
            ];
                
            // $val['child'] = $node;
            $struct['chat'][$key] = $node;
        }

        $struct['fields'] = [
            'standard' => '标准玩法',
            'fast' => '快捷玩法',
            'chat' => '聊天玩法',
            'child' => 'array 子节点',
            'nm' => 'string 玩法名称',
            'plid' => 'integer 玩法ID',
            'pltx1' => 'string 玩法提示',
            'pltx2' => 'string 玩法提示',
            'valid' => 'string 校验注数方法',
            'args' => 'array valid计算参数',
            'tags' => 'array 标签',
            'sv' => 'array 标签选号显示数据(前端显示用)',
            'vv' => 'array 标签选号提交数据与sv对应(提交订单用)',
            'tp' => 'array 标签选号提示与sv对应',
        ];

        // print_r($struct);
        // file_put_contents('struct_'.$cid.'.json', json_encode($struct));
        return $struct;
    }

    public function vedioRun($cid) {
        $this->_cid = $cid;
        $this->clear($cid);
        $this->_loadLotteryConfig3($cid);
        $this->_checked($cid);
        $struct = ['vedio' => []];
        $maps = ['vedio' => []];
        // 生成第二层数据
        foreach ($this->_lotteryChatStructMaps[$cid] as $key => $val) {
            // $node = [];

        
            $tags = [];
            foreach ($val['tags'] as $key3 => $val3) {
                $tp = [];
                foreach ($val3['sv'] as $v) {
                    $tp[] = null;
                }
                $val3['tp'] = $tp;
                $tags[] = $val3;
            }

            $node = [
                       'nm' => $val['aliasName'][1],
                       'plid' => $val['playId'],
                       'tags' => $tags,
                       'pltx1' => '玩法提示#1',
                       'valid' => isset($this->_lotteryMaps[$cid][$val['playId']]['valid']) ? $this->_lotteryMaps[$cid][$val['playId']]['valid'] : 'valid',
                       'args' => isset($this->_lotteryMaps[$cid][$val['playId']]['args']) ? $this->_lotteryMaps[$cid][$val['playId']]['args'] : (isset($this->_lotteryMaps[$cid][0]['args']) ? $this->_lotteryMaps[$cid][0]['args'] : []),
                       'pltx2' => '玩法提示#2',
            ];
                
            // $val['child'] = $node;
            $struct['vedio'][$key] = $node;
        }

        $struct['fields'] = [
            'standard' => '标准玩法',
            'fast' => '快捷玩法',
            'chat' => '聊天玩法',
            'child' => 'array 子节点',
            'nm' => 'string 玩法名称',
            'plid' => 'integer 玩法ID',
            'pltx1' => 'string 玩法提示',
            'pltx2' => 'string 玩法提示',
            'valid' => 'string 校验注数方法',
            'args' => 'array valid计算参数',
            'tags' => 'array 标签',
            'sv' => 'array 标签选号显示数据(前端显示用)',
            'vv' => 'array 标签选号提交数据与sv对应(提交订单用)',
            'tp' => 'array 标签选号提示与sv对应',
        ];

        // print_r($struct);
        // file_put_contents('struct_'.$cid.'.json', json_encode($struct));
        return $struct;
    }

    public function odds($cid) {
        $this->_cid = $cid;
        $this->clear($cid);
        $this->_loadLotteryConfig2($cid);
        $this->_checked($cid);
        $odds = [];
        foreach ($this->_lotterMerge[$cid] as $key => $val) {
            $struct = [
                'name' => '', 
                // 'lottery_id' => 0, 
                'lottery_pid' => $cid, 
                'play_id' => 0, 
                'play_sub_id' => 0, 
                'max_odds' => 0, 
                'odds' => 0, 
                // 'hall_id' => 0,
                // 'min_bet' => 0,
                // 'max_bet' => 0,
                // 'one_term' => 0,
                // 'one_max_money' => 0,
                // 'rebet' => 0,
                // 'period_room_max' => 0,
            ];
            $struct['play_id'] = $key;
            foreach ($val['standardOdds'] as $k1 => $v1) {
                $struct['play_sub_id'] = $k1;
                $struct['name'] = $val['standardOddsDesc'][$k1];
                $struct['max_odds'] = $v1;
                $struct['odds'] = $v1;
                $odds[] = $struct;
            }
        }

        // print_r($odds);
        return $odds;
    }

    public function merge($cid, $struct, $odds) {
        $playIdOdds = [];
        foreach ($odds as $k => $v) {
            if (!isset($playIdOdds[$v['play_id']])) {
                $playIdOdds[$v['play_id']] = [];
            }
            $playIdOdds[$v['play_id']][$v['name']] = $v['odds']; 
        }

        if (isset($struct['standard'])) {
            foreach ($struct['standard'] as $k1 => $v1) {
                foreach ($v1['child'] as $k2 => $v2) {
                    $haveOdds = false;
                    foreach ($v2['tags'] as $k3 => $v3) {

                        $odds = [];
                        foreach ($v3['vv'] as $k4 => $v4) {
                            if (isset($playIdOdds[$v2['plid']]) && isset($playIdOdds[$v2['plid']][$v4])) {
                                $haveOdds = true;
                                break;
                            }
                        }

                        if ($haveOdds) {
                            foreach ($v3['vv'] as $k4 => $v4) {
                                $odds[] = $playIdOdds[$v2['plid']][$v4];
                            }
                            $v3['odds'] = $odds;
                        }
                        $v2['tags'][$k3] = $v3;
                    }

                    if (!$haveOdds) {
                        $v2['odds'] = array_values($playIdOdds[$v2['plid']]);
                    }

                    $v1['child'][$k2] = $v2;
                }

                $struct['standard'][$k1] = $v1;
            }
        }

        if (isset($struct['fast'])) {
            foreach ($struct['fast'] as $k1 => $v1) {
                foreach ($v1['child'] as $k2 => $v2) {
                    $haveOdds = false;
                    foreach ($v2['tags'] as $k3 => $v3) {

                        $odds = [];
                        foreach ($v3['vv'] as $k4 => $v4) {
                            if (isset($playIdOdds[$v2['plid']]) && isset($playIdOdds[$v2['plid']][$v4])) {
                                $haveOdds = true;
                                break;
                            }
                        }

                        if ($haveOdds) {
                            foreach ($v3['vv'] as $k4 => $v4) {
                                $odds[] = $playIdOdds[$v2['plid']][$v4];
                            }
                            $v3['odds'] = $odds;
                        }
                        $v2['tags'][$k3] = $v3;
                    }

                    if (!$haveOdds) {
                        $v2['odds'] = array_values($playIdOdds[$v2['plid']]);
                    }

                    $v1['child'][$k2] = $v2;
                }

                $struct['fast'][$k1] = $v1;
            }
        }
        // print_r($playIdOdds);
        // print_r($struct['fast']);
        // exit;
        // file_put_contents('struct_merge_'.$cid.'.json', json_encode($struct));
        return $struct;
    }

    public function mergeChat($cid, $struct, $odds) {
        $playIdOdds = [];
        foreach ($odds as $k => $v) {
            if (!isset($playIdOdds[$v['play_id']])) {
                $playIdOdds[$v['play_id']] = [];
            }
            $playIdOdds[$v['play_id']][$v['name']] = $v['odds']; 
        }

        if (isset($struct['chat'])) {
            foreach ($struct['chat'] as $k1 => $v1) {
                foreach ($v1['child'] as $k2 => $v2) {
                    $haveOdds = false;
                    foreach ($v2['tags'] as $k3 => $v3) {

                        $odds = [];
                        foreach ($v3['vv'] as $k4 => $v4) {
                            if (isset($playIdOdds[$v2['plid']]) && isset($playIdOdds[$v2['plid']][$v4])) {
                                $haveOdds = true;
                                break;
                            }
                        }

                        if ($haveOdds) {
                            foreach ($v3['vv'] as $k4 => $v4) {
                                $odds[] = $playIdOdds[$v2['plid']][$v4];
                            }
                            $v3['odds'] = $odds;
                        }
                        $v2['tags'][$k3] = $v3;
                    }

                    if (!$haveOdds) {
                        $v2['odds'] = array_values($playIdOdds[$v2['plid']]);
                    }

                    $v1['child'][$k2] = $v2;
                }

                $struct['chat'][$k1] = $v1;
            }
        }
        // print_r($struct2);
        // file_put_contents('struct_merge_'.$cid.'.json', json_encode($struct));
        return [];
    }

    public function clear($cid) {
        $this->_lotteryMaps[$cid] = [];
        $this->_lotteryStructMaps[$cid] = [];
        $this->_lotterMerge[$cid] = [];
    }

    /**
     * 创建玩法数据sql
     * @param  [type] $cid          [description]
     * @param  array  $mergeCsvData [description]
     * @return [type]               [description]
     */
    public function sqlData($cid, $mergeCsvData = []) {
        $this->_cid = $cid;
        $this->clear($cid);
        $this->_loadLotteryConfig2($cid);
        //$this->_loadLotteryConfig3($cid);
        //$this->_loadLotteryConfig4($cid);
        $this->_checked($cid);
        $sqls = [];
        foreach ($this->_lotterMerge[$cid] as $key => $val) {
            $tags = $val['tags'];
            foreach ($tags as $k => $v) {
                if(!isset($v['tp'])) {
                    $tps = [];
                    foreach ($v['vv'] as $v2) {
                        $tps[] = null;
                    }
                    $v['tp'] = $tps;
                }
                $tags[$k] = $v;
            }

            $tags = json_encode($tags, JSON_UNESCAPED_UNICODE);
            // print_r($val);
            $struct = [
                'name' => $val['name'][2],
                'lottery_pid' => $cid, 
                'group' => $val['name'][1],
                'model' => $val['name'][0],
                'tags' => $tags,
                'play_id' => $key,
                'valid' => isset($val['valid']) ? explode("::", $val['valid']['exec'])[1] : explode("::", $this->_lotteryMaps[$cid][0]['valid']['exec'])[1],
                'args' => json_encode(isset($val['valid']) && isset($val['valid']['args']) ? $val['valid']['args'] : []),
                'sort' => 0,
                'open' => 1,
            ];

            if ($struct['model'] == '快捷') {
                $struct2 = $struct;
                // $struct2['model'] = '聊天';
                // $sqls[] = $struct2;
            }

            $sqls[] = $struct;
        }

        foreach ($this->_lotteryChatStructMaps[$cid] as $key => $val) {
            $tags = $val['tags'];
            foreach ($tags as $k => $v) {
                if(!isset($v['tp'])) {
                    $tps = [];
                    foreach ($v['vv'] as $v2) {
                        $tps[] = null;
                    }
                    $v['tp'] = $tps;
                }
                $tags[$k] = $v;
            }

            $tags = json_encode($tags, JSON_UNESCAPED_UNICODE);
            $play = $this->_lotteryMaps[$cid][$val['playId']];
            // print_r($val);
            $struct = [
                'name' => $val['aliasName'],
                'lottery_pid' => $cid, 
                'group' => $play['name'][1],
                'model' => '聊天',
                'tags' => $tags,
                'play_id' => $val['playId'],
                'valid' => isset($play['valid']) ? explode("::", $play['valid']['exec'])[1] : explode("::", $this->_lotteryMaps[$cid][0]['valid']['exec'])[1],
                'args' => json_encode(isset($play['valid']) && isset($play['valid']['args']) ? $play['valid']['args'] : []),
                'sort' => 0,
                'open' => 1,
            ];
            $sqls[] = $struct;
        }

        foreach ($this->_lotteryVedioStructMaps[$cid] as $key => $val) {
            $tags = $val['tags'];
            foreach ($tags as $k => $v) {
                if(!isset($v['tp'])) {
                    $tps = [];
                    foreach ($v['vv'] as $v2) {
                        $tps[] = null;
                    }
                    $v['tp'] = $tps;
                }
                $tags[$k] = $v;
            }

            $tags = json_encode($tags, JSON_UNESCAPED_UNICODE);
            $play = $this->_lotteryMaps[$cid][$val['playId']];
            // print_r($val);
            $struct = [
                'name' => $val['aliasName'][1],
                'lottery_pid' => $cid, 
                'group' => $val['aliasName'][0],
                'model' => '直播',
                'tags' => $tags,
                'play_id' => $val['playId'],
                'valid' => isset($play['valid']) ? explode("::", $play['valid']['exec'])[1] : explode("::", $this->_lotteryMaps[$cid][0]['valid']['exec'])[1],
                'args' => json_encode(isset($play['valid']) && isset($play['valid']['args']) ? $play['valid']['args'] : []),
                'sort' => 0,
                'open' => 1,
            ];
            $sqls[] = $struct;
        }

        // 合并旧数据的描述文本
        if (!empty($mergeCsvData)) {
            // echo 'mergeCsvData', PHP_EOL;
            foreach ($sqls as $k => $v) {
                foreach ($mergeCsvData as $k2 => $v2) {
                    if ($v['play_id'] == $v2['plid'] && $v['model'] == $v2['model'] && $v['name'] == $v2['name']) {
                        $sqls[$k]['play_text1'] = $v2['pltx1'];
                        $sqls[$k]['play_text2'] = $v2['pltx2'];
                        break;
                    }
                }
            }
        }
        return $sqls;
    }

    public function dataToInsert($table, $data) {
        $sql = [];
        foreach ($data as $key => $val) {
            foreach ($val as $k => $v) {
                $val[$k] = "'".$v."'";
            }
            $keys = [];
            foreach (array_keys($val) as $v) {
                $keys[] = '`'.$v.'`';
            }
            $keys = join(',', $keys);
            $vals = join(',', array_values($val));
            $sql[] = "INSERT INTO {$table}({$keys}) VALUES({$vals});";
        }
        return join(PHP_EOL, $sql);
    }
}    