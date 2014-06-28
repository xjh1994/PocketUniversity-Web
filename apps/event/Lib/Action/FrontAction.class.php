<?php

/**
 * FrontAction
 * 活动页面
 * @uses Action
 * @package
 * @version $id$
 * @copyright 2009-2011 SamPeng
 * @author SamPeng <sampeng87@gmail.com>
 * @license PHP Version 5.2 {@link www.sampeng.cn}
 */
class FrontAction extends Action {

    private $eventId;
    private $event;
    private $obj;
    private $sjVote;

    /**
     * __initialize
     * 初始化
     * @access public
     * @return void
     */
    public function _initialize() {
        $this->assign('isAdmin', '1');
        $this->sjVote = false;
        $this->assign('sjVote', $this->sjVote);
        $this->event = D('Event');
        //活动
        $id = intval($_REQUEST['id']);
        //检测id是否为0
        if ($id<=0) {
            $this->assign('jumpUrl', getUrlDomain());
            $this->error("错误的访问页面，请检查链接");
        }
        //api登录
        if(isset($_POST['oauth_token']) && isset($_POST['oauth_token_secret'])){
            $verifycode['oauth_token'] = h($_POST['oauth_token']);
            $verifycode['oauth_token_secret'] = h($_POST['oauth_token_secret']);
            $verifycode['type'] = 'location';
            if($login = M('login')->where($verifycode)->field('uid')->find() ){
                $this->mid = $login['uid'];
                $_SESSION['mid'] = $this->mid;
            }
        }
        $this->event->setMid($this->mid);
        $map['id'] = $id;
        $map['isDel'] = 0;
        if ($result = $this->event->where($map)->find()) {
            if($result['status'] == 0){
                $this->error('该活动正在审核中');
            }elseif ($result['status'] == 2) {
                $this->error('该活动未通过审核');
            }
            $result['isEnd'] = false;
            if($result['eTime'] <= time() ||
                    ($result['is_school_event'] && $result['school_audit'] != 2)){
                $result['isEnd'] = true;
            }
            $this->obj = $result;
            $this->assign('event', $result);
        } else {
            $this->assign('jumpUrl', U('/Index/index'));
            $this->error('活动不存在或，未通过审核或，已删除');
        }

        $this->assign('eventId', $id);
        $this->eventId = $id;
        //背景图片
        $height = 357;
        //$file = getLogo($result['logoId']);
        $file = tsGetLogo($result['logoId'],$result['typeId'],$result['default_banner']);
//        $arr = getimagesize($file);
//        if($arr[1]<357){
//            $height = $arr[1];
//        }
//        $bgcss = '';
        //if($result['logoId'] != 0){
            $bgcss = '.bg_pic{background: url("'.$file.'") no-repeat center top; float:left; width:100%; height:'.$height.'px;background-size: 100% 100%;}';
        //}
        $this->assign('bgcss', $bgcss);
        $this->assign("hasVideo", D('EventFlash')->where(array('eventId' => $this->eventId,'show'=>1))->count());
        $this->assign("hasPhoto", D('EventImg')->where(array('uid' => 0,'eventId' => $this->eventId))->count());
    }

    private function _routerSj(){
        if($this->eventId == C('SJ_GROUP')){
            $this->player1(3);exit;
        }
        if($this->eventId == C('SJ_PERSON')){
            $this->player1(5);exit;
        }
        if($this->eventId == C('SJ_FS')){
            $this->player1(9);exit;
        }
    }

    private function _routerSjDetails(){
        if($this->eventId == C('SJ_GROUP')){
            $this->playerDetails1();exit;
        }
        if($this->eventId == C('SJ_PERSON')){
            $this->playerDetails1();exit;
        }
        if($this->eventId == C('SJ_FS')){
            $this->playerDetails1();exit;
        }
    }

