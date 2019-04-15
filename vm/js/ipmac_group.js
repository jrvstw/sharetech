function validaddgroup(obj){
	if(ValidatorTrim(obj.group.value).length==0){
		alert("<?=$LVar["_GACCOUNT_ERROR_NONAME"]; ?>");
		obj.group.focus();
		return false;
	}
	id = obj.group.value;
	if (id.length<1) {
		alert("<?=$LVar["_GACCOUNT_ERROR_TOOSHORT"]; ?>");
		obj.group.focus();
		return false;
	}
	if (id.length>100) {
		alert("<?=$LVar["_GACCOUNT_ERROR_TOOLONG"]; ?>");
		obj.group.focus();
		return false;
	} 
  	if(!submitedit(document.adduser.member,document.adduser.user)){
  		alert("<?=$LVar["_GACCOUNT_ERROR_NOMEMBER"]; ?>");
  		obj.user.focus();
  		return false;	
  	}
	if( (obj.group.value.indexOf("$")!=-1 || obj.group.value.indexOf(",")!= -1 || obj.group.value.indexOf(";")!= -1)){
		alert("<?=$LVar["_NAME_ERROR_POINT"];?>");
		obj.group.focus();
		return false;
	}	
  	//submitonce(obj);				
	return true;	
}

function validaddaccess(obj){
	
  	if(!submitedit2(document.adduser.member,document.adduser.user)){
  		alert("<?=$LVar["_GACCOUNT_ERROR_NOMEMBER"]; ?>");
  		obj.user.focus();
  		return false;	
  	}
	
  	submitonce(obj);				
	return true;	
}

function validtobook(obj){
	
  	if(!submitedit2(document.adduser.member,document.adduser.user)){
  		alert("<?=$LVar["_GACCOUNT_ERROR_NOMEMBER"]; ?>");
  		obj.user.focus();
  		return false;	
  	}
	
  	submitonce(obj);				
	return true;	
}

function valideditaccess(obj){
	
  	if(!submitedit2(document.adduser.member,document.adduser.user)){
  		alert("<?=$LVar["_GACCOUNT_ERROR_NOMEMBER"]; ?>");
  		obj.user.focus();
  		return false;	
  	}
	
  	submitonce(obj);				
	return true;	
}

function valideditgroup(obj){
	if(ValidatorTrim(obj.group.value).length==0){
		alert("<?=$LVar["_GACCOUNT_ERROR_NONAME"]; ?>");
		obj.group.focus();
		return false;
	}
	id = obj.group.value;
	if (id.length<1) {
		alert("<?=$LVar["_GACCOUNT_ERROR_TOOSHORT"]; ?>");
		obj.group.focus();
		return false;
	}
	if (id.length>100) {
		alert("<?=$LVar["_GACCOUNT_ERROR_TOOLONG"]; ?>");
		obj.group.focus();
		return false;
	} 

  	if(!submitedit(document.adduser.member,document.adduser.user)){
  		alert("<?=$LVar["_GACCOUNT_ERROR_NOMEMBER"]; ?>");
  		obj.user.focus();
  		return false;	
  	}
	if( (obj.group.value.indexOf("$")!=-1 || obj.group.value.indexOf(",")!= -1 || obj.group.value.indexOf(";")!= -1)){
		alert("<?=$LVar["_NAME_ERROR_POINT"];?>");
		obj.group.focus();
		return false;
	}	

	return true;	
}


function validaddbookgroup(obj){
	if(ValidatorTrim(obj.bookgroup.value).length==0){
		alert("<?=$LVar["_BOOKGROUP_ERROR_NONAME"]; ?>");
		obj.bookgroup.focus();
		return false;
	}
	id = obj.bookgroup.value;
	if (id.length<1) {
		alert("<?=$LVar["_BOOKGROUP_ERROR_TOOSHORT"]; ?>");
		obj.bookgroup.focus();
		return false;
	}
	if (id.length>100) {
		alert("<?=$LVar["_BOOKGROUP_ERROR_TOOLONG"]; ?>");
		obj.bookgroup.focus();
		return false;
	} 
	if( (obj.bookgroup.value.indexOf("$")!=-1 || obj.bookgroup.value.indexOf(",")!= -1 || obj.bookgroup.value.indexOf(";")!= -1)){
		alert("<?=$LVar["_NAME_ERROR_POINT"];?>");
		obj.bookgroup.focus();
		return false;
	}	
  	submitonce(obj);				
	return true;	
}

