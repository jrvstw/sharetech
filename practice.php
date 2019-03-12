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

<INPUT TYPE=TEXT NAME="name" VALUE="Glen Morris">

<?php
echo "name is $name<br>";

echo "</p><p> 3. <br><br>";

$n = 200;
$k = 200;

for ($i = 1; $i <= $n; $i++) {
    $light = false;
    for ($j = 1; $j <= $k; $j++)
        if ($i % $j == 0)
            $light = !$light;
    if ($light == true)
        echo "$i ";
}

echo "</p><p> 4. <br><br>";

$input = "abcdefghijklmnopqrstuvwxyz";
while (strlen($input) > 0) {
    echo substr($input, -1);
    $input = substr($input, 0, -1);
}

echo "</p><p> 5. <br><br>";

$width = 5;

$n = $width * $width;
$col = ($width + 1) / 2;
$row = 0;

for ($i = 1; $i <= $n; $i++) {
    $matrix[$row][$col] = $i;
    if ($i % $width == 0)
        $row--;
    $col += 1;
    $row = ($row + $n - 1) % $n;
}

for ($i = 0; $i < 5; $i++) {
    for ($j = 0; $j < 5; $j++) {
        echo $matrix[$row][$col];
        echo "  ";
    }
    echo "\n";
}

?>

    </body>
</html>