        /**
     * index
     * 首页
     * @access public
     * @return void
     */
    public function index() {
        $this->_routerSj();
        // 活动分类
        $cate = D('EventType')->getType();
        $this->assign('category', $cate);
        $list = D('EventNews')->where(array('eventId' => $this->eventId))->order('id DESC')->limit(6)->findAll();
        $this->assign("news", $list);
        $list = D('EventPlayer')->field('id,realname,school,path,ticket,stoped,commentCount')->where(array('eventId' => $this->eventId,'status'=>1))->order('ticket DESC')->limit(12)->findAll();
        $this->assign("player", $list);
        $list = D('EventImg')->where(array('uid' => 0,'eventId' => $this->eventId))->order('id ASC')->limit(8)->findAll();
        $this->assign("photo", $list);
        $list = D('EventFlash')->where(array('eventId' => $this->eventId,'show'=>1))->order('id ASC')->limit(6)->findAll();
        $this->assign("video", $list);
        $orga = D('EventOrga')->where(array('eventId'=> $this->eventId))->find();
        $this->assign('orga', $orga);
        //已投几票
        $this->assign('restVote', $this->_getRestVote());
        $this->assign('bandVote', $this->_getBandVote());
        $this->display();
    }

    private function _getRestVote(){
        $restVote = 0;
        if($this->mid){
            $votedCount = M('event_vote')->where('eventId='.$this->eventId.' and mid='.$this->mid)->count();
            $restVote = $this->obj['maxVote'] - $votedCount;
        }
        return $restVote;
    }

    //不可重复投票pid
    private function _getBandVote(){
        $res = array();
        if($this->mid && !$this->obj['repeated_vote']){
            $pids = M('event_vote')->where('eventId='.$this->eventId.' and mid='.$this->mid)->field('pid')->findAll();
            $res = getSubByKey($pids,'pid');
            $res = array_unique($res);
        }
        return $res;
    }


    /**
     * 新闻列表
     */
    public function news(){
        $this->_routerSj();
        $order = 'id DESC';
        $map['eventId'] = $this->eventId;
        $list = D('EventNews')->where($map)->order($order)->findPage(6);
        $this->assign($list);
        $this->display();
    }

    /**
     * 新闻详细
     */
    public function newsDetails() {
        $this->_routerSj();
        $id = intval($_REQUEST['nid']);
        $map['id'] = $id;
        $map['eventId'] = $this->eventId;
        $app = D('EventNews')->where($map)->find();
        if (!$app) {
            $this->error('新闻不存在或已被删除！');
        }
        $app['content'] = htmlspecialchars_decode($app['content']);
        $this->assign('app',$app);
        $this->display();
    }

    /**
     *人气排行列表
     */
    public function player() {
        $this->_routerSj();
        $keyword = msubstr(h($_POST['keyword']),0,10,'utf-8',false);
        //echo $keyword;
        if (strlen($keyword) > 0) {
            $map['realname'] = array('like', "%{$keyword}%");
            $this->assign('keyword', $keyword);
        }
        $map['status'] = 1;
        $map['eventId'] = $this->eventId;

        $list = D('EventPlayer')->field('id,realname,school,path,ticket,stoped,commentCount')->where($map)->order('ticket DESC, id DESC')->findPage(8);
        $this->assign($list);
        $this->assign('restVote', $this->_getRestVote());
        $this->assign('bandVote', $this->_getBandVote());
        $this->display();
    }

    public function player1($type) {
        if (!empty($_POST)) {
            $_SESSION['es_searchSjf'] = serialize($_POST);
        } else if (isset($_GET[C('VAR_PAGE')])) {
            $_POST = unserialize($_SESSION['es_searchSjf']);
        } else {
            unset($_SESSION['es_searchSjf']);
        }
        $sid = intval($_POST['sid']);
        if ($sid) {
            $map['sid'] = $sid;
        }
        $keyword = msubstr(h($_POST['keyword']),0,10,'utf-8',false);
        if (strlen($keyword) > 0) {
            $map['title'] = array('like', "{$keyword}%");
            $_POST['keyword'] = $keyword;
        }
        $map['type'] = $type;
        $map['status'] = 5;
        $list = M('sj')->where($map)->field('id,sid,title,ticket')->order('ticket DESC, id DESC')->findPage(8);
        //是否已投票
        $mid = 0;
        $restCount = 10;
        if($this->mid){
            $voteArr = M('sj_vote')->where('mid='.$this->mid.' and eventId='.$this->eventId)->field('pid')->findAll();
            if($voteArr){
                $voted = getSubByKey($voteArr,'pid');
                foreach ($list['data'] as $key=>$value) {
                    $list['data'][$key]['voted'] = in_array($value['id'], $voted);
                }
                $restCount = 10-count($voteArr);
            }
            $mid = $this->mid;
        }
        $this->assign('mid',$mid);
        $this->assign('restCount',$restCount);
        $sjIds = getSubByKey($list['data'], 'id');
        $map = array();
        $map['sjid'] = array('in', $sjIds);
        $map['status'] = 1;
        $imgArr = M('sj_img')->where($map)->field('sjid,attachId')->findAll();
        $imgs = orderArray($imgArr,'sjid');
        $this->assign('imgs',$imgs);
        $this->assign($list);
        $this->display('player1');
    }

