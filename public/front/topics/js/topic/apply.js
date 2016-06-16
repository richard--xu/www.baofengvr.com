$(function(){
	var init=function(){
		checkForm();
		addItem();
		loadFile();
		awardList();
		checkInf();
		ct();
	}
	var ct = function() {
		document.title = "易班中职 - 第十二届全国中等职业学校“文明风采”竞赛上海地区复赛";

		$('#logo, .footer-logo').on('click', function() {
			window.location.href = 'http://zz.yiban.cn/';
		})
	};
	var hideA=$('.tips-hide');
	for (var i = 0; i < hideA.length; i++) {
		hideA.eq(i).on('click',function(){
			$(this).hide();
			$(this).next().focus();
		})
	}
	var fInput=$('.fp_input');
	for (var i = 0; i < fInput.length; i++) {
		if (fInput.eq(i).val()=='') {
			fInput.eq(i).prev().show();
		}else{
			fInput.eq(i).prev().hide();
		}
		fInput.eq(i).on('focus',function() {
			$(this).prev().hide();
		});
	}
	for (var i = 0; i < fInput.length; i++) {
		fInput.eq(i).on('blur',function() {
			if ($(this).val()=='') {
				$(this).prev().show();
			}else{
				$(this).prev().hide();
			}
		});
	}
	function checkForm(){
		var obj=$('input');
		var objArr = {},msg={};
		for (var i = 0; i < obj.length; i++) {
			if (parseInt(obj.eq(i).attr('must')) == 1){
				var thisName=obj.eq(i).attr('name');
				if (thisName=='opusName') {
					objArr[thisName]={
						required:true,
						maxlength:40
					}
				}else if (thisName=='tutorName') {
					objArr[thisName]={
						required:true,
						maxlength:10
					}
				}else if (thisName=='tutorPhone') {
					objArr[thisName]={
						required:true,
						digits:true,
						minlength:11,
						maxlength:11
					}
				}else if (thisName=='name[]') {
					objArr[thisName]={
						required:true,
						maxlength:15
					}
				}else if (thisName=='schoolCardId[]') {
					objArr[thisName]={
						required:true,
						minlength:18,
						maxlength:18
					}
				}
				else{
					objArr[thisName] = 'required';
				}
				
			}
		};
		for (var i = 0; i < $('textarea').length; i++) {
			if (parseInt($('textarea').eq(i).attr('must')) == 1){
				objArr[$('textarea').eq(i).attr('name')]={
					required:true,
					maxlength:500
				}
			}
		};
		for (var i = 0; i < obj.length; i++) {
			if (parseInt(obj.eq(i).attr('must')) == 1){
				var comName=obj.eq(i).attr('name');
				if(comName=="opusName"){
					msg[comName]={
						required:'请输入作品名称',
						maxlength:'作品名称不能超过40个字'
					}
				}else if(comName=='tutorName'){
					msg[comName]={
						required:'请输入指导教师',
						maxlength:'指导教师不能超过10个字'
					}
				}else if(comName=='tutorPhone'){
					msg[comName]={
						required:'请输入指导教师手机',
						digits:'手机号码应该是11位数字',
						minlength:'手机号码应该是11位数字',
						maxlength:'手机号码应该是11位数字'
					}
				}else if(comName=='name[]'){
					msg[comName]={
						required:'请输入学生姓名',
						maxlength:'学生姓名不能超过15个字'
					}
				}else if(comName=='schoolCardId[]'){
					msg[comName]={
						required:'请输入学籍卡号',
						minlength:'学籍卡号是18位',
						maxlength:'学籍卡号是18位'
					}
				}
				else if(comName ==comName){
					msg[comName]=comName+'不能为空';
				}else{
					msg[comName]=obj.eq(i).prev('.tips-hide').html();
				}
			}
		};
		for (var i = 0; i < $('textarea').length; i++) {
			if (parseInt($('textarea').eq(i).attr('must')) == 1){
				if ($('textarea').eq(i).attr('name')) {
					msg[$('textarea').eq(i).attr('name')]={
						required:$('textarea').eq(i).attr('name')+'不能为空',
						maxlength:"长度不能超过500"
					}
				}
			}
		};

		/*form验证*/
		$("#register").validate({
	    	ignore: "",
	    	focusInvalid : false,
	    	errorPlacement : function(error,element){
	    		if(element.attr('name') == 'name[]' || element.attr('name') == 'schoolCardId[]'){
	    			error.insertAfter('[name="schoolCardId[]"]');
	    		}else {
	    			error.insertAfter(element);
	    		}
	    	},
	    	groups : {
	    		time : 'name[] schoolCardId[]'
	    	},
	    	rules: objArr,
	        messages: msg,
	        submitHandler : function(form){
	        	var obja=$('.fp_input_s'),objb=$('.fp_input_b');
	        	var re=/^.{18}$/;
	        	for (var i = 0; i < obja.length; i++) {
	        		if (obja.eq(i).val()=='') {
	        			obja.eq(i).parent().find('.error').show();
						obja.eq(i).parent().find('.error').html('请输入学生姓名');
						return;
					}else{
						obja.eq(i).parent().find('.error').hide();
						obja.eq(i).parent().find('.error').html('');
					}
	        	};
	        	for (var i = 0; i < objb.length; i++) {
	        		if (objb.eq(i).val()=='') {
	        			obja.eq(i).parent().find('.error').show();
						objb.eq(i).parent().find('.error').html('请输入学籍卡号');
						return;
					}else if(!re.test($.trim(objb.eq(i).val()))){
						obja.eq(i).parent().find('.error').show();
						objb.eq(i).parent().find('.error').html('学籍卡号是18位');
						return;
					}else{
						objb.eq(i).parent().find('.error').html('');
					}
	        	};
	        	
	        	$('.button-save').prop('disabled', true);
	        	
	        	$.ajax({
	                type: "POST",
	                dataType: "json",
	                url: $('#register').attr('action'),
	                data: $('#register').serialize(),
	                async: false
	            }).done(function (data) {
	            	if(data.status){
	            		form.reset();
	            		var id=data.data.id,num=data.data.num;
	            		if(id){
	            			window.location.href = '/sites/topics/topic/applysuccess?id='+id+'&num='+num;
	            		}else{
	            			Dialog.alert({
			                    msg : data.data.msg
			                });
	            		}
	            	} else {
	            		Dialog.alert({
		                    msg : data.data
		                });
	            	}
	            	$('.button-save').prop('disabled', false);
	            }).fail(function () {
	            	$('.button-save').prop('disabled', false);
	                // alert("error:" + data.responseText);
	            });
	        }
	    });
	}

	var addItem=function(){
		$('#register').on('click','.add-ico',function(){
			$('.insert-index').find('.many-input').clone(true).insertAfter('.insert-index');
			for(var i=1;i<$('.many-input').length;i++){
				$('.many-input').eq(i).find('.form_label').show();
				$('.many-input').eq(i).find('.delete-ico').show();
			}
			checkInf();
			deleteItem();
		});
	},deleteItem=function(){
		$('#register').on('click', '.delete-ico', function(){
			$(this).closest('.many-input').remove();
		})
	}

	var checkInf=function(){
		var re=/^.{18}$/;
		var obja=$('.fp_input_s'),objb=$('.fp_input_b');
		for (var i = 0; i < obja.length; i++) {
			obja.eq(i).on('blur', function() {
				if ($(this).val()=='') {
					$(this).parent().find('.error').show();
					$(this).parent().find('.error').html('请输入学生姓名');
				}else{
					$(this).parent().find('.error').html('');
					$(this).parent().find('.error').hide();
				}
			});
			objb.eq(i).on('blur', function() {
				if ($(this).val()=='') {
					$(this).parent().find('.error').show();
					$(this).parent().find('.error').html('请输入学籍卡号');
				}else if(!re.test($.trim($(this).val()))){
					$(this).parent().find('.error').show();
					$(this).parent().find('.error').html('学籍卡号是18位');
				}else{
					$(this).parent().find('.error').html('');
					$(this).parent().find('.error').hide();
				}
			});
		};
	
	}


	var loadFile=function(){
		var uploadAttr = $('#progress2').attr('pattr');
		$('#loadArt').uploadify({
	        'width'           : 158,
	        'height'          : 42,
	        'buttonClass'     : 'reset-upload-btn',
	        'buttonText'      : '上传作品',
	        'swf'             : '/front/js/uploadify/uploadify.swf',
	        'uploader'        : '/sites/helper/uploadYdfs',
	        'fileObjName'     : 'file',
	        'fileSizeLimit'	  : '500MB',
	        'multi'    		  : false,
	        'fileTypeExts'    : uploadAttr == '' ? '*.jpg;*.jpeg;*.png;*.rar' : uploadAttr,
	        'formData'        : {which:'opus', max:500*1024*1024*1024*1, type:uploadAttr},
	        'queueID'         : 'progress2',
	        'onUploadSuccess' : function(file, data, response) {
	        	$('#uploadfile-error').hide();
	        	var data = $.parseJSON(data);
	        	if (!data.status && data.data) {
	                Dialog.alert({
	                    msg : data.data
	                });
					return;
				}
				
				$('#uploadfile').val(data.data);
				if (data.status) {
					$('.error-tips').html(file.name+"上传成功");
	        	}
	        }
	    });
	}
	var awardList=function(){
		var obj=$('.award-detail');
		for (var i = 0; i < obj.length; i++) {
			obj.eq(i).on('click',function(event){
				$(this).next().show();
				event.stopPropagation();
			})
		};
		$(document).on('click',function(){
			$('.award-list').hide();
		});
		for (var i = 0; i < $('.award-list').length; i++) {
			(function(i){
				$('.award-list').eq(i).on('click','li',function(){
					$('.award-data').eq(i).val($(this).find('.item').html())	
				})
			})(i)	
		};
	}
	$(init);
})