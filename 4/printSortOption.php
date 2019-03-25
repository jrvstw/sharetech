<?php

$tableColumn = $bookTable->get_columns();

echo '<form action="index.php" method="get">';


/*
 * prints sort option.
 */
echo '排序<select name="sort" onchange="this.form.submit()">';
$sortOption = array("publisher", "name", "author", "price", "date");
foreach ($sortOption as $name)
	if (in_array($name, $tableColumn)) {
		if ($_GET['sort'] == $name)
			echo "<option value='$name' selected='selected'>" .
				 $COLUMNS[$name]["shown"] . "</option>";
		else
			echo "<option value='$name'>" .
				 $COLUMNS[$name]["shown"] . "</option>";
	}
echo '</select>';
unset($value);


/*
 * prints asc/dsc option.
 */
echo '方向<select name="order" onchange="this.form.submit()">';
$orderName = array("asc" => "ASC", "dsc" => "DSC");
foreach ($orderName as $key => $value) {
	if ($_GET['order'] == $key)
		echo "<option value='$key' selected='selected'>$value</option>";
	else
		echo "<option value='$key'>$value</option>";
}
unset($value);
echo '</select>';

echo '</form>';

