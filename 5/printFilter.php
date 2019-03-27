<?php

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

/*
 * prints sort option.
 */
echo '<form action="index.php" method="get">';
echo '排序方式<select name="sortOrder" onchange="this.form.submit()">';
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
function print_page_link($to, $button)
{
	$bookTable = new MyTable("books");
	$last = $bookTable->get_pages();
	echo '&nbsp;';
	if ($to < 1 or $last < $to or $to == $_GET['page'])
		echo $button;
	else {
		echo '<a href="index.php?sortOrder=' . $_GET['sortOrder'] . '&page='
			. $to . '">' . $button . '</a>';
	}
	echo '&nbsp;';
}

$page = $_GET['page'];
if ($page == "")
	$page = 1;
$last = $bookTable->get_pages();

if ($last > 1) {
	print_page_link(1, "|<");
	print_page_link($page - 1, "<<");
	echo '第&nbsp;' . $page . '&nbsp;頁';
	print_page_link($page + 1, ">>");
	echo '<span title="第 ' . $last . ' 頁">';
	print_page_link($last, ">|");
	echo '</span>';
	echo '頁碼:<input type="text" name="page" value="' . $page . '">
		<input type="image" src="images/go.jpg" alt="Submit Form" />';
	echo '</form>';
	echo '<span id="ppp" style="display: none">some text here</span>';
}
?>

