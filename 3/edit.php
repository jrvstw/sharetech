<html>
<head>
<title> Edit </title>
</head>
<body>

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
 * Reads the data to be edited
 */
$lines=file("books.txt");
//echo $lines[$_POST['edit']];
$datatmp = str_getcsv($lines[$_POST['edit']], ",");
$data = array( "isbn" => $datatmp[0],
              "publisher" => $datatmp[1],
              "name" => $datatmp[2],
              "author" => $datatmp[3],
              "price" => $datatmp[4],
              "date" => $datatmp[5]);
foreach ($columns as $col => $name)
    if (empty($_POST[$col]) == false)
        $data[$col] = $_POST[$col];
?>
<center>
<form action="edit_sent.php" method="post">
    <table border="1">
        <?php
        /*
         * prints every field
         */
        foreach ($columns as $col => $name) {
            echo '
        <tr>
            <td>' . $name . '</td>
            <td><input type="text" name="' . $col . '" value="' . $data[$col] . '"';
            if ($col == "isbn")
                echo " readonly";
            echo '>';
            if (preg_match($colreg[$col], $data[$col]) == false or
                ($col == "price" and is_numeric($data["price"]) == false) or
                ($col == "date" and strtotime($data["date"]) == false))
                echo "invalid format";
            echo '
            </td>
        </tr>
            ';
        }
        ?>
    </table>
    <button name="edit" value="<?php echo $_POST['edit'] ?>" type="submit">
        EDIT</button>
</form>

</center>
</body>
</html>
