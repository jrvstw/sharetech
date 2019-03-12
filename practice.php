<?php session_start(); ?>
<html>
    <head>
        <title> 家瑋的練習題 </title>
    </head>
    <body>
<?php

echo "<p> 1.<br><br>";

echo "101<br>94 104 114<br>87 97 107 117 127<br>";
echo "80 90 100 110 120 130 140<br>73 83 93 103 113 123 133 143 153<br>";

echo "<p></p> 2.<br><br>";

for ($i = 0; $i < 10; $i++)
    $num[$i] = $i;
for ($i = 0; $i < 4; $i++) {
    $random = rand($i, 9);
    $swp = $num[$i];
    $num[$i] = $num[$random];
    $num[$random] = $swp;
}
for ($i = 0; $i < 10; $i++)
    echo "\$num[$i] = $num[$i] <br>";

?>

<form method="get">
    <input type="text" name="guess">
    <input type="submit">
</form>

<?php

$_SESSION[1] = $_GET[guess];
$tmp = 1;
//if (isset($_POST[guess])
    $tmp += 1;
echo $tmp;
//echo "you input $_SESSION[1]";

?>

</p><p> 3. <br><br>

<form method="get">
    Lights: <input type="text" name="lights">
<br>
    People: <input type="text" name="people">
    <input type="submit">
</form>
<?php
$n = $_GET[lights];
$k = $_GET[people];

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
$input = $_GET[string];
while (strlen($input) > 0) {
    echo substr($input, -1);
    $input = substr($input, 0, -1);
}

?>

</p><p> 5. <br><br>
<form method="get">
    Width: <input type="text" name="width">
    <input type="submit">
</form>
<?php
$width = $_GET[width];

$n = $width * $width;
$col = ($width - 1) / 2;
$row = 0;
if ($width % 2 == 0 || $width > 25)
    echo "invalid number<br>";
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
