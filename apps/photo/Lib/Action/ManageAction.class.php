<?php
//相册应用 - ManageAction 编辑、维护 专辑和图片
class ManageAction extends BaseAction{
	public function _initialize() {
		parent::_initialize();
		$IsHotList = IsHotList();
		$this->assign('IsHotList',$IsHotList);
	}

	//删除图片
	public function delete_photo() {
		$map['id']		=	intval($_REQUEST['id']);
		$map['albumId']	=	intval($_REQUEST['albumId']);
		$map['userId']	=	$this->mid;

		$result	=	D('Album')->deletePhoto($map['id'],$this->mid);
		if($result){
			X('Credit')->setUserCredit($this->mid,'delete_photo');
			//删除成功
			echo 1;exit;
		}else{
			//删除失败
			echo 0;exit;
		}
	}

	//相册管理
    public function index(){
		$this->display();
    }

	//创建专辑
	public function create_album_tab(){
		$this->display();
	}
	public function do_create_album() {
		$name	=	h(t(mStr(trim($_POST['name']),12,'utf-8',false)));
		if(strlen($name)==0){
			$this->error('相册名称不能为空！');
		}
		$album	=	D('Album');
		//检测相册是否已存在
		$albumId = $album->getField('id',"userId={$this->mid} AND name='{$name}'");
		if($albumId){
			echo -1;
			exit;
		}

		$album->cTime	=	time();
		$album->mTime	=	time();
		$album->userId	=	$this->mid;
		$album->name	=	$name;
		$album->privacy	=	intval($_POST['privacy']);

		//设置相册密码
		if(intval($_POST['privacy'])==4)
			$album->privacy_data	=	t($_POST['privacy_data']);

		$result	= $album->add();

		if($result){
			X('Credit')->setUserCredit($this->mid,'add_album');
			echo json_encode(array('albumId'=>$result,'albumName'=>$name));
		}else{
			echo false;
		}
	}

	//编辑专辑
	public function album_edit() {

		$id		=	intval($_REQUEST['id']);
		$uid	=	$this->mid;

		//获取相册信息
		$album		=	D('Album')->where(" id='$id' AND userId='$uid' ")->find();
		$this->assign('album',$album);

		$albumlist	=	D('Album')->where(" userId='$uid' ")->findAll();
		$this->assign('albumlist',$albumlist);

		if(!$album){

			$this->error('专辑不存在或已被删除！');
		}else{

			//获取图片数据
			$map['albumId']	=	$id;
			$photos		=	D('Photo')->where($map)->findAll();
			$this->assign('photos',$photos);

		}

		$this->display();
	}

	//保存相册编辑信息
	public function do_update_album() {

		//相册信息
		$albumId			=	intval($_POST['albumId']);
		$album_name			=	t($_POST['album_name']);
		$album_cover		=	intval($_POST['album_cover']);
		$album_privacy		=	intval($_POST['album_privacy']);
		$album_privacy_data	=	$_POST['album_privacy_data'];

		$albumDao		=	D('Album');
		$photoDao		=	D('Photo');
		//$photoIndexDao	=	D('AlbumPhoto');

		//获取相册信息
		$albumInfo		=	$albumDao->where("id='$albumId' AND userId='$this->mid' ")->find();
		if(!$albumInfo){
			$this->error('你没有权限编辑该相册！');
		}

		/*   处理图片信息  */

			//解析图片数据
			foreach($_POST['name'] as $k=>$v){
				$new_photos[$k]['name']		=	t($v);
				$new_photoids[]	=	$k;
			}
			foreach($_POST['move_to'] as $k=>$v){
				$new_photos[$k]['albumId']	=	$v;
			}


			//对比原始数据，筛选出需要更新的图片
			$photo_ids['id']	=	array('in',$new_photoids);
			$old_photos			=	$photoDao->where($photo_ids)->findAll();
			foreach($old_photos as $k=>$v){
				//如果相册ID和名称都没变化，不需要保存
				$photoid	=	$v['id'];
				if($v['albumId']==$new_photos[$photoid]['albumId'] && $v['name']==$new_photos[$photoid]['name'] ){
					unset($new_photos[$photoid]);
				}
			}

			//保存图片信息
			foreach($new_photos as $k=>$v){
				unset($map);
				$map['userId']		=	$this->mid;
				$map['albumId']		=	$v['albumId'];
				$map['name']		=	$v['name'];
				$map['privacy']		=	$album_privacy;
				//相册信息更新
				$photoDao->limit(1)->where("id='$k'")->save($map);
				//重置相册图片数
				$albumDao->updateAlbumPhotoCount($map['albumId']);

				//相册索引更新
				//$index['albumId']	=	$v['albumId'];
				//$photoIndexDao->limit(1)->where("albumId='".$v['albumId']."' AND photoId='".$k."'")->save($index);
			}

		/*   处理相册信息  */

			//重置相册图片数
			$albumDao->updateAlbumPhotoCount($albumId);

			//如果相册封面发生变化
			if($albumInfo['coverImageId']!=$album_cover){
				$album['coverImageId']	=	$album_cover;
				if($coverInfo	=	$photoDao->field('id,savepath')->find($album_cover)){
					$album['coverImagePath'] = $coverInfo['savepath'];
				}
			}

			//如果相册隐私发生变化
			if( $albumInfo['privacy'] != $album_privacy ){
				$album['privacy']	=	$album_privacy;
				//更新该相册下所有图片的隐私
				unset($map);
				$map['privacy']		=	$album_privacy;
				$photoDao->where("albumId='$albumId'")->save($map);
			}

			//如果相册隐私数据发生变化
			if( $albumInfo['privacy_data'] != $album_privacy_data ){
				$album['privacy_data']	=	h(t($album_privacy_data));
			}

			$album['name']	=	h(t(mStr($album_name,14,'utf-8',false)));

			$result	=	$albumDao->where("id='$albumId'")->save($album);

		//跳转到相册页面
        $this->assign('jumpUrl', U('/Index/album',array(id=>$albumId,uid=>$this->mid)));
		$this->success('相册编辑成功！');
	}

