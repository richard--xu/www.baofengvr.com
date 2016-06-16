$(function() {
    $('[nt="addnew"]').on('click', function(e) {
        var _parentId = typeof $(this).data('parent') == 'undefined' ? '' : $(this).data('parent');
        var _id       = typeof $(this).data('id') == 'undefined' ? '' : $(this).data('id');
        var _name     = typeof $(this).data('name') == 'undefined' ? '' : $(this).data('name');
        var data 	  = {parent:_parentId, id:_id, name:_name};
        var dialog = Dialog.create({
                title: '新增/修改分类',
                width: 760,
                bodyView: juicer($('#dealTpl').html(), data),
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
                    	var url = _id ? '/admin/category/edit' : '/admin/category/add';
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
    });

    $('[nt="delete"]').on('click', function(e) {
        var _id = typeof $(this).data('delete') == 'undefined' ? 0 : $(this).data('delete');
        if (!_id) {
        	Dialog.alert({msg:'参数丢失!'});
        	return false;
        }

        var dialog = Dialog.confirm({
            msg: '确定删除该分类？',
            yes: function () {
                $.post('/admin/category/delete', {id:_id}, function(data){
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