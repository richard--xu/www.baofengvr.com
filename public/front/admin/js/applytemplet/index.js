(function () {
    'use strict';

    var promise = function (data) {
        return $.ajax({
            method: 'POST',
            url: '/sites/admin/applytemplet/delete',
            data: data,
            dataType: 'json'
        });
    };


    function MainViewModel (list) {
        var self = this;
       

        // mapping list form `window.list` to `self.list`
        $(list).each(function() {
            this.owned = ko.observable(false);
            this.editUrl = '/sites/admin/applytemplet/edit?id=' + this.id;
        });
        self.list = ko.observableArray(list);
        // remove item
        self.removeItem = function (item) {
            var dialog = Dialog.confirm({
                msg: '是否删除该条数据？',
                yes: function () {
                    promise({id: item.id})
                        .done(function (res) {
                            dialog.close();
                            if (res.status)
                                window.location.reload();
                            else
                                Dialog.alert({msg: res.data});
                        });
                },
                no: function () {
                    dialog.close();
                }
            });
        }

        // remove items
        self.removeItems = function () {
            var ids = [];
            self.list().forEach(function (item) {
                if (item.owned())
                    ids.push(item.id);
            });
            var dialog = Dialog.confirm({
                msg: '是否删除已选择的数据？',
                yes: function () {
                    promise({id: ids})
                        .done(function (res) {
                            dialog.close();
                            if (res.status){
                                window.location.reload();
                            } else {
                                Dialog.alert({msg: res.data});
                            }
                        })
                },
                no: function () {
                    dialog.close();
                }
            });
        };


        // 查看详情
        self.viewMore = function (item) {
            var source   = $("#dialog-viewmore-tpl").html(),
                template = Handlebars.compile(source),
                data     = {content: JSON.parse(item.content)};

            var dialog = Dialog.create({
                title: '查看',
                width: 760,
                bodyView: template(data),
                buttons: [{
                    id: 'submit-audit',
                    className: 'dialog-btn-secondary',
                    value: '关 闭'
                }],
                events: {
                    '#submit-audit click':function(){
                        dialog.close();
                    }
                }
            });
        };

        // count of all checked items
        self.checkedCount = ko.pureComputed(function () {
            if (!Array.prototype.filter) {
              Array.prototype.filter = function(fun/*, thisArg*/) {
                'use strict';
                if (this === void 0 || this === null) {
                  throw new TypeError();
                }

                var t = Object(this);
                var len = t.length >>> 0;
                if (typeof fun !== 'function') {
                  throw new TypeError();
                }
                var res = [];
                var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
                for (var i = 0; i < len; i++) {
                  if (i in t) {
                    var val = t[i];
                    if (fun.call(thisArg, val, i, t)) {
                      res.push(val);
                    }
                  }
                }
                return res;
              };
            }
            return self.list().filter(function (item) {
                return item.owned();
            }).length;
        });

        // count of items taht are not checked
        self.remainingCount = ko.pureComputed(function () {
            return self.list().length - self.checkedCount();
        });

        // writeable pureComputed observable to handle marking all checked/incheck
        self.allChecked = ko.pureComputed({
            read: function () {
                return !self.remainingCount();
            },
            write: function (newValue) {
                if (!Array.prototype.forEach) {  
                    Array.prototype.forEach = function(callback, thisArg) {  
                        var T, k;  
                        if (this == null) {  
                            throw new TypeError(" this is null or not defined");  
                        }  
                        var O = Object(this);  
                        var len = O.length >>> 0; // Hack to convert O.length to a UInt32  
                        if ({}.toString.call(callback) != "[object Function]") {  
                            throw new TypeError(callback + " is not a function");  
                        }  
                        if (thisArg) {  
                            T = thisArg;  
                        }  
                        k = 0;  
                        while (k < len) {  
                            var kValue;  
                            if (k in O) {  
                                kValue = O[k];  
                                callback.call(T, kValue, k, O);  
                            }  
                            k++;  
                        }  
                    };  
                }  
                self.list().forEach(function (item) {
                    item.owned(newValue);
                });
            }
        });
    }        

    var mainViewModel = new MainViewModel(list);

    ko.applyBindings(mainViewModel);


    // handlebars help for dialog-viewmore-tpl
    // 判断是否必选项
    Handlebars.registerHelper('require', function(type, options) {
        if (type == 1) {
            return options.fn(this);
        }
    });


    // handlebars help for dialog-viewmore-tpl
    // 渲染field项
    Handlebars.registerHelper('field', function(self) {
        var str = '';

        // 类型为单行文本框
        if (self.ele == 'text') {
            str += '<input class="text-field" type="text" />';

        // 类型为多行文本框
        } else if (self.ele == 'textarea') {
            str += '<textarea class="textarea-field"></textarea>';

        // 类型为下拉框
        } else if (self.ele == 'select') {
            str += '<select class="select-field">';
            for (var i = 0, l = self.items.length; i < l; i++) {
                str += '<option>' + self.items[i] + '</option>';
            }
            str += '</select>';

        // 类型为下载按钮
        } else if (self.ele == 'fileupload') {
            str += '<button class="btn btn-thirdly icon-wrap"><span class="icon icon-upload"></span><span>上传作品</span></button>';
        }

        // 类型为学籍卡信息
        if (self.elements) {
            str += '<input id="student-number-1" class="text-field" type="text" /> - <input id="student-number-2" class="text-field" type="text" />';
        }

        return str;
    });


}());