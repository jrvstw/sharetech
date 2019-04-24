var row = document.getElementsByClassName('hoverable');
for (var i = 0; i < row.length; i++) {
	row[i].onmouseover = function()
	{
		this.classList.add("hovered");
	}
	row[i].onmouseout = function()
	{
		this.classList.remove("hovered");
	}
}

function popup_menu()
{
  document.getElementById('menu').classList.toggle("show");
}

