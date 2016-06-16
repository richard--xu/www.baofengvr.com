/**
 * @author Robin
 * @param {dataobj} warpDom,placeholoerDom button Dom 
 * 
 */
define(function(require,exports){
	var parms,warp;
	var init=function(parm){
		var defaults={
			warp:'.set_search',
			place:'.placeholder',
			btn:'.set_search_btn',
			inppatt:'input[type=text],input[type=password]'
		};
		$.extend(defaults,parm);
		parms=defaults;
		warp=$(parms.warp);
		warp.each(function(){
			var __=$(this);
			(function(__){
				var place=__.find(parms.place);
				var inpt=__.find(parms.inppatt);
				var sbtn=__.find(parms.btn);
				doaction(place,inpt,sbtn);
				if( $.trim(inpt.val()).length>0)place.hide();
			})(__);
		});
		
	},
	doaction=function(place,inpt,sbtn){
		inpt.bind('click keyup', function() {
			place.hide();
			inpt.focus();
		}).bind('blur', function() {
			var itxt = $.trim(inpt.val());
			if(itxt.length == 0) {
				place.show();
				inpt.val('');
			}
		}).bind('focus', function() {
			place.hide();
		});
		place.bind('click', function() {
			$(this).hide();
			inpt.focus();
		});
		sbtn.bind('click.plc',function(){
			if(!inpt.val()){
				inpt.focus();
				return false;
			}
		})
	};
	return init;
});
