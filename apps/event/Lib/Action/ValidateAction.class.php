<?php

/**
 * JfAction
 * 校方审核
 * @uses Action
 * @package
 * @version $id$
 * @copyright 2013-2015 张晓军
 * @author张晓军
 * @license PHP Version 5.3
 */
class ValidateAction extends TeacherAction {

    public function _initialize() {
        //管理权限判定
        parent::_initialize();
        if ($this->rights['can_group'] != 1) {
            $this->assign('jumpUrl', U('event/Readme/index'));
            $this->error('您没有部落权限！');
        }
    }

    public function index() {
        $this->display();
    }

    public function validatelist() {
        $daoGroup = M('group');
        $map['school'] = $this->school['id'];
        if($this->user['event_level']!=10){
        $map['sid1'] = $this->user['sid1'];
        }
        $map['is_del'] = 0;
        $map['disband'] = 0;
        if (!empty($_POST)) {
            $_SESSION['sb_sValidate'] = serialize($_POST);
        } else if (isset($_GET[C('VAR_PAGE')])) {
            $_POST = unserialize($_SESSION['sb_sValidate']);
        } else {
            unset($_SESSION['sb_sValidate']);
        }
        $_POST['title'] && $map['name'] = array('like','%'.t($_POST['title']).'%');
        $_POST['cid'] && $map['cid0'] =   $_POST['cid'];
        $cat = M('group_category')->field('id,title')->findAll();
        $this->assign('categorys', $cat);
        $list=$daoGroup->where($map)->order('id DESC')->findPage(20);
        $this->assign('list', $list);
        $this->assign($_POST);
        $this->display();
    }

   //待审核部落
//    public function audit() {
//        $map['a.school'] = $this->school['id'];
//        $map['a.status'] = 1;
//        $map['a.vStatus'] = 0;
//        $map['b.status'] = 1;
//        $db_prefix = C('DB_PREFIX');
//        $list = M('group')->table("{$db_prefix}group AS a ")
//                        ->join("{$db_prefix}group_validate AS b ON  b.gid=a.id")
//                        ->field('a.name,b.id,b.gid,b.atime')
//                        ->where($map)->order('b.id DESC')->findPage(10);
//        $this->assign('list', $list);
//        $this->display();
//    }
//
////驳回原因
//    public function doAuditReason() {
//        $this->assign('vid', intval($_REQUEST['id']));
//        $this->display();
//    }

//通过评星
    public function doAuditStern() {
        $this->assign('vid', intval($_REQUEST['id']));
        $this->display();
    }

//修改评星
    public function doStern() {
        $this->assign('vid', intval($_REQUEST['id']));
        $this->assign('stern', intval($_REQUEST['stern']));
        $this->display();
    }

    public function saveStern() {
        $gid = intval($_REQUEST['gid']);
        $stern = intval($_REQUEST['stern']);
        if ($stern != 0 &&$stern != 1 && $stern != 2 && $stern != 3 && $stern != 4 && $stern != 5) {
            $this->error('星级格式错误');
        }
        $daoGroup = M('group');
        $group = $daoGroup->field('school,vStatus,vStern')->find($gid);
        if (!$group) {
            $this->error('该部落不存在或已删除');
        }
        if ($group['school'] != $this->school['id']) {
            $this->error('不是本校部落，无权操作');
        } elseif ($group['vStatus'] != 1) {
            $this->error('该部落还未通过认证');
        }
        if ($stern == $group['vStern']) {
            $this->success('修改星级成功');
        }
        $result = $daoGroup->where("id=$gid")->setField('vStern', $stern);
        if ($result) {
            $this->success('修改星级成功');
        }
        $this->error('修改星级失败，请重试');
    }

////驳回  update数据库
//    public function doBack() {
//        $id = intval($_POST['id']);
//        $reject = t($_POST['reject']);
//        if (empty($reject)) {
//            $this->error('请填写驳回原因');
//        }
//        $gid = $this->_checkCanValid($id);
//        $result = M('group_validate')->where("id=$id")
//                ->setField(array('uid', 'vtime', 'reject', 'status'), array($this->uid, time(), $reject, 0));
//        if (!$result) {
//            $this->error('驳回失败，请重试');
//        }
//        $group = M('group')->field('name,uid')->find($gid);
//        $notify_data['title'] = $group['name'];
//        $notify_data['group_id'] = $gid;
//        service('Notify')->sendIn($group['uid'], 'event_group_delaudit', $notify_data);
//        $this->success('驳回成功');
//    }

    //检查是否可以审核
    private function _checkCanValid($vid) {
        $valid = M('group_validate')->field('gid,sid,status')->find($vid);
        if (!$valid) {
            $this->error('该申请不存在或已删除');
        }
        if ($valid['sid'] != $this->school['id']) {
            $this->error('不是本校部落');
        }
        if ($valid['status'] != 1) {
            $this->error('该申请已被审核过了');
        }
        return $valid['gid'];
    }

