<?php
echo "hi";
$lines=file("books.txt");
foreach ($lines as $line_num => $line) {
    //echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) .  "<br />\n";
    $sheet[$line_num] = str_getcsv($line, ",");
}
unset($line);

function cmp($a, $b)
{
    $value = $a[$_GET['sort']] - $b[$_GET['sort']];
    if ($value == 0)
        return 0;
    if ($value > 0 xor $_GET['order'] == 'dsc')
        return 1;
    else
        return -1;
}

print_r($sheet);
uasort($sheet, 'cmp');

echo "<br><br>";

$current ="";
foreach ($sheet as $line_num => $line) {
    foreach ($sheet[$line_num] as $field_num => $field)
        $current .= $field . ",";
    $current = rtrim($current, ",") . "\n";
}
echo $current;
if (file_put_contents('books.txt', $current) == true)
    echo "ok";
else
    echo "no ok";

header("Refresh:0; url='main.php'");
?>
