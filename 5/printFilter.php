<?php

/*
 * prints sort option.
 */
$sortOption = array(
	"default"		=> "請選擇",
	"isbnasc"		=> "ISBN:ASC",
	"isbndsc"		=> "ISBN:DSC",
	"publisherasc"	=> "出版社:ASC",
	"publisherdsc"	=> "出版社:DSC",
	"nameasc"		=> "書名:ASC",
	"namedsc"		=> "書名:DSC",
	"authorasc"		=> "作者:ASC",
	"authordsc"		=> "作者:DSC",
	"priceasc"		=> "定價:ASC",
	"pricedsc"		=> "定價:DSC",
	"dateasc"		=> "發行日:ASC",
	"datedsc"		=> "發行日:DSC");

echo '排序方式&ensp;<select name="sortOrder" onchange="submitAndShowFirst()">';
foreach ($sortOption as $name => $shown) {
		if ($_GET['sortOrder'] == $name)
			echo "<option value='$name' selected='selected'>" .  $shown .
			"</option>";
		else
			echo "<option value='$name'>" .  $shown . "</option>";
	}
unset($shown);
echo '</select>';


/*
 * prints page option if there are more than 1 pages.
 */
$page = $_GET['page'];
if ($page == "")
	$page = 1;
$last = $bookTable->get_pages();

if ($last > 1) {
	echo '&emsp;';

	/*
	 * prints buttons.
	 */
	print_page_link($page, "|<", 1);
	print_page_link($page, "<<", $page - 1);
	echo '第&nbsp;' . $page . '&nbsp;頁';
	print_page_link($page, ">>", $page + 1);

	if ($page == $last)
		print_page_link($page, ">|", $last);
	else {
		echo '<span class="popup"
					onmouseover="togglePopup(\'lastPageButton\')">';
		print_page_link($page, ">|", $last);
		echo '<span class="popupPage" id="lastPageButton">第 ' . $last .
				' 頁</span></span>';
	}
	/*
	 * prints text input.
	 */
	echo '&emsp;頁碼:<input type="text"
							name="page"
							style="width: 40px;"
							value="' .  $page . '">
		  <input type="image"
				src="images/go.jpg"
				style="vertical-align: middle;"
				onclick="return submitPage(\'' .  $last . '\');"/>';
}


/*
 * This is used to print page buttons.
 */
function print_page_link($from, $button, $to)
{
	$bookTable = new MyTable("books");
	$last = $bookTable->get_pages();
	echo '&nbsp;';
	if ($to < 1 or $last < $to or $to == $from)
		echo $button;
	else
		echo '<a href="index.php?sortOrder=' . $_GET['sortOrder'] . '&page='
			. $to . '">' . $button . '</a>';
	echo '&nbsp;';
}