    /**
     *  人气排行详细页
     */
    public function playerDetails() {
        $this->_routerSjDetails();
        $id = intval($_REQUEST['pid']);
        $map['id'] = $id;
        $map['status'] = 1;
        $map['eventId'] = $this->eventId;
        $app = D('EventPlayer')->where($map)->find();
        if (!$app) {
            $this->error('选手不存在或已被删除！');
        }
        if($app['paramValue']){
            $app['paramValue'] = unserialize($app['paramValue']);
        }else{
            $app['paramValue'] = array();
        }
        $this->assign('app', $app);
        $flash = D('EventFlash')->where(array('uid' => $id))->order('id ASC')->findAll();
        $this->assign('flash', $flash);
        $img = D('EventImg')->where(array('uid' => $id))->order('id ASC')->findAll();
        $this->assign('img', $img);
        $this->assign('restVote', $this->_getRestVote());
        $this->assign('bandVote', $this->_getBandVote());
        $param = D('EventParameter')->getParam($this->eventId);
        $this->assign($param);
        $this->display();
    }
    public function playerDetails1() {
        $id = intval($_REQUEST['pid']);
        $map['id'] = $id;
        $map['status'] = 5;
        $app = M('sj')->where($map)->field('id,sid,sid1,title,title2,description,zusatz,ticket,type,content')->find();
        if (!$app) {
            $this->error('选手不存在或已被删除！');
        }
        $this->assign('app', $app);

        $mid = 0;
        $restCount = 10;
        $voted = false;
        if($this->mid){
            $dao = M('sj_vote');
            $countVoted = $dao->where('mid='.$this->mid.' and eventId='.$this->eventId)->count();
            $restCount = 10-$countVoted;
            if($countVoted){
                $votedCount = $dao->where('mid='.$this->mid.' and pid='.$id)->count();
                if($votedCount){
                    $voted = true;
                }
            }
            $mid = $this->mid;
        }
        $this->assign('mid',$mid);
        $this->assign('voted',$voted);
        $this->assign('restCount',$restCount);

        //视频
        $flashArr = M('sj_flash')->where(array('sjid' => $id))->field('flashId')->findAll();
        $ids = getSubByKey($flashArr, 'flashId');
        $map = array();
        $map['id'] = array('in', $ids);
        $flash = M('flash')->where($map)->field('id,path')->findAll();
        $this->assign('flash', $flash);
        //图片
        $daoSjImg = M('sj_img');
        $img = $daoSjImg->where(array('sjid' => $id))->field('attachId,status')->findAll();
        $this->assign('img', $img);
        foreach($img as $v){
            if($v['status'] == 1){
                $this->assign('topImg', $v['attachId']);
            }
        }
        $template = 'playerDetails'.$app['type'];
        $this->display($template);
    }

