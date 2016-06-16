$(function(){
	var init=function(){
		togle();
		getHtml();
		ct();
	}
	var ct = function() {
		document.title = "易班中职 - 第十二届全国中等职业学校“文明风采”竞赛上海地区复赛";

		$('#logo, .footer-logo').on('click', function() {
			window.location.href = 'http://zz.yiban.cn/';
		})
	};

	var getHtml=function(){
		var obj=$('div[nt="getString"]');
		var obj2=$('p[nt="insetHtml"]');
		for (var i = 0; i < obj.length; i++) {
			var a=$('div[nt="getString"]').eq(i).html().substring(0,300);
			$('p[nt="insetHtml"]').eq(i).html(a);
		};	
	}
	var togle=function(){
		var obj=$('.com-ico');
		for (var i = 0; i < obj.length; i++) {
			if (obj.eq(i).hasClass('close-ico')){
				obj.eq(i).parent().find('p[nt="insetHtml"]').hide();
			}
		};
		for (var i = 0; i < obj.length; i++) {
			obj.eq(i).on('click',function(){
				if ($(this).hasClass('close-ico')) {
					var oldHeight=$(this).parent().find('.itCon').css('height');
					$(this).parent().find('.itCon').animate({height:80},100,'swing',function(){
						// $(this).find(".article-p").addClass('unqi-p');
						$(this).parent().find('a.close-ico').removeClass('close-ico').addClass('open-ico');
						$(this).parent().removeClass('border-color');
						$(this).parent().find('.lineDot').removeClass('lineDot-a');
						$(this).css('backgroundColor','#f4f9f8');
						$(this).parent().find('p[nt="insetHtml"]').show();
					});
				}else if ($(this).hasClass('open-ico')){ //打开
					for (var i = 0; i < obj.length; i++) {
						obj.eq(i).parent().find('p[nt="insetHtml"]').show();
						if (obj.eq(i).hasClass('close-ico')) {
							// obj.eq(i).parent().find(".article-p").addClass('unqi-p');
							obj.eq(i).parent().find('a.close-ico').removeClass('close-ico').addClass('open-ico');
							obj.eq(i).parent().removeClass('border-color');
							obj.eq(i).parent().find('.lineDot').removeClass('lineDot-a');
							obj.eq(i).parent().find('.itCon').css('backgroundColor','#f4f9f8');
							obj.eq(i).parent().find('.itCon').animate({height:80},100,'swing',function(){
								obj.eq(i).parent().find('p[nt="insetHtml"]').show();
							});
						}
					}
					$(this).parent().find('.itCon').css('height','auto');
					var hh=$(this).parent().find('.itCon').css('height')+60;
					$(this).parent().find('.itCon').css('backgroundColor','#f1f1f1');
					$(this).parent().find('.itCon').animate({'height':hh},30,'swing',function(){
						// $(this).find(".article-p").removeClass('unqi-p');
						$(this).parent().find('a.open-ico').removeClass('open-ico').addClass('close-ico');
						$(this).parent().addClass('border-color');
						$(this).parent().find('.lineDot').addClass('lineDot-a');
						// $(this).css('backgroundColor','#f1f1f1');
						$(this).parent().addClass('opened');
						$(this).parent().find('p[nt="insetHtml"]').hide();
					});
				}
			});
		}
	}
	$(init);
})