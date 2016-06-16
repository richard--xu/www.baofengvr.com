/**
 * @author YF
 */

$(function(){
	var init = function(){
		sub();
	};

	var enterKey = 13;

	var sub = function(){
		// 点击enter按钮，提交表单
		$('input').on('keypress', function (e) {
			if (e.keyCode === enterKey) {
				$('.login_botton').trigger('click');
			}
		});

		// 提交表单
		$('.login_botton').on('click',function (e) {
			e.preventDefault();
			param = $("form").serialize();
			$.post('/admin/login/login',param,function(res){
	   			if (res.status == 1) {
	   				window.location.href='/admin/login/home';
	   			} else {
	   				$(".login-error-wrap").text(res.data);
	   			}
	   		},'json');
		});
	}
	
	$(init);
});