	//图片排序
	public function reorder_photos() {
		$this->display();
	}

	//编辑图片
	public function edit_photo_tab() {
		$map['id']		=	intval($_REQUEST['pid']);
		$map['albumId']	=	intval($_REQUEST['aid']);
		$map['userId']	=	$this->mid;
		$map['isDel']	=	0;
		$photo			=	D('Photo')->where($map)->find();
		if(!$photo){
			echo "错误的相册信息！";
		}
		$this->assign('photo',$photo);
		$this->display();
	}

	//执行图片修改操作
	public function do_update_photo() {
		$id		        =	intval($_REQUEST['id']);
		$map['albumId']	=	intval($_REQUEST['albumId']);
		$map['name']	=	t($_REQUEST['name']);
		$nextId			=	intval($_REQUEST['nextId']);
		$photoDao       =   D('Photo');
		$albumDao       =   D('Album');
		//图片原信息
		$oldInfo        =	$photoDao->where("id={$id} AND userId={$this->mid}")->field('albumId')->find();
		//更新信息
		$result			=	$photoDao->where("id={$id} AND userId={$this->mid}")->save($map);
		//移动图片则重置相册图片数
		if($map['albumId']!=$oldInfo['albumId']){
			$albumDao->updateAlbumPhotoCount($map['albumId']);
			$albumDao->updateAlbumPhotoCount($oldInfo['albumId']);
		}
		//返回
		if($result){
			echo 1;//成功
		}else{
			echo 0;
		}
	}

	//设置封面
	public function set_cover() {
		$albumId	=	intval($_POST['albumId']);
		$photoId	=	intval($_POST['photoId']);

		$photo		=	D('Photo')->where("id='$photoId' AND albumId='$albumId' ")->find();
		if($photo){
			$map['coverImageId']	=	$photoId;
			$map['coverImagePath']	=	$photo['savepath'];
			$result		=	D('Album')->where(" id='$albumId' ")->save($map);
			if($result){
				//设置成功
				echo "1";
			}else{
				//设置失败 如果封面已是该图片 则也返回该值
				echo "0";
			}
		}else{
			//该图片不存在
			echo "-1";
		}
	}

	//删除专辑
	public function delete_album() {
		$map['id']		=	intval($_REQUEST['id']);
		$map['userId']	=	$this->mid;
		//$album			=	D('Album')->field('isDel')->where($map)->find();

		//if($album['isDel']==0){
			$result	= D('Album')->deleteAlbum($map['id'],$this->mid);
			if($result){
				//删除成功
				X('Credit')->setUserCredit($this->mid,'delete_album');
				$this->assign('jumpUrl', U('/Index/albums'));
				$this->success('删除相册成功~');
			}else{
				//删除失败
				$this->error('删除失败~！');
			}
		//}else{
			//不存在或已被删除
			//echo "-1";exit;
		//}
	}

	//图片排序
	public function album_order() {

        $id     =   intval($_REQUEST['id']);
        $uid    =   $this->mid;

        //获取相册信息
        $album  =   D('Album')->where(" id='$id' AND userId='$uid' ")->find();
        if(!$album){
            $this->error('专辑不存在或已被删除！');
        }

        $map['albumId'] =   $id;
        $map['userId']  =   $uid;
        $map['isDel']   =   0;

        $photos =   D('Photo')->order('`order` DESC,id DESC')->where($map)->findAll();
        $this->assign('photos',$photos);
        //获取标记数据
        //D('PhotoMarks')->where($map)->findAll();

        $this->setTitle(getUserName($this->uid).'的相册：'.$album['name']);
        $this->assign('aid',$id);
        $this->assign('album',$album);
		$this->display();
	}

	//保存图片排序
	public function save_order(){
		$order	=	explode(',',$_POST['order']);
		$order	=	array_reverse($order);
		$id		=	intval($_POST['id']);
		$dao	=	D('Photo');

		foreach($order as $key=>$value){
			$condition['id'] = $value;
            $map['order'] = intval($key);
            $result = $dao->where($condition)->save($map);
		}
		//if($result){
			echo 1;
		//}
	}
}
?>