    private function _checkCanDisband($vid) {
        $valid = M('group')->field('disband,is_del,school,vStatus,sid1')->find($vid);
        if (!$valid) {
            $this->error('部落不存在');
        }
        if ($valid['school'] != $this->school['id']) {
            $this->error('不是本校部落' . $this->school['id']);
        }
        if($this->user{'event_level'}!=10&&$this->user['sid1']!=$valid['sid1']){
              $this->error('不是本院部落');
        }
        if ($valid['disband'] != 0) {
            $this->error('部落已解散');
        }
        if ($valid['is_del'] == 1) {
            $this->error('该部落已经删除');
        }

    }

    //通过部落
    public function doAudit() {
        $id = intval($_REQUEST['id']);
        $stern = intval($_REQUEST['stern']);
        if ($stern != 1 && $stern != 2 && $stern != 3) {
            $this->error('星级格式错误');
        }
        $gid = $this->_checkCanValid($id);
        $result = M('group_validate')->where("id=$id")->setField(array('uid', 'vtime', 'status'), array($this->uid, time(), 2));
        if ($result) {
            $daoGroup = M('group');
            $daoGroup->where("id=$gid")->setField(array('vStatus', 'vStern'), array(1, $stern));
            // 发送通过通知
            $group = $daoGroup->field('name,uid')->find($gid);
            $notify_data['title'] = $group['name'];
            $notify_data['group_id'] = $gid;
            $notify_dao = service('Notify');
            $notify_dao->sendIn($group['uid'], 'event_group_audit', $notify_data);
            $this->success("审核成功");
        } else {
            $this->error('审核失败，请重试');
        }
    }

    public function look() {
        $map['id'] = intval($_GET['id']);
        $map['sid'] = $this->school['id'];
        $result = M('group_validate')->where($map)->find();
        if (!$result) {
            $this->error('该部落申请不存在');
        }
        $this->assign($result);
        $group = M('group')->field('name,uid')->find($result['gid']);
        $this->assign('group', $group);
        $this->display();
    }

//允许解散部落

    public function doDisband() {
        $gid = t($_GET['id']);
        $this->_checkCanDisband($gid);
        $daoGroup = D('EventGroup');
//        $result = D('Group', 'group')->remove($gid);
        $result = $daoGroup->disBandGroup($gid);
        if ($result) {
            // 发送通过通知
            $group = $daoGroup->field('name,uid')->find($gid);
            $notify_data['title'] = $group['name'];
            $notify_dao = service('Notify');
            $notify_dao->sendIn($group['uid'], 'event_group_disband', $notify_data);
            $this->success("成功解散");
        } else {
            $this->error('解散失败，请重试');
        }
    }

    //驳回解散部落申请
    public function notDisband() {
        $this->assign('gid', intval($_REQUEST['id']));
        $this->display();
    }

    public function backDisband() {
        $id = intval($_POST['id']);
        $reject = t($_POST['reject']);
        if (empty($reject)) {
            $this->error('请填写驳回原因');
        }
        $this->_checkCanDisband($id);
        $result = M('group')->where("id=$id")
                ->setField('disband', 0);
        if (!$result) {
            $this->error('驳回失败，请重试');
        }
        $group = M('group')->field('name,uid')->find($gid);
        $notify_data['title'] = $group['name'];
        $notify_data['reason'] = $reject;
        service('Notify')->sendIn($group['uid'], 'event_group_nodisband', $notify_data);
        $this->success('驳回成功');
    }

    public function addGroup() {
        $thisYear = date('y',time());
        $years = array();
        for ($i = 9; $i <= $thisYear; $i++) {
            $years[] = sprintf("%02d", $i);
        }
        $this->assign('years', $years);
        $cat = M('group_category')->field('id,title')->findAll();
        $this->assign('cat', $cat);
        $school = model('Schools')->makeLevel0Tree($this->sid);
        $this->assign('addSchool', $school);
        $this->display();
    }


