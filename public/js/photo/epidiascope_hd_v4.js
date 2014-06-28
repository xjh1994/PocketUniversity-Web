﻿var sina = {
	$ : function(objName){if(document.getElementById){return eval('document.getElementById("'+objName+'")')}else{return eval('document.all.'+objName)}},
	isIE : navigator.appVersion.indexOf("MSIE")!=-1?true:false,

	//Event
	addEvent : function(obj,eventType,func){if(obj.attachEvent){obj.attachEvent("on" + eventType,func);}else{obj.addEventListener(eventType,func,false)}},
	delEvent : function(obj,eventType,func){
		if(obj.detachEvent){obj.detachEvent("on" + eventType,func)}else{obj.removeEventListener(eventType,func,false)}
	},
	//Cookie
	readCookie : function(l){var i="",I=l+"=";if(document.cookie.length>0){var offset=document.cookie.indexOf(I);if(offset!=-1){offset+=I.length;var end=document.cookie.indexOf(";",offset);if(end==-1)end=document.cookie.length;i=document.cookie.substring(offset,end)}};return i},

	writeCookie : function(O,o,l,I){var i="",c="";if(l!=null){i=new Date((new Date).getTime()+l*3600000);i="; expires="+i.toGMTString()};if(I!=null){c=";domain="+I};document.cookie=O+"="+escape(o)+i+c},
	//Style
	readStyle:function(i,I){if(i.style[I]){return i.style[I]}else if(i.currentStyle){return i.currentStyle[I]}else if(document.defaultView&&document.defaultView.getComputedStyle){var l=document.defaultView.getComputedStyle(i,null);return l.getPropertyValue(I)}else{return null}},
	absPosition : function(obj,parentObj){ //位置
		var left = 0;
		var top = 0;
		var tempObj = obj;
		try{
			do{
				left += tempObj.offsetLeft;
				top += tempObj.offsetTop;
				tempObj = tempObj.offsetParent;
			}while(tempObj.id!=document.body && tempObj.id!=document.documentElement && tempObj != parentObj && tempObj!= null);
		}catch(e){};
		return {left:left,top:top};
	},
	_getJsData : function(url,callback){
		var _script = document.createElement("script");
		_script.type = "text/javascript";
		_script.language = "javascript";

		_script[_script.onreadystatechange === null ? "onreadystatechange" : "onload"] = function(){
			if(this.onreadystatechange){
				if(this.readyState != "complete" && this.readyState != "loaded") {return;}
			};
			if(callback){callback()};
			setTimeout(function(){_script.parentNode.removeChild(_script)},1000);
		};
		_script.src = url;

		document.getElementsByTagName("head")[0].appendChild(_script);

	},
	style : {
		setOpacity : function(obj,opacity){
			if(typeof(obj.style.opacity) != 'undefined'){
				obj.style.opacity = opacity;
			}else{
				obj.style.filter = 'Alpha(Opacity=' + (opacity*100) + ')';
			};
		}
	},
	extend : {
		show : function(obj,timeLimit){
			if(sina.readStyle(obj,'display') === 'none'){
				obj.style.display = 'block';
			};
			sina.style.setOpacity(obj,0);
			if(!timeLimit){
				timeLimit = 200;
			};
			var opacity = 0,step = timeLimit / 20;
			clearTimeout(obj._extend_show_timeOut);
			obj._extend_show_timeOut = setTimeout(function(){
				if(opacity >= 1){
					return;
				};
				opacity += 1/step;
				sina.style.setOpacity(obj,opacity);
				obj._extend_show_timeOut = setTimeout(arguments.callee,20);

			},20);
		},
		hide : function(obj,timeLimit){
			if(!timeLimit){
				timeLimit = 200;
			};
			sina.style.setOpacity(obj,1);
			var opacity = 1,step = timeLimit / 20;
			clearTimeout(obj._extend_show_timeOut);
			obj._extend_show_timeOut = setTimeout(function(){
				if(opacity <= 0){
					obj.style.display = 'none';
					sina.style.setOpacity(obj,1);
					return;
				};
				opacity -= 1/step;
				sina.style.setOpacity(obj,opacity);
				obj._extend_show_timeOut = setTimeout(arguments.callee,20);

			},20);
		},
		actPX : function(obj,key,start,end,speed,endFn,u){
			if(typeof(u) == 'undefined'){u = 'px'};
			clearTimeout(obj['_extend_actPX' + key.replace(/\-\.\=/,'_') + '_timeOut']);
			if(start > end){
				speed = - Math.abs(speed);
			}else{
				speed = Math.abs(speed);
			};
			var now = start;
			var length = end - start;
			obj['_extend_actPX' + key.replace(/\-\.\=/,'_') + '_timeOut'] = setTimeout(function(){
				now += speed;
				//debugger;
				var space = end - now;
				if(start < end){
					if(space < length/3){
						speed = Math.ceil(space/3);
					};
					if(space <= 0){ //end
						obj[key] = end + u;
						if(endFn){endFn()};
						return;
					};
				}else{
					if(space > length/3){
						speed = Math.floor(space/3);
					};
					if(space >= 0){ //end
						obj[key] = end + u;
						if(endFn){endFn()};
						return;
					};
				};

				obj[key] = now + u;
				obj['_extend_actPX' + key.replace(/\-\.\=/,'_') + '_timeOut'] = setTimeout(arguments.callee,20);

			},20);
		}
	}
};

sina.Step = function(){
	this.stepIndex = 0; //当前步数
	this.classBase = 'step_'; //class规则
	this.limit = 3; //步总数
	this.stepTime = 20; //步时长
	this.element = null; //html对象
	this._timeObj = null; //setInterval对象
	this._type = '+'; //步方向
};
sina.Step.prototype.action = function(type){
	if(!this.element){return};
	var tempThis = this;
	if(type=='+'){
		this._type = '+';
	}else{
		this._type = '-';
	};
	clearInterval(this._timeObj);
	this._timeObj = setInterval(function(){tempThis.nextStep()},this.stepTime);
};
sina.Step.prototype.nextStep = function(){
	if(this._type == '+'){
		this.stepIndex ++;
	}else{
		this.stepIndex --;
	};

	if(this.stepIndex <= 0){
		clearInterval(this._timeObj);
		this.stepIndex = 0;
		if(this._type == '-'){
			if(this.onfirst){this.onfirst()};
		};
	};
	if(this.stepIndex >= this.limit){
		clearInterval(this._timeObj);
		this.stepIndex = this.limit;
		if(this._type == '+'){
			if(this.onlast){this.onlast()};
		};
	};
	this.element.className = this.classBase + this.stepIndex;

	if(this.onstep){this.onstep()};
};

