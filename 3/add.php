<html>
<head>
<title> Add </title>
</head>
<body>

<center>
<form action="add_sent.php" method="post">
    <table border="1">
        <?php
/*
 * defines column format
 */
$columns = array("isbn" => "ISBN",
                 "publisher" => "出版社",
                 "name" => "書名",
                 "author" => "作者",
                 "price" => "定價",
                 "date" => "發行日");
$colreg = array("isbn" => "/^([0-9]{3}-){3}[0-9]$/",
                 "publisher" => "/^[^,]+$/",
                 "name" => "/^[^,]+$/",
                 "author" => "/^[^,]+$/",
                 "price" => "/^[0-9]+$/",
                 "date" => "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/");

/*
 * prints every field
 */
        foreach ($columns as $col => $name) {
            echo '
        <tr>
            <td>' . $name . '</td>
            <td><input type="text" name="' . $col . '" value="' . $_POST[$col] . '"';
            echo '>';
            if (isset($_POST[$col]) == true)
                if (preg_match($colreg[$col], $_POST[$col]) == false or
                    ($col == "price" and is_numeric($_POST["price"]) == false) or
                    ($col == "date" and strtotime($_POST["date"]) == false))
                    echo "invalid format";
            echo '
            </td>
        </tr>
            ';
        }
        ?>
    </table>
    <input type="submit" value="ADD">
</form>

</center>
</body>
</html>
