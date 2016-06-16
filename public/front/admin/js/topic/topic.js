$(function() {
    $('[nt="addnew"]').on('click', function(e) {
        var _id   = typeof $(this).data('id') == 'undefined' ? '' : $(this).data('id');
        var _data = {name:'', categoryId:'', squence:'', products:[], tmpProducts:[]};
        if (_id && typeof topic[_id] != 'undefined') {
            _data = topic[_id];
            if (productList != null && typeof productList[_id] != 'undefined') {
                _data.products    = [];
                _data.tmpProducts = [];
                for (var i in productList[_id]) {
                    var pId = productList[_id][i].productId;
                    _data.products.push(pId);
                    _data.tmpProducts.push({id:pId, title:productList[_id][i].title});
                }
            }
        }
        var dialog = Dialog.create({
                title: '新增/修改专题',
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
                        console.log(_id);
                        var postUrl = _id && !isNaN(_id) ? '/admin/topic/edit' : '/admin/topic/add';
                        console.log(postUrl);
                        _data.name       = $('input[name="name"]').val();
                        _data.categoryId = $('select[name="categoryId"]').val();
                        _data.squence    = $('input[name="squence"]').val();
                        console.log(_data);
                        $.post(postUrl, _data, function(data){
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

        $('#searchAdd').on('click', function(){
            var title = $('input[name="search"]').val();
            console.log(title);
            if (title) {
                $.post('/admin/topic/search', {title:title}, function(data){
                    if (!data.status) {
                        Dialog.alert({
                            msg: data.data
                        });
                    } else {
                        if ($.inArray(data.data.id, _data.products) != -1) {
                            Dialog.alert({
                                msg: '已经添加过了'
                            });
                            return false;
                        }

                        _data.products.push(data.data.id);
                        _data.tmpProducts.push({id:data.data.id,title:data.data.title});
                        loadProducts();
                        $('#search').val('');
                    }
                }, 'json');
            }
        });

        cancelProduct = function(id){
            var index = $.inArray(id, _data.products);
            if (id &&  index !== -1) {
                _data.products.splice(index, 1);
                for (var i in _data.tmpProducts) {
                    if (_data.tmpProducts[i].id == id) {
                        _data.tmpProducts.splice(i, 1);
                        break;
                    }
                }
            }
            loadProducts();
        }

        loadProducts = function(){
            $('#addResult').html('');
            console.log(_data.tmpProducts);
            if (_data.tmpProducts.length > 0) {
                for (var i in _data.tmpProducts) {
                    var id    = _data.tmpProducts[i].id;
                    var title = _data.tmpProducts[i].title;
                    var span  = '<span>' + title;
                        span += '<i class="cancelIcon" onClick="cancelProduct('+id+')">&nbsp;</i>';
                        span += '</span>';
                    $('#addResult').append(span);
                }
            }
        }

        listProduct = function(cid){
            $.post('/admin/product/getAllProduct',{cid:cid}, function(data) {
                if (data.status === true) {
                    var autosearchFn = select({
                            active : 'select_open',
                            listData : data.data,
                            insertDom : $('.product_autosearch'),
                            inittxt : '选择作品名称,点击+号添加作品',
                            param : {
                                name : 'title',
                                inpname : 'search',
                                value: $('.product_autosearch').attr('name')
                            }
                    });
                }
            },'json');
        }

        //初始化选择
        if (_data.categoryId) {
            console.log(_data.categoryId);
            listProduct(_data.categoryId);
        }
        
        $('select[name="categoryId"]').on('change', function(){
            console.log($(this).val());
            //自动搜索
            listProduct($(this).val());
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
                $.post('/admin/topic/delete', {id:_id}, function(data){
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