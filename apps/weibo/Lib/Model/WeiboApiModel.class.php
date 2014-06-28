<?php
require_once(SITE_PATH.'/apps/weibo/Lib/Model/WeiboModel.class.php');
class WeiboApiModel extends WeiboModel
{
	var $tableName = 'weibo';

	//获取最新更新的公共微博消息
	public function public_timeline($since_id, $max_id, $count = 20, $page = 1,$mid=0)
	{
		$limit = ($page-1)*$count.','.$count;
		$map['type'] = array('IN',array(0,1));
		if($since_id){
			$map['weibo_id'] = array('gt',$since_id) ;
		}elseif ($max_id){
			$map['weibo_id'] = array('lt',$max_id);
		}
		$map['isdel'] = 0;
		$list = $this->where($map)->limit($limit)->order('weibo_id DESC')->findAll();

		$this->_doWeiboAndUserCache($list);

		$weibo_ids = getSubByKey($list, 'weibo_id');
		foreach ($list as $k => $v)
			$result[$k] = $this->getOneApi('', $v,$mid);

    	return $result;
	}

	//获取当前用户所关注用户的最新微博信息
	public function friends_timeline($uid, $since_id, $max_id, $count = 20, $page = 1)
	{
		$limit = ($page-1) * $count . ',' . $count;
                $daoFollow = D('Follow', 'weibo');
                $following = $daoFollow->getNowFollowing($uid);
                $following[] = $uid;
                $map['uid'] = array('in',$following);

		$map['type'] = array('IN', array('1','0'));
		if ($since_id) {
			$map['weibo_id'] = array('gt', $since_id);
		} else if ($max_id) {
			$map['weibo_id'] = array('lt', $max_id);
		}
		$map['isdel'] = 0;

		$list = $this->where($map)->limit($limit)->order('weibo_id DESC')->findAll();
		$this->_doWeiboAndUserCache($list);
		$weibo_ids = getSubByKey($list, 'weibo_id');
                $result = array();
		foreach($list as $k => $v) {
			$result[$k]     = $this->getOneApi('', $v,$uid);
                }
            return $result;
	}

        //微博广场
    public function apiWeibo($count = 10, $page = 1,$uid=0) {
        $map['isdel'] = 0;
        $limit = ($page - 1) * $count . ',' . $count;
        $list = $this->where($map)->limit($limit)->order('weibo_id DESC')->findAll();
        $this->_doWeiboAndUserCache($list);
//        $weibo_ids = getSubByKey($list, 'weibo_id');
        foreach ($list as $k => $v) {
            $result[$k] = $this->getOneApi('', $v,$uid);
        }
        return $result;
    }

	//获取用户发布的微博信息列表
	public function user_timeline($uid, $uname, $since_id, $max_id, $count = 20, $page = 1,$mid=0)
	{
		if (!$uid) {
			$user = M('user')->where("uname='$uname'")->field('uid')->find();
			$uid = $user['uid'];
		}
		$limit = ($page-1)*$count.','.$count;
		//$map['type'] = array('IN',array('1','0'));
		if ($since_id) {
			$map['weibo_id'] = array('gt',$since_id) ;
		} else if ($max_id) {
			$map['weibo_id'] = array('lt',$max_id);
		}
		$map['isdel'] = 0;
		$map['uid']   = $uid;
		$list = $this->where($map)->limit($limit)->order('weibo_id DESC')->findAll();

		$this->_doWeiboAndUserCache($list);

		$weibo_ids = getSubByKey($list, 'weibo_id');
		foreach($list as $k => $v) {
			$result[$k]     = $this->getOneApi('', $v,$mid);
	    }
    	return $result;
	}

	//获取@当前用户的微博列表
	public function mentions($uid, $since_id, $max_id, $count = 20, $page = 1)
	{
		$limit = ($page-1)*$count.','.$count;
		$map = "(type=1 OR type=0) AND isdel=0";
		if($since_id){
			$map.= " AND weibo_id > $since_id";
		}elseif ($max_id){
			$map.= " AND weibo_id < $max_id";
		}
		$list = $this->where("$map AND (weibo_id IN (SELECT weibo_id FROM {$this->tablePrefix}weibo_atme WHERE uid=$uid))")->order('weibo_id DESC')->limit($limit)->findAll();

		$this->_doWeiboAndUserCache($list);

		$weibo_ids = getSubByKey($list, 'weibo_id');
		foreach($list as $k=>$v){
			$result[$k]     = $this->getOneApi('', $v,$uid);
	    }
    	return $result;
	}

