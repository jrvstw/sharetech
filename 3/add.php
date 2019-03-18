<html>
<head>
<title> Add </title>
</head>
<body>

<center>
<form action="add_sent.php" method="post">
    <table border="1">
        <tr>
            <td>ISBN</td>
            <td><input type="text" name="isbn"></td>
        </tr><tr>
            <td>出版社</td>
            <td><input type="text" name="publisher"></td>
        </tr><tr>
            <td>書名</td>
            <td><input type="text" name="name"></td>
        </tr><tr>
            <td>作者</td>
            <td><input type="text" name="author"></td>
        </tr><tr>
            <td>定價</td>
            <td><input type="text" name="price"></td>
        </tr><tr>
            <td>發行日</td>
            <td><input type="text" name="date"></td>
    </table>
    <input type="submit" value="ADD">
</form>

</center>
</body>
</html>
