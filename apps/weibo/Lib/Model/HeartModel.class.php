<?phpclass HeartModel extends Model {    var $tableName = 'weibo_heart';    static $user_cache = array();    static $cacheHash = array();    public function getList($uid, $since_id, $max_id, $count = 20, $page = 1) {        $limit = ($page - 1) * $count . ',' . $count;        $weiboIds = $this->where('uid='.$uid)->field('weibo_id')->findAll();        $weiboIds = getSubByKey($weiboIds, 'weibo_id');        if($weiboIds){            $str = implode(',', $weiboIds);            $map['_string'] = "weibo_id IN ($str)";        }else{            return array();        }        if ($since_id) {            $map['weibo_id'] = array('gt', $since_id);        } else if ($max_id) {            $map['weibo_id'] = array('lt', $max_id);        }        $list = M('weibo')->where($map)->order('weibo_id DESC')->limit($limit)->findAll();        /*         * 缓存被转发微博的详情, 作者信息, 被转发微博的作者信息         */        $ids = getSubBeKeyArray($list, 'weibo_id,transpond_id,uid');        $transpond_list = D('Weibo', 'weibo')->setWeiboObjectCache($ids['transpond_id']);        // 本页的用户IDs = 作者IDs + 被转发微博的作者IDs        $ids['uid'] = array_merge($ids['uid'], getSubByKey($transpond_list, 'uid'));        D('User', 'home')->setUserObjectCache($ids['uid']);        $weibo_ids = getSubByKey($list, 'weibo_id');        foreach ($list as $key => $value) {            $value['is_hearted'] = 1;            $list[$key] = D('Weibo', 'weibo')->getOneApi('', $value);        }        return $list;    }    //点赞微博    public function heartWeibo($id, $uid) {        $data['uid'] = $uid;        $data['weibo_id'] = $id;        $res = $this->add($data);        if ($res) {            $this->_addHeartCache($uid, $id);            D('Weibo', 'weibo')->setInc('heart', 'weibo_id=' . $id);        }        return $res;    }    //取消点赞    public function dodelete($id, $uid) {        $res = $this->where("weibo_id=$id AND uid=$uid")->delete();        if ($res) {            $this->_delHeartCache($uid, $id);            D('Weibo', 'weibo')->setDec('heart', 'weibo_id=' . $id);        }        return $res;    }    /**     * 检查给定用户是否点赞给定微博     *     * @param int 		 $weibo_id 		 微博ID     * @param int 		 $uid      		 用户ID     * @param array|null $weibo_id_array $weibo_id所属的微博集合(不为空时会一次性查询, 以减少数据库请求数)     * @param string     $key            为防止前一次调用对后一次调用的干扰, 为每个$weibo_id_array赋予唯一key     * @return int 已点赞返回1, 否则返回0     */    public function isHearted($weibo_id, $uid, $static = false) {        if(!$uid){            return false;        }        if ($static) {            self::$user_cache = $this->_getHeartCache($uid);            $list = self::$user_cache;        } else {            $list = $this->_getHeartCache($uid);        }        return in_array($weibo_id, $list);    }    private function _getListData($uid) {        return $this->field('weibo_id')->where("uid=$uid")->findAll();;    }    private function _getHeartCache($uid) {        if(S('heart_' . $uid)==false){            $this->_setHeartCache($uid);        }        return json_decode(S('heart_' . $uid));    }    private function _addHeartCache($uid, $weibo_id) {        $cache = $this->_getHeartCache($uid);        if (false == $cache || !is_array($cache)) {            return $this->_setHeartCache($uid);        } else {            !in_array($weibo_id, $cache) && $cache[] = $weibo_id;        }        S('heart_' . $uid, json_encode($cache));        return $this->_getHeartCache($uid);    }    private function _setHeartCache($uid) {        $list = getSubByKey($this->_getListData($uid), 'weibo_id');        S('heart_' . $uid, json_encode($list));        return $list;    }    private function _delHeartCache($uid, $weibo_id) {        $cache = $this->_getHeartCache($uid);        if ($cache && in_array($weibo_id, $cache)) {            $key = array_search($weibo_id, $cache);            unset($cache[$key]);            return S('heart_' . $uid, json_encode($cache));        } else {            return true;        }    }}?>