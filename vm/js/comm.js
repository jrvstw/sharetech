function $(tid) { 
	return document.getElementById(tid); 
}

var oBrowser = new detectBrowser();
function detectBrowser() { 
	var sAgent = navigator.userAgent.toLowerCase();
	this.isIE = (sAgent.indexOf("msie")!=-1); //IE6.0-7
	this.isFF = (sAgent.indexOf("firefox")!=-1);//firefox
}
if(oBrowser.isIE == false && oBrowser.isFF == false) {
	oBrowser.isFF = true;
}
if(oBrowser.isIE) {
	setTimeout(hideFocus, 500);
}
function hideFocus() {
	var elt = document.getElementsByTagName("button");
	for(i in elt)	{		
		elt[i].hideFocus = "true";
	}
}

var resizeInterval = setInterval(resizeIFrame, 500);
var myIframe;
var resizeOnload = 1;
function resizeIFrame() {
	try {
		if(typeof(myIframe) == "undefined") {
			myIframe = parent.document.getElementById("myIframe");
		}		
		var adjHeight = document.body.scrollHeight;
		if(resizeOnload == 1) {
			adjHeight = 540; //最小高度
			resizeOnload = 0;
		}
		if(adjHeight < 540) {
			adjHeight = 540; //最小高度
		}
		if(myIframe.height != adjHeight) {
			myIframe.height = adjHeight;
		}
	} catch(ee) {
		//do nothing
	}
}

function show_dialog_window(hyperlink, width, height, target) { 
	if(typeof(target) == "undefined") {
		target = null;
	}
	var feature ="width="+width+",height="+height+",menubar=no,toolbar=no,location=no,scrollbars=yes,resizable=yes,status=no,modal=yes"; 
	window.open(hyperlink, null, feature); 
}

function tableEffect(tid) {
	var obj = $(tid);
	for( var i = 0; i < obj.rows.length; i++)
	{
		if(i == 0)
		{//標題列不處理
			continue; 
		}
		if(i % 2 == 1)
		{//單數列			
			obj.rows[i].className = "tbMark01";
		}
		if(i % 2 == 0)
		{//雙數列			
			obj.rows[i].className = "tbMark02";
		}	
		obj.rows[i].onmouseover = function(){			
			var elt = this.className.split(" ");
			for(i in elt) {
				if(elt[i] == "tbMark03")
					return false;
			}
			this.className += " tbMark03";
		}
		obj.rows[i].onmouseout = function(){
			var myClass = "";
			var elt = this.className.split(" ");
			for(i in elt) {
				if(elt[i] == "tbMark03")
					continue;
				myClass += " "+elt[i];
			}
			this.className = myClass;		
		}
	}
}

function tableMonoColor(tid) {
	var obj = $(tid);
	for(var i = 0; i < obj.rows.length; i++)
	{
		if(i == 0)
		{//標題列不處理
			continue; 
		}
		if(i % 2 == 1)
		{//單數列			
			obj.rows[i].className = "tbMark01";
		}
		if(i % 2 == 0)
		{//雙數列			
			obj.rows[i].className = "tbMark02";
		}
	}
}	

function jumptopage(myOffset, mySilder) {
	if(typeof(mySilder) != "undefined") {
		var regex = new RegExp(/^[0-9]+$/g);
		if(regex.test(myOffset)) {			
			myOffset = (myOffset - 1) * mySilder;
		} else {
			return false; //格式錯誤		
		}
	}	
	var myURL = $("jumptourl").value;
	if(myURL.search(/\?/) > -1) {
		location.href	= myURL + "&offset=" + myOffset;
	}	else {
		location.href	= myURL + "?offset=" + myOffset;
	}
}
function jumptourlv2(myURL) {
	location.href	= myURL;
}

var effectBgColor = "#FFC8C8";
var effectBorder = "1px solid #86A2BD";
var effectSecond = 3 * 1000;

function verifyRegex(aID, aRegex, aMessage)
{
	var obj = $(aID);
	var regex = null;
	eval("regex = new RegExp(/" + aRegex + "/)");	//轉為程式語言	
	if(regex.test(obj.value)) {
		return true;
	} else {
		verifyPrompt(obj, aMessage);
		return false;
	}
}
function verifyNumber(aID, aRange, aMessage)
{
	var obj = $(aID);

	var tmp = aRange.split("-");
	var start = parseInt(tmp[0]);
	var end = parseInt(tmp[1]);
	var one = parseInt(obj.value);

	if(!isNaN(obj.value) &&  one >= start && one <= end) {
		return true;
	} else {
		verifyPrompt(obj, aMessage);
		return false;
	}
}
function verifyIP(aID, aMessage) {
	var obj = $(aID);
	var regex = new RegExp(/^[\d]+\.[\d]+\.[\d]+\.[\d]+$/);
	if(regex.test(obj.value))
	{
		var aIP = obj.value.split(".");
		for(d in aIP)
		{
			if(parseInt(aIP[d]) < 0 || parseInt(aIP[d]) > 255)
			{
				verifyPrompt(obj, aMessage);
				return false;
			}
		}
		return true;
	}	else {
		verifyPrompt(obj, aMessage);
		return false;
	}
}
function verifyMAC(aID, aMessage) {
	var obj = $(aID);
	var regex = new RegExp(/^[\s]*[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}[\s]*$/);
	if(regex.test(obj.value)) {
		return true;
	}	else {
		verifyPrompt(obj, aMessage);
		return false;
	}
}
function verifyEmpty(aID, aMessage) {
	var obj = $(aID);	
	if(obj.value.replace(/^\s+|\s+$/g,"") == "") {
		return true;
	}	else {
		verifyPrompt(obj, aMessage);
		return false;
	}
}
function verifyPrompt(obj, aMessage) {
	if(typeof(aMessage) == "undefined") {
		aMessage = ""; //空字串
	}

	var tip = document.createElement("font");
	tip.id = "tip" + obj.id;
	tip.className = "verifyTip";
	tip.innerHTML = "<font color=red><b>!!</b>&nbsp;</font>" + aMessage;
	
	//移除之前的
	removeBgColor(obj.id);
	removeElement(tip.id);
	
	//加入效果
	obj.style.backgroundColor = effectBgColor;
	obj.style.border = effectBorder;
	insertAfter(tip, obj);			
	
	//三秒後移除
	setTimeout("removeBgColor('"+obj.id+"')", effectSecond);			
	setTimeout("removeElement('"+tip.id+"')", effectSecond);
}
function insertAfter(newEl, targetEl) {
	var parentEl = targetEl.parentNode;    
  if(parentEl.lastChild == targetEl) {
  	parentEl.appendChild(newEl);
  } else {
  	parentEl.insertBefore(newEl,targetEl.nextSibling);
  }            
}
function removeElement(aID) {	
	try{
		var targetEl = $(aID);
		var parentEl = targetEl.parentNode;    
	  parentEl.removeChild(targetEl);
	} catch(ee) {}
}
function removeBgColor(aID) {
	try{
		var targetEl = $(aID);
		targetEl.style.backgroundColor = "#FFFFFF";		
		targetEl.style.border = effectBorder;
	} catch(ee){}
}

