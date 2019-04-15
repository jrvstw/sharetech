/*
	Need /js/comm.js
*/

//replace comm.js
var resizeInterval = setInterval(resizeIFrame, 50);

function newvalidip(field,blank,msg,allow_zero_at_last){
	var address = field;
	if ( $(field) ){
		address = $(field).value;
	}
	var valid = true;

	if ( address == "" && blank == 'true' ){
		valid = true;
	} else {
		var numbers = address.split( "." );

		if ( numbers.length != 4 ){
			valid = false;
		}

		for ( var number = 0 ; number < 4 ; number++ ){
			if ( ! numbers[ number ] ){
				valid = false;
				break;
			}

			for ( var character = 0 ; character < numbers[ number ].length ; character++ ){
				if (
					( numbers[ number ].charAt( character ) < '0' ) ||
					( numbers[ number ].charAt( character ) > '9' ) ){
					valid = false;
					break;
				}
			}

			if (( numbers[ number ] < 0 ) || ( numbers[ number ] > 255 )){
				valid = false;
			}
			if(number == 0 && (numbers[ number ] == 0)){
				valid = false;
			}
			if(number == 3 && (numbers[ number ] == 255)){
				valid = false;
			}
			if(arguments[3] == undefined && number == 3 && (numbers[ number ] == 0)){
				valid = false;
			}
		}
	}

	if (!valid && $(field)){
		verifyPrompt($(field),msg);
	}
	return valid;
}

function newvalidmask(field,blank,msg){
	var mask = field;
	if ( $(field) ){
		mask = $(field).value;
	}
	var valid = true;

	if ( mask == "" && blank == 'true' ){
		valid = true;
	} else {
		// is it a valid ip ?
		if ( newvalidip( field, blank ) ){
			valid = true;
		} else if ( mask > 0 && mask <= 32 ){
			valid = true;
		} else {
			valid = false;
		}
	}

	if (!valid && $(field)){
		verifyPrompt($(field),msg);
	}
	return valid;
}

function newvalidipormask(line, field, blank, msg){
	var ipormask = line;
	var valid = true;

	if ( ipormask == "" && blank == 'true' ){
		valid = true;
	} else {
		// is it an ip only ?
		if ( newvalidip( line, blank ) ){
			valid = true;
		} else {
			/* split it into a number and a mask */
			var detail_finder = new RegExp( /^(.*)\/(.*)$/ );
			var matches = detail_finder.exec( ipormask );
			if ( !matches ){
				valid = false;
			} else {
				if ( !newvalidip(matches[1],null,null,"allow_zero_at_last")){
					valid = false;
				} else {
					valid = newvalidmask(matches[2]);
				}
			}
		}
	}

	if (!valid && $(field)){
		verifyPrompt($(field),msg);
	}
	return valid;
}

function newvalidipandmask(line, field, blank, msg){
	var ipandmask = line;
	var valid = true;

	if ( ipandmask == ""){
		valid = true;
	} else {
		/* split it into a number and a mask */
		var detail_finder = new RegExp( /^(.*?)\/(.*?)$/ );
		var matches = detail_finder.exec( ipandmask );
		if ( !matches ){
			valid = false;
		} else {
			if ( !newvalidip(matches[1],null,null,"allow_zero_at_last") ){
				valid = false;
			} else {
				valid = newvalidmask(matches[2]);
			}
		}
	}

	if (!valid && $(field)){
		verifyPrompt($(field),msg);
	}
	return valid;
}

function newvalidipfileld(fieldname,fieldnumber,msg){
	var inputval = $(fieldname+fieldnumber).value;
	var valid = false;

	if( inputval == undefined || inputval == ""){
		valid = false;
	} else {
		var re = new RegExp( '^[0-9]{1,3}$' );
		var ma = re.exec( inputval );

		if ( ma == null ){
			valid = false;
		}
		else {
			valid = true;
			if(inputval > 255 || inputval < 0){
				valid = false;
			}
			if(fieldnumber == 1 && inputval == 0){
				valid = false;
			}
			if(fieldnumber == 4 && (inputval == 0 || inputval == 255)){
				valid = false;
			}
		}
	}

	if (!valid && $(fieldname+fieldnumber)){
		verifyPrompt($(fieldname+fieldnumber),msg);
	}
	return valid;
}

