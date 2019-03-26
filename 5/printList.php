<?php

header("Content-Type:text/html; charset=utf-8");
if ($_GET['sortOrder'] == "default") {
	$sort = "";
	$order = "";
} else {
	$sort = substr($_GET['sortOrder'], 0, -3);
	$order = substr($_GET['sortOrder'], -3);
}

if (empty($_GET['page'])) {
	$page = 1;
	// page upperbound?
} else
	$page = $_GET['page'];

/*
 * prints books list. If sort option is set, prints in order.
 */
$table = $bookTable->get_table($sort, $order, $page);
foreach ($table as $line) {
	echo "<tr>";
	echo '<td><input type="checkbox" form="export" name="checked[]" value="' .
	$line["id"] . '"/></td>';
	foreach ($COLUMNS as $key => $col)
		if ($key != "id")
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

