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
echo "hihi
wewe";
$searchType = $_POST['type'];
$searchName = $_POST['name'];

$mysqli = new mysqli('localhost', 'sharetechrd33', '27050888') or
    die("Connection failed: " . $conn->connect_error);
//if ($conn->connect_error)

$mysqli->select_db("work2") or
    die("connection to contact database failed");


echo "<table border=1 bordercolor=#000000>";
echo "<tr><td>Id</td><td>Name</td><td>Gender</td><td>Phone</td><td>Birth";
echo "day</td><td>Address</td><td>E-mail</td><td>Edit/Delete</td></tr>";

if ($searchName == NULL)
    $result = $mysqli->query("select * from contact") or
    die("query failed");
else
    $result = $mysqli->query("select * from contact where $searchType=\"$searchName\"") or
    die("query failed");
for ($i = 0; $i < $result->num_rows; $i++) {
    echo "<tr>";
    $result->data_seek($i);
    $row = $result->fetch_row();
    for ($j = 0; $j < $result->field_count; $j++)
        echo "<td>$row[$j]</td>";
?>
<td>
    <input type="submit" value="Edit">
    <input type="submit" value="Delete">
</td>
<?php
    echo "</tr>";
}

$result->free();
echo "</table>";
$mysqli->close();
?>

<hr>
<form action="add.php">
    <input type="submit" value="Add Record">
</form>
</center>

</body>
</html>