var getData = {
	initF:false,
	nextUrl : "",
	preUrl : "",
	curUrl : "",
	fillData : function(flagPre){	//填充数据
		epidiascope.clearData();
		var images = slide_data.images;
		var flashPic = "",flashTxt = "";
		for(var i=0;i<images.length;i++){
			var title,pic,datetime,intro,middlePic,smallPic,commUrl,imageId;
			pic = images[i].image_url;
			datetime = images[i].createtime;
			title = images[i].title;
			intro = images[i].intro;
			middlePic = images[i].thumb_160;
			smallPic = images[i].thumb_50;
			//commUrl = images[i].comment;
			imageId = images[i].id;

			epidiascope.add({
				src : pic,
				date : datetime,
				title : title,
				text : intro,
				lowsrc_b : middlePic,
				lowsrc_s : smallPic,
				//comment : commUrl,
				id : imageId
			});

			//for flash
			if(flashPic != ""){flashPic += "|"};
			flashPic += pic;
			if(flashTxt != ""){flashTxt += "|"};
			if(!getData.initF){
				//第一次初始化时需要转码特殊字符
				flashTxt += encodeURIComponent(title) + "#　　" + encodeURIComponent(intro.replace(/<.*?>/g,''));
			}else{
				flashTxt += title + "#　　" + intro.replace(/<.*?>/g,'');
			}
		}

		getData.nextUrl = slide_data.next_album.interface;
		//getData.preUrl = slide_data.prev_album.interface;

		//设置页面上的缩略图集的上一图集、下一图集
                /*
		var efpPrePic = sina.$("efpPrePic");
		var efpPreTxt = sina.$("efpPreTxt");
		var efpNextPic = sina.$("efpNextPic");
		var efpNextTxt = sina.$("efpNextTxt");
		efpPrePic.getElementsByTagName("a")[0].href = slide_data.prev_album.url;
		efpPrePic.getElementsByTagName("img")[0].src = slide_data.prev_album.thumb_50;
		efpPrePic.getElementsByTagName("img")[0].alt = slide_data.prev_album.title;
		efpPrePic.getElementsByTagName("img")[0].title = slide_data.prev_album.title;
		efpPreTxt.getElementsByTagName("a")[0].href = slide_data.prev_album.url;
		efpPreTxt.getElementsByTagName("a")[0].title = slide_data.prev_album.title;
		efpNextPic.getElementsByTagName("a")[0].href = slide_data.next_album.url;
		efpNextPic.getElementsByTagName("img")[0].src = slide_data.next_album.thumb_50;
		efpNextPic.getElementsByTagName("img")[0].alt = slide_data.next_album.title;
		efpNextPic.getElementsByTagName("img")[0].title = slide_data.next_album.title;
		efpNextTxt.getElementsByTagName("a")[0].href = slide_data.next_album.url;
		efpNextTxt.getElementsByTagName("a")[0].title = slide_data.next_album.title;
                */

		document.title = slide_data.slide.title;//设置文档标题

		if(!getData.initF){
			//只需要初始化一次epidiascope和flash
			epidiascope.init();
			fullFlash(flashTxt,flashPic);
			getData.initF = true;
		}else{
			epidiascope.initNot();
			//给flash传数据
			getFullScreenFlash().onMethod(flashPic+"---"+flashTxt);
			if(flagPre){
				//如果加载的是上一组，则跳到最后一张
				epidiascope.select(images.length-1);
			}else{
				epidiascope.select(0);
			}
		}
	}
};

