
function check_all(obj, cName)
{
	var checkboxes = document.getElementsByName(cName);
	for (var i = 0; i < checkboxes.length; i++) {
		checkboxes[i].checked = obj.checked;
	}
}

function change_checkall()
{
	var checkboxes = document.getElementsByName("checked[]");
	var write = true;
	for (var i = 0; i < checkboxes.length; i++)
		if (checkboxes[i].checked == false) {
			write = false;
			break;
		}
	document.getElementsByName("checkall[]")[0].checked = write;
}

function validateExport()
{
	var select = document.forms["export"]["select"].value;
	if (select == "") {
		alert("請選擇匯出方式");
		return false;
	} else if (select =="checked") {
		var checkboxes = document.getElementsByName("checked[]");
		var someChecked = false;
		for (var i = 0; i < checkboxes.length; i++)
			if (checkboxes[i].checked == true) {
				someChecked = true;
				break;
			}
		if (someChecked == false) {
			alert("請勾選匯出項目");
			return false;
		}
	}
	return true;
}

function submitAndShowFirst()
{
	document.forms["filter"]["page"].value = "1";
	document.forms["filter"].submit();
}

function submitPage(last)
{
	var page = document.forms["filter"]["page"].value;
	var reg = /^[1-9][0-9]*$/;
	if (page == "") {
		alert("請輸入頁碼");
		return false;
	} else if (reg.test(page) == false || page < 1) {
		alert("無效的頁碼格式");
		return false;
	} else if (last < page) {
		alert("資料表不超過" + last + "頁");
		return false;
	}
}

// When the user clicks on <div>, open the popup
function myFunction() {
  var popup = document.getElementById("myPopup");
  popup.classList.toggle("show");
}


