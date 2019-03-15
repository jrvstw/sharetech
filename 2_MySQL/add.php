<html>
<head>
<title> Add Record </title>
</head>
<body>

<center>
<h1> Contact List </h1>
<h2> Add Record </h2>
<hr>

<form action="add_sent.php" method="post">
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

</center>

</body>
</html>
