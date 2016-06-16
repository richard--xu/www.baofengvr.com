$(function() {
    
    (function() {
        document.title = "易班中职 - 第十二届全国中等职业学校“文明风采”竞赛上海地区复赛";

        $('#logo, .footer-logo').on('click', function() {
            window.location.href = 'http://zz.yiban.cn/';
        })
    })();

    //操作取消效果
    $('.cancer-bt').on('mouseover', function() {
        $(this).find('.cancer').css({
            "backgroundPosition": "-95px -231px"
        });
    });
    $('.cancer-bt').on('mouseout', function() {
        $(this).find('.cancer').css({
            "backgroundPosition": "-72px -231px"
        });
    })
    $('.cancer-bt').on('click', function() {
        var tpl = $('#tpl').html();
        var status = $(this).data('status');
        var id = $(this).attr('getID');
        var html = juicer(tpl, {});

        hm.popbox({
            html: html,
            height: 320,
            width: 160,
            noTitle: true,
            nostyle: true,
            callBack: function(obj) {
                obj.popobj.on('click', 'a.cancer-btm', function() {
                    obj.close();
                });
                obj.popobj.on('click', 'a.confirm-btm', function() {
                    if(status != 0) {
                        hm.toast({
                            'text': '已经评选过的作品,不能删除!'
                        });
                        obj.close();
                        return;
                    }
                    $.ajax({
                            url: '/sites/topics/topic/deleteApply?id=' + id,
                            type: 'GET',
                            dataType: 'json',
                            data: {}
                        })
                        .done(function(data) {
                            if (data.status) {
                                window.location.href = window.location.href;
                            } else {
                                obj.close();
                                hm.toast({
                                    'text': data.data
                                });
                            }
                        });
                });
            }
        });
    });

    $('#save').on('click', function(e) {
        var edit = $('#edit').attr('edit');
        e.preventDefault();
        var name = $('[name="name"]').val();
        var phone = $('[name="phone"]').val();

        if (!name || !phone) {
            hm.alert({
                text: '请输入完整信息'
            });
            return;
        }

        if (edit == 'true') {
            $(this).text('保存');
            $('[name="name"], [name="phone"]').prop('disabled', false);
            var edit = $('#edit').attr('edit','false');
        } else {
            $.ajax({
                url: '/sites/topics/topic/superintendent',
                type: 'POST',
                dataType: 'json',
                data: {
                    name: $('[name="name"]').val(),
                    tel: $('[name="phone"]').val(),
                    summary: $('#uploadfile').val()
                },
                success: function(res) {
                    var edit = $('#edit').attr('edit','true');
                    if (!res.status) {
                        hm.toast({
                            'text': res.data
                        });
                    } else {
                        window.location.href =  window.location.href;
                    }
                }
            })
        }
    })

    $('#upload').uploadify({
        'width': 120,
        'height': 36,
        'buttonClass': 'msg-btn',
        'buttonText': '上传初赛总结',
        'swf': '/front/js/uploadify/uploadify.swf',
        'uploader': '/sites/helper/upload',
        'fileObjName': 'file',
        'fileSizeLimit': '10MB',
        'fileTypeExts': '*.doc;*.docx',
        'formData': {
            which: 'topicSummary',
            type : '*.doc;*.docx',
            max  : '10485760'
        },
        'queueID': 'progress2',
        'onUploadSuccess': function(file, data, response) {
            var data = $.parseJSON(data);
            if (!data.status && data.data) {
                hm.toast({
                    'text': '上传成功，点击' + $('#save').text() + '!'
                });
                return;
            }
            console.log(data);
            $('#uploadfile').val(data.data);
            hm.toast({
                'text': '上传成功，点击' + $('#save').text() + '!'
            });
        }
    });

})