     public function doAddGroup() {
        if (!$_POST['title']) {
            $this->error('标题不能为空');
        } else if (get_str_length($_POST['title']) > 20) {
            $this->error('标题不能超过20个字');
        }
        if (!$_POST['uid']) {
            $this->error('主席不能为空');
        }
        if (!$_POST['cat']) {
            $this->error('请选择部落分类');
        }
        if (!$_POST['category']) {
            $this->error('请选择部门分类');
        }
        if (!$_POST['sid1']) {
            $this->error('请选择院系');
        }
        $group['name'] = h(t($_POST['title']));
        $group['cid0'] = intval($_POST['cat']);
        $group['category'] = intval($_POST['category']);
        $_POST['year'] && $group['year'] = t($_POST['year']);
        $group['sid1'] = intval($_POST['sid1']);
        $group['uid'] = intval($_POST['uid']);
        $group['school'] = $this->sid;
        $group['vStern'] = intval($_POST['stern']);
        $group['vStatus'] = 1;
        $group['whoDownloadFile'] = 3;
        $group['need_invite'] = 1;
        $group['ctime'] = time();
        $gid = M('group')->add($group);
        if ($gid) {
//             把自己添加到成员里面
            D('EventGroup', 'event')->joinGroup($group['uid'], $gid, 1, $incMemberCount = true);
            //发通知消息
            $notify_data['title'] = $group['name'];
            $notify_data['group_id'] = $gid;
            $notify_dao = service('Notify');
            $notify_dao->sendIn($group['uid'], 'event_group_init', $notify_data);
            $this->success('创建成功');
        } else {
            $this->error('创建失败');
        }
    }
    //查找主席
    public function findTeam() {
        if (!$_POST['team']) {
            exit();
        }
        if (preg_match("/[\x7f-\xff]+/", $_POST['team'])) {
            $realname = $_POST['team'];
            $name = M('user')
                            ->where("`realname` = '$realname' AND sid =" . $this->school['id'])
                            ->field('realname,uid,email')->findAll();
            if ($name) {
                foreach($name as $k=>$v){
                    $name{$k}['email'] = getUserEmailNum($v['email']);
                }
                exit(json_encode($name));
            }
        } else {
            $stuId = t($_POST['team']);
            $email = $stuId . $this->school['email'];
            $name = M('user')
                            ->where("`email` = '$email' AND sid =" . $this->school['id'])
                            ->field('realname,uid')->find();
            if ($name) {
                $name[0]['email'] ='000';
                exit(json_encode($name));
            }
        }
    }

   //分类
    public function catTree() {
        $cat = intval($_POST['cat']);
        $data = M('group_category')->where('pid =' . $cat)->field('id,title')->findAll();
        echo json_encode($data);
    }

    public function editGroup() {
        $id = $_GET['id'];
        $daoGroup = M('group');
        $this->_checkCanDisband($id);
        $group = $daoGroup->field('id,name,cid0,year,category,sid1,uid,vStern')->find($id);
        $school = model('Schools')->makeLevel0Tree($this->sid);
        $thisYear = date('y', time());
        $years = array();
        for ($i = 9; $i <= $thisYear; $i++) {
            $years[] = sprintf("%02d", $i);
        }
        $this->assign('years', $years);
        $cat = M('group_category')->field('id,title')->findAll();
        $this->assign('cat', $cat);
        $this->assign('addSchool', $school);
        $this->assign($group);
        $this->display();
    }

      public function doEditGroup() {
        $id = $_GET['id'];
        $daoGroup = M('group');
        $this->_checkCanDisband($id);
        if (!$_POST['title']) {
            $this->error('标题不能为空');
        } else if (get_str_length($_POST['title']) > 20) {
            $this->error('标题不能超过20个字');
        }
        if (!$_POST['uid']) {
            $this->error('主席不能为空');
        }
        if (!$_POST['cat']) {
            $this->error('请选择部落分类');
        }
        if (!$_POST['category']) {
            $this->error('请选择部门分类');
        }
        if (!$_POST['sid1']) {
            $this->error('请选择院系');
        }
        $group['name'] = h(t($_POST['title']));
        $group['cid0'] = $_POST['cat'];
        $group['category'] = intval($_POST['category']);
        $_POST['year'] && $group['year'] = t($_POST['year']);
        $group['sid1'] = intval($_POST['sid1']);
        $group['uid'] = intval($_POST['uid']);
        $group['vStern'] = intval($_POST['stern']);
        $res = $daoGroup->where('id = ' . $id)->save($group);
        if ($res) {
            $this->success('编辑成功');
        } else {
            $this->error('编辑失败');
        }
    }

    public  function transfer(){
          $id = intval($_GET['id']);
             $daoGroup = M('group');
        $group = $daoGroup->field('id,uid')->find($id);
        $this->assign($group);
        $this->display();
    }

    public function doTransfer() {
        $id = intval($_POST['gid']);
        $uid = intval($_POST['uid']);
        $this->_checkCanDisband($id);
        $daoGroup = M('group');
        $olduid = $daoGroup->getField('uid','id='.$id);
        $res = $daoGroup->where('id =' . $id)->setField('uid', $uid);
        if ($res) {
            //剔除原先主席
            D('GroupMember')->toNormalMember($olduid,$id);
            //添加新主席
            $map['gid'] = $id;
            $map['uid'] = $uid;
            $result = D('GroupMember')->where($map)->find();
            if (!$result) {
                D('EventGroup', 'event')->joingroup($uid, $id, 1, $incMemberCount = true);
            } else {
                D('GroupMember')->where($map)->setField(array('uid', 'level'), array($uid, 1));
                //修改部落活动发起权限
                $daoEventGroup = M('event_group');
                $data['gid'] = $id;
                $data['uid'] = $uid;
                $daoEventGroup->add($data);
                M('user')->where('uid =' . $uid)->setField('can_add_event', 1);
            }
            $this->success('转让成功');
        } else {
            $this->error('转让失败');
        }
    }


}
