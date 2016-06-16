/**
 * @author H.Yvonne
 * @create 2015.1.6
 * Simulation of the drop-down box
 * add autosearch function
 */
(function(root,$,factory){
	if(typeof define === 'function' && (define.cmd || define.amd)){
		define(function(){
			return factory(root,$);
		});
	} else {
		root.select = factory(root,$);
	}
})(window,$,function(root,$){
	var pubsub = {
		_handlers : '',
		on : function(etype,handler){
			if(typeof this._handlers !== 'object'){
				this._handlers = [];
			}
			if(!this._handlers[etype]){
				this._handlers[etype] = [];
			}
			if(typeof handler == 'function'){
				this._handlers[etype].push(handler);
			}
			return this;
		},
		emit: function(etype) {
			var args = Array.prototype.slice.call(arguments, 1)
			var handlers = this._handlers[etype] || [];
			for (var i = 0, l = handlers.length; i < l; i++) {
				handlers[i].apply(null, args)
			}
			return this;
		}
	};
	/**
	 * 入口
	 * @param activeClass select class
	 */
	var select = function(config,activeClass){
		config = config || {};
		var o;
		for(o in config){
			this[o] = config[o];
		}
		this.init();
	};

	/*config*/
	$.extend(select.prototype,pubsub,{
		selwarp : '[nt="select-warp"]',
		selBtn : '[nt="select-btn"]',
		val : '[nt="select_val"]',
		option : '[nt="option-btn"]',
		active : '',
		inittxt : '',
		listData : '',
		insertDom : '',
		idval : '',
		hidename : '',
		param:''
	});

	/*render dom*/
	$.extend(select.prototype,pubsub,{
		init : function(){
			this.renderTpl();
			this.doaction();
		},
		doaction : function(){
			this.clickFun();
			this.selInit();
			this.autosearchFn();
		},
		template : function(){
			var html = '<div class="selectwarp" nt="select-warp">'+
						'<label class="select_btn" nt="select-btn" title="'+this.param.value+'">'+
							'<input type="text" class="select_input" nt="select_val" data-attr="'+this.idval+'" placeholder="'+this.inittxt+'" name="'+this.param.inpname+'" value="'+this.param.value+'" autoComplete="off" />'+
							'<input type="hidden" value="'+this.idval+'" name="'+this.hidename+'" />'+
							'<b class="select_icon"></b>'+
						'</label>'+
						'<ul class="select_list clearfix" nt="select-list"></ul>'+
					'</div>';
			return html;
		},
		renderTpl : function(){
			var _self = this;
			_self.insertDom.html(_self.template());
			_self.renderList(_self.listData);
		},
		renderList : function(data){
			var _self = this, ul = _self.insertDom.find('[nt="select-list"]');
			ul.html('');
			for(var i in data){
				var html = '<li class="it">'+
								'<a href="javascript:;" class="option_btn" nt="option-btn" attr="'+data[i][_self.param.id]+'">'+data[i][_self.param.name]+'</a>'+
							'</li>';
				ul.append(html);
			}
		}

	});

	/*show or hide the option list*/
	$.extend(select.prototype,pubsub,{
		flag : false,
		clickFun : function(){
			var _self = this;
			$(_self.insertDom).find(_self.selBtn).off('click').on('click',function(){
				_self.show($(this));
			});
		},
		show : function(obj,lock){
			var _self = this,_warp = obj.parents('[nt="select-warp"]');
			if(_warp.hasClass(_self.active)){
				if(lock) return;
				_warp.removeClass(_self.active);
			} else {
				$(_self.selwarp).removeClass(_self.active);
				obj.parents('[nt="select-warp"]').addClass(_self.active);	
			}
			_self.hide();
		},
		/*click body to hide option list*/
		hide : function(){
			var _self = this;
			$('body').click(function(e){
				var target = e.target;
				if (!$(target).closest(_self.selwarp).length){
					$(_self.selwarp).removeClass(_self.active);
				}
			});
		}
	});

	/*option select and change the value*/
	$.extend(select.prototype,pubsub,{
		selInit : function(){
			this.optionSel();
		},
		oldVal : '',
		optionSel : function(){
			var _self = this;
			_self.oldVal = _self.insertDom.find(_self.val).val();
			_self.insertDom.off('click').on('click',_self.option,function(){
				var values = $(this).html(),attr = $(this).attr('attr'),warp = $(this).parents('[nt="select-warp"]');
				warp.find('[nt="select_val"]').val(values).attr('data-attr',attr);
				warp.find('a[nt="select-btn"]').attr('title',values);
				warp.find('input[type="hidden"]').val(attr);
				$(_self.selwarp).removeClass(_self.active);
				_self.changeFn(values,warp,attr);
			});
		},
		changeFn : function(values,obj,attr){
			var _self = this;
			if(_self.oldVal != values){
				_self.emit('change',attr,obj);
				_self.oldVal = values;
				_self.doaction();
			}
		}
	}); 

	/*autosearch*/
	$.extend(select.prototype,pubsub,{
		autosearchFn : function(){
			this.inputFun();
		},
		inputFun : function(){
			var _self = this;
			$(_self.insertDom).find(_self.val).on('focus',function(){
				_self.oldVal = $(this).val();
			}).on('keyup',function(){
				var val = $(this).val();
				if(!val){
					_self.renderList(_self.listData);
					return;
				}
				_self.filterFn(val);
				_self.show($(this),true);
			}).off('blur').on('blur',function(){
				var val = $(this).val();
				if(!val){
					$(this).val('').attr('data-attr','');
					$(this).siblings('input[type="hidden"]').val('');
					_self.changeFn('',$(this).parents('[nt="select-warp"]'),'');
				} else {
					var data = _self.listData,k = 0;
					var newdata = [];
					for(var i in data){
						if(data[i][_self.param.name].indexOf(val) > -1){
							newdata.push(data[i]);
						}
					}
					if(newdata.length) {
						$(this).val(newdata[0][_self.param.name]).attr('data-attr',newdata[0][_self.param.id]);
						$(this).siblings('input[type="hidden"]').val(newdata[0][_self.param.id]);
						_self.changeFn(newdata[0][_self.param.name],$(this).parents('[nt="select-warp"]'),data[0][_self.param.id]);
					} else {
						_self.renderList(_self.listData);
						$(this).val('').attr('data-attr','');
						$(this).siblings('input[type="hidden"]').val('');
						_self.changeFn('',$(this).parents('[nt="select-warp"]'),'');
					}
					
					// for(var i in data){
					// 	if(data[i][_self.param.name] == val){
					// 		$(this).val(data[i][_self.param.name]).attr('data-attr',data[i][_self.param.id]);
					// 		_self.changeFn(data[i][_self.param.name],$(this).parents('[nt="select-warp"]'),data[i][_self.param.id]);
					// 		break;
					// 	}
					// 	k++;
					// }
					// if(k == data.length){
					// 	$(this).val('');
					// 	_self.changeFn('',$(this).parents('[nt="select-warp"]'),'');
					// }
				}

			});
		},
		filterFn : function(val){
			var _self = this, data = _self.listData, newdata = [];
			for(var i in data){
				if(data[i][_self.param.name].indexOf(val) > -1){
					newdata.push(data[i]);
				}
			}
			_self.renderList(newdata);
		}
	});

	return function(config) {
		return selectObj = new select(config);
	}
});