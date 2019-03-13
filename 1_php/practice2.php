<html>
<head>
<title> 家瑋的練習題: 2</title>
</head>
<body>

 2. 1A2B <br><br>

<?php
session_start();

function generateAnswer() {
    $numberStack = range(0, 9);
    shuffle($numberStack);
    $answer = '';
    for ($i = 0; $i < 4; $i++)
        $answer .= $numberStack[$i];
    return $answer;
}

function checkAnswer($guess, $answer)
{
    if (is_numeric($guess) == false || strlen($guess) != 4)
        return "invalid input<br>";
    $counta = 0;
    $countb = 0;
    for ($i = 0; $i < 4; $i++)
        for ($j = 0; $j < 4; $j++)
            if (substr($guess, $i, 1) == substr($answer, $j, 1)) {
                if ($i == $j)
                    $counta++;
                else
                    $countb++;
            }
    if ($counta == 4) {
        $_SESSION['answer'] = generateAnswer();
        return "bingo!";
    } else
        return $counta . "A" . $countb . "B";
        //return $counta . "A" . $countb . "B, " . $answer;
}
?>

<form method="post">
    <input type="text" name="guess" value="">
    <input type="submit" name="try" value="Try">
</form>

<?php
if (empty($_SESSION['answer']))
    $_SESSION['answer'] = generateAnswer();

if (isset($_POST['guess'])){
    $result = checkAnswer($_POST['guess'], $_SESSION['answer']);
    $_SESSION['info'][] = array($_POST['guess'] => $result);
}

if (isset($_SESSION['info'])) {
    for ($i = count($_SESSION['info']) - 1; $i >= 0; $i--) {
        foreach ($_SESSION['info'][$i] as $guess => $result){
            echo '<p>' . $guess . ': ' . $result . '</p>';
        }
        if ($result == "bingo!")
            session_destroy();
    }
}
?>

</body>
</html>