    /**
     * 视频列表
     */
    public function video() {
        $list = D('EventFlash')->where(array('eventId' => $this->eventId,'show'=>1))->order('id DESC')->findPage(12);
        $this->assign($list);
        $this->display();
    }
    /**
     * 照片列表
     */
    public function photo() {
        $list = D('EventImg')->where(array('eventId' => $this->eventId, 'uid'=>0))->order('id ASC')->findPage(9);
        $this->assign($list);
        $this->display();
    }
    public function join(){
        $event = $this->obj;
        $event['isLogin'] = false;
        $event['canJoin'] = true;
        $event['hasMember'] = false;
        $event['notInEventSchool'] = false;
        $upload = false;
        if($this->mid > 0){
            $event['isLogin'] = true;
            //检查用户是否是该活动允许的学校
            $mapSchool['eventId'] = $this->eventId;
            $mapSchool['sid'] = $this->user['sid'];
            $eventSchool = M('event_school')->where($mapSchool)->find();
            if(!$eventSchool){
                $event['notInEventSchool'] = true;
            }else if ($result = D('EventUser')->hasUser($this->mid, $this->eventId)) {
                $event['canJoin'] = false;
                $event['hasMember'] = $result['status'];
                //是否已上传选手
                if($event['hasMember'] && $event['player_upload']){
                    $upload = M('event_player')->where('uid='.$this->mid.' and eventId='.$this->eventId)->find();
                }
            }
        }
        //是否显示上传资料
        $player = array();
        $player['showUpload'] = false;
        if($event['player_upload'] && $event['hasMember'] && $event['deadline']>time() && $event['school_audit'] != 5){
            $player['showUpload'] = true;
        }
        $player['status'] = '1';
        if($upload){
            $player['status'] = '2';
            if($upload['status']){
                $player['status'] = '3';
            }
        }
        $this->assign('player', $player);
        if($player['status'] != '3'){
            $param = D('EventParameter')->getParam($this->eventId);
            $this->assign($param);
            $fdjs = "<script language='javascript'>";
            $fdjs = $fdjs . "function checkUpPlayer(){ ";
            foreach ($param['defaultName'] as $key => $val) {
                if($key!='path'){
                    $fdjs = $fdjs . "if (document.myform.$key.value.length == 0) {\n";
                    $fdjs = $fdjs . "alert('$val 不能为空');\n";
                    $fdjs = $fdjs . "document.myform.$key.focus();\n";
                    $fdjs = $fdjs . "return false;}\n";
                }
            }
            $fdjs = $fdjs . "if(imgCount==0) {\n";
            $fdjs = $fdjs . "alert('至少上传一张图片作为头像');\n";
            $fdjs = $fdjs . "return false;}\n";
            foreach ($param['parameter'] as $key => $val) {
                $k=$key+1;
                if ($val[2] == 1) {
                    $fdjs = $fdjs . "if (document.myform.para$k.value.length == 0) {\n";
                    $fdjs = $fdjs . "alert('$val[0] 不能为空');\n";
                    $fdjs = $fdjs . "document.myform.para$k.focus();\n";
                    $fdjs = $fdjs . "return false;}\n";
                    if($val[1] == 4) {
                        $fdjs = $fdjs . "var link = document.myform.para$k.value;\n";
                        $fdjs = $fdjs . "if (!checkFlash(link)) {\n";
                        $fdjs = $fdjs . "alert('视频链接不合法');\n";
                        $fdjs = $fdjs . "return false;}\n";
                    }
                }
            }
            $fdjs = $fdjs . "}</script>";
            $this->assign('fdjs',$fdjs);
        }
        $this->assign('event', $event);
        $this->assign('upload', $upload);
        $config = getPhotoConfig();
        $this->assign($config);
        $this->display();
    }

    public function doAddUser() {
//        if (empty($_REQUEST['realname'])) {
//            $this->error('姓名不能为空！');
//        }
        $this->insertUser();
    }

    public function insertUser() {
        $data['id'] = $this->eventId;
        $data['uid'] = $this->mid;
        //$data['realname'] = t($_REQUEST['realname']);
        $data['realname'] = $this->user['realname'];
        $data['sex'] = $this->user['sex'];
        if($this->obj['need_tel']){
            $data['tel'] = $this->user['mobile'];
        }
        $data['usid'] = $this->user['sid'];
        //$data['sid'] = intval($_REQUEST['sid']);

        $this->event->setMid($this->mid);
        $result = $this->event->doAddUser($data);
        if($result['status'] == 0){
            $this->error($result['info']);
        }else{
            $this->assign('jumpUrl', U('/Front/join',array('id'=>$this->eventId)));
            $this->success($result['info']);
        }
    }

    public function details(){
        $map['id'] = $this->eventId;
        if(isset($_REQUEST['uid'])){
            $map['uid'] = intval($_REQUEST['uid']);
        }
        $this->assign('echoFocus',U('/Front/jsonPhoto',$map));
        $this->display();
    }