	//获取评论列表
    public function getCommentList($uid, $type = 'all', $since_id, $max_id, $count = 20, $page = 1)
    {
    	$limit = ($page-1)*$count.','.$count;
		if($since_id){
			$map['comment_id'] = array('gt',$since_id) ;
		}elseif ($max_id){
			$map['comment_id'] = array('lt',$max_id);
		}
    	if($type=='all'){
    		$map['_string'] = "reply_uid=$uid OR uid=$uid";
    	}elseif($type=='send'){ // 发出的评论
    		$map['reply_uid'] = array('neq',$uid);
    		$map['uid']       = $uid;
    	}else{ // 收到的评论
    		$map['reply_uid'] = $uid;
    		$map['uid']       = array('neq',$uid);
    	}
		$map['isdel'] = 0;
    	$list = M('weibo_comment')->where($map)->order('comment_id DESC')->limit($limit)->findall();
    	foreach ($list as $key=>$value){
    		$list[$key]['status']       = $this->getOneApi($value['weibo_id'],'',$uid);
    		if ($type=='receive') { // 查看收到的评论时, 展示发送者的用户信息
	    		$list[$key]['user']     = getUserInfo($value['uid'],'',$uid,false);
	    		$list[$key]['uname'] = $list[$key]['user']['uname'];
	    	}
    		if( $value['reply_comment_id'] && $value['reply_uid'] ){
    			$list[$key]['type']    = 'comment';
    			$list[$key]['comment']   = $this->where('comment_id='.$value['reply_comment_id'].' AND isdel=0')->find();
    		}else{
    			$list[$key]['type']  = 'weibo';
    		}
    		//$list[$key]['reply_user']   = getUserInfo($value['reply_uid'],'',$uid,false);
    		$list[$key]['timestamp'] = $value['ctime'];
    		$list[$key]['ctime'] = date('Y-m-d H:i', $value['ctime']);
    	}
    	return $list;
    }

    //获取API评论
    function comments($id,$since_id,$max_id,$count=20,$page=1){
        $limit = ($page-1)*$count.','.$count;
		if($since_id){
			$map['comment_id'] = array('gt',$since_id) ;
		}elseif ($max_id){
			$map['comment_id'] = array('lt',$max_id);
		}
		$map['weibo_id'] = $id;
		$map['isdel'] = 0;
    	$list = M('weibo_comment')->where($map)->order('comment_id DESC')->field('comment_id,uid,content,ctime')->limit($limit)->findall();
    	foreach($list as $key=>$value){
    		$list[$key]['uname'] = getUserName($value['uid']);
    		$list[$key]['ctime'] = date('Y-m-d H:i',$value['ctime']);;
    		$list[$key]['timestamp'] = $value['ctime'];
    	}
    	return $list;
    }

    //获取用户关注的列表
    function following($uid,$uname,$since_id,$max_id,$count=20,$page=1){
    	$limit = ($page-1)*$count.','.$count;
       	if(!$uid){
			$user = M('user')->where("uname='$uname'")->field('uid')->find();
			$uid = $user['uid'];
		}
    	if($since_id){
			$map['follow_id'] = array('gt',$since_id) ;
		}elseif ($max_id){
			$map['follow_id'] = array('lt',$max_id);
		}
		$map['uid']=$uid;
		$list = M('weibo_follow')->where($map)->limit($limit)->field('fid as uid,follow_id as id')->order('follow_id DESC')->findAll();

		$uids = getSubByKey($list, 'uid');
		D('User', 'home')->setUserObjectCache($uids);
		model('UserCount')->setUserFollowerCount($uids);
		model('UserCount')->setUserFollowingCount($uids);
		model('UserCount')->setUserWeiboCount($uids);
		foreach ($list as $k=>$v){
			if(isset($_SESSION['mid']) && $_SESSION['mid'] > 0){
				$list[$k]['user'] = getUserInfo($v['uid'], '', $_SESSION['mid']);
			}else{
				$list[$k]['user'] = getUserInfo($v['uid'], '', $uid);
			}

			$mini = $this->where('uid='.$v['uid'].' AND isdel=0')->order('weibo_id DESC')->find();
			$list[$k]['weibo'] = $this->getOneApi('', $mini,$uid);
		}
		return $list;
    }

    //获取用户粉丝列表
    function followers($uid,$uname,$since_id,$max_id,$count=20,$page=1){
    	$limit = ($page-1)*$count.','.$count;
       	if(!$uid){
			$user = M('user')->where("uname='$uname'")->field('uid')->find();
			$uid = $user['uid'];
		}
    	if($since_id){
			$map['follow_id'] = array('gt',$since_id) ;
		}elseif ($max_id){
			$map['follow_id'] = array('lt',$max_id);
		}
		$map['fid']  = $uid;
		$map['type'] = 0;
		$list = M('weibo_follow')->where($map)->limit($limit)->field('uid,follow_id as id')->order('follow_id DESC')->findAll();

		$uids = getSubByKey($list, 'uid');
		D('User', 'home')->setUserObjectCache($uids);
		model('UserCount')->setUserFollowerCount($uids);
		model('UserCount')->setUserFollowingCount($uids);
		model('UserCount')->setUserWeiboCount($uids);
		foreach ($list as $k=>$v){
			if(isset($_SESSION['mid']) && $_SESSION['mid'] > 0){
				$list[$k]['user'] = getUserInfo($v['uid'], '', $_SESSION['mid']);
			}else{
				$list[$k]['user'] = getUserInfo($v['uid'], '', $uid);
			}
			$mini = $this->where('uid='.$v['uid'].' AND isdel=0')->order('weibo_id DESC')->find();
			$list[$k]['weibo'] = $this->getOneApi('', $mini,$uid);
		}
		return $list;
    }

