function verifyipormask(aID)
{
	/* split it into a number and a mask */
		var ipormask = document.getElementById(aID).value;
		var detail_finder = new RegExp( /^(.*)\/(.*)$/ );
		var matches = detail_finder.exec( ipormask );
		var address = "";
		if(matches) address = matches[1];
		else address = ipormask;
		var numbers = address.split( "." );
		var valid = true;
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
		}

		if(matches){
			var mask = matches[2];
			if ( mask < 0 || mask > 32 ){
				valid = false;
			}
		} 
		return valid;
}

function verifyipv6(aID)
{
	/* split it into a number and a mask */
		var ipormask = document.getElementById(aID).value;
		var detail_finder = new RegExp( /^(.*)\/(.*)$/ );
		var matches = detail_finder.exec( ipormask );
		var address = "";
		if(matches) address = matches[1];
		else address = ipormask;
		var valid = true;
	
		var errored = !/^\s*((([0-9A-Fa-f]{1,4}:){7}(([0-9A-Fa-f]{1,4})|:))|(([0-9A-Fa-f]{1,4}:){6}(:|((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})|(:[0-9A-Fa-f]{1,4})))|(([0-9A-Fa-f]{1,4}:){5}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){4}(:[0-9A-Fa-f]{1,4}){0,1}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){3}(:[0-9A-Fa-f]{1,4}){0,2}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){2}(:[0-9A-Fa-f]{1,4}){0,3}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:)(:[0-9A-Fa-f]{1,4}){0,4}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(:(:[0-9A-Fa-f]{1,4}){0,5}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})))(%.+)?\s*$/.test(address);
	
		if(errored) {
			valid = false;
		}
		if( ipormask == ""){
			valid = false;
		}

		if(matches){
			var mask = matches[2];
			if ( mask < 0 || mask > 128 ){
				valid = false;
			}
		} 
		return valid;
}