$(function() {
    $('[nt="addnew"]').on('click', function(e) {
        var _id         = typeof $(this).data('id') == 'undefined' ? '' : $(this).data('id');
        var productData = {title:'', description:'', small:'', big:[], filePath:'', classes:[], tmpClasses:[]};
        if (_id && typeof pItems[_id] != 'undefined') {
            productData = pItems[_id];
            //var pic = $.parseJSON(productData.pic);
            var pic = productData.pic;
            productData.small = pic.small;
            productData.big = pic.big;
            if (typeof rItems[_id] != 'undefined') {
                productData.classes    = [];
                productData.tmpClasses = [];
                for (var i in rItems[_id]) {
                    var cId = rItems[_id][i].categoryId;
                    productData.classes.push(cId);
                    if (typeof cItems[cId] != 'undefined') {
                        var tmpItem = {id:cId, name:cItems[cId].name};
                        var pId = cItems[cId].parentId;
                        tmpItem.pName = typeof cItems[pId] != 'undefined' ? cItems[pId].name : ''; 
                        productData.tmpClasses.push(tmpItem);
                    }
                }
            }
        }
        var dialog = Dialog.create({
                title: '新增/修改作品',
                width: 800,
                bodyView: juicer($('#dealTpl').html(), productData),
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
                        console.log(_id);
                        var postUrl = _id && !isNaN(_id) ? '/admin/product/edit' : '/admin/product/add';
                        console.log(postUrl);
                        productData.title       = $('input[name="title"]').val();
                        productData.description = $('textarea[name="description"]').val();
                        console.log(productData);
                        $.post(postUrl, productData, function(data) {
                            var message = data.data;
                            Dialog.alert({msg : message});
                            if (data.status) {
                                window.location.href = '/admin/product/index' 
                            }
                        }, 'json');
                    },
                }
            });

        $('#loadsmall,#loadbig,#loadFile').each(function(){
            var selfObj = $(this);
            var id = selfObj.attr('id');
            var which = id == 'loadFile' ? 'opus' : 'pic';
            var type  = '*.jpg;*.jpeg;*.png;*.gif;*.mp4;*.avi;*.mpeg;*.rm';
            
            selfObj.uploadify({
                'width'           : 158,
                'height'          : 42,
                'buttonClass'     : 'reset-upload-btn',
                'buttonText'      : '上传作品',
                'swf'             : '/front/js/uploadify/uploadify.swf',
                'uploader'        : '/helper/uploadhelper/upload',
                'fileObjName'     : 'file',
                'fileSizeLimit'   : '1GB',
                'formData'        : {which:which, max:1024*1024*1024*1024*1, type:type},
                'multi'           : id == 'loadbig' ? true : false,
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
                    var type = id == 'loadsmall' ? 'small' 
                                                 : (id == 'loadbig' ? 'big'
                                                                    : (id == 'loadFile' 
                                                                    ? 'file' : ''));
                    addFile(data.data, type);
                    if (data.status) {
                        $('.error-tips').html(file.name+"上传成功");
                    }
                }
            });
        });
        //增加文件
        addFile = function(path, type) {
            switch (type) {
                case 'small':
                    productData.small = path;
                    break;
                case 'big'  :
                    if ($.inArray(path, productData.big) === -1) {
                        productData.big.push(path);
                    }
                    break;
                case 'file' :
                    productData.filePath = path;
                    break;
                default:
                    break;
            }
            loadFiles(type);
        }
        //删除文件
        removeFile = function(path, type) {

        }

        //显示图片
        loadFiles = function(type) {
            switch (type) {
                case 'small':
                    if (productData.small) {
                        $('#loadsmall').siblings().remove();
                        var img = '<img src="'+ productData.small +'" style="width: 150px;" >';
                        $('#loadsmall').parent().append(img);
                    }
                    break;
                case 'big'  :
                    if (productData.big.length > 0) {
                        $('#loadbig').siblings().remove();
                        for (var i in productData.big) {
                            var img = '<img src="'+ productData.big[i] +'" style="height: 100px;margin-right: 5px;" >';
                            $('#loadbig').parent().append(img);
                        }
                        
                    }
                    break;
                case 'file' :
                    if (productData.filePath) {
                        $('#loadFile').siblings().remove();
                        var alink = '<a href="'+productData.filePath+'" >'+productData.filePath+'</a>';
                        $('#loadFile').parent().append(alink);
                    }
                    break;
                default:
                    break;
            }
        }

        $('#choose span').on('click', function(){
            var id    = $(this).data('id');
            var name  = $(this).data('name');
            var pName = $(this).data('pname');
            if ($.inArray(id, productData.classes) === -1) {
                productData.classes.push(id);
                productData.tmpClasses.push({id:id, name:name, pName:pName});
                loadClasses();
            }
        });

        cancelClasses = function(id){
            var index = $.inArray(id, productData.classes);
            if (id &&  index !== -1) {
                productData.classes.splice(index, 1);
                for (var i in productData.tmpClasses) {
                    if (productData.tmpClasses[i].id == id) {
                        productData.tmpClasses.splice(i, 1);
                        break;
                    }
                }
            }

            loadClasses();
        }

        loadClasses = function(){
            $('#chooseResult').html('');
            if (productData.tmpClasses.length > 0) {
                for (var i in productData.tmpClasses) {
                    var id    = productData.tmpClasses[i].id;
                    var name  = productData.tmpClasses[i].name;
                    var pName = productData.tmpClasses[i].pName;
                    var span  = '<span>' + pName + ':' + name;
                        span += '<i class="cancelIcon" onClick="cancelClasses('+id+')">&nbsp;</i>';
                        span += '</span>';
                    $('#chooseResult').append(span);
                }
            }
        }
    });
    
    $('select[name="pCid"]').on('change', function(e) {
        var pid = $(this).val();
        $('select[name="cCid"]').html('<option value="">全部</option>');
        if (pid) {
            var current = category[pid];
            if (typeof current.child != 'undefined') {
                for (var i in current.child) {
                    $('select[name="cCid"]').append('<option value="'+current.child[i].id+'">'+current.child[i].name+'</option>');
                }
            }
        }
    });

    $('[nt="delete"]').on('click', function(e) {
        var _id = typeof $(this).data('delete') == 'undefined' ? 0 : $(this).data('delete');
        if (!_id) {
            Dialog.alert({msg:'参数丢失!'});
            return false;
        }

        var dialog = Dialog.confirm({
            msg: '确定删除该作品？',
            yes: function () {
                $.post('/admin/product/delete', {id:_id}, function(data){
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