var toggleBlock = null;
var isHold = false;
function toggleEffect(evt, message) {
	if(isHold)
	{//己經有其他區塊Hold
		return false;
	}	
	if(toggleBlock == null)
	{
		toggleBlock = document.createElement("div");
		toggleBlock.className= "toggleBlock";
		document.body.appendChild(toggleBlock);
	}	

	//設定訊息內容	
	toggleBlock.innerHTML	= '<div class="toggleBlockShadow">' + message.replace("&nbsp;", "") + '</div>';

	//顯示區塊
	var posX = 0, posY = 0;	
	toggleBlock.style.top = posY + "px";
	toggleBlock.style.left = posX + "px";
	toggleBlock.style.display = "block";

	//調整最佳 X,Y 位置
	var screenWidth = document.body.clientWidth - 16;
	var toggleWidth = toggleBlock.clientWidth;
	posX = evt.clientX ? evt.clientX : evt.pageX;
	posY = evt.clientY ? evt.clientY : evt.pageY;		
	
	var restX = screenWidth - posX;
	if(restX < toggleWidth)
	{
		posX = posX - (toggleWidth - restX);
		if(posX < 0) posX = 0;		
	}
	
	var scrollHeight = document.body.scrollTop;
	toggleBlock.style.top = (posY + scrollHeight + 12) + "px";
	toggleBlock.style.left = posX + "px";

	document.body.onmouseup = bindHoldEvent;
}
function TagToTip(evt, aID) {
	if(typeof($(aID)) == "undefined")
	{//物件不在在
		return false;
	}	
	evt = window.event ? window.event : evt;
	toggleEffect(evt, $(aID).innerHTML);
}
function Tip(evt, msg) {
	evt = window.event ? window.event : evt;
	toggleEffect(evt, msg);
}
function UnTip() {
	if(isHold)
	{//己經有其他區塊Hold
		return false;
	}	
	if(typeof(toggleBlock) == "object") {
		toggleBlock.style.display = "none";
	}
	if(oBrowser.isIE) {
		document.body.detachEvent("onmouseup", bindHoldEvent);
	}
	if(oBrowser.isFF) {
		document.body.removeEventListener("mouseup", bindHoldEvent, false);
	}
}
function bindHoldEvent(evt)
{	
	evt = window.event ? window.event : evt;
	if(oBrowser.isIE && evt.button == 1) {
		isHold = (isHold) ? false : true;	
	}	if(oBrowser.isFF && evt.button == 0) {
		isHold = (isHold) ? false : true;
	}
	if(isHold == false) UnTip();
}

function urlencode(clearString) {
	var output = '';
	var x = 0;
	clearString = utf16to8(clearString.toString());
	var regex = /(^[a-zA-Z0-9-_.]*)/;
	while (x < clearString.length) {
		var match = regex.exec(clearString.substr(x));
		if (match != null && match.length > 1 && match[1] != '') {
			output += match[1];
			x += match[1].length;
		} else {
			if (clearString[x] == ' ')
				output += '+';
			else {
				var charCode = clearString.charCodeAt(x);
				var hexVal = charCode.toString(16);
				output += '%' + ( hexVal.length < 2 ? '0' : '' ) + hexVal.toUpperCase();
			}
			x++;
		}
	}
	function utf16to8(str) {
		var out, i, len, c;
		out = "";
		len = str.length;
		for(i = 0; i < len; i++) {
			c = str.charCodeAt(i);
			if ((c >= 0x0001) && (c <= 0x007F)) {
				out += str.charAt(i);
			} else if (c > 0x07FF) {
				out += String.fromCharCode(0xE0 | ((c >> 12) & 0x0F));
				out += String.fromCharCode(0x80 | ((c >>  6) & 0x3F));
				out += String.fromCharCode(0x80 | ((c >>  0) & 0x3F));
			} else {
				out += String.fromCharCode(0xC0 | ((c >>  6) & 0x1F));
				out += String.fromCharCode(0x80 | ((c >>  0) & 0x3F));
			}
		}
		return out;
	}
	return output;
}