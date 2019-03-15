<html>
<head>
<title> adding contact </title>
</head>
<body>

<?php
if (isset($_POST['name'])) {
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $birthday = $_POST['birthday'];
    $address = $_POST['address'];
    $email = $_POST['email'];

    $formatcheck = true;
    if (preg_match("/^[a-zA-Z]+$/", $name) == false) {
        $formatcheck = false;
        echo "Name format invalid.";
    }
    if (preg_match("/^Male$|^Female$/", $gender) == false) {
        $formatcheck = false;
        echo "Gender format invalid.";
    }
    if (preg_match("/^[0-9]{4}-[0-9]{6}$/", $phone) == false) {
        $formatcheck = false;
        echo "Phone format invalid.";
    }
    if (preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $birthday) == false) {
        $formatcheck = false;
        echo "Birthday format invalid.";
    }
    if (preg_match("/^[a-zA-z0-9 ']+$/", $address) == false) {
        $formatcheck = false;
        echo "Address format invalid.";
    }
    if (preg_match("/^[a-zA-Z0-9_+-]+(\.[a-zA-Z0-9_+-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*$/", $email) == false) {
        $formatcheck = false;
        echo "email format invalid.";
    }

    $mysqli = new mysqli('localhost', 'sharetechrd33', '27050888') or
        die("Connection failed: " . $conn->connect_error);
    $mysqli->select_db("work2") or
        die("connection to contact database failed");

    if ($name != NULL && $formatcheck == true) {
        $querystring = 'insert into contact values(NULL, "' .
            $name . '", "' . $gender . '", "' . $phone . '", "' .
            $birthday . '", "' . $address . '", "' . $email . '")';
        //echo $querystring;
        $result = $mysqli->query($querystring) or die("query failed");
        //$result->free();
        //^^^^^^^^^^^^^^^^ why this doesn't work?
    }

    $mysqli->close();
}
header("Refresh:0; url='list.php'");
?>

</body>
</html>
