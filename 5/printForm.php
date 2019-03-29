<?php

/*
 * prints $DataToWrite (from update.php) to screen.
 */
if ($_GET['type'] == "edit")
	echo '<tr> <td>' . $COLUMNS["isbn"]["shown"] . '</td> <td>' .
			$bookTable->get_field("isbn", "id", $_GET['id']) . '</td> </tr>';

foreach ($DataToWrite as $col => $value) {
	if ($col == "isbn" and $_GET['type'] == "edit")
		continue;
	$value = str_replace('"', '&quot;', $value);
	echo '<tr> <td>' . $COLUMNS[$col]["shown"] . '</td> <td> <input type="';

	if ($col == "date")
		echo 'date';
	else
		echo 'text';

	echo '" name="' . $col . '" value="' . $value . '">';

	if ($_POST['userSubmit'] == 1 and $dataValid[$col] == false)
		echo '<br><font color="red">invalid format.</font>';

	echo'</td></tr>';
}
unset($value);
echo '<input type="hidden" name="userSubmit" value=1>';

