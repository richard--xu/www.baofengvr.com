/**
 * https://github.com/superwzx/wDialog
 * Author: superwzx <wzx175@gmail.com>
 */

 // UMD (Universal Module Definition)
// see https://github.com/umdjs/umd/blob/master/returnExports.js
(function (root, factory) {
	'use strict';

	/*global define, exports, module */
	if (typeof define === 'function' && define.amd) {
		// AMD. Register as an anonymous module.
		define(factory);
	} else if (typeof exports === 'object') {
		// Node. Does not work with strict CommonJS, but
		// only CommonJS-like enviroments that support module.exports,
		// like Node.
		module.exports = factory();
	} else {
		// Browser globals (root is window)
		root.Dialog = factory();
	}

}(this, function () {

	var	defaults = {
			width: 400,
			top: '10%',
			bodyView: null,
			hasMask: true,
			hasXBtn: true,
			draggable: false,
			init: null,
			closeCallback: null
		},
		Dialog = {};

	function Modal (configs) {
		Modal.occupied = true;
		this.o = $.extend({}, defaults, configs);
		this.open();
	}


	/**
	 * config.title
	 * config.msg
	 * config.delay
	 * config.closeCallback
	 */
	Dialog.alert = function (config) {
		var o = config || {};
		var dialog = new Modal({
			title: o.title,
			top: '20%',
			bodyView: o.msg,
			closeCallback: o.closeCallback
		});
		if ( o.delay && (+o.delay > 0) ) {
			setTimeout(function () {
				dialog.close();
			}, o.delay);
		}
	};


	/**
	 * title
	 * msg
	 * ----------------
	 * yes -> `function`
	 * no  -> `function`
	 * ----
	 * yes {text: '确定', handle: function () {}}
	 * no  {text: '取消', handle: function () {}}
	 * ----------------
	 */
	Dialog.confirm = function (config) {
		var o = config || {},
			yesHandle,
			noHandle;

		if (Object.prototype.toString.call(o['yes']) === '[object Object]'
			&& Object.prototype.toString.call(o['yes']) === '[object Object]') {

			if (typeof o.yes.handle == 'function') {
				yesHandle = o.yes.handle;
				noHandle = o.no.handle;
			} else {
				throw new Error('wDialog confirm handle is a `function`!');
			}

		} else if (Object.prototype.toString.call(o['yes']) === '[object Function]'
			&& Object.prototype.toString.call(o['no']) === '[object Function]') {
			yesHandle =  o.yes;
			noHandle =  o.no;

		} else {
			throw new Error('wDialog confirm handle is a `function`!');
		}

		var yesText = (typeof o.yes.text === 'string') ? o.yes.text : '确 定';

		var noText = (typeof o.no.text === 'string') ? o.no.text : '取 消';

		var dialog = new Modal({
			title: o.title,
			top: '20%',
			bodyView: o.msg,
			buttons: [
				{
                    id: 'wdialog-confirm-yes',
                    className: 'btn-secondary',
                    value: yesText
                },
                {
                    id: 'wdialog-confirm-no',
                    className: 'btn-default',
                    value: noText
                }
			],
			events: {
            	'#wdialog-confirm-yes click': function () {
            		try {
                		yesHandle();
                	} catch (e) {
                		throw new Error(e);
                	}
            	},
                '#wdialog-confirm-no click': function () {
                	try {
                		noHandle();
                	} catch (e) {
                		throw new Error(e);
                	}
                }
            },
			closeCallback: o.closeCallback
		});

		return dialog;
	};

	Dialog.create = function (configs) {
		if (Modal.occupied) {
			return;
		}

		return new Modal(configs);
	}

	Dialog.version = "0.1.0";

	Modal.occupied = false;


	Modal.prototype.open = function () {

		// 构造弹窗遮罩层
		if (this.o.hasMask) {
			this.setMask();
		}

		// 构造弹窗主体
		this.setBox();

		// 构造弹窗关闭按钮
		if (this.o.hasXBtn) {
			this.setXBtn();
		}

		// 构造弹窗Header
		this.setHeader();

		// 构造弹窗Body
		this.setBody();

		// 构造弹窗Footer
		this.setFooter();

		// 设置弹窗宽高；定位弹窗
		this.setShape();
		
		// 委派弹窗事件
		this.delegateEvents();

		// 弹窗是否可拖拽
		if (this.o.draggable) {
			this.setDraggable();
		}

		// 初始化
		this.initialize();

		// 显示弹窗
		this.box.show();

		// 注册弹窗resize事件
		this.resize();

	};

	/**
	 * 弹窗初始化
	 */
	Modal.prototype.initialize = function () {
		var init = this.o.init;
		if (init && typeof init === 'function') {
			this.o.init.apply(this);
		}
	};


	/**
	 * Set the Dialog Mask
	 */
	Modal.prototype.setMask = function () {
		// Create the Mask
		this.mask = $('<div class="wdialog-bg" />').appendTo('body');
		return this;
	};

	/**
	 * Set the Dialog Box
	 */
	Modal.prototype.setBox = function () {
		// Create the box
		// this.boxWrap = $('<div class="wdialog-wrap" />').appendTo('body');
		this.box = $('<div class="wdialog" />')
			.css('top', this.o.top)
			.appendTo(this.mask);
		this.container = $('<div class="wdialog-container" />').appendTo(this.box);
		return this;
	};


	/**
	 * Set the Dialog Close-button
	 */
	Modal.prototype.setXBtn = function () {
		// Create the Close-button
		this.XBtn = $('<a class="wdialog-xbtn">×</a>')
			.click($.proxy(function () {
				this.close();
			}, this))
			.appendTo(this.box);
		return this;
	}


	/**
	 * Set the Dialog Header
	 */
	Modal.prototype.setHeader = function () {
		// Create the Dialog Header
		this.containerHeader = $('<div class="wdialog-container-header" />');

		// Set the Dialog Title
		var title = this.o.title,
			t;
		if (title && Object.prototype.toString.call(title) === '[object String]') {
			t = '<h3>' + title + '</h3>';
		} else {
			t = '<h3>提示</h3>';
		}
		this.containerHeader.append(t);

		// Append ContainerHeader to Container
		this.containerHeader.appendTo(this.container);
		return this;
	};


	/**
	 * Set the Dialog Body-View
	 */
	Modal.prototype.setBody = function () {
		// Create the Dialog Body
		this.containerBody = $('<div class="wdialog-container-body" />')
			.appendTo(this.container);

		// Create the Dialog Tooltip
		this.tooltip = $('<div class="wdialog-container-tooltip" />')
			.appendTo(this.containerBody);

		// Set the content
		var view = this.o.bodyView;
		if (view && Object.prototype.toString.call(view) === '[object String]') {
			this.containerBody.append(view);
		}

		// Return this
		return this;
	};


	/**
	 * Set the Dialog Footer
	 */
	Modal.prototype.setFooter = function () {
		// Set the Footer Button
		var buttons = this.o.buttons;
		if (buttons && Object.prototype.toString.call(buttons) === '[object Array]') {
			// Create the Dialog setFooter
			this.containerFooter = $('<div class="wdialog-container-footer" />');
			var l = buttons.length;
			for (var i = 0; i < l; i++) {
				var button = buttons[i],
					attributes = {},
					value;
				if (button.id) {
					attributes['id'] = button.id;
				}

				if (button.className) {
					// @TODO clsss 与 className
					// obj.class为什么会报错？
					// $.fn.attr('class or className')
					attributes['class'] = button.className;
				}
				if (button.value && typeof button.value === 'string') {
					value = button.value;
				} else {
					throw new Error("Button's value must be a String.");
				}

				$('<button type="button" />')
					.attr(attributes)
					.text(value)
					.appendTo(this.containerFooter);
			}
			// Append ContainerFooter to Container
			this.containerFooter.appendTo(this.container);
		}
		
		// Return this
		return this;
	};


	// Close the dialog, and reset it.
	Modal.prototype.close = function () {

		if (this.o.closeCallback) {
			this.o.closeCallback.apply(this);
		}

		if (this.o.hasMask) {
			this.mask.remove();	
		}
		
		// this.boxWrap.remove();
		
		Modal.occupied = false;
	};


	/**
	 * set dialog width & height
	 ****************************
	 * args:
	 *     width     {Number}
	 *     height    {Number}
	 *     tpl       {String}
	 ****************************
	 * return:
	 *     this
	 */
	Modal.prototype.setShape = function () {

		this.box.css({
			width: this.o.width
		});

		return this;
	};


	/**
	 * set dialog draggable
	 *************************
	 * args:
	 * isDraggable    {Boolean}    ture | false
	 *************************
	 * return:
	 *     this
	 */
	Modal.prototype.setDraggable = function () {

		var bMouseUp = true,
			nMouseX,
			nMouseY,
			nStartX,
			nStartY;

		this.box.on({
			mousedown: function (e) {
				target = e.target; 
				bMouseUp = false;
				nStartX = nStartY = 0;
				nStartX += target.offsetLeft;
				nStartY += target.offsetTop;
				nMouseX = e.clientX;
				nMouseY = e.clientY;
				return false;
			},
			mousemove: function (e) {
				if (bMouseUp) return;
				e.target.style.left = nStartX + e.clientX - nMouseX + 'px';
				e.target.style.top = nStartY + e.clientY - nMouseY + 'px';
			},
			mouseup: function (e) {
				bMouseUp = true;
			}
		});

		$(document).on('mouseup', function (e) {
			bMouseUp = true;
		});

		return this;
	};


	/**
	 * set dialog scrollable
	 *************************
	 * args:
	 * isScrollable    {Boolean}    ture | false
	 *************************
	 * return:
	 *     this
	 */
	Modal.prototype.setScrollable = function () {
		this.o.isScrollable ? (function () {

		})() : (function () {

		})();
		return this;
	};


	/**
	 * Set the tooltip show the Dialog Message
	 *******************************************
	 * args:
	 *     config    {Object}
	 *     -- msg    {String}  "message"
	 *     -- flag   {String}  "right | error | normal",
	 *     -- delay  {Number}  1000   
	 */
	Modal.prototype.showTooltip = function (config) {

		var msg   = config.msg,
			flag  = config.flag,
			delay = config.delay;

		if (!msg && typeof msg === 'string') {
			return;
		}

		this.tooltipMsg = this.tooltipMsg || $('<div class="wdialog-msg" />').text(msg);
		

		if (flag === 'right') {
			this.tooltipMsg.addClass('wdialog-msg-right');
		} else if (flag === 'error') {
			this.tooltipMsg.addClass('wdialog-msg-error');
		}

		this.tooltipMsg.appendTo(this.tooltip);

		if (delay && delay>>0) {
			var _this = this;
			window.setTimeout(function () {
				_this.offTooltip();
			}, Math.abs(delay));
		}

		return this;
	};


	/**
	 * Set the tooltip off the Dialog Message
	 *
	 */
	Modal.prototype.offTooltip = function () {
		this.tooltipMsg.remove();
		return this;
	};


	/**
	 * When window has changed,
	 * resize the dialog.
	 *************************
	 * return:
	 *      this
	 */
	Modal.prototype.resize = function () {
		$(window).on('resize', $.proxy(function () {
			this.setShape();
		}, this));
		return this;
	};


	// delegate 
	Modal.prototype.delegateEvents = function () {
		var events = this.o.events;
		if (events && Object.prototype.toString.call(events) === '[object Object]') {
			var eventSplitter = /(.*)\s(\w+)/;
			for (var key in events) {
				var methodName = key;
					method     = events[methodName],
					match      = key.match(eventSplitter),
					selector   = match[1],
					eventName  = match[2];
				this.box.delegate(selector, eventName, method);
			}
		}
		return this;
	};

	return Dialog;
}));


