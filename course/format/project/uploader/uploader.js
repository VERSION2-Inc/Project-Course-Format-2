/**
 *  Project Format Uploader
 */
if (!window.project_format) window.project_format = {};

window.project_format.Uploader = function (
	wwwroot, courseid, sesskey, sessid, raw_session_name, raw_session_id)
{
	var baseurl = wwwroot + "/course/format/project/uploader";
	
	function get(id) { return document.getElementById(id); }
	
	function swf(id)
	{
		return ~navigator.appName.indexOf("Microsoft") ? get(id) : document[id];
	}
	
	function mkid(sectionid, type)
	{
		return [ "uploader", sectionid, type ].join("_");
	}
	
	function toquery(/*params...*/)
	{
		var q = [];
		for (var i = 0; i < arguments.length; i++) {
			var params = arguments[i];
			for (var key in params)
				q.push(key + "=" + params[key]);
		}
		return q.join("&");
	}
	
	this.write = function (sectionid)
	{
		var extras = {
			"pkey1": "course"        , "pval1": courseid,
			"pkey2": "section"       , "pval2": sectionid,
			"pkey3": raw_session_name, "pval3": raw_session_id
		};
		
		var src = baseurl + "/uploader.swf?" + toquery({
			"inipath"      : baseurl + "/",
			"sesskey"      : sesskey,
			"sessid"       : sessid,
			"id"           : sectionid,
			"targetId"     : sectionid,
			"resourcemode" : ~~(sectionid != 0)
		}, extras);
		
		var id = mkid(sectionid, "swf"), style = 'width="16" height="16" align="top"';
		
		// TODO: use swfobject.js instead?
		document.write(
			'<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" id="' + id + '" ' + style +
			' codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0">' +
				'<param name="allowScriptAccess" value="sameDomain" />' +
				'<param name="movie" value="' + src + '" />' +
				'<param name="quality" value="high" />' +
				'<param name="wmode" value="transparent" />' +
				'<embed type="application/x-shockwave-flash" id="' + id + '" name="' + id + '" ' + style +
				' allowScriptAccess="sameDomain" src="' + src + '" quality="high" wmode="transparent"' +
				' pluginspage="http://www.macromedia.com/go/getflashplayer" />' +
			'</object>' +
			'<span id="' + mkid(sectionid, "progress") + '" class="progress"></span>'
		);
	}
	
	
	this.ExternalError = function (msg, url, sesskey, sessid, sectionid)
	{
		get(mkid(sectionid, "progress")).innerHTML = "";
		
		//alert(msg); // alert in flash callback freezes Firefox
		setTimeout(function () { alert(msg); }, 0);
	}
	
	this.ExternalOnLoad = function () {}
	
	this.ExternalOnAddFile = function (sectionid, resourcemode)
	{
		swf(mkid(sectionid, "swf")).uploadItems();
	}
	
	this.ExternalAddFile = function (file) {}
	
	this.ExternalProgress = function (percentage, sectionid, resourcemode)
	{
		get(mkid(sectionid, "progress")).innerHTML = percentage + "%";
	}
	
	this.ExternalComplete = function ()
	{
		location.reload();
	}
}
