/**
 * load another script in sync!
 * @Class   EXPORTS             the namespace where we load function or class
 * @Class   CONFIG              the config files, it start with false after ues M.getconfig it will become a config objet you can get you file's address in it.
 * @Class   require             load the config files
 * @author  Z.Mofei
 * @time    2012-11-26 14:38:46
 */
var M = M || {};
M.EXPORTS = M.EXPORTS || {};
M.EXPORTS.RANDOM = 0;
M.CONFIG = false;
M.required = {};
//config all the require's url
M.getconfig = function () {
	if (!M.CONFIG) {
		$.getScript('/js/2013/config/Mconfig.js');
	}
};

//get what you wanted,use sync ajax;
M.require = function (funName) {
	if (M.required[funName]) {
		return M.EXPORTS[funName];
	}
	$.ajaxSettings.async = false;
	M.getconfig();
	M.required[funName] = true;
	$.getScript(M.CONFIG[funName]);
	$.ajaxSettings.async = true;
	return M.EXPORTS[funName];
};

M.REQUIR = function (object) {
	//you forget the agrument;
	if (!object) {
		throw new Error ('M.REQUIR:Illegality arguments;you need use like this "{name:"sting",url:"string",config:true[false]}"');
	}
	var name = object.name || ('SYSTEM_' + M.EXPORTS.RANDOM++);
	var url = object.url;
	var config = object.config;
	//you forget the url;
	if (!config && !url) {
		throw new Error ('M.REQUIR:you need provide the script\'s url');
	}
	//if you already load this,just return the report
	if (M.EXPORTS[name]) {
		M.EXPORTS[name]++;
		return {
			'state' : 'exist' ,
			'name' : name ,
			'loadTimes' : M.EXPORTS[name]
		};
	}
	//if you set config,load the config and find the src;
	if (config) {
		$.ajaxSettings.async = false;
		M.getconfig();
		url = M.CONFIG[name];
		$.ajaxSettings.async = true;
	}
	
	//let's load
	$.ajaxSettings.async = false;
	$.getScript(url);
	$.ajaxSettings.async = true;
	M.EXPORTS[name] = 1;

	return {
		'state' : 'new' ,
		'name' : name
	};
};
//golbal
M.GOLBAL = (function(){
	M.REQUIR({'name':'backtop','config':true});
})();
