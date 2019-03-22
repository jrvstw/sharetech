<?php

foreach ($showColumn as $col) {
	echo "<tr> <td>" . $columnName[$col] . "</td> <td> <input type='text'
		name='" . $col . "' value='" . $showValue[$col] . "'";
	if ($_GET['type'] == 'edit' and $col == 'isbn')
		echo " readonly";
	echo">";
	if ($_POST['userSubmit'] == 1 and $dataValid[$col] == false)
		echo "<br><font color='red'>invalid format.</font>";
	echo"</td></tr>";
}
echo "<input type='hidden' name='userSubmit' value=1>";

