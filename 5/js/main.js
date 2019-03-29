
function checkAll(obj, cName)
{
	var checkboxes = document.getElementsByName(cName);
	for (var i = 0; i < checkboxes.length; i++) {
		checkboxes[i].checked = obj.checked;
	}
}

function changeCheckall()
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
	} else if (parseInt(last) < page) {
		alert("資料表不超過" + last + "頁");
		return false;
	}
}

// When the user clicks on <div>, open the popup
function togglePopup(id)
{
  document.getElementById(id).classList.toggle("show");
}

var row = document.getElementsByTagName('tr');
for (var i = 1; i < row.length; i++) {
	row[i].onmouseover = function()
	{
		this.style.background = "#EEEEEE";
	}
	row[i].onmouseout = function()
	{
		this.style.background = "#FFFFFF";
	}
}

