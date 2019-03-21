<?php

/*
 * connect to MySQL
 */
$mysqli = new mysqli('localhost', 'jarvis', '27050888') or
    die("Connection failed: " . $conn->connect_error);
$mysqli->select_db("work4") or
    die("connection to database failed");

$query = "select * from books";
if (isset($_GET['sort']) and isset($_GET['order']))
	$query .= " order by " . $_GET['sort'];
if ($_GET['order'] == "dsc")
	$query .= " desc";
//echo $query;
$result = $mysqli->query($query) or die("query failed");

for ($row = 0; $row < $result->num_rows; $row++) {
	$result->data_seek($row);
	$field = $result->fetch_row();
	echo "<tr>";
	for ($col = 1; $col < $result->field_count; $col++)
        echo "<td>" . $field[$col] . "</td>";
	echo "<td>
			  <form action='update.php' style='display:inline' method='get'>
				  <input type='hidden' name='id' value='$field[0]'></input>
				  <button name='type' value='edit' type='submit'>
					  Edit
				  </button>
			  </form>
        	  <form action='delete.php' style='display:inline' method='get'>
				  <button name='id' value='$field[0]' type='submit'>
					  Delete
				  </button>
			  </form>
		  </td>";
	echo "</tr>";
}

$mysqli->close();

