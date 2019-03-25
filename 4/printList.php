<?php

header("Content-Type:text/html; charset=utf-8");
/*
 * prints books list. If sort option is set, prints in order.
 */
$table = $bookTable->get_table($_GET['sort'], $_GET['order']);
foreach ($table as $line) {
	echo "<tr>";
	foreach ($COLUMNS as $key => $col)
		if ($key != "id")
			echo "<td>" . $line[$key] . "</td>";
	echo "<td>
			  <form action='update.php' style='display:inline' method='get'>
				  <button name='type' value='edit' type='submit'> Edit </button>
				  <input type='hidden' name='id' value='" . $line["id"] . "'></input>
				  <input type='hidden' name='userSubmit' value=0></input>
			  </form>
        	  <form action='deleted.php' style='display:inline' method='get'>
				  <button name='id' value='" . $line["id"] . "' type='submit'
					  onClick=\"javascript:return confirm
					  ('are you sure you want to delete this?');\">
					  Delete
				  </button>
			  </form>
		  </td>";
	echo "</tr>";
	unset($col);
}
unset($line);

