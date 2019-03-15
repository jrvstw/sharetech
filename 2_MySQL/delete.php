<html>
<head>
<title>
deleting
</title>
</head>
<body>
<?php
$mysqli = new mysqli('localhost', 'sharetechrd33', '27050888') or
    die("Connection failed: " . $conn->connect_error);
$mysqli->select_db("work2") or
    die("connection to contact database failed");

$delid = $_POST['delete'];
$querystring = "delete from contact where id='" . $delid . "'";
//echo $querystring;
$result = $mysqli->query($querystring) or die("query failed");

$mysqli->close();
header("Refresh:0; url='list.php'");
?>
</body>
</html>
