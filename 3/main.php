<html>
<head>
<title> 書籍管理系統 </title>
</head>
<body>

匯入資料:
<form action="select.php" style="display:inline" method="post">
    <button name="select" value="1" type="submit">選擇檔案</button>
</form>
未選擇任何檔案<br>

<?php

// stores "books.txt" to the array "sheet".
$lines=file("books.txt");
foreach ($lines as $line_num => $line) {
    //echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) .  "<br />\n";
    $sheet[$line_num] = str_getcsv($line, ",");
    $order[$line_num] = $line_num;
}
unset($line);

/*
if (empty($_GET['sort'])
    $_GET['sort'] = "1";
if (empty($_GET['order'])
    $_GET['order'] = "asc";
 */
?>

<table border=1>
    <tr> 資料匯出:
        <form action="export.php" style="display:inline" method="post">
            <button name="export" value="1" type="submit">匯出
                </button>
        </form>
        <form action="sort.php" style="display:inline" method="get">
            Sort:
            <select name="sort" onchange="this.form.submit()">
<?php
// echo column names
$column = array("", "Publisher", "Book Name", "Author", "Price", "Date");
foreach ($column as $key => $value) {
    if ($key == 0)
        continue;
    echo "<option value='$key'>$value</option>";
}
?>
            </select>
            Order:
            <select name="order">
                <option value="asc">ASC</option>
                <option value="dsc">DSC</option>
            </select>
        </form>

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
    // deals with EDIT and DEL buttons.
    foreach ($sheet as $line_num => $line) {
        echo "<tr>";
        foreach ($sheet[$line_num] as $field_num => $field)
            echo "<td>" . $field . "</td>";
        unset($field);
        echo "<td>
                <form action='edit.php' style='display:inline' method='get'>
                    <button name='edit' value='$line_num' type='submit'>
                        EDIT</button>
                </form>
                <form action='del.php' style='display:inline' method='get'>
                    <button name='del' value='$line_num' type='submit'>
                        DEL</button>
                </form>
              </td>
              </tr>";
    }
    unset($line);
?>
</table>

<form action="add.php" style="display:inline" method="post">
    <button name="add" value="1" type="submit">ADD</button> </form>

</body>
</html>