    public function jsonPhoto(){
        if($this->eventId == C('SJ_GROUP') || $this->eventId == C('SJ_PERSON') || $this->eventId == C('SJ_FS')){
            $this->jsonPhoto1();
            exit;
        }
        $dao = M('EventImg');
        $uid = intval($_REQUEST['uid']);
        $result = array();
        $result['slide']['title'] = $this->obj['title'].'-照片';
        $result['slide']['createtime'] = '2012-11-21 16:16:02';
        $result['slide']['url'] = U('/Front/details',array('id'=> $this->eventId,'uid'=>$uid));
        $list = $dao->where(array('eventId'=>$this->eventId,'uid' => $uid))->order('id ASC')->findAll();
        foreach ($list as $value) {
            $vo['title'] = getShort($value['title'],35);
            $vo['intro'] = $value['title'];
            $vo['thumb_50'] = getThumb($value['path'],50,50);
            $vo['thumb_160'] = getThumb($value['path'],160,160);
            $vo['image_url'] = get_photo_url($value['path']);
            $vo['createtime'] = friendlyDate($value['cTime']);
            $vo['source'] = 'PocketUni';
            $vo['id'] = $value['id'];
            $result['images'][] = $vo;
        }
        $result['next_album']['interface'] = U('/Front/jsonPhoto',array('id'=>  $this->eventId,'uid'=>$uid));
        $result['next_album']['title'] = $this->obj['title'].'-照片';
        $result['next_album']['url'] = U('/Front/details',array('id'=>  $this->eventId,'uid'=>$uid));
        $result['next_album']['thumb_50'] = '';
        echo 'var slide_data = '.json_encode($result);
    }

    public function jsonPhoto1(){
        $dao = M('sj_img');
        $uid = intval($_REQUEST['uid']);
        $obj = M('sj')->where('id='.$uid)->field('title')->find();
        $result = array();
        $result['slide']['title'] = $obj['title'].'-照片';
        $result['slide']['createtime'] = date('Y-m-d H:i',$this->obj['cTime']);
        $result['slide']['url'] = U('/Front/details',array('id'=> $this->eventId,'uid'=>$uid));
        $attIds = $dao->where('sjid='.$uid)->field('attachId')->findAll();
        $attIds = getSubByKey($attIds, 'attachId');
        $map['id'] = array('in',$attIds);
        $list = M('attach')->where($map)->order('id ASC')->field('id,name,savepath,savename,uploadTime')->findAll();
        foreach ($list as $value) {
            $path = $value['savepath'].$value['savename'];
            $vo['title'] = getShort($obj['title'],35);
            $vo['intro'] = $obj['title'];
            $vo['thumb_50'] = tsMakeThumbUp($path,50,50);
            $vo['thumb_160'] = tsMakeThumbUp($path,160,160);
            $vo['image_url'] = SITE_URL.'/data/uploads/'.$path;
            $vo['createtime'] = friendlyDate($value['uploadTime']);
            $vo['source'] = 'PocketUni';
            $vo['id'] = $value['id'];
            $result['images'][] = $vo;
        }
        $result['next_album']['interface'] = U('/Front/jsonPhoto',array('id'=>  $this->eventId,'uid'=>$uid));
        $result['next_album']['title'] = $this->obj['title'].'-照片';
        $result['next_album']['url'] = U('/Front/details',array('id'=>  $this->eventId,'uid'=>$uid));
        $result['next_album']['thumb_50'] = '';
        echo 'var slide_data = '.json_encode($result);
    }

    //ajax 投票
    public function vote() {
        if(intval($this->mid) <= 0){
            $this->error('请先登录！');
        }
        $pid = intval($_REQUEST['pid']);
        $dao = D('EventPlayer');
        if($dao->vote($this->mid,$pid,$this->obj,$this->user['sid'])){
            $this->success($dao->getError());
        }else{
            $this->error($dao->getError());
        }
    }
    //ajax 投票
    public function vote1() {
        if(intval($this->mid) <= 0){
            $this->error('请先登录！');
        }
        if(!$this->sjVote){
            $this->error('投票失败，投票尚未开始或已关闭');
        }
        $pid = intval($_REQUEST['pid']);
        $map['mid'] = $this->mid;
        $map['eventId'] = $this->eventId;
        $dao = M('sj_vote');
        $count = $dao->where($map)->count();
        if($count>=10){
            $this->error('您已投满了10票! 每人每评选最多投10票');
        }
        $map['pid'] = $pid;
        $ticketed = $dao->where($map)->count();
        if($ticketed){
            $this->error('您已给他投过票了!');
        }
        $map['cTime'] = time();
        $vid = $dao->add($map);
        if($vid){
            if($count == 9){
                $this->_incSjTicket();
                $this->success('投票成功！您已投满了10票!');
            }else{
                $resCount = 10 - $count - 1;
                $res['status'] = 2;
                $res['info'] = '投票成功！投满10票才生效，还差【'.$resCount.'】票!';
                echo json_encode($res);exit;
            }
        }
        $this->error('投票失败！');
    }