//replace in comm.js
function removeElement(aID) {
	if($(aID)){
		var targetEl = $(aID);
		var parentEl = targetEl.parentNode;
		parentEl.removeChild(targetEl);
	}
}

var letter_digit = "a-zA-Z0-9";

function verifyFullDomain(aID, aMessage){
	var fullDomainRegex = "^((["+letter_digit+"]+([\\-_]+["+letter_digit+"]+)*)\\.)*([a-zA-Z]{2,})$";
	return verifyRegex(aID, fullDomainRegex, aMessage);
}

function verifyHalfDomain(aID, aMessage){
	var halfDomainRegex = "^(["+letter_digit+"]+([\\-_]+["+letter_digit+"]+)*(\\.["+letter_digit+"]+([\\-_]+["+letter_digit+"]+)*)*)*$";
	return verifyRegex(aID, halfDomainRegex, aMessage);
}

var additinal_char = "\*";

function verifyFullRecord(aID, aMessage){
	var fullDomainRegex = "^((["+additinal_char+letter_digit+"]+([\\-_]+["+additinal_char+letter_digit+"]+)*)\\.)*([a-zA-Z]{2,})$";
	return verifyRegex(aID, fullDomainRegex, aMessage);
}

function verifyHalfRecord(aID, aMessage){
	var halfDomainRegex = "^(["+additinal_char+letter_digit+"]+([\\-_]+["+additinal_char+letter_digit+"]+)*(\\.["+additinal_char+letter_digit+"]+([\\-_]+["+additinal_char+letter_digit+"]+)*)*)*$";
	return verifyRegex(aID, halfDomainRegex, aMessage);
}

function getKeyCode(event) {
	return window.event	? event.keyCode	: event.which;
}

function switchDataAddress() {
	switch(arguments.length){
		case 2:
			var dataID = arguments[0];
			var wannumber = arguments[1];
			if(wannumber <= 0){
				$(dataID+'wan').selectedIndex = 0;
				for(var i=1;i<=4;i++){
					$(dataID+i).readOnly = false;
					$(dataID+i).value = "";
				}
			}
			if(dataID == "data"){
				change_backup_select();
				$("backup_select").selectedIndex = 0;
			}
			break;
		case 3:
			var DELIMITER = arguments[0];
			var data_array = arguments[1].split(DELIMITER);
			var dataID = arguments[2];
			$(dataID+'view').selectedIndex = data_array[0];
			var wannumber = data_array[1];
			var ip_part = data_array[2].split(".");
			for(var i=1;i<$(dataID+'wan').length;i++){
				if($(dataID+'wan').options[i].value == wannumber){
					$(dataID+'wan').selectedIndex = i;
					break;
				}
				else{
					$(dataID+'wan').selectedIndex = 0;
				}
			}
			for(var i=1;i<=4;i++){
				$(dataID+i).value = ip_part[i-1];
				if(wannumber <= 0){
					$(dataID+i).readOnly = false;
				}
				else{
					//$(dataID+i).readOnly = true;
				}
			}
			if(dataID == "data"){
				change_backup_select();
				if(data_array[3] > 0){
					for(var i = 0;i < $("backup_select").options.length;i++){
						if($("backup_select").options[i].value == data_array[3]){
							$("backup_select").options[i].selected = true;
						}
					}
				}else{
					$("backup_select").options[0].selected = true;
				}
			}
			break;
		case 4:
			var DELIMITER = arguments[0];
			var wan_array = arguments[1].split(DELIMITER);
			var dataID = arguments[2];
			var wannumber = arguments[3];
			var modestr = arguments[3];

			if(modestr == 'editable'){
				$(dataID+'wan').selectedIndex = 0;
				for(var i=1;i<=4;i++){
					$(dataID+i).readOnly = false;
				}
			}
			else if(modestr == 'ignore'){
				$(dataID+'wan').selectedIndex = 0;
				for(var i=1;i<=4;i++){
					$(dataID+i).readOnly = true;
					$(dataID+i).value = "";
				}

			}
			else if(wannumber <= 0) {
				$(dataID+'wan').selectedIndex = 0;
				for(var i=1;i<=4;i++){
					$(dataID+i).readOnly = false;
					$(dataID+i).value = "";
				}
			}
			else{
				for(var i=1;i<$(dataID+'wan').length;i++){
					if($(dataID+'wan').options[i].value == wannumber){
						$(dataID+'wan').selectedIndex = i;
						break;
					}
				}
				//var dataIDi_exists = 0;
				//for(var i=1;i<=4;i++){
					//if($(dataID+i).value) dataIDi_exists++;
				//}

				//if(dataIDi_exists != 4){
					var ip = new Array();
					for(var i in wan_array){
						var ip_part = wan_array[i].split(".");
						ip[i] = new Array;
						for(var j in ip_part){
							ip[i][j] = ip_part[j];
						}
					}
					for(var i=1;i<=4;i++){
						//$(dataID+i).readOnly = true;
						$(dataID+i).value = ip[wannumber-1][i-1];
					}
				//}
			}
			if(dataID == "data"){
				change_backup_select();
			}
			break;
	}
	if($(dataID+'wan').options[$(dataID+'wan').selectedIndex].disabled){
		switchDataAddress(dataID,-1);
	}
}

