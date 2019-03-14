<html>
<head>
<title> Add Record </title>
</head>
<body>

<center>
<h1> Contact List </h1>
<h2> Add Record </h2>
<hr>

<form action "list.php" method="post">
<table border=1 bordercolor=#000000>
    <tr><td>Name</td><td><input type="text" name="name"></td></tr>
    <tr><td>Gender</td><td><input type="radio" name="gender" value="Male">Male</input>
                <input type="radio" name="gender" value="Female">Female</input></td></tr>
    <tr><td>Phone</td><td><input type="text" name="phone"></td></tr>
    <tr><td>Birthday</td><td><input type="text" name="birthday"></td></tr>
    <tr><td>Address</td><td><input type="text" name="address"></td></tr>
    <tr><td>E-mail</td><td><input type="text" name="email"></td></tr>
</table>

<hr>
    <input type="submit" value="Add Record">
</form>

<?php
$name = $_POST['name'];
$gender = $_POST['gender'];
$phone = $_POST['phone'];
$birthday = $_POST['birthday'];
$address = $_POST['address'];
$email = $_POST['email'];

$mysqli = new mysqli('localhost', 'sharetechrd33', '27050888') or
    die("Connection failed: " . $conn->connect_error);
$mysqli->select_db("work2") or
    die("connection to contact database failed");

if ($name != NULL) {
    $result = $mysqli->query("select max(id) from contact") or
        die("max id ??");
    $newId = $result->fetch_row()[0] + 1;
    $result = $mysqli->query("insert into contact values('$newId','$name',
        '$gender','$phone','$birthday','$address','$email')") or
        die("query failed");
    $result->free();
}

$mysqli->close();
?>
</center>

</body>
</html>
