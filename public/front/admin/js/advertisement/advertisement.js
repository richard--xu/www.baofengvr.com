$(function() {
    $('[nt="addnew"]').on('click', function(e) {
        var _id   = typeof $(this).data('id') == 'undefined' ? '' : $(this).data('id');
        var _data = {};
        if (_id && typeof advert[_id] != 'undefined') {
            _data = advert[_id];
        }
        var dialog = Dialog.create({
                title: '新增/修改广告',
                width: 760,
                bodyView: juicer($('#dealTpl').html(), _data),
                buttons: [{
                    id: 'submit-confirm',
                    className: 'dialog-btn-default',
                    value: '确定'
                }, {
                    id: 'submit-cancel',
                    className: 'dialog-btn-secondary',
                    value: '取消'
                }],
                events: {
                    '#submit-cancel click': function() {
                        dialog.close();
                    },
                    '#submit-confirm click': function() {
                    	var url = _id ? '/admin/advertisement/edit' : '/admin/advertisement/add';
                        $.post(url, $('#dealForm').serialize(), function(data){
                        	if (data.status) {
                        		location.reload();
                        	} else {
                        		Dialog.alert({
                                    msg: data.data
                                });
                        	}
                        }, 'json');
                    },
                }
            });
            
            $('#loadImg').uploadify({
                'width'           : 158,
                'height'          : 42,
                'buttonClass'     : 'reset-upload-btn',
                'buttonText'      : '上传作品',
                'swf'             : '/front/js/uploadify/uploadify.swf',
                'uploader'        : '/helper/uploadhelper/upload',
                'fileObjName'     : 'file',
                'fileSizeLimit'   : '5MB',
                'formData'        : {which:'pic', max:1024*1024*1024*1024*1, type:'*.jpg;*.jpeg;*.png;'},
                'multi'           : false,
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

                    if (data.status) {
                        $('#showImg').html('<img src="' + data.data + '" style="width: 150px;" >');
                        $('input[name="pic"]').val(data.data);
                    }
                }
            });
    });

    $('[nt="delete"]').on('click', function(e) {
        var _id = typeof $(this).data('delete') == 'undefined' ? 0 : $(this).data('delete');
        if (!_id) {
        	Dialog.alert({msg:'参数丢失!'});
        	return false;
        }

        var dialog = Dialog.confirm({
            msg: '确定删除该广告？',
            yes: function () {
                $.post('/admin/advertisement/delete', {id:_id}, function(data){
                	if (data.status) {
                		dialog.close();
                		location.reload();
                	} else {
                		Dialog.alert({msg:data.data});	
                	}

                }, 'json');
            },
            no: function () {
                dialog.close();
            }
        });
    });
});