var epidiascope = {
	picTitleId : "d_picTit",
	picMemoId : "d_picIntro",
	picTimeId : 'd_picTime',
	picListId : "efpPicListCont",
	BigPicId : "d_BigPic",
	picArrLeftId : "efpLeftArea",
	picArrRightId : "efpRightArea",
	playButtonId : "ecbPlay",
	statusId : "ecpPlayStatus",
	mainBoxId : "efpBigPic",
	PVUrl_a : null,
	PVUrl_m : null,
	repetition : false, //循环播放
	prefetch : false, //预读图片
	autoPlay : false, //自动播放
	mode : 'player', //模式 player|list
	autoPlayTimeObj : null,
	timeSpeed : 5,
	maxWidth : 948,
	filmstrips : [],
	prefetchImg : [],
	commNum : [],
	selectedIndex : -1,
	previousPicList : {},
	nextPicList : {},
	loadTime : 0,
	PVUrl_AC : '',
	add : function(s){
		this.filmstrips.push(s);
		if(this.prefetch){ //预载图片
			var tempImg = new Image();
			tempImg.src = s.src;
			this.prefetchImg.push(tempImg);
		};
	},
	clearData : function(){
		this.selectedIndex = -1;
		this.filmstrips = [];
	},
	init : function(){
		var tempThis = this;
		var tempWidth = 0;
		if(this.filmstrips.length * 110 < sina.$(this.picListId).offsetWidth){
			tempWidth = Math.round(sina.$(this.picListId).offsetWidth / 2 - this.filmstrips.length * 110/2);
		};
		var commKey = "";
		var tempHTML = '<div style="width:32760px;padding-left:' + tempWidth + 'px;">',i;
		for(i=0;i<this.filmstrips.length;i++){
			//子列表
			tempHTML += '<div class="pic" id="slide_' + i + '"><table cellspacing="0"><tr><td class="picCont"><table cellspacing="0"><tr><td class="pb_01"></td><td class="pb_02"></td><td class="pb_03"></td></tr><tr><td class="pb_04"></td><td><a href="javascript:epidiascope.select(' + i + ');" onclick="this.blur();"><img src="' + this.filmstrips[i].lowsrc_s + '" alt="' + this.filmstrips[i].title + '" oncontextmenu="event.returnValue=false;return false;" /></a></td><td class="pb_05"></td></tr><tr><td class="pb_06"></td><td class="pb_07"></td><td class="pb_08"></td></tr></table></td></tr></table></div>';

			//评论数据
                        /*
			var commId = this.filmstrips[i].comment.match(/channel\=(.*?)\&newsid\=(.*?)(\&|$)/);
			this.filmstrips[i].commNum = 0;
			this.filmstrips[i].commId = "";
			if(commId){
				if(i>140){continue};
				commId = commId[1] + ":" + commId[2] + ":0";

				this.filmstrips[i].commId = commId;
				if(commKey!=''){commKey+=','};
				commKey += commId;
			};
                        */
		};

		//sina._getJsData('http://comment5.news.sina.com.cn/cmnt/count?format=js&jsvar=g_clist&newslist='+commKey,function(){tempThis.readCommNum()});

		sina.$(this.picListId).innerHTML = tempHTML + "</div>";

		//
		sina.$(this.picArrLeftId).onclick = function(){epidiascope.previous();epidiascope.stop();};
		sina.$(this.picArrRightId).onclick = function(){epidiascope.next();epidiascope.stop();};

		if(window.location.href.indexOf('2010.')!=-1){
			this.autoPlay = true;
			sina.$(this.picArrRightId).onclick = function(){epidiascope.next();epidiascope.play();};
		};

		//按钮
		this.buttonNext = new epidiascope.Button('ecbNext'); //下一页
		this.buttonPre = new epidiascope.Button('ecbPre'); //上一页
		this.buttonPlay = new epidiascope.Button('ecbPlay'); //播放暂停
		//this.buttonCommTop = new epidiascope.Button('ecbComm'); //评论
		this.buttonMode = new epidiascope.Button('ecbMode'); //模式切换
		this.buttonFullScreen = new epidiascope.Button('ecbFullScreen','2'); //全屏

		this.buttonSpeed = new epidiascope.Button('ecbSpeed'); //速度
		this.buttonModeReturn = new epidiascope.Button('ecbModeReturn'); //模式切换

		this.buttonPre.element.onclick = function(){epidiascope.previous();epidiascope.stop();};
		this.buttonNext.element.onclick = function(){epidiascope.next();epidiascope.stop();};
		this.buttonMode.element.onclick = function(){epidiascope.setMode('list');};
		this.buttonModeReturn.element.onclick = function(){epidiascope.setMode('player');};
		this.buttonFullScreen.element.onclick = function(){epidiascope.fullScreen.chk()};
		//
		this.BigImgBox = sina.$(this.BigPicId);

		//禁止右键
		this.BigImgBox.oncontextmenu = function(e){
			e = e?e:event;
			e.returnValue=false;
			return false;
		};

		this._imgLoad = function(){
			if(epidiascope.maxWidth == 0 ){return};
			if(this.width > epidiascope.maxWidth){
				this.width = epidiascope.maxWidth;
				//this.height = Math.round(epidiascope.maxWidth * this.height / this.width);
			};
			if(this.width < 948){
				sina.$('d_BigPic').style.paddingTop = "15px";
				this.style.border = "1px solid #000";
			}else{
				sina.$('d_BigPic').style.paddingTop = "0px";
				this.style.border = "none";
				this.style.borderBottom = "1px solid #e5e6e6";
			};

			//隐藏loading图片
			clearTimeout(tempThis._hideBgTimeObj);
			sina.$('d_BigPic').className = '';
		};

		this._preLoad = function(){
                    /*
			var tempTime = new Date().getTime();
			var deltaTime = tempTime - epidiascope.loadTime;
			if(deltaTime > 5000 && Math.ceil(Math.random()*1000)==1){
				var tempLoadImage = new Image().src = "http://roll.book.sina.com.cn/interface/slow_log.php?time="+ deltaTime +"&url=" + encodeURIComponent(this.src) + "&t=2";
			}
                    */
		}

		this.createImg(this.filmstrips[0].src);

		var page;
		var imgId = window.location.search.match(/img=(\d+)/i);
		if(imgId){
			imgId = imgId[1];
			page = 0;
			for(var i = 0, len = this.filmstrips.length; i<len; i++){
				if(parseInt(this.filmstrips[i]['id']) == parseInt(imgId)){
					page = i;
					break;
				}
			}
		}else{
			page = window.location.hash.match(/p=(\d+)/i);
			if(page){
				page = page[1] - 1;
				if(page<0 || page >= this.filmstrips.length){
					page = 0;
				};
			}else{
				page = 0;
			};
		}
		this.select(page);

		if(!sina.isIE){
			this.BigImgBox.style.position = 'relative';
			this.BigImgBox.style.overflow = "hidden";

		}else{

			clearInterval(this._ieButHeiTimeObj);
			this._ieButHeiTimeObj = setInterval(function(){tempThis.setPicButtonHeight()},300);
		};
		//设置下一图集
		var nextPics = sina.$('efpNextGroup').getElementsByTagName('a');
		sina.$('nextPicsBut').href = nextPics[0].href;

		if(this.autoPlay){this.play()}else{this.stop()};


		if(this.onstart){this.onstart()};
		//iPad兼容处理
		this.iPad.init();
	},
	initNot : function(){
		//不是第一次的初始化，去掉一些按钮事件的重复加载。
		var tempThis = this;
		var tempWidth = 0;
		if(this.filmstrips.length * 110 < sina.$(this.picListId).offsetWidth){
			tempWidth = Math.round(sina.$(this.picListId).offsetWidth / 2 - this.filmstrips.length * 110/2);
		};
		var commKey = "";
		var tempHTML = '<div style="width:32760px;padding-left:' + tempWidth + 'px;">',i;
		for(i=0;i<this.filmstrips.length;i++){
			//子列表
			tempHTML += '<div class="pic" id="slide_' + i + '"><table cellspacing="0"><tr><td class="picCont"><table cellspacing="0"><tr><td class="pb_01"></td><td class="pb_02"></td><td class="pb_03"></td></tr><tr><td class="pb_04"></td><td><a href="javascript:epidiascope.select(' + i + ');" onclick="this.blur();"><img src="' + this.filmstrips[i].lowsrc_s + '" alt="' + this.filmstrips[i].title + '"  onload="DrawImage(this);" oncontextmenu="event.returnValue=false;return false;" /></a></td><td class="pb_05"></td></tr><tr><td class="pb_06"></td><td class="pb_07"></td><td class="pb_08"></td></tr></table></td></tr></table></div>';

			//评论数据
                        /*
			var commId = this.filmstrips[i].comment.match(/channel\=(.*?)\&newsid\=(.*?)(\&|$)/);
			this.filmstrips[i].commNum = 0;
			this.filmstrips[i].commId = "";
			if(commId){
				if(i>140){continue};
				commId = commId[1] + ":" + commId[2] + ":0";

				this.filmstrips[i].commId = commId;
				if(commKey!=''){commKey+=','};
				commKey += commId;
			};
                        */
		};

		//sina._getJsData('http://comment5.news.sina.com.cn/cmnt/count?format=js&jsvar=g_clist&newslist='+commKey,function(){tempThis.readCommNum()});

		sina.$(this.picListId).innerHTML = tempHTML + "</div>";
		this.createImg(this.filmstrips[0].src);

		var page;
		var imgId = window.location.search.match(/img=(\d+)/i);
		if(imgId){
			imgId = imgId[1];
			page = 0;
			for(var i = 0, len = this.filmstrips.length; i<len; i++){
				if(parseInt(this.filmstrips[i]['id']) == parseInt(imgId)){
					page = i;
					break;
				}
			}
		}else{
			page = window.location.hash.match(/p=(\d+)/i);
			if(page){
				page = page[1] - 1;
				if(page<0 || page >= this.filmstrips.length){
					page = 0;
				};
			}else{
				page = 0;
			};
		}
		this.select(page);
		setTimeout(function(){tempThis.picList.foucsTo(page + 1)},500);

		if(!sina.isIE){
			this.BigImgBox.style.position = 'relative';
			this.BigImgBox.style.overflow = "hidden";

		}else{
			clearInterval(this._ieButHeiTimeObj);
			this._ieButHeiTimeObj = setInterval(function(){tempThis.setPicButtonHeight()},300);
		};

		//列表模式初始化标志
		this.listInitStatus = false;

		//设置下一图集
		var nextPics = sina.$('efpNextGroup').getElementsByTagName('a');
		sina.$('nextPicsBut').href = nextPics[0].href;
		if(this.autoPlay){this.play()}else{this.stop()};
		if(this.onstart){this.onstart()};
	},
	readTry : 0,
	readCommNum : function(){
		var tempThis = this;
		try{
			for(var i in g_clist.result.count){
				for(var j=0;j<this.filmstrips.length;j++){
					if(this.filmstrips[j].commId == i){
						this.filmstrips[j].commNum = g_clist.result.count[i].total;
						break;
					};
				};
			};
			sina.$('commAObjNum').innerHTML = this.filmstrips[this.selectedIndex].commNum;
		}catch(e){
			this.readTry ++;
			if(this.readTry<10){
				setTimeout(function(){tempThis.readCommNum()},1000);
			};
			return;
		};

	},
	createImg : function(src){
		if(this.ImgObj1){
			this.ImgObj1.parentNode.removeChild(this.ImgObj1);
		};
		this.ImgObj1 = document.createElement("img");
		this.ImgObj1.onmousedown = function(){return false};
		this.ImgObj1.galleryImg = false;
		this.ImgObj1.onload = this._imgLoad;
		if(src){
			this.ImgObj1.src = src;
		};
		this.BigImgBox.appendChild(this.ImgObj1);
	},
	select : function(num,type){
		var tempThis = this;
		if(this.endSelect.status == 1){
			this.endSelect.close();
		};
		if(num == this.selectedIndex){return};
		var i;
		if(num >= this.filmstrips.length || num < 0){return};

		sina.$(this.picTitleId).innerHTML = this.filmstrips[num].title;

		//如果幻灯单张图片的字数小于30则居中显示，add by fanrong at 2012-08-06
		if(this.filmstrips[num].text.length <= 30) {
			this.filmstrips[num].text = "<center>" + this.filmstrips[num].text + "</center>";
		}
		sina.$(this.picMemoId).innerHTML = this.filmstrips[num].text;
		sina.$(this.picTimeId).innerHTML = this.filmstrips[num].date;

		//隐藏loading图片1秒钟
		sina.$('d_BigPic').className = '';
		clearTimeout(this._hideBgTimeObj);
		this._hideBgTimeObj = setTimeout("sina.$('d_BigPic').className='loading'",500);

		this.createImg();

		if(this._timeOut){
			for(i=0;i<this._timeOut.length;i++){
				clearTimeout(this._timeOut[i]);
			};
		};
		this._timeOut = [];

		if(this.ImgObj1.style.opacity === undefined){
			this.ImgObj1.src = 'http://i0.sinaimg.cn/dy/deco/2008/0331/yocc080331img/news_mj_005.gif';
			this.ImgObj1.filters[0].Apply();

			this.ImgObj1.src = this.filmstrips[num].src;
			this.ImgObj1.filters[0].Play();
		}else{
			this.ImgObj1.style.opacity = 0;
			this.ImgObj1.src = this.filmstrips[num].src;
			for(i = 0;i <= 3;i ++){
				this._timeOut[i] = setTimeout("epidiascope.ImgObj1.style.opacity = " + i * 0.3,i * 100);
			};
			this._timeOut[i] = setTimeout("epidiascope.ImgObj1.style.opacity = 1;",4 * 100);
		};

		if(sina.$("slide_" + this.selectedIndex)){sina.$("slide_" + this.selectedIndex).className = "pic"};
		sina.$("slide_" + num).className = "picOn";
		this.selectedIndex = num;

		this.picList.foucsTo(num + 1); //滚动

		sina.$("total").innerHTML = '(<span class="cC00">'+(num + 1) + "</span>/" + this.filmstrips.length + ')';
		if(this.autoPlay){this.play()};
		this.PVCount(type, this.filmstrips[num].src); //PV统计

		//预载下一张
		if(!this.prefetch && num < this.filmstrips.length - 1){ //未预载全部图片
			this.reLoad = new Image();
			this.reLoad.src = this.filmstrips[num + 1].src;
			this.loadTime = new Date().getTime();
			this.reLoad.onload = this._preLoad;
		};

		//评论
                /*
		if(this.filmstrips[num].comment != ''){
			//sina.$('commAObj').href = this.filmstrips[num].comment;
			if(typeof __ch_id__ != 'undefined' && __ch_id__ == '23'){//如果是世界杯图集 则使用comment.2010.sina.com.cn域名
				sina.$('commAObj').href = this.filmstrips[num].comment.replace('http://comment4.news.sina.com.cn','http://comment.2010.sina.com.cn');
			}else{
				sina.$('commAObj').href = this.filmstrips[num].comment.replace('http://comment4.news.sina.com.cn','http://comment5.news.sina.com.cn');
			}

			sina.$('commAObj').target = '_blank';
		}else{
			sina.$('commAObj').href = 'javascript:void(0)';
			sina.$('commAObj').target = '_self';
		};

		sina.$('commAObjNum').innerHTML = this.filmstrips[num].commNum;
                */
	},
	setPicButtonHeight : function(){
		sina.$(this.picArrLeftId).style.height = sina.$(this.picArrRightId).style.height = sina.$(this.picArrLeftId).parentNode.offsetHeight + 'px';
	},
	PVCount : function(type, imgurl){
                /*
		if(type=="auto"){
			if(this.PVUrl_a == null){return;};
		}else{
			if(this.PVUrl_m == null){return;};
		};
		if(!this.firstPage){ //第一次不请求PV
			this.firstPage = true;
			return;
		};
		//移除iframe
		if(this.PVFrame){
			this.PVFrame.parentNode.removeChild(this.PVFrame);
		};
		//create new iframe
		this.PVFrame = document.createElement("iframe");
		//style="height:0px;width:1px;overflow:hidden;"
		this.PVFrame.style.height = "0px";
		this.PVFrame.style.width = "1px";
		this.PVFrame.style.overflow = "hidden";
		this.PVFrame.frameBorder = 0;
		sina.$(this.mainBoxId).appendChild(this.PVFrame);
		var a=window.location.href;
		var b=Math.random();
		this.PVFrame.src = (type=="auto"?(a.indexOf('2010.')!==-1?(b<0?this.PVUrl_AC:this.PVUrl_a):this.PVUrl_a):this.PVUrl_m) + "?p=" + imgurl + "&r=" + Math.random();
            */
		//set page
		this.setPageInfo(this.selectedIndex);
	},
	setPageInfo : function(num){
		window.location.hash = "p="+Math.round(num+1);
	},
	next : function(type){
		var tempNum = this.selectedIndex + 1;
		if(tempNum >= this.filmstrips.length){
			if(this.repetition){ //循环播放
				tempNum = 0;
			}else{
				this.endSelect.open(); //选择
				return;
			};
		};
		//自动播放，判断下张图片是否载入
		if(type=="auto"){
			var testImg = new Image();
			testImg.src = this.filmstrips[tempNum].src;
			if(!testImg.complete){
				return;
			};
		};
		this.select(tempNum,type);
	},
	previous : function(){
		var tempNum = this.selectedIndex - 1;
		if(tempNum < 0){ //循环播放
			if(this.repetition){
				tempNum = this.filmstrips.length - 1
			}else{
				return;
			};
		};
		this.select(tempNum);
	},
	play : function(){
		clearInterval(this.autoPlayTimeObj);
		this.autoPlayTimeObj = setInterval("epidiascope.next('auto')",this.timeSpeed*1000);
		sina.$(this.playButtonId).onclick = function(){epidiascope.stop()};
		sina.$(this.statusId).className = "stop";
		sina.$(this.statusId).title = "暂停";
		this.autoPlay = true;
	},
	stop : function(){
		clearInterval(this.autoPlayTimeObj);
		sina.$(this.playButtonId).onclick = function(){epidiascope.play();epidiascope.next('auto');};
		sina.$(this.statusId).className = "play";
		sina.$(this.statusId).title = "播放";
		this.autoPlay = false;
	},

	rePlay : function(){ //重新播放
		if(this.endSelect.status == 1){this.endSelect.close()};
		this.autoPlay = true;
		this.select(0);
	},
	clickComment : function(){ //评论
		var thisFilmstrip = this.filmstrips[this.selectedIndex];
		if(thisFilmstrip.comment){window.open(thisFilmstrip.comment)};
	},
	downloadPic : function(){ //下载图片
		var thisFilmstrip = this.filmstrips[this.selectedIndex];

	},
	setMode : function(mode){ //切换模式
		this.speedBar.close();
		//this.commTop.close();
		if(this.endSelect.status == 1){
			this.endSelect.close();
		};
		if(mode == 'list'){
			if(!this.listInitStatus){
				this.listInit();
			};

			this.buttonSpeed.hide();
			this.buttonFullScreen.hide();
			this.buttonPlay.hide();
			this.buttonNext.hide();
			this.buttonPre.hide();
			sina.$('ecbLine').style.visibility = 'hidden';
			this.buttonMode.element.style.display = 'none';
			this.buttonModeReturn.element.style.display = 'block';
			this.buttonModeReturn.rePosi();

			this.stop();
			this.mode = 'list';

			this.listSelect(this.selectedIndex);

			sina.$('eFramePic').style.display = 'none';
			sina.$('ePicList').style.display = 'block';

			this.listView();
		}else{
			window.scroll(0,0);
			this.buttonSpeed.show();
			this.buttonFullScreen.show();
			this.buttonPlay.show();
			this.buttonNext.show();
			this.buttonPre.show();
			sina.$('ecbLine').style.visibility = 'visible';
			this.buttonMode.element.className = '';

			this.buttonMode.element.style.display = 'block';
			this.buttonModeReturn.element.style.display = 'none';

			this.mode = 'player';

			sina.$('eFramePic').style.display = 'block';
			sina.$('ePicList').style.display = 'none';

			//this.select(this.listSelectedIndex);
		};
	},
	switchMode : function(){
		if(this.mode == 'list'){
			this.setMode('player');
		}else{
			this.setMode('list');
		};
	},
	listData : null,
	listFrameId : 'ePicList',
	listSelectedIndex : null,
	listSelect : function(num){
		if(num<0 || num >= this.filmstrips.length){return};
		if(this.listSelectedIndex !== null){
			if(sina.$('picList_' + this.listSelectedIndex)){sina.$('picList_' + this.listSelectedIndex).className = 'picBox'};
		};
		this.listSelectedIndex = num;
		if(sina.$('picList_' + this.listSelectedIndex)){sina.$('picList_' + this.listSelectedIndex).className = 'picBox selected'};
	},
	listInit : function(){
		var tempThis = this;
		var html = '';
		for(var i=0;i<this.filmstrips.length;i++){
			html += '<div class="picBox" id="picList_'+ i +'" onmousemove="epidiascope.listSelect('+i+')" onclick="epidiascope.select(epidiascope.listSelectedIndex);epidiascope.setMode(\'player\');"><table cellspacing="0"><tr><td><img src="' + this.filmstrips[i].lowsrc_b + '" alt="" /></td></tr></table><h3>' + this.filmstrips[i].title + '</h3><p class="time">' + this.filmstrips[i].date + '</p></div>';
		};
		sina.$(this.listFrameId).innerHTML = html;
		this.listInitStatus = true;
	},
	listRowSize : 4, //每行个数
	listView : function(){
		var element = sina.$('picList_' + this.listSelectedIndex);

		var bodyHeight = document.documentElement.clientHeight==0?document.body.clientHeight:document.documentElement.clientHeight;
		var scrollTop = document.documentElement.scrollTop==0?document.body.scrollTop:document.documentElement.scrollTop;

		var posi = sina.absPosition(element,document.documentElement);
		if((posi.top + (element.offsetHeight * 0.3)) < scrollTop || (posi.top + (element.offsetHeight * 0.7)) > scrollTop + bodyHeight){
			window.scroll(0,posi.top - Math.round((bodyHeight - element.offsetHeight)/2));
		};
	},
	listMoveUp : function(){
		var newNum = this.listSelectedIndex - this.listRowSize;
		if(newNum<0){
			return;
		};
		this.listSelect(newNum);
		this.listView();
	},
	listMoveDown : function(){
		var newNum = this.listSelectedIndex + this.listRowSize;
		if(newNum>=this.filmstrips.length){
			nweNum = this.filmstrips.length - 1;
		};
		this.listSelect(newNum);
		this.listView();
	},
	listMoveLeft : function(){
		var newNum = this.listSelectedIndex - 1;
		if(newNum<0){
			return;
		};
		this.listSelect(newNum);
		this.listView();
	},
	listMoveRight : function(){
		var newNum = this.listSelectedIndex + 1;
		if(newNum>=this.filmstrips.length){
			return;
		};
		this.listSelect(newNum);
		this.listView();
	},
	postComm : function(content,pos){ //提交评论
		if(content == ''){
			alert('请输入评论内容！');
			return;
		};
		var connInfo = this.filmstrips[this.selectedIndex].commId;
		if(connInfo){
			connInfo = connInfo.match(/(.*?)\:(.*?)\:/);
			var Cmsg = {};
			Cmsg.channel 	= connInfo[1];
			Cmsg.newsid 	= connInfo[2];
			Cmsg.content	= content;
			cmntPost(Cmsg);
		};
		if(pos == 'bottom'){
			this.commBottom.close();
		}else{
			this.commTop.close();
		};
	}
};
epidiascope.fullScreen = {
	status : 'window',
	chk : function(){ //检查是否支持flash全屏
		var flash_i = false;
		if (navigator.plugins) {
			for (var i=0; i < navigator.plugins.length; i++) {
				if (navigator.plugins[i].name.toLowerCase().indexOf("shockwave flash") >= 0) {
					flash_i = true;
				};
			};
			if(flash_i == false){
				this.full();
			};
		};
	},
	full : function(){
		if(this.status == 'window'){
			this.status = 'fullScreen';
			document.body.className = 'statusFull';
		}else{
			this.status = 'window';
			document.body.className = '';
		};
	}
};

