<form id="userData" action="edit.php" method="post">
    <input type="hidden" value="<?php echo $_POST['isbn']?>" name="isbn">
    <input type="hidden" value="<?php echo $_POST['publisher']?>" name="publisher">
    <input type="hidden" value="<?php echo $_POST['name']?>" name="name">
    <input type="hidden" value="<?php echo $_POST['author']?>" name="author">
    <input type="hidden" value="<?php echo $_POST['price']?>" name="price">
    <input type="hidden" value="<?php echo $_POST['date']?>" name="date">
    <input type="hidden" value="<?php echo $_POST['edit']?>" name="edit">
</form>
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
 * Checks format.
 */
$formatvalid = array("isbn" => false,
                     "publisher" => false,
                     "name" => false,
                     "author" => false,
                     "price" => false,
                     "date" => false);
foreach ($columns as $key => $value) {
    if (preg_match($colreg[$key], $_POST[$key]))
        $formatvalid[$key] = true;
    if ($key == "price" and is_numeric($_POST["price"]) == false)
        $formatvalid["price"] = false;
    if ($key == "date" and strtotime($_POST["date"]) == false)
        $formatvalid["date"] = false;
}

/*
 * goes back to edit.php if the format is invalid,
 * otherwise writes data into "books.txt".
 */
if (($formatvalid["isbn"] and
     $formatvalid["publisher"] and
     $formatvalid["name"] and
     $formatvalid["author"] and
     $formatvalid["price"] and
     $formatvalid["date"]) == false) {
    ?>
    <script type="text/javascript">
        document.getElementById('userData').submit();
        //document.getElementById('editID').submit();
    </script>
    <?php
} else {
    /*
     * Reads "books.txt" and replace the line to be edited.
     */
    $contents = "";
    $lines = file('books.txt');
    foreach ($lines as $line_num => $line) {
        if ($line_num != $_POST['edit'])
            $contents .= $line;
        else
            $contents .= $_POST['isbn'] . "," .
                         $_POST['publisher'] . "," .
                         $_POST['name'] . "," .
                         $_POST['author'] . "," .
                         $_POST['price'] . "," .
                         $_POST['date'] .  "\n";
    }
    //echo $contents;
    file_put_contents('books.txt', $contents , LOCK_EX);
    header("Refresh:0 url='main.php'");
}

?>
