<?php

include("columnAttributes.php");
$sortOption = array("publisher", "name", "author", "price", "date");
$orderName = array("asc" => "ASC", "dsc" => "DSC");

echo '<form action="index.php" method="get">';

echo '排序<select name="sort" onchange="this.form.submit()">';
foreach ($columnName as $key => $value)
	if (in_array($key, $sortOption)) {
		if ($_GET['sort'] == $key)
			echo "<option value='$key' selected='selected'>$value</option>";
		else
			echo "<option value='$key'>$value</option>";
	}
echo '</select>';
unset($value);

echo '方向<select name="order" onchange="this.form.submit()">';
foreach ($orderName as $key => $value) {
	if ($_GET['order'] == $key)
		echo "<option value='$key' selected='selected'>$value</option>";
	else
		echo "<option value='$key'>$value</option>";
}
unset($value);
echo '</select>';

echo '</form>';

