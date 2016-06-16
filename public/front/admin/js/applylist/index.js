$(function() {
    var init = function() {
            timeRange();
            viewMore();
            addComment();
            checkFn();
            downloadSelected();
            downloadId();
            exportData();
            exportAuth();
            exportInfo();
            itemSelectUpdate();
            // $('#start-date').val('');
            // $('#end-date').val('');
        },

        fullName = (function() {
            $('.authName').hover(function() {
                $(this).find('.auth-pop').show();
            }, function() {
                $(this).find('.auth-pop').hide();
            })
        })(),
        resAjaxFn = function(data, url, type) {
            return $.ajax({
                method: 'POST',
                url: '/sites/admin/applylist/' + url,
                data: data,
                dataType: type
            });
        },
        downUrl = function(url) {
            return '/sites/admin/applylist/' + url;
        },
        itemSelectUpdate = function() {
            $('[nt="pass"]').on('change', function() {
                var self = $(this),
                    id = self.data('item'),
                    val = self.val();
                resAjaxFn({
                    id: id,
                    column: 1,
                    data: val
                }, 'setPrize').done(function(res) {
                    if (val != 1) {
                        $('[nt="up"]').val(0);
                        resAjaxFn({
                            id: id,
                            column: 2,
                            data: '0'
                        }, 'setPrize').done(function(res) {
                            window.location.href = '/sites/admin/applylist/index'
                        })
                    } else {
                        window.location.href = '/sites/admin/applylist/index'
                    }
                })
            })

            $('[nt="up"]').on('change', function() {
                var self = $(this),
                    id = self.data('item'),
                    val = self.val();
                resAjaxFn({
                    id: id,
                    column: 2,
                    data: val
                }, 'setPrize').done(function(res) {
                    window.location.href = '/sites/admin/applylist/index'
                })
            });

            $('[nt="prize"]').on('change', function() {
                var self = $(this),
                    id = self.data('item'),
                    val = self.val();
                resAjaxFn({
                    id: id,
                    column: 3,
                    data: val
                }, 'setPrize').done(function(res) {
                    window.location.href = '/sites/admin/applylist/index'
                })
            });
        },
        downloadId = function() {
            $('[nt="download"]').on('click', function(e) {
                var id = $(this).data('download');
                window.open(downUrl('downloadApply?id=') + id);
            })
        },
        downloadSelected = function() {
            $('[nt="download-selected"]').on('click', function(e) {
                var downloadSelectedId = [];
                $('[nt="check"]:checked').each(function() {
                    var id = $(this).data('id');
                    downloadSelectedId.push(id);
                })

                downloadSelectedId = downloadSelectedId.toString();
                downloadSelectedId && window.open(downUrl('downloadOpus?id=') + downloadSelectedId) ? '' : alert('请勾选作品！');
            })
        },
        exportData = function() {
            $('[nt="export-data"]').on('click', function() {
                /*var exportDataId = [];
                $('[nt="check"]:checked').each(function() {
                    var id = $(this).data('id');
                    exportDataId.push(id);
                })
                exportDataId = exportDataId.toString();
                if (exportDataId) {*/
                    var queryStr = window.location.search;
                    var fmtDlg   = Dialog.create({
                            title: '导出数据',
                            width: 300,
                            top: '260px',
                            bodyView: '',
                            buttons: [{
                                id: 'submit-fmt1',
                                className: 'dialog-btn-default',
                                value: '格式A'
                            }, {
                                id: 'submit-fmt2',
                                className: 'dialog-btn-default',
                                value: '格式B'
                            }],
                            events: {
                                '#submit-fmt1 click': function() {
                                    window.open(downUrl('downloadData') + queryStr + (queryStr ? '&format=1' : '?format=1'));
                                    fmtDlg.close();
                                },
                                '#submit-fmt2 click': function() {
                                    window.open(downUrl('downloadData') + queryStr + (queryStr ? '&format=2' : '?format=2'));
                                    fmtDlg.close();
                                }
                            }
                        });
                /*} else {
                     alert('请勾选作品！');
                }*/
            })
        },
        exportAuth = function() {
            $('[nt="export-auth"]').on('click', function() {
                // var exportAuthId = [];
                // $('[nt="check"]:checked').each(function() {
                //     var id = $(this).data('id');
                //     exportAuthId.push(id);
                // })

                // exportAuthId = exportAuthId.toString();
                window.open(downUrl('downloadLeader'));
            })
        },
        exportInfo = function() {
            $('[nt="export-info"]').on('click', function() {
                // var infoId = [];
                // $('[nt="check"]:checked').each(function() {
                //     var id = $(this).data('id');
                //     infoId.push(id);
                // })

                // infoId = infoId.toString();
                // window.open('/sites/admin/applylist/downloadSummer?id=' + infoId);

                window.open('/sites/admin/applylist/downloadSummer');
            })
        },
        checkFn = function() {
            $('[nt="checkAll"]').on('click', function() {
                var isChecked = $(this).prop('checked');
                if (isChecked) {
                    $('[nt="check"]').prop('checked', true);
                } else {
                    $('[nt="check"]').prop('checked', false);
                }
            })

            var updateChecked = function() {
                var itemLen = $('[nt="check"]').length;
                var itemCheckedLen = $('[nt="check"]:checked').length;
                if (itemCheckedLen) {
                    if (itemCheckedLen === itemLen) {
                        $('[nt="checkAll"]').prop('checked', true);
                    } else {
                        $('[nt="checkAll"]').prop('checked', false);
                    }
                } else {
                    $('[nt="checkAll"]').prop('checked', false);
                }
            };

            $('[nt="check"]').on('click', function() {
                updateChecked();
            })
        },
        timeRange = function() {
            var startDate,
                endDate,
                updateStartDate = function() {
                    startPicker.setStartRange(startDate);
                    endPicker.setStartRange(startDate);
                    endPicker.setMinDate(startDate);
                },
                updateEndDate = function() {
                    startPicker.setEndRange(endDate);
                    startPicker.setMaxDate(endDate);
                    endPicker.setEndRange(endDate);
                },
                startPicker = new Pikaday({
                    field: document.getElementById('start-date'),
                    maxDate: new Date(2020, 12, 31),
                    onSelect: function() {
                        startDate = this.getDate();
                        updateStartDate();
                    }
                }),
                endPicker = new Pikaday({
                    field: document.getElementById('end-date'),
                    maxDate: new Date(2020, 12, 31),
                    onSelect: function() {
                        endDate = this.getDate();
                        updateEndDate();
                    }
                }),
                _startDate = startPicker.getDate(),
                _endDate = endPicker.getDate();

            if (_startDate) {
                startDate = _startDate;
                updateStartDate();
            }

            if (_endDate) {
                endDate = _endDate;
                updateEndDate();
            }
        },
        viewMore = function() {
            $('[nt="review-more"]').on('click', function(e) {
                var _id = $(this).data('view');
                resAjaxFn({
                    id: _id
                }, 'show', 'json').done(function(res) {
                    var resp = JSON.parse(res.data);
                    var dialog = Dialog.create({
                        title: '查看',
                        width: 760,
                        bodyView: juicer($('#viewTpl').html(), {}),
                        buttons: [{
                            id: 'submit-audit',
                            className: 'dialog-btn-secondary',
                            value: '关 闭'
                        }],
                        events: {
                            '#submit-audit click': function() {
                                dialog.close();
                            }
                        }
                    });
                    var commentData = function() {
                        var cLen = resp.comment.length;
                        var str = '';
                        for (var i = 0; i < cLen; i++) {
                            str += '<p><b>' + Number(i + 1) + '、&nbsp;</b>' + resp.comment[i].comment + '</p>';
                        }
                        return str;
                    };

                    var renderTpl = function() { // 有点坑，待优化
                        $('[nt="opusName"]').html(resp.opusName);
                        $('[nt="identifier"]').html(resp.identifier);
                        $('[nt="TopicClassify_id"]').html($('[data-item="' + _id + '"] [nt="classify"]').text()); //
                        $('[nt="schoolName"]').html(resp.schoolName);
                        $('[nt="ahthorName"]').html($('[data-ahthorName="' + _id + '"] span').html()); //
                        $('[nt="schoolPrize"]').html(resp.schoolPrize);
                        $('[nt="status"]').html($('[data-item="' + _id + '"]' + '[nt="pass"] option:selected').text());
                        $('[nt="cityPrize"]').html($('[data-item="' + _id + '"]' + '[nt="up"] option:selected').text());
                        $('[nt="content"]').html(commentData());
                    }();

                }).error(function(err) {
                    console.log(err)
                })
            })
        },
        addComment = function() {
            $('[nt="comment"]').on('click', function(e) {
                var id = $(this).data('comment');
                var schoolName = $('[data-schoolname="' + id + '"]').text();
                var opusName = $('[data-opusName="' + id + '"]').text();
                var comInfo = {
                    list: [{
                        _schoolName: schoolName,
                        _opusName: opusName
                    }]
                };
                var dialog = Dialog.create({
                    title: '查看',
                    width: 760,
                    bodyView: juicer($('#commentTpl').html(), comInfo),
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
                        '#submit-confirm click': function() {
                            var content = $('[nt="com-txt"]').val();
                            resAjaxFn({
                                id: id,
                                content: content
                            }, 'comment').done(function(res) {
                                Dialog.alert({
                                    msg: '评论成功！'
                                });
                                location.reload();
                            }).error(function() {
                                Dialog.alert({
                                    msg: '评论失败！'
                                });
                            })
                        },
                        '#submit-cancel click': function() {
                            dialog.close();
                        }
                    }
                });
            })
        };

        $('select[name="activeName"]').on('change', function(){
            if (typeof topicItems != 'undefined' && topicItems) {
                var selected = $(this).val();
                var topicItemsObj = JSON.parse(topicItems);

                $('select[name="type"]').html('<option value="">全部</option>');
                if (topicItemsObj[selected] && topicItemsObj[selected].classityItems) {
                    var tmpClassity = topicItemsObj[selected].classityItems;
                    for (var i in tmpClassity) {
                        $('select[name="type"]').append("<option value='" + tmpClassity[i].id + "'>" + tmpClassity[i].name + "</option>");
                    }
                }
            }
        });

        $('[nt="delete"]').on('click', function(){
            var id = $(this).data('delete');
            var dialog=Dialog.confirm({
                msg: '确认删除该作品?',
                yes: function(){
                    dialog.close();
                    resAjaxFn({id: id}, 'delete', 'json').done(function(res) {
                        if (res.status) {
                            window.location.href = '/sites/admin/applylist/index'
                        } else {
                            Dialog.alert({msg: res.data});
                        }
                    });
                },
                no: function(){
                    dialog.close();
                }
            });
        });

    $(init);
})
