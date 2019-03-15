<html>
<head>
<title> Edit Record </title>
</head>
<body>

<center>
<h1> Contact List </h1>
<h2> Edit Record </h2>
<hr>

<?php
$editid = $_POST['edit'];

$mysqli = new mysqli('localhost', 'sharetechrd33', '27050888') or
    die("Connection failed: " . $conn->connect_error);
$mysqli->select_db("work2") or
    die("connection to contact database failed");
$result = $mysqli->query("select * from contact where id='" . $editid . "'") or
    die("query failed");

//echo "editid = " . $editid;
$result->data_seek(1);
$row = $result->fetch_row();

echo '<table border=1 bordercolor=#000000>';
echo '<form action="edit_sent.php" method="post">';
// id
echo '<tr><td>Id</td><td><input type="text" name="id" value='
     . $row[0] . ' readonly></td></tr>';
// name
echo '<tr><td>Name</td><td><input type="text" name="name" value='
     . $row[1] . '></td></tr>';
// gender
echo '<tr><td>Gender</td><td><input type="radio" name="gender"
     value="Male"';
if ($row[2] == "Male")
    echo " checked";
echo '>Male</input>';

echo '<input type="radio" name="gender" value="Female"';
if ($row[2] == "Female")
    echo " checked";
echo '>Female</input></td></tr>';
// phone
echo '<tr><td>Phone</td><td><input type="text" name="phone" value="'
     . $row[3] . '"></td></tr>';
// birthday
echo '<tr><td>Birthday</td><td><input type="text" name="birthday" value="'
     . $row[4] . '"></td></tr>';
// address
echo '<tr><td>Address</td><td><input type="text" name="address" value="'
     . $row[5] . '"></td></tr>';
// email
echo '<tr><td>E-mail</td><td><input type="text" name="email" value="'
     . $row[6] . '"></td></tr>';
?>
</table>

<hr>
    <input type="submit" value="Edit Record">
</form>
</center>

</body>
</html>