function valideditbookgroup(obj){
	if(ValidatorTrim(obj.bookgroup.value).length==0){
		alert("<?=$LVar["_BOOKGROUP_ERROR_NONAME"]; ?>");
		obj.bookgroup.focus();
		return false;
	}
	id = obj.bookgroup.value;
	if (id.length<1) {
		alert("<?=$LVar["_BOOKGROUP_ERROR_TOOSHORT"]; ?>");
		obj.bookgroup.focus();
		return false;
	}
	if (id.length>100) {
		alert("<?=$LVar["_BOOKGROUP_ERROR_TOOLONG"]; ?>");
		obj.bookgroup.focus();
		return false;
	} 
	if(!submitedit(document.adduser.member,document.adduser.user)){
  		alert("<?=$LVar["_GACCOUNT_ERROR_NOMEMBER"]; ?>");
  		obj.user.focus();
  		return false;	
  	}
	if( (obj.bookgroup.value.indexOf("$")!=-1 || obj.bookgroup.value.indexOf(",")!= -1 || obj.bookgroup.value.indexOf(";")!= -1)){
		alert("<?=$LVar["_NAME_ERROR_POINT"];?>");
		obj.bookgroup.focus();
		return false;
	}	
  	submitonce(obj);				
	return true;	
}


function addUser(thisobj,targetSelect){	
    for(i=0;i<thisobj.options.length;i++){
		if(thisobj.options[i].selected){
		  if(i==0) return false;
		  Length = targetSelect.length ;
    	  targetSelect.length = Length+1;
 		  targetSelect.options[Length].value = thisobj.options[i].value;		  
		  targetSelect.options[Length].text = thisobj.options[i].text;	  
		  
		}		
	}
	return true;
}
function delUser(thisobj){
	delval = new Array();
	deltext = new Array();
	j = 0;
	if(thisobj.options[0].selected) return false;
	for(i=1;i<thisobj.options.length;i++){
		 if(!thisobj.options[i].selected){
		   delval[j] = thisobj.options[i].value;
		   deltext[j] = thisobj.options[i].text;		   
		   j++;
		 }	  
	} 
	len = thisobj.options.length;
	while(len !=0){
	  thisobj.options[len] = null;
	  len--;
	}	
	thisobj.options.length = delval.length+1;
	for(i=0;i<delval.length;i++){
	  thisobj.options[i+1].value = delval[i];
	  thisobj.options[i+1].text = deltext[i];	  
	}
}

