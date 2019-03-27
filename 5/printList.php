<?php

header("Content-Type:text/html; charset=utf-8");
if ($_GET['sortOrder'] == "default") {
	$sort = "";
	$order = "";
} else {
	$sort = substr($_GET['sortOrder'], 0, -3);
	$order = substr($_GET['sortOrder'], -3);
}

$page = $_GET['page'];

/*
 * prints books list. If sort option is set, prints in order.
 */
$table = $bookTable->get_table("", $sort, $order, $page);
$publisherTable = new MyTable("publishers");
foreach ($table as $line) {
	echo "<tr>";
	echo '<td><input type="checkbox" form="export" name="checked[]" value="' .
	$line["id"] . '"/></td>';
	foreach ($COLUMNS as $key => $col)
		if ($key == "publisher" and $publisherTable->get_field(
			"phone", "publisher", $line[$key]) != null)
			echo '<td title="' .
			$publisherTable->get_field("phone", "publisher", $line[$key]) .
			'&#010;' .
			$publisherTable->get_field("address", "publisher", $line[$key]) .
			'">' . $line[$key] . '</td>';
		elseif ($key != "id")
			echo "<td>" . $line[$key] . "</td>";
	echo "<td>
			  <form action='update.php' style='display:inline' method='get'>
				  <button name='type' value='edit' type='submit'
					  style='background-color:#FF9900'>編輯</button>
				  <input type='hidden' name='id' value='" . $line["id"] . "'></input>
				  <input type='hidden' name='userSubmit' value=0></input>
			  </form>
        	  <form action='deleted.php' style='display:inline' method='get'>
				  <button name='id' value='" . $line["id"] . "' type='submit'
					  onClick=\"javascript:return confirm
					  ('are you sure you want to delete this?');\" style=
					  'background-color:#FF0000'>
					  刪除
				  </button>
			  </form>
		  </td>";
	echo "</tr>";
	unset($col);
}
unset($line);
?>

<script type="text/javascript">
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
</script>