    private function _incSjTicket(){
        $sjIdArr = M('sj_vote')->where('eventId='.$this->eventId.' and mid='.$this->mid)->field('pid')->findAll();
        $sjIds = getSubByKey($sjIdArr, 'pid');
        $map['id'] = array('in', $sjIds);
        return M('sj')->where($map)->setInc('ticket');
    }

    public function comment(){
        $this->display();
    }

    public function doAddPlayer() {
        if(!$this->mid){
            $this->error('请先登录！');
        }
        if(!$this->obj['player_upload']){
            $this->error('活动发起人已关闭上传资料');
        }
        if($this->obj['deadline']<time()){
            $this->error('上传资料时间已过');
        }
        $data['realname'] = t($_POST['realname']);
        if(empty($data['realname'])){
            $data['realname'] = $this->user['realname'];
        }
        $data['school'] = t($_POST['school']);
        if(!$data['school']){
            $this->error('选手院校未填');
        }
        $hasJoin = D('EventUser')->hasUser($this->mid, $this->eventId);
        if (!$hasJoin || $hasJoin['status']==0) {
            $this->error('尚未报名或报名未通过发起者审核');
        }
        if(empty($_POST['imgs'])){
            $this->error('至少上传一张图片作为头像');
        }
        $param = D('EventParameter')->getParam($this->eventId);
        $paramValue = array();
        $flash = array();
        foreach ($param['parameter'] as $k => $v) {
            $key = $k+1;
            $key = 'para'.$key;
            $input = isset($_POST[$key])?$_POST[$key]:'';
            if($v[2]==1 && $input==''){
                $this->error($v[0].' 不能为空');
            }
            //视频
            if($v[1]==4){
                if($input!=''){
                    $parseLink = parse_url($input);
                    if (!preg_match("/(youku.com|ku6.com|sina.com.cn|yixia.com|t.cn)$/i", $parseLink['host'], $hosts)) {
                        $this->error('视频链接不合法');
                    }
                    $flash[] = $input;
                }
                $paramValue[] = '';
            }else{
                $paramValue[] = t($_POST[$key],'nl');
            }
        }
        $data['paramValue'] = serialize($paramValue);
        $data['path'] = $_POST['imgs'][0];
        $data['cTime'] = time();
        $data['eventId'] = $this->eventId;
        $data['content'] = t($_REQUEST['content'],'nl');
        $data['uid'] = $this->mid;
        $data['sid'] = $this->user['sid'];
        $data['status'] = 0;
        $res = D('EventPlayer')->add($data);
        if(!$res){
            $this->error('上传失败，请稍后再试！');
        }
        $imgCount = count($_POST['imgs']);
        if($imgCount>1){
            for($i=1;$i<$imgCount;$i++){
                $this->_addPhoto($_POST['imgs'][$i], $res);
            }
        }
        if(!empty($flash)){
            foreach ($flash as $link) {
                $this->_addFlash($link, $res);
            }
        }
        $this->assign('jumpUrl', U('/Front/join',array('id'=>$this->eventId)));
        $this->success('上传成功，等待审核');
    }

    private function _addPhoto($path,$uid,$title=''){
        $data['path'] = $path;
        $data['eventId'] = $this->eventId;
        $data['uid'] = $uid;
        $data['title'] = $title;
        $data['cTime'] = time();
        D('EventImg')->add($data);
    }

    private function _addFlash($link,$uid){
        $link = getShortUrl($link);
        $addonsData = array();
        Addons::hook("weibo_type", array("typeId" => 3, "typeData" => $link, "result" => &$addonsData));
        $addonsData = unserialize($addonsData['type_data']);
        //var_dump($addonsData);die;
        $addonsData['title'] = preg_replace(array('/—在线播放(.*)/', '/ 在线观看(.*)/'), '', $addonsData['title']);
        $data['title'] = $addonsData['title'];
        $data['path'] = $addonsData['flashimg'] ? $addonsData['flashimg'] : '';
        $data['flashvar'] = $addonsData['flashvar'];
        $data['host'] = $addonsData['host'];
        $data['link'] = $link;
        $data['eventId'] = $this->eventId;
        $data['uid'] = $uid;
        $data['cTime'] = time();
        $data['show'] = 0;
        D('EventFlash')->add($data);
    }
}
