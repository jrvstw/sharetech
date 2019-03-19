<html>
<head>
<title> Edit </title>
</head>
<body>

<?php
$lines=file("books.txt");
//echo $lines[$_GET['edit']];
$data = str_getcsv($lines[$_GET['edit']], ",");
?>
<center>
<form action="edit_sent.php" method="post">
    <table border="1">
        <tr>
            <td>ISBN</td>
            <td><input type="text" name="isbn" value=
                "<?php echo $data[0] ?>"readonly></td>
        </tr><tr>
            <td>出版社</td>
            <td><input type="text" name="publisher" value=
                "<?php echo $data[1] ?>"></td>
        </tr><tr>
            <td>書名</td>
            <td><input type="text" name="name" value=
                "<?php echo $data[2] ?>"></td>
        </tr><tr>
            <td>作者</td>
            <td><input type="text" name="author" value=
                "<?php echo $data[3] ?>"></td>
        </tr><tr>
            <td>定價</td>
            <td><input type="text" name="price" value=
                "<?php echo $data[4] ?>"></td>
        </tr><tr>
            <td>發行日</td>
            <td><input type="text" name="date" value=
                "<?php echo $data[5] ?>"></td>
    </table>
    <button name="edit" value="<?php echo $_GET['edit'] ?>" type="submit">
        EDIT</button>
</form>

</center>
</body>
</html>
