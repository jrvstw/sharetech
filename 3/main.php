<?php

/*
 * A function used to sort book lists.
 */
function cmp($a, $b)
{
    $value = strnatcmp($a[$_GET['sort']],
                       $b[$_GET['sort']]);
    if ($value == 0)
        return 0;
    if ($value > 0 xor $_GET['order'] == 'dsc')
        return 1;
    else
        return -1;
}

/*
 * Reads "books.txt" into the array $table.
 */
$database = "books.txt";
$lines=file($database);
foreach ($lines as $line_num => $line) {
    //echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) .  "<br />\n";
    $table[$line_num] = str_getcsv($line, ",");
    $table[$line_num][6] = $line_num;
}
unset($line);

/*
 * Sorts book list and saves list into "sorted.txt"
 */
if (isset($_GET['sort'], $_GET['order']))
    uasort($table, 'cmp');
$contents = "";
foreach ($table as $line_num => $line) {
    foreach ($line as $field_num => $field) {
        if ($field_num == "6")
            continue;
        $contents .= $field . ",";
    }
    $contents .= "\n";
}
$contents = preg_replace("/,\n/", "\n", $contents);
file_put_contents('sorted.txt', $contents, LOCK_EX);

?>

<html>
<head>
    <title> 書籍管理系統 </title>
</head>
<body>

<center>

<div style="width:800px">
    <div style="text-align:left">
        <form enctype="multipart/form-data" action="imported.php" method="post">
            匯入資料:
            <input type="file" name="import" onchange="this.form.submit()">
        </form>
        <form action="export.php" style="display:inline" method="post">
            資料匯出:
            <button name="export" value="1" type="submit">匯出 </button>
        </form>
        <span style="float:right">
        <form action="main.php" style="display:inline" method="get">
            排序:
            <select name="sort" onchange="this.form.submit()">
                <?php
                /*
                 * prints sorting options
                 */
                $column = array("ISBN", "出版社", "書名", "作者", "定價", "發行日");
                foreach ($column as $key => $value) {
                    if ($key == 0)
                        continue;
                    if ($_GET['sort'] == $key)
                        echo "<option selected='selected' value='$key'>$value</option>";
                    else
                        echo "<option value='$key'>$value</option>";
                }
                unset($value);
                ?>
            </select>
            方向:
            <select name="order" onchange="this.form.submit()">
                <?php // print order options
                $column = array("ASC" => "asc", "DSC" => "dsc");
                foreach ($column as $key => $value) {
                    if ($_GET['order'] == $value)
                        echo "<option selected='selected' value='$value'>$key</option>";
                    else
                        echo "<option value='$value'>$key</option>";
                }
                unset($value);
                ?>
            </select>
        </form>
        </span>
    </div>
    <table border=1 align="center" style="width:800px">
        <tr>
            <td>ISBN</td>
            <td>出版社</td>
            <td>書名</td>
            <td>作者</td>
            <td>定價</td>
            <td>發行日</td>
            <td>編輯/刪除</td>
        </tr>
        <?php
            /*
             * prints every line
             */
            foreach ($table as $key => $line) {
                echo "<tr>";
                foreach ($line as $field_num => $field) {
                    if ($field_num == "6")
                        continue;
                    echo "<td>" . $field . "</td>";
                }
                unset($field);
                echo "<td>
                        <form action='edit.php' style='display:inline' method='post'>
                            <button name='edit' value='$line[6]' type='submit'>
                                EDIT</button>
                        </form>
                        <form action='del.php' style='display:inline' method='get'>
                            <button name='del' value='$line[6]' type='submit'>
                                DEL</button>
                        </form>
                      </td>
                      </tr>";
            }
            unset($line);
        ?>
    </table>
    <form action="add.php" style="display:inline" method="post">
        <button name="add" value="1" type="submit">ADD</button>
    </form>
</div>
</center>

</body>
</html>