     //获取用户互粉列表
    function eachFollower($uid, $uname, $since_id, $max_id, $count = 20, $page = 1) {
        $limit = ($page - 1) * $count . ',' . $count;
        if (!$uid) {
            $user = M('user')->where("uname='$uname'")->field('uid')->find();
            $uid = $user['uid'];
        }
        if ($since_id) {
            $map['follow_id'] = array('gt', $since_id);
        } elseif ($max_id) {
            $map['follow_id'] = array('lt', $max_id);
        }
        $userInfo = D('User', 'home')->getUserInfoCache($uid);
        $daoFo = M('weibo_follow');
        if ($userInfo['following'] < $userInfo['follower']) {

            $map['uid'] = $uid;
            $follow = $daoFo->where($map)->field('fid')->findAll();
            $fids = getSubByKey($follow, 'fid');
            $where['fid'] = $uid;
            $where['uid'] = array('IN', $fids);
            $list = $daoFo->where($where)->limit($limit)->field('uid,follow_id as id')->order('follow_id DESC')->findAll();
        } else {
            $map['fid'] = $uid;
            $map['type'] = 0;
            $follow = $daoFo->where($map)->field('uid')->findAll();
            $uids = getSubByKey($follow, 'uid');
            $where['uid'] = $uid;
            $where['fid'] = array('IN', $uids);
            $list = $daoFo->where($where)->limit($limit)->field('fid as uid,follow_id as id')->order('follow_id DESC')->findAll();
        }
        $uids = getSubByKey($list, 'uid');
        D('User', 'home')->setUserObjectCache($uids);
        model('UserCount')->setUserFollowerCount($uids);
        model('UserCount')->setUserFollowingCount($uids);
        model('UserCount')->setUserWeiboCount($uids);
        foreach ($list as $k => $v) {
            if (isset($_SESSION['mid']) && $_SESSION['mid'] > 0) {
                $list[$k]['user'] = getUserInfo($v['uid'], '', $_SESSION['mid']);
            } else {
                $list[$k]['user'] = getUserInfo($v['uid'], '', $uid);
            }
            $mini = $this->where('uid=' . $v['uid'] . ' AND isdel=0')->order('weibo_id DESC')->find();
            $list[$k]['weibo'] = $this->getOneApi('', $mini,$uid);
        }
        return $list ? $list : array();
    }

    //搜索微博（话题)
    function search($key,$since_id,$max_id,$count=20,$page=1,$mid=0){
    	$key=t($key);
    	if(!$key) return false;
   	    $limit = ($page-1)*$count.','.$count;
		$map = "(type=1 OR type=0) AND isdel=0";
		if($since_id){
			$map.= " AND weibo_id > $since_id";
		}elseif ($max_id){
			$map.= " AND weibo_id < $max_id";
		}
		$list = $this->where($map." AND content LIKE '%{$key}%'")->limit($limit)->order('weibo_id DESC')->findAll();
		$weibo_ids = getSubByKey($list, 'weibo_id');
		foreach($list as $k => $v) {
			$result[$k] = $this->getOneApi('', $v,$mid);
	    }
	    unset($list);
		return $result;
    }


    //搜索用户
    function searchUser($key,$mid,$since_id,$max_id,$count=20,$page=1,$sid=0,$sid1=0){
    	$key=t($key);
    	if(!$key) return false;
       	$limit = ($page-1)*$count.','.$count;
       	$map = '1=1';
		if($since_id){
			$map.= " AND uid > $since_id";
		}elseif ($max_id){
			$map.= " AND uid < $max_id";
		}
                if($sid>0){
                    $map.=" AND sid=$sid";
                }
                   if($sid1>0){
                    $map.=" AND sid1=$sid1";
                }
    	$list = $this->table(C('DB_PREFIX').'user')->where($map." AND realname LIKE '{$key}%'")->limit($limit)->findall();
        $daoCount = Model('UserCount');
    	foreach ($list as $k=>$v){
            $userCount = $daoCount->getUserCount($v['uid']);
            $list[$k]['mini'] = M('weibo')->where('uid='.$v['uid'].' AND type=0')->order('weibo_id DESC')->find();
            $list[$k]['followed_count'] 	= $userCount['following'];
            $list[$k]['followers_count']	= $userCount['follower'];
            $list[$k]['is_followed']  		= getFollowState($mid, $v['uid']);
            $list[$k]['area']         		= '';
            $list[$k]['face']				= getUserFace($v['uid']);
            $list[$k]['weibo_count']	= model('UserCount')->getUserWeiboCount($v['uid']);
    	}
    	return $list;
    }

}

?>