epidiascope.speedBar = { //速度条
	boxId : "SpeedBox", //容器id
	contId : "SpeedCont", //内容id
	slideId : "SpeedSlide", //滑区id
	slideButtonId : "SpeedNonius", //滑块id
	infoId : "ecbSpeedInfo", //信息id
	grades : 10, //等级数
	grade : 5, //等级
	_slideHeight : 112, //滑区高度
	_slideButtonHeight : 9, //滑块高度
	_baseTop : 4, //top基数
	_marginTop : 0,
	_mouseDisparity : 0,
	_showStep : 0,
	_showType : 'close',
	_showTimeObj : null,
	init : function(){
		var tempThis = this;
		this._marginTop = Math.round(this._slideHeight/this.grades * (this.grade - 1));

		sina.$(this.slideButtonId).style.top = this._marginTop + this._baseTop + "px";
		sina.$(this.infoId).innerHTML = this.grade + "秒";

		//动画效果
		this.step = new sina.Step();
		this.step.element = sina.$(this.contId);
		this.step.limit = 6;
		this.step.stepTime = 20;
		this.step.classBase = 'speedStep_';
		this.step.onfirst = function(){
			epidiascope.buttonSpeed.setStatus('ok');
			sina.$(epidiascope.speedBar.boxId).style.display = 'none';
		};

		sina.$(this.slideId).onselectstart = function(){return false};
		sina.$(this.slideButtonId).onmousedown = function(e){tempThis.mouseDown(e);return false};
		sina.$(this.slideId).onmousedown = function(e){tempThis.slideClick(e);return false};

		epidiascope.buttonSpeed.element.onmousedown = function(){tempThis.show();return false;};
		epidiascope.buttonSpeed.element.onselectstart = function(){return false};
	},
	show : function(){
		if(this._showType == 'close'){
			this.open();
		}else{
			this.close();
		};
	},
	open : function(){
		var tempThis = this;
		this._showType = 'open';
		var tempMouseDown = document.onmousedown;
		var mousedown = function(e){
			e = window.event?event:e;
			if(e.stopPropagation){ //阻止冒泡
				e.stopPropagation();
			}else{
				window.event.cancelBubble = true;
			};
			var eventObj = e.target?e.target:e.srcElement;

			while(eventObj != sina.$(tempThis.boxId) && eventObj != epidiascope.buttonSpeed.element){
				if(eventObj.parentNode){
					eventObj = eventObj.parentNode;
				}else{
					break;
				};
			};
			if(eventObj == sina.$(tempThis.boxId) || eventObj == epidiascope.buttonSpeed.element){
				return;
			}else{
				tempThis.close();
			};
			sina.delEvent(document,'mousedown',mousedown);
		};
		sina.addEvent(document,'mousedown',mousedown);

		epidiascope.buttonSpeed.setStatus('down');
		sina.$(this.boxId).style.display = 'block';

		this.step.action('+');
	},
	close : function(){
		var tempThis = this;
		this._showType = 'close';
		epidiascope.buttonSpeed.setStatus('ok');
		this.step.action('-');
	},
	slideClick : function(e){
		e = window.event?event:e;
		var Y = e.layerY?e.layerY:e.offsetY;
		if(!Y){return};

		this._marginTop = Y - Math.round(this._slideButtonHeight/2);
		if(this._marginTop<0){this._marginTop=0};
		this.grade = Math.round(this._marginTop/(this._slideHeight/this.grades) + 1);
		sina.$(this.slideButtonId).style.top = this._marginTop + this._baseTop + "px";
		sina.$(this.infoId).innerHTML = this.grade + "秒";

		if(this.onend){this.onend()};
	},
	setGrade : function(num){
		this.grade = num;
		epidiascope.timeSpeed = this.grade;
		this._marginTop = Math.round(this._slideHeight/this.grades * (this.grade - 1));

		sina.$(this.slideButtonId).style.top = this._marginTop + this._baseTop + "px";
		sina.$(this.infoId).innerHTML = this.grade + "秒";
		sina.writeCookie("eSp",this.grade,720);
	},
	mouseDown : function(e){
		var tempThis = this;
		e = window.event?window.event:e;
		this._mouseDisparity = (e.pageY?e.pageY:e.clientY) - this._marginTop;
		document.onmousemove = function(e){tempThis.mouseOver(e)};
		document.onmouseup = function(){tempThis.mouseEnd()};
	},
	mouseOver : function(e){
		e = window.event?window.event:e;
		this._marginTop = (e.pageY?e.pageY:e.clientY) - this._mouseDisparity;
		if(this._marginTop > (this._slideHeight - this._slideButtonHeight)){this._marginTop = this._slideHeight - this._slideButtonHeight};
		if(this._marginTop < 0){this._marginTop = 0;};
		sina.$(this.slideButtonId).style.top = this._marginTop + this._baseTop + "px";

		this.grade = Math.round(this._marginTop/(this._slideHeight/this.grades) + 1);

		if(this.onmover){this.onmover()};
	},
	mouseEnd : function(){
		if(this.onend){this.onend()};

		document.onmousemove = null;
		document.onmouseup = null;
	},
	onmover : function(){
		sina.$(this.infoId).innerHTML = this.grade + "秒";
	},
	onend : function(){
		sina.writeCookie("eSp",this.grade,720);
		epidiascope.timeSpeed = this.grade;
		if(epidiascope.autoPlay){epidiascope.play()};
	}
};
/*
epidiascope.commTop = {
	_showType : 'close',
	boxId : 'CommFormTopBox',
	contId : 'CommFormTopCont',
	playStatus : null,
	init : function(){
		var tempThis = this;

		//动画效果
		this.step = new sina.Step();
		this.step.element = sina.$(this.contId);
		this.step.limit = 6;
		this.step.stepTime = 20;
		this.step.classBase = 'commTopStep_';
		this.step.onfirst = function(){
			epidiascope.buttonCommTop.setStatus('ok');
			sina.$(epidiascope.commTop.boxId).style.display = 'none';
		};

		//关闭
		sina.$('cftClose').onclick = function(){
			epidiascope.commTop.close();
		};

		epidiascope.buttonCommTop.element.onmousedown = function(){tempThis.show();return false;};
		epidiascope.buttonCommTop.element.onselectstart = function(){return false};
	},
	show : function(){
		if(this._showType == 'close'){
			this.open();
		}else{
			this.close();
		};
	},
	open : function(){
		this.playStatus = epidiascope.autoPlay;
		epidiascope.stop();

		var tempThis = this;
		this._showType = 'open';
		var mousedown = function(e){
			e = window.event?event:e;
			if(e.stopPropagation){ //阻止冒泡
				e.stopPropagation();
			}else{
				window.event.cancelBubble = true;
			};
			var eventObj = e.target?e.target:e.srcElement;

			while(eventObj != sina.$(tempThis.boxId) && eventObj != epidiascope.buttonCommTop.element){
				if(eventObj.parentNode){
					eventObj = eventObj.parentNode;
				}else{
					break;
				};
			};
			if(eventObj == sina.$(tempThis.boxId) || eventObj == epidiascope.buttonCommTop.element){
				return;
			}else{
				tempThis.close();
			};
			sina.delEvent(document,'mousedown',mousedown);
		};

		sina.addEvent(document,'mousedown',mousedown);

		epidiascope.buttonCommTop.setStatus('down');
		sina.$(this.boxId).style.display = 'block';

		this.step.action('+');
	},
	close : function(){
		epidiascope.autoPlay = this.playStatus;
		if(epidiascope.autoPlay){epidiascope.play()};

		var tempThis = this;
		this._showType = 'close';
		epidiascope.buttonCommTop.setStatus('ok');
		this.step.action('-');
	}
};

epidiascope.commBottom = {
	_showType : 'close',
	boxId : 'CommFormBottomBox',
	contId : 'CommFormBottomCont',
	playStatus : null,
	init : function(){
		var tempThis = this;

		//动画效果
		this.step = new sina.Step();
		this.step.element = sina.$(this.contId);
		this.step.limit = 6;
		this.step.stepTime = 20;
		this.step.classBase = 'commBottomStep_';
		this.step.onfirst = function(){
			sina.$(epidiascope.commBottom.boxId).style.display = 'none';
		};

		//关闭
		sina.$('cfbClose').onclick = function(){
			epidiascope.commBottom.close();
		};

		sina.$('buttonCommBottom').onmousedown = function(){tempThis.show();return false;};
	},
	show : function(){
		if(this._showType == 'close'){
			this.open();
		}else{
			this.close();
		};
	},
	open : function(){
		this.playStatus = epidiascope.autoPlay;
		epidiascope.stop();
		var tempThis = this;
		this._showType = 'open';
		var mousedown = function(e){
			e = window.event?event:e;
			if(e.stopPropagation){ //阻止冒泡
				e.stopPropagation();
			}else{
				window.event.cancelBubble = true;
			};
			var eventObj = e.target?e.target:e.srcElement;

			while(eventObj != sina.$(tempThis.boxId) && eventObj != sina.$('buttonCommBottom')){
				if(eventObj.parentNode){
					eventObj = eventObj.parentNode;
				}else{
					break;
				};
			};
			if(eventObj == sina.$(tempThis.boxId) || eventObj == sina.$('buttonCommBottom')){
				return;
			}else{
				tempThis.close();
			};
			sina.delEvent(document,'mousedown',mousedown);
		};

		sina.addEvent(document,'mousedown',mousedown);

		sina.$(this.boxId).style.display = 'block';

		this.step.action('+');
	},
	close : function(){
		epidiascope.autoPlay = this.playStatus;
		if(epidiascope.autoPlay){epidiascope.play()};

		var tempThis = this;
		this._showType = 'close';
		this.step.action('-');
	}
};
*/


