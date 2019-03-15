<html>
<head>
<title> Contact List </title>
</head>
<body>

<center>
<h1> Contact List </h1>
<h2> List View </h2>
<hr>
<form method="post">
    Search: <select name="type">
                <option value="name"> Name </option>
                <option value="gender"> Gender </option>
                <option value="phone"> Phone </option>
                <option value="birthday"> Birthday </option>
                <option value="address"> Address </option>
                <option value="email"> E-mail </option>
                </select>
    <input type="text" name="name">
</form>

<?php
/*
 * connect to MySQL
 */
$mysqli = new mysqli('localhost', 'sharetechrd33', '27050888') or
    die("Connection failed: " . $conn->connect_error);
$mysqli->select_db("work2") or
    die("connection to database failed");

/*
 * draw table
 */
echo "<table border=1 bordercolor=#000000>";
echo "<tr><td>Id</td><td>Name</td><td>Gender</td><td>Phone</td><td>Birth";
echo "day</td><td>Address</td><td>E-mail</td><td>Edit/Delete</td></tr>";

if (isset($_POST['name']) && $_POST['name'] != "")
    $result = $mysqli->query("select * from contact where " .
                             $_POST['type'] . "='" . $_POST['name'] . "'")
        or die("query failed");
else
    $result = $mysqli->query("select * from contact") or
    die("query failed");

// draw each rows
for ($i = 0; $i < $result->num_rows; $i++) {
    echo "<tr>";
    $result->data_seek($i);
    $row = $result->fetch_row();
    echo "<td>" . sprintf('%03d', $row[0]) . "</td>";
    for ($j = 1; $j < $result->field_count; $j++)
        echo "<td>" . $row[$j] . "</td>";
    echo "<td>
        <form action='edit.php' style='display:inline' method='post'>
        <button name='edit' value='$row[0]' type='submit'>Edit</button>
        </form>
        <form action='delete.php' style='display:inline' method='post'>
        <button name='delete' value='$row[0]' type='submit'>Delete</button>
        </form>";
echo "</td></tr>";
}

echo "</table>";
//$result->free();
$mysqli->close();
?>

<hr>
<form action="add.php">
    <input type="submit" value="Add Record">
</form>
</center>

</body>
</html>
