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
 * prints page option.
 */
echo '頁碼:<input type="text" name="page" value="' . $_GET['page'] . '">
		<input type="image" src="images/go.jpg" alt="Submit Form" />';

echo '</form>';