epidiascope.picList = { //列表滚动
	leftArrId : "efpListLeftArr",
	rightArrId : "efpListRightArr",
	picListId : "efpPicListCont",
	timeoutObj : null,
	pageWidth : 110,
	totalWidth : 0,
	offsetWidth : 0,
	lock : false,
	init : function(){
		sina.$(this.rightArrId).onmousedown = function(){epidiascope.picList.leftMouseDown()};
		sina.$(this.rightArrId).onmouseout = function(){epidiascope.picList.leftEnd("out");this.className='';};
		sina.$(this.rightArrId).onmouseup = function(){epidiascope.picList.leftEnd("up")};
		sina.$(this.leftArrId).onmousedown = function(){epidiascope.picList.rightMouseDown()};
		sina.$(this.leftArrId).onmouseout = function(){epidiascope.picList.rightEnd("out");this.className='';};
		sina.$(this.leftArrId).onmouseup = function(){epidiascope.picList.rightEnd("up")};
		this.totalWidth = epidiascope.filmstrips.length * this.pageWidth;
		this.offsetWidth = sina.$(this.picListId).offsetWidth;

	},
	leftMouseDown : function(){
		if(this.lock){return};
		this.lock = true;
		this.timeoutObj = setInterval("epidiascope.picList.moveLeft()",10);
	},
	rightMouseDown : function(){
		if(this.lock){return};
		this.lock = true;
		this.timeoutObj = setInterval("epidiascope.picList.moveRight()",10);
	},
	moveLeft : function(){
		if(sina.$(this.picListId).scrollLeft + 10 > this.totalWidth - this.offsetWidth){
			sina.$(this.picListId).scrollLeft = this.totalWidth - this.offsetWidth;
			this.leftEnd();
		}else{
			sina.$(this.picListId).scrollLeft += 10;
		};
	},
	moveRight : function(){
		sina.$(this.picListId).scrollLeft -= 10;
		if(sina.$(this.picListId).scrollLeft == 0){this.rightEnd()};
	},
	leftEnd : function(type){
		if(type=="out"){if(!this.lock){return}};
		clearInterval(this.timeoutObj);
		this.lock = false;
		this.move(30);
	},
	rightEnd : function(type){
		if(type=="out"){if(!this.lock){return}};
		clearInterval(this.timeoutObj);
		this.lock = false;
		this.move(-30);
	},
	foucsTo : function(num){
		if(this.lock){return;};
		this.lock = true;

		var _moveWidth = Math.round(num * this.pageWidth - this.offsetWidth / 2) - 33;

		_moveWidth -= sina.$(this.picListId).scrollLeft;

		if(sina.$(this.picListId).scrollLeft + _moveWidth < 0){
			_moveWidth = - sina.$(this.picListId).scrollLeft;
		};
		if(sina.$(this.picListId).scrollLeft + _moveWidth >= this.totalWidth - this.offsetWidth){
			_moveWidth = this.totalWidth - this.offsetWidth - sina.$(this.picListId).scrollLeft;
		};

		this.move(_moveWidth);
	},
	move : function(num){
		var thisMove = num/4;
		if(Math.abs(thisMove)<1 && thisMove!=0){
			thisMove = (thisMove>=0?1:-1)*1;
		}else{
			thisMove = Math.round(thisMove);
		};

		var temp = sina.$(this.picListId).scrollLeft + thisMove;
		if(temp <= 0){sina.$(this.picListId).scrollLeft = 0;this.lock = false;return;}
		if(temp >= this.totalWidth - this.offsetWidth){sina.$(this.picListId).scrollLeft = this.totalWidth - this.offsetWidth;this.lock = false;return;}
		sina.$(this.picListId).scrollLeft += thisMove;
		num -= thisMove;
		if(Math.abs(num) <= 1){this.lock = false;return;}else{
			setTimeout("epidiascope.picList.move(" + num + ")",10)
		}
	}
};
//键盘控制
epidiascope.keyboard = {
	_timeObj : null,
	status : 'up',
	init : function(){
		var tempThis = this;
		sina.addEvent(document,'keydown',function(e){tempThis.keyDown(e)});
		sina.addEvent(document,'keyup',function(e){tempThis.keyUp(e)});

		this.step = new sina.Step();
		this.step.element = sina.$('efpClew');
		this.step.limit = 5;
		this.step.stepTime = 30;
		this.step.classBase = 'efpClewStep_';

		if(!this.closeObj){
			this.closeObj = document.createElement('span');
			this.closeObj.style.display = 'block';
			this.closeObj.id = 'efpClewClose';
			sina.$('efpClew').appendChild(this.closeObj);

			this.closeObj.onclick = function(){tempThis.clewClose()};
		};

		//提示次数
		this.clewNum = parseInt(sina.readCookie('eCn'));
		if(isNaN(this.clewNum)){this.clewNum = 0};
		if(this.clewNum<5){
			//this.clewNum ++;
			//sina.writeCookie('eCn',this.clewNum,24*7);
			this.clewOpen();
		};

	},
	clewClose : function(){
		this.step.action('-');
		sina.writeCookie('eCn',6,24*7);
	},
	clewOpen : function(){
		this.step.action('+');
	},
	keyDown : function(e){
		if(this.status == 'down'){return};
		this.status = 'down';
		e = window.event?event:e;
		var obj = e.target?e.target:e.srcElement;
		if(obj.tagName == 'INPUT' || obj.tagName == 'SELECT' || obj.tagName == 'TEXTAREA'){
			if(e.stopPropagation){ //阻止冒泡
				e.stopPropagation();
			}else{
				window.event.cancelBubble = true;
			};
			return;
		};

		var stopKey = false; //是否阻止按键
		if(epidiascope.mode == 'list'){ //列表模式
			if(e.keyCode == 40){
				epidiascope.listMoveDown();
				stopKey = true;
			};
			if(e.keyCode == 37){
				epidiascope.listMoveLeft();
				stopKey = true;
			};
			if(e.keyCode == 38){
				epidiascope.listMoveUp();
				stopKey = true;
			};
			if(e.keyCode == 39){
				epidiascope.listMoveRight();
				stopKey = true;
			};
			if(e.keyCode == 13){
				epidiascope.setMode('player');
				epidiascope.select(epidiascope.listSelectedIndex);
				stopKey = true;
			};

		}else{ //默认模式
			if(e.keyCode == 39){
				epidiascope.next();
				stopKey = true;
				this.clewClose();
			};
			if(e.keyCode == 37){
				epidiascope.previous();
				stopKey = true;
				this.clewClose();
			};
		};

		if(e.keyCode == 9){
			epidiascope.switchMode();
			stopKey = true;
		};

		if(stopKey === true){
			if(e.preventDefault){
				e.preventDefault();
			}else{
				e.returnValue=false;
			};
		};
	},
	keyUp : function(){
		this.status = 'up';
	}
};