function switchDataAddressBackup() {
	var DELIMITER = arguments[0];
	var wan_array = arguments[1].split(DELIMITER);
	var dataID = arguments[2];
	var wannumber = arguments[3];
	if(wannumber <= 0) {
		$(dataID).selectedIndex = 0;
		for(var i=1;i<=4;i++){
			$(dataID+i).readOnly = false;
			$(dataID+i).value = "";
			$(dataID+'ipshow').style.display = "none";
		}
	}
	else{
		for(var i=1;i<$(dataID).length;i++){
			if($(dataID).options[i].value == wannumber){
				$(dataID).selectedIndex = i;
				break;
			}
		}
		//var dataIDi_exists = 0;
		//for(var i=1;i<=4;i++){
			//if($(dataID+i).value) dataIDi_exists++;
		//}
		//if(dataIDi_exists != 4){
			var ip = new Array();
			for(var i in wan_array){
				if(wan_array[i] != '') var ip_part = wan_array[i].split(".");
				else var ip_part = new Array('','','','');
				ip[i] = new Array;
				for(var j in ip_part){
					ip[i][j] = ip_part[j];
				}
			}
			for(var i=1;i<=4;i++){
				//$(dataID+i).readOnly = true;
				$(dataID+i).value = ip[wannumber-1][i-1];
			}
		//}
		if($(dataID+'ipshow').style.display == "none") $(dataID+'ipshow').style.display = "";
	}
}

function show_dialog_by_dev(choice, dev){
	var wan = '1';
	if(dev != '0') wan = dev;
	show_dialog_window('../Services/IP_Option.php?choice='+choice+'&dev=wan'+wan);
}

function show_dialog_window(url){
	 var feature;
	 feature ="width=500,height=400,menubar=no,toolbar=no,location=no,";
	 feature+="scrollbars=yes,resizable=yes,status=no,modal=yes";
	 window.open(url,null,feature);
}

function change_backup_select(){
	if(document.getElementById("datawan") == undefined || document.getElementById("backup_select") == undefined){
		return false;
	}
	var view = document.getElementById("dataview").value;
	var inte_obj = document.getElementById("datawan");
	var inte_opt = inte_obj.options;
	
	var backup_obj=document.getElementById("backup_select");
	
	backup_obj.options.length=0;
	backup_obj.options.add(new Option("None",0));
	for(var i = 1; i < inte_opt.length; i++){
		if(inte_obj.options[i].selected == true){
			continue;
		}
		if(document.getElementById("isset_wan_"+view+i).value == 0){
			continue;
		}
		var a = inte_obj.options[i].text;
		var b = inte_obj.options[i].value;
		backup_obj.options.add(new Option(a,b));
	}
}