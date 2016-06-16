(function () {

    'use strict';

    // 组件配置
    var ApplytempletViewModel = function(element, allowType) {

        var self = this;

        //已经确定的表单对象
        self.element = ko.observableArray(element);

        //上传文件所有允许的格式
        self.allowTypeConfig = ko.observableArray(allowType);

        //设置显示控件的类型 1: 文本框 2:下拉列表框 3:多行文本框 4:上传文件
        self.eleType = ko.observable(0);

        // 初始化组件
        self.textElement = ko.observable({
            title: ko.observable(''),
            name: '',
            type: ko.observable(''),
            ele: 'text',
            must: ko.observable('1'),
            del: 1,
            msg: ko.observable('')
        });

        self.selectElement = ko.observable({
            title: ko.observable(''),
            name: '',
            ele: 'select',
            items: null,
            must: ko.observable('1'),
            del: 1,
            msg: ko.observable('')
        });

        self.textareaElement = ko.observable({
            title: ko.observable(''),
            name: '',
            ele: 'textarea',
            must: ko.observable('1'),
            del: 1,
            msg: ko.observable('')
        }); 

        self.fileuploadElement = ko.observable({
            title: ko.observable(''),
            name: '',
            ele: 'fileupload',
            allowExt: null,
            must: ko.observable('1'),
            del: 1,
            msg: ko.observable('')
        });


        // 增加监听函数，监听数据变化
        self.textElement().title.subscribe(function (value) {
            if (value) self.textElement().msg('');
        });

        self.selectElement().title.subscribe(function (value) {
            if (value) self.selectElement().msg('');
        });

        self.textareaElement().title.subscribe(function (value) {
            if (value) self.textareaElement().msg('');
        });

        self.fileuploadElement().title.subscribe(function (value) {
            if (value) self.fileuploadElement().msg('');
        });


        //控制fileupload中后缀的全选
        self.checkAll        = ko.observable(false);
        self.chooseAllowExt  = ko.observableArray();

        //模板名称
        var curTempName      = typeof templetName == 'undefined' ? '' : templetName; //修改和新增一起

        // 
        self.templetName = ko.observable(curTempName);

        self.templetMsg = ko.observable('');

        self.templetName.subscribe(function (value) {
            if (value) self.templetMsg('');
        });
    

        // 临时存放select中的选项值
        self.selectItems     = ko.observableArray();
        // 存放select用户在输入框中输入的选项
        self.selectItemToAdd = ko.observable("");

        self.addSelectItem = function() {
            if (self.selectItemToAdd() != '') {
                self.selectItems.push(self.selectItemToAdd());
                self.selectItemToAdd("");
            }
        };

        self.removeSelectItem = function(item) {
            if (self.selectItems.indexOf(item) >= 0) {
                self.selectItems.remove(item);
            }
        }.bind(self);

        //form提交
        self.submitForm = function(id) {
            if (!self.templetName()) {
                self.templetMsg('请填写报名模板名称！')
                return false;
            }


            //判断新增还是修改
            var postUrl = typeof id !== 'undefined' && !isNaN(id) ? '/sites/admin/applytemplet/doEdit' : '/sites/admin/applytemplet/doAdd';
            var postData = {templetName: self.templetName(), content: JSON.stringify(self.element())};
            if (!isNaN(id) && id) {
                postData.id = id;
            }
            var dialog=Dialog.confirm({
                msg: '确认发布?',
                yes: function(){
                    dialog.close();
                    $.post(postUrl, postData, function(data){
                        if (!data.status) {
                            Dialog.alert({msg: data.data});
                            return false;
                        }else{
                           Dialog.alert({msg: data.data});
                           setTimeout(function(){
                                window.location.href = '/sites/admin/applytemplet/index';
                           },1000); 
                        }     
                    }, 'json');
                },
                no: function(){
                    dialog.close();
                }

            });

            
        };


        // 添加组件
        self.addElementShow = function(ele) {
            self.eleType(ele);
        };

        // 删除控件
        self.removeItem = function(item){
            self.element.remove(item);
        }

        // 自定义元件
        self.write = function (type) {
            // 定义当前临时组件
            var tmpComponent;

            // 如果组件类型是文本框
            if (type == 'text') {
                tmpComponent = self.textElement();

                // 验证名称
                if ($.trim(tmpComponent.title()) == '') {
                    tmpComponent.msg('请输入单行文本框名称！');
                    return;
                }
                tmpComponent.name = tmpComponent.title();

                // 提交数据
                self.element.push(ko.toJS(tmpComponent));

                // 重置表单
                self.textElement({
                    title: ko.observable(''),
                    name: '',
                    type: ko.observable(''),
                    ele: 'text',
                    must: ko.observable('1'),
                    del: 1,
                    msg: ko.observable('')
                });


            // 如果组件类型是下拉框
            } else if (type == 'select') {
                tmpComponent = self.selectElement();

                if ($.trim(tmpComponent.title()) == '') {
                    tmpComponent.msg('请输入下拉框名称！');
                    return;
                }

                if (self.selectItems().length == 0) {
                    tmpComponent.msg('请给下拉框设置选择项!');
                    return;
                }

                tmpComponent.items = self.selectItems();

                tmpComponent.name = tmpComponent.title();

                // 提交数据
                self.element.push(ko.toJS(tmpComponent));

                // 重置表单
                self.selectElement({
                    title: ko.observable(''),
                    name: '',
                    ele: 'select',
                    items: null,
                    must: ko.observable('1'),
                    del: 1,
                    msg: ko.observable('')
                });


            // 如果组件类型是多行文本框
            } else if (type == 'textarea') {

                tmpComponent = self.textareaElement();

                if ($.trim(tmpComponent.title()) == '') {
                    tmpComponent.msg('请输入多行文本框名称！');
                    return;
                }

                tmpComponent.name = tmpComponent.title();

                // 提交数据
                self.element.push(ko.toJS(tmpComponent));

                // 重置表单
                self.textareaElement({
                    title: ko.observable(''),
                    name: '',
                    ele: 'textarea',
                    must: ko.observable('1'),
                    del: 1,
                    msg: ko.observable('')
                });


            // 如果组件类型是上传组件
            } else if (type == 'fileupload') {

                tmpComponent = self.fileuploadElement();

                // 验证名称
                if ($.trim(tmpComponent.title()) == '') {
                    tmpComponent.msg('请输入上传文件项名称！');
                    return;
                }
                // 验证上传格式
                if (self.chooseAllowExt().length == 0) {
                    tmpComponent.msg('请选择上传文件项格式！');
                    return;
                }

                tmpComponent.name = tmpComponent.title();

                tmpComponent.allowExt = self.chooseAllowExt();

                // 提交数据
                self.element.push(ko.toJS(tmpComponent));
                
                // 重置表单
                self.fileuploadElement({
                    title: ko.observable(''),
                    name: '',
                    ele: 'fileupload',
                    allowExt: null,
                    must: ko.observable('1'),
                    del: 1,
                    msg: ko.observable('')
                });
            }

            // 重置面板
            self.eleType(0);

        };


        //全选
        self.checkedAll = ko.computed({
            read: function(){
                return self.chooseAllowExt().length === self.allowTypeConfig()[0].length;
            },
            write: function (value) {
                if (value) {
                    /*此处如果直接用self.chooseAllowExt(self.allowTypeConfig()[0])的话, 等改变
                    chooseAllowExt的值得时候allowTypeConfig的值也会跟着变(大概是对象赋值,内存地址是同一个地方的原因).
                    因此只能逐个值赋予*/
                    var tmpAllExt = new Array();
                    for (var i = 0; i<self.allowTypeConfig()[0].length; i++) {
                        tmpAllExt.push(self.allowTypeConfig()[0][i]);
                    }
                    self.chooseAllowExt(tmpAllExt);
                } else {
                    self.chooseAllowExt([]);
                }
            },
            owner: self
        });
    };

    ko.applyBindings(new ApplytempletViewModel(defaultElement, allowType));

})();