//结束选择
epidiascope.endSelect = {
	endSelectId : "endSelect",
	closeId : "endSelClose",
	rePlayButId : "rePlayBut",
	status : 0, //1:open  0:close
	open : function(){
		this.status = 1;
			sina.$(this.endSelectId).style.display = "block";
			//临时修正ie6卡死问题，added by zy
			var isIE6= /msie 6/i.test(navigator.userAgent);
			if(isIE6){
				var imgs = sina.$(this.endSelectId).getElementsByTagName('img');
				for(var i=0,iL=imgs.length; i<iL; i++){
					if(imgs[i].style.width != '') break;
					var cWidth = imgs[i].clientWidth,
						cHeight = imgs[i].clientHeight - 15,
						scaleYX = cHeight / cWidth,
						scaleBln = scaleYX < 0.75 ? true : false,
						rWidth, rHeight;
					rWidth = scaleBln ? cWidth > 120 ? 120 : undefined : cHeight > 90 ? Math.round(90 / scaleYX) : undefined;
					rHeight = scaleBln ? cWidth > 120 ? Math.round(scaleYX * 120) : undefined : cHeight > 90 ? 90 : undefined;
					if(!rWidth) continue;
					imgs[i].style.width = rWidth + 'px';
					imgs[i].style.height = rHeight + 'px';
				}
			}
			//added over
			sina.$(this.endSelectId).style.left = Math.round((sina.$(epidiascope.mainBoxId).offsetWidth - sina.$(this.endSelectId).offsetWidth)/2) + "px";
			sina.$(this.endSelectId).style.top = Math.round((sina.$(epidiascope.mainBoxId).offsetHeight - sina.$(this.endSelectId).offsetHeight)/2) + "px";

		epidiascope.stop();
		sina.$(epidiascope.playButtonId).onclick = function(){epidiascope.rePlay()};
		sina.$(this.closeId).onclick = function(){epidiascope.endSelect.close()};
		sina.$(this.rePlayButId).onclick = function(){epidiascope.rePlay()};
	},
	close : function(){
		this.status = 0;
		//sina.$(epidiascope.playButtonId).onclick = function(){epidiascope.play()};
		sina.$(this.endSelectId).style.display = "none";
	}
};
epidiascope.onstart = function(){
	try{document.execCommand('BackgroundImageCache', false, true);}catch(e){};

	//速度条
	epidiascope.speedBar.grade = parseInt(sina.readCookie("eSp"));
	if(isNaN(epidiascope.speedBar.grade)){epidiascope.speedBar.grade = 5};
	epidiascope.speedBar.init();
	epidiascope.speedBar.onend();

	//顶部评论
	//epidiascope.commTop.init();

	//底部评论
	//epidiascope.commBottom.init();

	//图片列表滚动
	epidiascope.picList.init();

	//按键控制
	epidiascope.keyboard.init();
};
//按钮构造函数
epidiascope.Button = function(id,classNameNum){
	this.status = 'ok';
	this.id = id;
	this.classNameNum = classNameNum;
	this.init();
};
epidiascope.Button.prototype.init = function(){
	if(!sina.$(this.id)){return};
	var tempThis = this;
	this.element = sina.$(this.id);

	if(this.element.offsetWidth == 43){
		this.classNameNum = '1';
	};
	if(!this.classNameNum){
		this.classNameNum = '';
	};
	this.mouseStatus = 'out';

	this.bgDiv = document.createElement('div');
	this.bgDiv.className = 'buttonBg' + this.classNameNum;
	this.element.parentNode.style.position = 'relative';
	this.element.style.position = 'relative';
	this.element.style.zIndex = '5';
	this.element.parentNode.appendChild(this.bgDiv);
	this.bgDiv.style.top = this.element.offsetTop - 6 + 'px';
	this.bgDiv.style.left = this.element.offsetLeft - 6 + 'px';


	//动画效果
	this.step = new sina.Step();
	this.step.element = this.bgDiv;
	this.step.limit = 3;
	this.step.stepTime = 30;
	this.step.classBase = 'buttonBg' + this.classNameNum + ' bBg' + this.classNameNum + 'S_';

	sina.addEvent(this.element,'mouseover',function(){tempThis.mouseover()});
	sina.addEvent(this.element,'mouseout',function(){tempThis.mouseout()});
	sina.addEvent(this.element,'mousedown',function(){tempThis.mousedown()});
	sina.addEvent(this.element,'mouseup',function(){tempThis.mouseup()});
};
epidiascope.Button.prototype.rePosi = function(){
	this.bgDiv.style.top = this.element.offsetTop - 6 + 'px';
	this.bgDiv.style.left = this.element.offsetLeft - 6 + 'px';
};
epidiascope.Button.prototype.mouseover = function(){
	this.mouseStatus = 'in';
	if(this.status != 'down'){
		this.element.className = "hover";
		this.step.action('+');
	};
};
epidiascope.Button.prototype.mouseout = function(){
	this.mouseStatus = 'out';
	if(this.status != 'down'){
		this.element.className = "";
		this.step.action('-');
	};
};
epidiascope.Button.prototype.mouseup = function(){
	if(this.status == 'down'){return;}
	this.element.className = "hover";
};
epidiascope.Button.prototype.mousedown = function(){
	if(this.status == 'down'){return;}
	this.element.className = "active";
};
epidiascope.Button.prototype.setStatus = function(status){
	switch(status){
		case 'ok':
			this.status = 'ok';
			this.element.className = "";
			if(this.mouseStatus == 'in'){
				this.step.action('+');
			}else{
				this.step.action('-');
			};
			break;
		case 'down':
			this.status = 'down';
			this.step.action('-');
			this.element.className = "active";
			break;
	};
};
epidiascope.Button.prototype.hide = function(){
	this.element.style.visibility = 'hidden';
	this.bgDiv.style.visibility = 'hidden';
};
epidiascope.Button.prototype.show = function(){
	this.element.style.visibility = 'visible';

	this.bgDiv.style.visibility = 'visible';
};
epidiascope.iPad = {
	x : 0,
	y : 0,
	lastX : 0,
	lastY : 0,
	status : 'ok',
	init : function(){
		if(!(/\((iPhone|iPad|iPod)/i).test(navigator.userAgent)){ //不支持触屏
			return;
		};
		sina.addEvent(window,'load',function(){setTimeout('window.scrollTo(0,78)'),500});

		sina.$('efpClew').style.backgroundImage = 'url(http://www.sinaimg.cn/dy/deco/2010/0513/e_ipad_m_02.png)';
		var tempThis = this;
		sina.addEvent(sina.$('efpBigPic'),'touchstart',function(e){tempThis._touchstart(e)});
		sina.addEvent(sina.$('efpBigPic'),'touchmove',function(e){tempThis._touchmove(e)});
		sina.addEvent(sina.$('efpBigPic'),'touchend',function(e){tempThis._touchend(e)});
	},
	_touchstart : function(e){

		this.x = e.touches[0].pageX;
		this.scrollX = window.pageXOffset;
		this.scrollY = window.pageYOffset; //用于判断页面是否滚动
	},
	_touchmove : function(e){
		if(e.touches.length > 1){ //多点触摸
			this.status = 'ok';
			return;
		};
		this.lastX = e.touches[0].pageX;
		var cX = this.x - this.lastX;

		if(cX<0){//第一页禁止向左
			if(epidiascope.selectedIndex == 0){
				return;
			};
		};

		if(this.status == 'ok'){
			if(this.scrollY == window.pageYOffset && this.scrollX == window.pageXOffset && Math.abs(cX)>50){ //横向触摸
				if(cX>0){//最后一页禁止向右
					if(epidiascope.selectedIndex == epidiascope.filmstrips.length - 1){
						if(epidiascope.endSelect.status == 0){
							epidiascope.endSelect.open();
						};
						return;
					};
				};

				this.status = 'touch';
				sina.$('efpBigPic').style.textAlign = 'left';
			}else{
				return;
			};
		};

		epidiascope.ImgObj1.style.marginLeft = - cX + Math.round((950 - epidiascope.ImgObj1.offsetWidth)/2) + 'px';
		e.preventDefault();
	},
	_touchend : function(e){
		if(this.status != 'touch'){return};
		this.status = 'ok';
		var cX = this.x - this.lastX;

		sina.extend.actPX(epidiascope.ImgObj1.style,'marginLeft',epidiascope.ImgObj1.offsetLeft,cX>0?-951:951,200,function(){
			epidiascope.ImgObj1.style.marginLeft = 0;
			sina.$('efpBigPic').style.textAlign = 'center';
			epidiascope.ImgObj1.style.paddingLeft = 0;
			if(cX<0){
				epidiascope.previous();
			}else{
				epidiascope.next();
			};
			//epidiascope.keyboard.clewClose();
		});
	}

}
// -------------------------------------------------------------------------------------

function DrawImage(ImgD,iwidth,iheight){
	var image=new Image();
	if(!iwidth)iwidth = 90;
	if(!iheight)iheight = 90; //定义允许高度，当宽度大于这个值时等比例缩小
	image.src=ImgD.src;
	if(image.width>0 && image.height>0){
		var flag=true;
		if(image.width/image.height>= iwidth/iheight){
			if(image.width>iwidth){
				ImgD.width=iwidth;
				ImgD.height=(image.height*iwidth)/image.width;
			}else{
				ImgD.width=image.width;
				ImgD.height=image.height;
			}
		}else{
			if(image.height>iheight){
				ImgD.height=iheight;
				ImgD.width=(image.width*iheight)/image.height;
			}else{
				ImgD.width=image.width;
				ImgD.height=image.height;
			}
		}
	}
};

//模拟Select mengjia 2008.12.30
function DivSelect(O,l,I){var C=this;C.id=O;C.divId=l;C.divClassName=I;C.selectObj=sina.$(C.id);if(!C.selectObj){return};var o=C;C.status="close";C.parentObj=C.selectObj.parentNode;while(sina.readStyle(C.parentObj,"display")!="block"){if(C.parentObj.parentNode){C.parentObj=C.parentObj.parentNode}else{break}};C.parentObj.style.position="relative";C.selectObjWidth=C.selectObj.offsetWidth;C.selectObjHeight=C.selectObj.offsetHeight;C.selectPosition=sina.absPosition(C.selectObj,C.parentObj);C.selectObj.style.visibility="hidden";C.divObj=document.createElement("div");C.divObj.id=C.divId;if(C.divClassName){C.divObj.className=C.divClassName};C.parentObj.appendChild(C.divObj);C.divObj.style.width=C.selectObjWidth+"px";C.divObj.style.position="absolute";C.divObj.style.left=C.selectPosition.left+"px";C.divObj.style.top=C.selectPosition.top+"px";C.divObj.onclick=function(){o.click()};C.divObj_count=document.createElement("div");C.divObj.appendChild(C.divObj_count);C.divObj_count.className="ds_cont";C.divObj_title=document.createElement("div");C.divObj_count.appendChild(C.divObj_title);C.divObj_title.className="ds_title";C.divObj_button=document.createElement("div");C.divObj_count.appendChild(C.divObj_button);C.divObj_button.className="ds_button";C.divObj_list=document.createElement("div");C.divObj.appendChild(C.divObj_list);C.divObj_list.className="ds_list";C.divObj_list.style.display="none";C.divObj_listCont=document.createElement("div");C.divObj_list.appendChild(C.divObj_listCont);C.divObj_listCont.className="dsl_cont";C.list=[];var i;for(var c=0;c<C.selectObj.options.length;c++){i=document.createElement("p");C.list.push(i);C.divObj_listCont.appendChild(i);i.innerHTML=C.selectObj.options[c].innerHTML;if(C.selectObj.selectedIndex==c){C.divObj_title.innerHTML=i.innerHTML};i.onmouseover=function(){this.className="selected"};i.onmouseout=function(){this.className=""};i.onclick=function(){o.select(this.innerHTML)}};C.select=function(i){var l=this;for(var I=0;I<l.selectObj.options.length;I++){if(l.selectObj.options[I].innerHTML==i){l.selectObj.selectedIndex=I;if(l.selectObj.onchange){l.selectObj.onchange()};l.divObj_title.innerHTML=i;break}}};C.clickClose=function(I){var i=I.target?I.target:event.srcElement;do{if(i==o.divObj){return};if(i.tagName=="BODY"){break};i=i.parentNode}while(i.parentNode);o.close()};C.open=function(){var i=this;i.divObj_list.style.display="block";i.status="open";sina.addEvent(document,"click",i.clickClose)};C.close=function(){var i=this;i.divObj_list.style.display="none";i.status="close";sina.delEvent(document,"click",i.clickClose)};C.click=function(){var i=this;if(i.status=="open"){i.close()}else{i.open()}}};
