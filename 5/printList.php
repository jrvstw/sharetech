<?php

header("Content-Type:text/html; charset=utf-8");

/*
 * retrieve parameters: sort, order, page.
 */
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
$publishers = new MyTable("publishers");

foreach ($table as $row => $line) {
	echo "<tr>";
	/*
	 * prints checkbox
	 */
	echo '<td><input type="checkbox"
					 form="export"
					 name="checked[]"
					 value="' .  $line["id"] . '"
					 onclick="changeCheckall()"/>
		  </td>';
	/*
	 * prints every column
	 */
	foreach ($COLUMNS as $col => $noNeed) {
		if ($col == "id")
			continue;
		$content = "<td>" . $line[$col] . "</td>";
		/*
		 * in column "publisher", makes popup box if there's info about it.
		 */
		if ($col == "publisher") {
			$phone = $publishers->get_field("phone", $col, $line[$col]);
			$address = $publishers->get_field("address", $col, $line[$col]);
			if ($phone != null or $address != null) {
				$popId = 'row' . $row . 'pub';
				$insert = ' class="popup"
							onmouseover="togglePopup(\'' . $popId . '\')"
							onmouseout="togglePopup(\'' . $popId . '\')"';
				$content = substr_replace($content, $insert, 3, 0);

				$insert = '<span class="popupPublisher" id="' . $popId . '">' .
							$phone . '<br>' . $address .  '</span>';
				$content = substr_replace($content, $insert, -5, 0);
			}
		}
		echo $content;
	}
	/*
	 * prints buttons: edit, delete.
	 */
	echo "<td>
			  <form action='update.php' style='display:inline' method='get'>
				  <button name='type' value='edit' type='submit' class='edit'>
					編輯</button>
				  <input type='hidden' name='id' value='" . $line["id"] . "'></input>
				  <input type='hidden' name='userSubmit' value=0></input>
			  </form>
        	  <form action='deleted.php' style='display:inline' method='get'>
				  <button name='id' value='" . $line["id"] . "' class='del' type='submit'
					  onClick=\"javascript:return confirm
					  ('are you sure you want to delete this?');\">
					  刪除
				  </button>
			  </form>
		  </td>";
	echo "</tr>";
}
unset($line);

