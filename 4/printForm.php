<?php

foreach ($writeData as $col => $value) {
	$value = str_replace('"', '&quot;', $value);
	echo '<tr> <td>' . $COLUMNS[$col]["shown"] . '</td> <td> <input type="';

	if ($col == "date")
		echo 'date';
	else
		echo 'text';

	echo '" name="' . $col . '" value="' . $value . '"';

	if ($_GET['type'] == 'edit' and $col == 'isbn')
		echo ' readonly';

	echo">";

	if ($_POST['userSubmit'] == 1 and $dataValid[$col] == false)
		echo '<br><font color="red">invalid format.</font>';

	echo'</td></tr>';
}
unset($value);
echo '<input type="hidden" name="userSubmit" value=1>';

