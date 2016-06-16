/* 
    @ by ly
    @ 1、Placeholer fn for not support attr[placeholder] browsers 
    @ 2、Prevent from search submit default value for form
*/
! function() {
    var param = {
        el: $('.search [placeholder]'),
        dc: '#999',
        fc: '#333',
        elBtn: $('.search-btn')
    };
    var hasPlaceholder = function() {
        var input = document.createElement('input');
        return 'placeholder' in input;
    };
    if (!hasPlaceholder()) {
        $.each(param.el, function(i, item) {
            var self = $(this);
            var p = self.attr('placeholder');

            self.val(p).css('color', param.dc);
            self.on('focus', function() {
                var v = self.val();
                if (v == p) {
                    self.val('').css('color', param.fc);
                }
            }).on('blur', function() {
                var v = self.val();
                if (v == p) {
                    self.val(p).css('color', param.dc);
                } else if (!v || !$.trim(v)) {
                    self.val(p).css('color', param.dc);
                }
            })
        })

        var search = location.href;
        var isAdmin = search.indexOf('admin') > 0;
        if (isAdmin) {
            param.elBtn.on('click', function(e) {
                $.each(param.el, function(i, item) {
                    var self = $(this);
                    var v = self.val();
                    var p = self.attr('placeholder');
                    if (v == p) {
                        self.val('');
                    }
                })
            })
        }
    }
}();
