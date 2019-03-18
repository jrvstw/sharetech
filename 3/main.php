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
$lines=file("books.txt");
foreach ($lines as $line_num => $line) {
    //echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) .  "<br />\n";
    $sheet[$line_num] = str_getcsv($line, ",");
}
unset($line);
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
                <option value="1">Publisher</option>
                <option value="2">Book Name</option>
                <option value="3">Author</option>
                <option value="4">Price</option>
                <option value="5">Date</option>
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
    foreach ($sheet as $line_num => $line) {
        echo "<tr>";
        foreach ($sheet[$line_num] as $field_num => $field)
            echo "<td>" . $field . "</td>";
        unset($field);
        echo '<td>
                <form style="display:inline">
                    <button>EDIT</button>
                    <button>DEL</button>
                </form>
              </td>
              </tr>';
    }
    unset($line);
?>
</table>

<form action="add.php" style="display:inline" method="post">
    <button name="add" value="1" type="submit">ADD</button> </form>

</body>
</html>
