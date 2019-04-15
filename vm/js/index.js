function MenuBinding() {
	var elt = document.getElementsByTagName("a");
	for(i in elt) {		
		var regex = new RegExp(/funct_[0-9]+_subject/g);  
		var obj = elt[i];
		if(regex.test(obj.id)) {//綁定事件
			obj.onclick = function() {
				expand(this.id);
				return false;
			}
		}
	}
}
function SubItemBinding() {
	var elt = document.getElementsByTagName("a");
	for(i in elt)	{		
		var regex = new RegExp(/\/Program\//g);  
		var obj = elt[i];
		if(regex.test(obj.href)) {//綁定事件
			obj.onclick = function() {
				if(this.href.search("MainWelcome") > -1 || this.href.search("Register") > -1 ||  this.href.search("/Notify") > -1) {
					$("browserPath").style.display = "none";
				} else {
					$("browserPath").style.display = "";				
					var tmpId = this.parentNode.parentNode.id.replace("detail", "subject");
					$("browserPath01").innerHTML = $(tmpId).innerHTML;				
					$("browserPath02").innerHTML = this.innerHTML;			
				}				
				$("myIframe").src = this.href;
				AdjustScreenWidth();
				return false;
			}
		}
	}
}
function expand(pID) {
	//展開自己	
	var tag = pID.split("_");
	var sID = "funct_"+tag[1]+"_detail";
	$(sID).style.display = "";
	//其他隱張
	var elt = document.getElementsByTagName("div");
	for(i in elt)	{
		var regex = new RegExp(/funct_[0-9]+_detail/g);  
		var obj = elt[i];
		if(regex.test(obj.id)) {
			if(obj.id == sID) {
				//do nothing...
			} else {
				obj.style.display = "none";
			}
		}
	}
}
var oBrowser = new detectBrowser();
function detectBrowser() { 
	var sAgent = navigator.userAgent.toLowerCase();
	this.isIE = (sAgent.indexOf("msie")!=-1); //IE6.0-7
	this.isFF = (sAgent.indexOf("firefox")!=-1);//firefox
	this.isCH = (sAgent.indexOf("chrome")!=-1);//chrome
}
function hideFocus() {
	var elt = document.getElementsByTagName("a");
	for(i in elt)	{		
		elt[i].hideFocus = "true";
	}
}
function $(tid) { 
	return document.getElementById(tid); 
}
window.onload = function(){
	MenuBinding();
	SubItemBinding();

	if(oBrowser.isIE) {
		hideFocus();
	}
	
	AdjustScreenWidth();
};
window.onresize = AdjustScreenWidth;
function AdjustScreenWidth() {
	var mywidth;
	var ifsrc = $("myIframe").src;

	if(ifsrc.search("/mailrec/") > -1	&& document.body.clientWidth < 1330) {
		mywidth = "1390px";
	} else if(ifsrc.search("/ContentRecorder/") > -1	&& document.body.clientWidth < 1280) {
		mywidth = "1254px";	
	} else if(ifsrc.search("/Rule/") > -1	&& document.body.clientWidth < 1130) {
		mywidth = "1150px";	
	} else if(ifsrc.search("/Network/") > -1	&& document.body.clientWidth < 1130) {
		mywidth = "1015px";	
	} else if(document.body.clientWidth < 1024) {
		mywidth = "992px";
	} else {
		mywidth = "100%";
	}

	if(
		ifsrc.search("ContentRecorder\/msn\-proxy\/conf\.php") > -1 || 
		ifsrc.search("mailrec\/CVirus_Engine\.php") > -1 ||
		ifsrc.search("mailrec\/CMailProxyService\.php") > -1 || 
		ifsrc.search("mailrec\/CSP_base\.php") > -1 || 
		ifsrc.search("mailrec\/AuditFilter\.php") > -1 
	)
	{//exception
		if(document.body.clientWidth < 1024) {
			mywidth = "992px";
		} else {
			mywidth = "100%";
		}
	}

	var mytb = document.body.childNodes;
	for (var i=0; i< mytb.length; i++) {
		if(mytb[i].tagName == "TABLE") {
   		mytb[i].style.width = mywidth;
   	}
  }
}

function pagePrint()
{
		if(oBrowser.isIE) {
			window.frames["myIframe"].focus();
			window.frames["myIframe"].print();
		}

		if(oBrowser.isFF || oBrowser.isCH) {
			document.getElementById("myIframe").focus();
			document.getElementById("myIframe").contentWindow.print();
		}
}

function show_dialog_window(hyperlink, width, height, target) { 
	if(typeof(target) == "undefined") {
		target = null;
	}
	var feature ="width="+width+",height="+height+",menubar=no,toolbar=no,location=no,scrollbars=yes,resizable=yes,status=no,modal=yes"; 
	window.open(hyperlink, null, feature); 
}