<?php session_start(); ?>
<html>
    <head>
        <title> 家瑋的練習題: 1, 3, 4, 5 </title>
    </head>
    <body>

<p> 1. <br><br>

<?php
$first = 101;
for ($i = 0; $i < 5; $i++) {
    for ($j = 0; $j < 2 * $i + 1; $j++)
        echo ($first - 7 * $i + 10 * $j) . " ";
    echo "<br>";
}
?>

</p><p> 3. <br><br>

<form method="get">
    Lights: <input type="text" name="lights">
<br>
    People: <input type="text" name="people">
    <input type="submit">
</form>

<?php
$n = $_GET['lights'];
$k = $_GET['people'];

echo "Lights on: ";
for ($i = 1; $i <= $n; $i++) {
    $light = false;
    for ($j = 1; $j <= $k; $j++)
        if ($i % $j == 0)
            $light = !$light;
    if ($light == true)
        echo "$i ";
}
?>

</p><p> 4. <br><br>

<form method="get">
    String: <input type="text" name="string">
    <input type="submit">
</form>

<?php
function print_reverse($input)
{
    if (strlen($input) == 0)
        return;
    echo substr($input, -1);
    print_reverse(substr($input, 0, -1));
    return;
}

print_reverse($_GET['string']);
?>

</p><p> 5. <br><br>

<form method="get">
    Width: <input type="text" name="width">
    <input type="submit">
</form>

<?php
$width = $_GET['width'];

$n = $width * $width;
$col = ($width - 1) / 2;
$row = 0;

if ($width % 2 == 0 || $width > 25 || is_numeric($width) == false)
    echo "invalid input<br>";
else {
    for ($i = 1; $i <= $n; $i++) {
        $matrix[$row][$col] = $i;
        if ($i % $width == 0) {
            $row = ($row + 1) % $width;
        } else {
            $col = ($col + 1) % $width;
            $row = ($row + $width - 1) % $width;
        }
    }
    echo "<table border=1 bordercolor=#000000 cellpadding=5>";
    for ($i = 0; $i < $width; $i++) {
        echo "<tr>";
        for ($j = 0; $j < $width; $j++) {
            echo "<td bordercolor=#000000 width=30>";
            echo $matrix[$i][$j];
            echo "</td>  ";
        }
        echo "<tr>";
    }
    echo "</table>";
}
?>
    </body>
</html>
