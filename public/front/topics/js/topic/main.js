$(function(){
	var init=function(){
		showEnter();
		shareTo();
		onlineReport();
		ct();
	}
	var ct = function() {
		document.title = "易班中职 - 第十二届全国中等职业学校“文明风采”竞赛上海地区复赛";

		$('#logo, .footer-logo').on('click', function() {
			window.location.href = 'http://zz.yiban.cn/';
		})
	};
	//分享
	var shareTo=function(){
		window._bd_share_config={
			"common":{
				"bdSnsKey":{},
				"bdText":"",
				"bdMini":"2",
				"bdPic":"",
				"bdStyle":"0",
				"bdSize":"16"
			},
			"share":{},
			"selectShare":{"bdContainerClass":null,"bdSelectMiniList":["qzone","tsina","tqq","renren","weixin"]}
			};with(document)0[(getElementsByTagName('head')[0]||body).appendChild(createElement('script')).src='http://bdimg.share.baidu.com/static/api/js/share.js?v=89860593.js?cdnversion='+~(-new Date()/36e5)];
	};
	//在线报名列表
	var onlineReport=function(){
		var timer=null;
		$('a[nt="online-sign"],.online-report').on('mouseover', function(){
			clearInterval(timer);
			$('.online-report').show();
			var objWidth=$('.online-report li').width()-40;
			if (objWidth<72) {
				objWidth=72;
				$('.online-report').css('width',objWidth+40);
			}
			$('a.setWidth').css('width',objWidth)

		});
		$('a[nt="online-sign"],.online-report').on('mouseout', function(){
			timer=setTimeout(function(){
				$('.online-report').hide();
			},200);
		});
	};
	//显示后台入口
	var showEnter=function(){
		$('#acount-setting,.tooltip').on('mouseover',function(){
			$('.tooltip').show();
		});
		$('#acount-setting,.tooltip').on('mouseout',function(){
			$('.tooltip').hide();
		});
	};

	$(init);
})
