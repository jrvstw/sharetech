
function someChecked(list)
{
	var someChecked = false;
	if (list.length == undefined)
		return list.checked;
	else
		for (var i = 0; i < list.length; i++)
			if (list[i].checked == true)
				return true;
	return false;
}

function checkDelButton()
{
	var list = document.forms["del"]["checked[]"]
	document.forms["del"]["submit"].disabled = !someChecked(list);
}

function validateDelete()
{
	var list = document.forms["del"]["checked[]"]
	if (someChecked(list) == false) {
		alert("Please select at least one entry.");
		return false;
	} else if (confirm("Are you sure to delete?"))
		return true;
	else
		return false;
}

function validateAdd()
{
	var ip = document.forms["add"]["ip"].value;
	var ip_reg = /^[0-9]{1,3}(\.[0-9]{1,3}){3}$/;
	if (ip_reg.test(ip) == false) {
		alert("Invalid format of address");
		return false;
	}
	var table = document.getElementById("arp_table");
	for (var i = 1; i < table.rows.length - 1; i++) {
		var ipcmp = table.rows[i].cells[0].innerHTML;
		if (ip == ipcmp) {
			alert("Duplicated ip address");
			return false;
		}
	}

	var mac = document.forms["add"]["mac"].value;
	var mac_reg = /^[0-9A-Fa-f]{2}([-:][0-9A-Fa-f]{2}){5}$/;
	if (mac_reg.test(mac) == false) {
		alert("Invalid format of hwaddress");
		return false;
	}

	var iface = document.forms["add"]["iface"].value;
	if (iface == "") {
		alert("Invalid format of Iface");
		return false;
	}
	return true;
}

