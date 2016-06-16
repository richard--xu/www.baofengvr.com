/**
 * @author jerry
 */
define(function(require, exports) {
	exports.getNode = function(enbar) {
		var collec = {};
		var getnode=function(jobj,ember){
			for (var i in ember) {
				if(typeof ember[i] === 'string'){
					collec[i] = $(ember[i]);
				}else{
					collec[i]=$(ember[i].selStr);
					delete ember[i]['selStr'];
					getnode(collec[i],ember[i]); 
				}
			}
			return collec;
		}
		
		return getnode($,enbar)
	};
	/**
	 * control the input maxLangth
	 */

	exports.verLetterCont = function(callback) {
		var maxLen = this.attr('maxlength'), str = this.val();
		str=str.replace(/([\n])*/g,function(a){
			if(a.toString().length==1){
				return '  ';
			}
			return '';		
		});
		str=str.slice(0,maxLen);
		
		var strlen = str.length;
		if ( typeof callback === 'function')
			callback(strlen, maxLen);
		if (this.val().length < maxLen)
			return false;
		this.val(str);
	};
	
	exports.canEnterWrodNum=function(callback){
		var obj=this,myLen = 0,maxLen = this.attr('maxlength'),fstr='';
		var i = 0;
		var str = $.trim(obj.val());
		//str=str.replace(/(\S*)/g,'');
		for(; i < str.length; i++) {
			if(str.charCodeAt(i) > 0 && str.charCodeAt(i) < 128)
				myLen++;
			else
				myLen += 2;
		}
		
		if (typeof callback === 'function'){
			var lnum=(myLen>maxLen?maxLen:myLen);
			callback(lnum, maxLen);
		}
		if (myLen <= maxLen)
			return false;
		var lenCont=0;
		for(i=0; i < str.length; i++) {
			if(str.charCodeAt(i) > 0 && str.charCodeAt(i) < 128)
				lenCont++;
			else
				lenCont += 2;
			fstr+=str[i];
			if(lenCont>=maxLen)
			break;
		}
		this.val(fstr);
	};
	
	exports.isMobil = function(s) {
		//var patrn = /^(13[0-9]|15[0|3|6|7|8|9]|18[0-9])\d{8}$/;
		var patrn = /^1[34578][0-9]{9}$/;
		if (!patrn.exec(s)) {
			return false;
		}
		return true;
	};

	exports.isTel = function(s) {
		var patrn = /^[+]{0,1}(\d){1,4}[ ]{0,1}([-]{0,1}((\d)|[ ]){1,12})+$/;
		if (!patrn.exec(s)) {
			return false;
		}
		return true;
	};
	
	exports.ispcode = function(s) {
		var patrn = /^[1-9]{1}[0-9]{5}$/;
		if (!patrn.exec(s)) {
			return false;
		}
		return true;
	};
	exports.getParm=function(key){
			var search=location.search.slice(1),
			arrSearch=search.split('&'),data={};
			for(var i in arrSearch){
				var tv=arrSearch[i].indexOf('=');
				data[arrSearch[i].substring(0,tv)]=arrSearch[i].substring(tv+1);
			}
			if(data[key]!=undefined){
				return data[key];
			}
			return false;
		};
	exports.login=function(){
		var search=encodeURIComponent(encodeURIComponent(location.search));
		var callback=location.pathname+search;
		window.location.href='/login?callback='+callback;
	};
	exports.getObjLen=function(obj){
		var count=0;
		for(var i in obj){
			count++;
		}
		return count;
	}
});