function validaddbook(obj){
	if(ValidatorTrim(obj.book_sn.value).length==0){
		alert("<?=$LVar["_BOOK_ERROR_NONAME"]; ?>");
		obj.book_sn.focus();
		return false;
	}
	id = obj.book_sn.value;
	if (id.length<1) {
		alert("<?=$LVar["_BOOK_ERROR_TOOSHORT"]; ?>");
		obj.book_sn.focus();
		return false;
	}
	if (id.length>255) {
		alert("<?=$LVar["_BOOK_ERROR_TOOLONG"]; ?>");
		obj.book_sn.focus();
		return false;
	} 
	if( (obj.book_sn.value.indexOf("$")!=-1 || obj.book_sn.value.indexOf(",")!= -1 || obj.book_sn.value.indexOf(";")!= -1)){
		alert("<?=$LVar["_BOOKSN_ERROR_POINT"];?>");
		obj.book_sn.focus();
		return false;
	}	
	if( (obj.book_givenname.value.indexOf("$")!=-1 || obj.book_givenname.value.indexOf(",")!= -1 || obj.book_givenname.value.indexOf(";")!= -1)){
		alert("<?=$LVar["_BOOKGIVENAME_ERROR_POINT"];?>");
		obj.book_givenname.focus();
		return false;
	}	
	
	if( ChkValue(obj.book_mobile,"<?=$LVar["_BOOK_ERROR_BOOK_MOBILE"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_ipphone,"<?=$LVar["_BOOK_ERROR_BOOK_IPPHONE"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_ofax,"<?=$LVar["_BOOK_ERROR_BOOK_OFAX"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_homephone,"<?=$LVar["_BOOK_ERROR_BOOK_HP"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_homefax,"<?=$LVar["_BOOK_ERROR_BOOK_HFAX"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_bbcall,"<?=$LVar["_BOOK_ERROR_BOOK_CALL"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_ophone,"<?=$LVar["_BOOK_ERROR_BOOK_OPHONE"]; ?>") == false) {return false;}
		
  	submitonce(obj);				
	return true;	
}

function valideditbook(obj){
	if(ValidatorTrim(obj.book_sn.value).length==0){
		alert("<?=$LVar["_BOOK_ERROR_NONAME"]; ?>");
		obj.book_sn.focus();
		return false;
	}
	id = obj.book_sn.value;
	if (id.length<1) {
		alert("<?=$LVar["_BOOK_ERROR_TOOSHORT"]; ?>");
		obj.book_sn.focus();
		return false;
	}
	if (id.length>255) {
		alert("<?=$LVar["_BOOK_ERROR_TOOLONG"]; ?>");
		obj.book_sn.focus();
		return false;
	} 
	
	if( ChkValue(obj.book_mobile,"<?=$LVar["_BOOK_ERROR_BOOK_MOBILE"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_ipphone,"<?=$LVar["_BOOK_ERROR_BOOK_IPPHONE"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_ofax,"<?=$LVar["_BOOK_ERROR_BOOK_OFAX"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_homephone,"<?=$LVar["_BOOK_ERROR_BOOK_HP"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_homefax,"<?=$LVar["_BOOK_ERROR_BOOK_HFAX"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_bbcall,"<?=$LVar["_BOOK_ERROR_BOOK_CALL"]; ?>") == false) {return false;}
	if( ChkValue(obj.book_ophone,"<?=$LVar["_BOOK_ERROR_BOOK_OPHONE"]; ?>") == false) {return false;}
		
  	submitonce(obj);				
	return true;	
}


function ChkValue(subobj,msg){
	id = subobj.value;
	for(var i=0 ; i<id.length ; i++) {  		
  		if(!( (id.charAt(i)>="A" && id.charAt(i)<="Z") ||
  	   	       (id.charAt(i)>="a" && id.charAt(i)<="z") ||
  	   	       (id.charAt(i)>="0" && id.charAt(i)<="9")
  	   	   ))  	   	   {
  	   		alert(msg);
  	   		subobj.focus();
	  	   	return false;
  		}  		
  	}
}

function ToggleAll(obj) {
	  c=obj.checkall2.checked=obj.checkall.checked;
	  for(var i=0;i<obj.elements.length;i++){
	  	var e=obj.elements[i];
	  	if(e.name=="chk[]") 	
		  e.checked=c;
	  }
}	
function ToggleAll2(obj) {
	  c=obj.checkall.checked=obj.checkall2.checked;
	  for(var i=0;i<obj.elements.length;i++){
	  	var e=obj.elements[i];
	  	if(e.name=="chk[]") 	e.checked=c;
	  }
}	

function ValidatorTrim(s) {
    var m = s.match(/^\s*(\S+(\s+\S+)*)\s*$/);
    return (m == null) ? "" : m[1];
}		

function submitedit(targetSelect1,targetSelect2){
	Length2 = targetSelect2.length ;
	targetSelect2.options[0].selected=false ;	
	
	for (i=1; i<Length2; i++) {
		targetSelect2.options[i].selected=true ;
	}
	return true;
}
function submitedit2(targetSelect1,targetSelect2){
	Length2 = targetSelect2.length ;
	targetSelect2.options[0].selected=false ;	
	if(Length2 < 2) {
		targetSelect2.options[0].selected=true ;
		return false;
	}	
	
	for (i=1; i<Length2; i++) {
		targetSelect2.options[i].selected=true ;
	}
	return true;
}
