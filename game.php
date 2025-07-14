<?php
require __DIR__ . '/config.php';

$easyCurve = array(
    "easy",
    "easy",
    "easy",
    "easy",
    "easy",
    "easy",
    "easy",
    "easy",
    "medium",
    "medium",
    "medium",
    "medium",
    "medium",
    "medium",
    "medium"
);
$normalCurve = array(
    "easy",
    "easy",
    "easy",
    "easy",
    "medium",
    "medium",
    "medium",
    "medium",
    "medium",
    "medium",
    "hard",
    "hard",
    "hard",
    "hard",
    "hard"
);
$hardCurve = array(
    "easy",
    "easy",
    "medium",
    "medium",
    "medium",
    "medium",
    "medium",
    "hard",
    "hard",
    "hard",
    "hard",
    "hard",
    "hard",
    "hard",
    "hard"
);
$easyCurveMoney = array(
    0,
    50,
    100,
    200,
    300,
    400,
    500,
    750,
    1000,
    1250,
    1500,
    2000,
    3000,
    5000,
    7500,
    10000
);
$normalCurveMoney = array(
    0,
    125,
    250,
    500,
    750,
    1250,
    2000,
    2500,
    3750,
    5000,
    7500,
    10000,
    12500,
    25000,
    50000,
    100000
);
$hardCurveMoney = array(
    0,
    500,
    1000,
    2000,
    3000,
    4000,
    5000,
    7500,
    10000,
    15000,
    25000,
    50000,
    100000,
    250000,
    500000,
    1000000
);

$action = $_POST['action'];

switch($action) {
    case "start":
        start_game($_POST['displayName'], $_POST['difficulty']);
        render_full(false);
        break;
    case "answer":
        $cookie = json_decode($_COOKIE['millionaire_game_cookie']);
        if($cookie->friendChoice != 0 && !$cookie->phoneAvailabe){
            $cookie->friendChoice = 0;
            $cookie_data = json_encode($cookie);
            setcookie("millionaire_game_cookie", $cookie_data, time() + (86400 * 1), "/");
            $_COOKIE['millionaire_game_cookie'] = $cookie_data;
        }
        if(check_answer($_POST['answer'], $cookie->answers)){
            if($cookie->step + 1 > 14){
                render_end(false, true);
            } else {
                advance();
                render_full(true);
            }
        } else {
            render_end(false, false);
        }
        break;
    case "walk":
        render_end(true, false);
        break;
    case "lifeline":
        switch($_POST['lifeline']){
            case "half":
                lifelineHalf();
                render_full(false);
                break;
            case "phone":
                lifelinePhone();
                render_full(false);
                break;
            case "new":
                lifelineNew();
                render_full(false);
                break;
        }
        break;
    default:
        start_game("", "normal");
        render_full(false);
        break;
}

function check_answer($answer, $answers){
    for($i = 0; $i < count($answers); $i++){
        if($answer === $answers[$i][0] && $answers[$i][1]){
            return true;
        } 
    }
    return false;
}

function start_game($displayname, $difficulty){
    if (isset($_COOKIE['millionaire_game_cookie'])) {
        unset($_COOKIE['millionaire_game_cookie']); 
    }

    $cookie = new stdClass();
    $cookie->displayname = $displayname;
    $cookie->difficulty = $difficulty;
    $cookie->step = 0;
    $cookie->halfAvailable = true;
    $cookie->phoneAvailable = true;
    $cookie->newAvailable = true;
    $cookie->friendChoice = 0;
    $cookie->startTime = time();

    $question = gen_question($difficulty, 0);

    $cookie->question = $question->question;
    $cookie->answers = $question->answers;

    $cookie_data = json_encode($cookie);
    setcookie("millionaire_game_cookie", $cookie_data, time() + (86400 * 1), "/");
    $_COOKIE['millionaire_game_cookie'] = $cookie_data;
}

function advance(){
    $cookie = json_decode($_COOKIE['millionaire_game_cookie']);

    $cookie->step++;

    $question = gen_question($cookie->difficulty, $cookie->step);

    $cookie->question = $question->question;
    $cookie->answers = $question->answers;

    $cookie_data = json_encode($cookie);
    setcookie("millionaire_game_cookie", $cookie_data, time() + (86400 * 1), "/");
    $_COOKIE['millionaire_game_cookie'] = $cookie_data;
}

function gen_question($difficulty, $step){
    global $easyCurve, $normalCurve, $hardCurve;

    switch($difficulty){
        case "easy":
            $url = "https://opentdb.com/api.php?amount=1&difficulty={$easyCurve[$step]}&type=multiple";
            break;
        case "normal":
            $url = "https://opentdb.com/api.php?amount=1&difficulty={$normalCurve[$step]}&type=multiple";
            break;
        case "hard":
            $url = "https://opentdb.com/api.php?amount=1&difficulty={$hardCurve[$step]}&type=multiple";
            break;
    }

    $w = 1;
    while(true){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response);

        if($data->response_code != 0){
            sleep($w);
            $w++;
        } else {
            break;
        }
    }
    $question = new stdClass();
    $question->question = $data->results[0]->question;

    $question->answers = array(
        array($data->results[0]->correct_answer, true),
        array($data->results[0]->incorrect_answers[0], false),
        array($data->results[0]->incorrect_answers[1], false),
        array($data->results[0]->incorrect_answers[2], false)
    );

    shuffle($question->answers);

    return $question;
}

function lifelineHalf(){
    $cookie = json_decode($_COOKIE['millionaire_game_cookie']);

    $cookie->halfAvailable = false;

    $correct_index = 0;
    for(; $correct_index < count($cookie->answers); $correct_index++){
        if($cookie->answers[$correct_index][1]){
            break;
        }
    }
    
    $values = [0, 1, 2, 3];
    array_splice($values, $correct_index, 1);
    array_splice($values, random_int(0, 2), 1);

    array_splice($cookie->answers, $values[1], 1);
    array_splice($cookie->answers, $values[0], 1);

    $cookie_data = json_encode($cookie);
    setcookie("millionaire_game_cookie", $cookie_data, time() + (86400 * 1), "/");
    $_COOKIE['millionaire_game_cookie'] = $cookie_data;
}

function lifelinePhone(){
    $cookie = json_decode($_COOKIE['millionaire_game_cookie']);
    global $easyCurve, $normalCurve, $hardCurve;

    $cookie->phoneAvailable = false;

    switch($cookie->difficulty){
        case "easy":
            $questionDifficulty = $easyCurve[$cookie->step];
            break;
        case "normal":
            $questionDifficulty = $normalCurve[$cookie->step];
            break;
        case "hard":
            $questionDifficulty = $hardCurve[$cookie->step];
            break;
    }

    $correct_index = 0;
    for(; $correct_index < count($cookie->answers); $correct_index++){
        if($cookie->answers[$correct_index][1]){
            break;
        }
    }

    if(count($cookie->answers) == 4){
        $values = [1, 2, 3, 4];
        switch($questionDifficulty){
            case "easy":
                $weights = [3, 3, 3, 3];
                $weights[$correct_index] = 91;
                break;
            case "medium":
                $weights = [8, 8, 8, 8];
                $weights[$correct_index] = 76;
                break;
            case "hard":
                $weights = [16, 16, 16, 16];
                $weights[$correct_index] = 52;
                break;
        }
    } else {
        $values = [1, 2];
        switch($questionDifficulty){
            case "easy":
                $weights = [10, 10];
                $weights[$correct_index] = 90;
                break;
            case "medium":
                $weights = [25, 25];
                $weights[$correct_index] = 75;
                break;
            case "hard":
                $weights = [50, 50];
                $weights[$correct_index] = 50;
                break;
        }
    }

    $totalWeight = array_sum($weights);
    $randomWeight = mt_rand(0, $totalWeight - 1);

    $runningWeight = 0;
    $selectedValue = null;
    for ($i = 0; $i < count($values); $i++) {
        $runningWeight += $weights[$i];
        
        if ($randomWeight < $runningWeight) {
            $selectedValue = $values[$i];
            break;
        }
    }

    $cookie->friendChoice = $selectedValue;

    $cookie_data = json_encode($cookie);
    setcookie("millionaire_game_cookie", $cookie_data, time() + (86400 * 1), "/");
    $_COOKIE['millionaire_game_cookie'] = $cookie_data;
}

function lifelineNew(){
    $cookie = json_decode($_COOKIE['millionaire_game_cookie']);

    $cookie->newAvailable = false;

    $question = gen_question($cookie->difficulty, $cookie->step);

    $cookie->question = $question->question;
    $cookie->answers = $question->answers;

    $cookie_data = json_encode($cookie);
    setcookie("millionaire_game_cookie", $cookie_data, time() + (86400 * 1), "/");
    $_COOKIE['millionaire_game_cookie'] = $cookie_data;
}

function render_end($walk, $bigWin){
    $cookie = json_decode($_COOKIE['millionaire_game_cookie']);
    global $easyCurveMoney, $normalCurveMoney, $hardCurveMoney;
    switch($cookie->difficulty){
        case "easy":
            $moneyCurve = $easyCurveMoney;
            break;
        case "normal":
            $moneyCurve = $normalCurveMoney;
            break;
        case "hard":
            $moneyCurve = $hardCurveMoney;
            break;
    }

    $diff = time() - $cookie->startTime;

    if(!$walk){
        if($bigWin){
            $cookie->step = 15;
        } else if ($cookie->step >= 10){
            $cookie->step = 10;
        } else if ($cookie->step >= 5){
            $cookie->step = 5;
        } else if ($cookie->step < 5){
            $cookie->step = 0;
        }
    }

    if (!empty($_SESSION['user_id'])) {
        $leaderboardEntry = new stdClass();
        $leaderboardEntry->username = $_SESSION['username'];
        $leaderboardEntry->displayName = (trim($cookie->displayname)) ? $cookie->displayname : $_SESSION['username'];
        $leaderboardEntry->money = $moneyCurve[$cookie->step];
        $leaderboardEntry->time = $diff;

        $inp = file_get_contents("data/leaderboard.json");
        $tempArray = json_decode($inp, true);

        if ($tempArray === null) {
            $tempArray = [];
        }
        
        $found = false;
        foreach ($tempArray as $index => $entry) {
            if ($entry->username === $leaderboardEntry->username && $entry->displayName === $leaderboardEntry->displayName) {
                if ($entry->money === $leaderboardEntry->money) {
                    if($entry->time > $leaderboardEntry->time){
                        $tempArray[$index] = $leaderboardEntry;
                    }
                } elseif ($entry->money < $leaderboardEntry->money){
                    $tempArray[$index] = $leaderboardEntry;
                }
                $found = true;
                break;
            }
        }

        if (!$found) {
            array_push($tempArray, $leaderboardEntry);
        }

        $jsonData = json_encode($tempArray);
        file_put_contents("data/leaderboard.json", $jsonData);
    }

    ?>
<!DOCTYPE html>
<html>
    <head>
        <title>Who Wants To Be A Millionaire</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <main id="endScreen">
            <div id="endTab">
                <div id="endContent">
                    <h1><?php 
                        if($walk){
                            print "Nothing Risked, Nothing Lost!";
                        } else if ($cookie->step < 5) {
                            print "Better Luck Next Time!";
                        } else if ($cookie->step < 10) {
                            print "Not Bad!";
                        } else if ($cookie->step < 15) {
                            print "Great Effort!";
                        } else if ($cookie->step == 15 && $cookie->difficulty === "hard") {
                            print "Congradulations, Millionaire!";
                        } else if ($cookie->step == 15) {
                            print "Bravo, Winner!";
                        } else {
                            print "And That's Game!";
                        }
                    ?></h1>
                    <?php if(!empty($_SESSION['user_id'])){ ?>
                    <h2><?php print $cookie->displayname ?></h2>
                    <?php } ?>
                    <h2>Money: <?php print number_format($moneyCurve[$cookie->step]); ?></h2>
                    <h2>Time: <?php
                        $hours = floor($diff / 3600);
                        $minutes = floor(($diff % 3600) / 60);
                        $seconds = $diff % 60;
                        print sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                    ?></h2>
                    <a href="index.php" class="viewLabel" id="returnBtn">Return To Menu</a>
                </div>
                <div class="lifelineBox endBox">
                    <div class="moneyStep" id="15">
                        <div class="step">15</div>
                        <div>$ <?php print number_format($moneyCurve[15]); ?></div>
                        <span class="grandSpan <?php switch($cookie->step){
                                case 15:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="14">
                        <div class="step">14</div>
                        <div>$ <?php print number_format($moneyCurve[14]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 14:
                                    print "goldOut";
                                    break;
                                case 15:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="13">
                        <div class="step">13</div>
                        <div>$ <?php print number_format($moneyCurve[13]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 13:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="12">
                        <div class="step">12</div>
                        <div>$ <?php print number_format($moneyCurve[12]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 12:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="11">
                        <div class="step">11</div>
                        <div>$ <?php print number_format($moneyCurve[11]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 11:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="10">
                        <div class="step">10</div>
                        <div>$ <?php print number_format($moneyCurve[10]); ?></div>
                        <span class="blueSpan"></span>
                        <span class="gradSpan <?php switch($cookie->step){
                                case 10:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="9">
                        <div class="step">9</div>
                        <div>$ <?php print number_format($moneyCurve[9]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 9:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="8">
                        <div class="step">8</div>
                        <div>$ <?php print number_format($moneyCurve[8]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 8:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="7">
                        <div class="step">7</div>
                        <div>$ <?php print number_format($moneyCurve[7]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 7:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="6">
                        <div class="step">6</div>
                        <div>$ <?php print number_format($moneyCurve[6]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 6:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="5">
                        <div class="step">5</div>
                        <div>$ <?php print number_format($moneyCurve[5]); ?></div>
                        <span class="blueSpan"></span>
                        <span class="gradSpan <?php switch($cookie->step){
                                case 5:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="4">
                        <div class="step">4</div>
                        <div>$ <?php print number_format($moneyCurve[4]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 4:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="3">
                        <div class="step">3</div>
                        <div>$ <?php print number_format($moneyCurve[3]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 3:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="2">
                        <div class="step">2</div>
                        <div>$ <?php print number_format($moneyCurve[2]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 2:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                    <div class="moneyStep" id="1">
                        <div class="step">1</div>
                        <div>$ <?php print number_format($moneyCurve[1]); ?></div>
                        <span class="goldSpan <?php switch($cookie->step){
                                case 1:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
<?php
}

function render_full($startInProgress){
    $cookie = json_decode($_COOKIE['millionaire_game_cookie']);
    global $easyCurveMoney, $normalCurveMoney, $hardCurveMoney;

    switch($cookie->difficulty){
        case "easy":
            $moneyCurve = $easyCurveMoney;
            break;
        case "normal":
            $moneyCurve = $normalCurveMoney;
            break;
        case "hard":
            $moneyCurve = $hardCurveMoney;
            break;
    }

    ?>
<!DOCTYPE html>
<html>
    <head>
        <title>Who Wants To Be A Millionaire</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <div class="domContainer">
            <input type="checkbox" id="lifeline-toggle" class="lifeline-toggle radioInput">
            <label id="lifeline-btn" class="overlay-btn" for="lifeline-toggle">
                Lifeline
            </label>
            <input type="checkbox" id="progress-toggle" class="progress-toggle radioInput" <?php print $startInProgress ? "checked" : "" ?>>
            <label id="progress-btn" class="overlay-btn" for="progress-toggle">
                $<?php print number_format($moneyCurve[$cookie->step]); ?>
            </label>

            <input type="checkbox" id="menu-toggle" class="menu-toggle radioInput">
            <label id="topLeftBtn" for="menu-toggle">
                <img src="menu.svg" height="100%" width="100%">
            </label>

            <div class="menu overlay">
                <div class="menuTab">
                    <label for="menu-toggle" class="close-btn">
                        <img src="x.svg" height="100%" width="100%">
                    </label>

                    <?php if (!empty($_SESSION['user_id'])){ ?>
                    <p>
                        Logged In As:<br>
                        <em><?= htmlspecialchars($_SESSION['username']) ?></em>
                    </p>
                    <?php } ?>
                    <a href="index.php" class="viewLabel">Quit Game</a>
                </div>
            </div>

            <div class="lifeline">
                <form class="startContainer" action="game.php" method="POST">
                    <div class="startSpace">
                        <label id="backButton" for="lifeline-toggle" class="viewLabel">
                            <img src="backArrow.svg" height="100%" width="100%">
                        </label>
                    </div>
                    <div class="lifelineBox">
                        <h1>Lifeline</h1>
                        <?php if($cookie->halfAvailable || $cookie->phoneAvailable || $cookie->newAvailable){ ?>
                        <input type="hidden" name="action" value="lifeline">
                        <?php if($cookie->halfAvailable){ ?>
                        <input type="radio" name="lifeline" id="half" value="half" class="radioInput" required>
                        <label for="half" id="half-btn" class="lifelineOption">50:50</label>
                        <?php } if($cookie->phoneAvailable){ ?>
                        <input type="radio" name="lifeline" id="phone" value="phone" class="radioInput" required>
                        <label for="phone" id="phone-btn" class="lifelineOption">Phone a Friend</label>
                        <?php } if($cookie->newAvailable){ ?>
                        <input type="radio" name="lifeline" id="new" value="new" class="radioInput" required>
                        <label for="new" id="new-btn" class="lifelineOption">New Question</label>
                        <?php } ?>
                        <button type="submit" class="lifelineSelect">Use Lifeline</button>
                        <?php } else { ?>
                        <p>No Lifelines Remaining</p>
                        <?php } ?>
                    </div>
                    <div class="startSpace"></div>
                </form>
            </div>

            <div class="progress">
                <form class="progressContainer" action="game.php" method="POST">
                    <div id="progressButtons">
                        <input type="hidden" name="action" value="walk">
                        <button type="submit" class="viewLabel">
                            Walk Home
                        </button>
                        <label for="progress-toggle" class="viewLabel">
                            Continue
                        </label>
                    </div>
                    <div class="lifelineBox">
                        <div class="moneyStep" id="15">
                            <div class="step">15</div>
                            <div>$ <?php print number_format($moneyCurve[15]); ?></div>
                            <span class="goldSpan"></span>
                        </div>
                        <div class="moneyStep" id="14">
                            <div class="step">14</div>
                            <div>$ <?php print number_format($moneyCurve[14]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 14:
                                    print "goldOut";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="13">
                            <div class="step">13</div>
                            <div>$ <?php print number_format($moneyCurve[13]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 13:
                                    print "goldOut";
                                    break;
                                case 14:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="12">
                            <div class="step">12</div>
                            <div>$ <?php print number_format($moneyCurve[12]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 12:
                                    print "goldOut";
                                    break;
                                case 13:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="11">
                            <div class="step">11</div>
                            <div>$ <?php print number_format($moneyCurve[11]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 11:
                                    print "goldOut";
                                    break;
                                case 12:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="10">
                            <div class="step">10</div>
                            <div>$ <?php print number_format($moneyCurve[10]); ?></div>
                            <span class="blueSpan"></span>
                            <span class="gradSpan <?php switch($cookie->step){
                                case 10:
                                    print "goldOut";
                                    break;
                                case 11:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="9">
                            <div class="step">9</div>
                            <div>$ <?php print number_format($moneyCurve[9]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 9:
                                    print "goldOut";
                                    break;
                                case 10:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="8">
                            <div class="step">8</div>
                            <div>$ <?php print number_format($moneyCurve[8]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 8:
                                    print "goldOut";
                                    break;
                                case 9:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="7">
                            <div class="step">7</div>
                            <div>$ <?php print number_format($moneyCurve[7]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 7:
                                    print "goldOut";
                                    break;
                                case 8:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="6">
                            <div class="step">6</div>
                            <div>$ <?php print number_format($moneyCurve[6]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 6:
                                    print "goldOut";
                                    break;
                                case 7:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="5">
                            <div class="step">5</div>
                            <div>$ <?php print number_format($moneyCurve[5]); ?></div>
                            <span class="blueSpan"></span>
                            <span class="gradSpan <?php switch($cookie->step){
                                case 5:
                                    print "goldOut";
                                    break;
                                case 6:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="4">
                            <div class="step">4</div>
                            <div>$ <?php print number_format($moneyCurve[4]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 4:
                                    print "goldOut";
                                    break;
                                case 5:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="3">
                            <div class="step">3</div>
                            <div>$ <?php print number_format($moneyCurve[3]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 3:
                                    print "goldOut";
                                    break;
                                case 4:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="2">
                            <div class="step">2</div>
                            <div>$ <?php print number_format($moneyCurve[2]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 2:
                                    print "goldOut";
                                    break;
                                case 3:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                        <div class="moneyStep" id="1">
                            <div class="step">1</div>
                            <div>$ <?php print number_format($moneyCurve[1]); ?></div>
                            <span class="goldSpan <?php switch($cookie->step){
                                case 1:
                                    print "goldOut";
                                    break;
                                case 2:
                                    print "goldIn";
                                    break;
                            }?>"></span>
                        </div>
                    </div>
                </form>
            </div>

            <div id="bsButtonHider"></div>

            <main class="gamePage">
                <form action="game.php" method="POST">
                    <div id="question"><?php print $cookie->question; ?></div>
                    <input type="hidden" name="action" value="answer">
                    <button type="submit" id="answerSubmit">Are you sure? Click Here to submit Final Answer</button>
                    <div class="answers">
                        <input type="radio" name="answer" id="answer1" value="<?php print $cookie->answers[0][0]; ?>" class="radioInput" required>
                        <label for="answer1" id="answer1-btn" class="answerButton<?php print ($cookie->friendChoice == 1) ? " friendHint" : ""; ?><?php print ($cookie->answers[0][1]) ? " correct" : " incorrect"; ?>"><?php print $cookie->answers[0][0]; ?></label>
                        <input type="radio" name="answer" id="answer2" value="<?php print $cookie->answers[1][0]; ?>" class="radioInput" required>
                        <label for="answer2" id="answer2-btn" class="answerButton<?php print ($cookie->friendChoice == 2) ? " friendHint" : ""; ?><?php print ($cookie->answers[1][1]) ? " correct" : " incorrect"; ?>"><?php print $cookie->answers[1][0]; ?></label>
                    </div>
                    <?php 
                        if(count($cookie->answers) > 2){
                    ?>
                    <div class="answers">
                        <input type="radio" name="answer" id="answer3" value="<?php print $cookie->answers[2][0]; ?>" class="radioInput" required>
                        <label for="answer3" id="answer3-btn" class="answerButton<?php print ($cookie->friendChoice == 3) ? " friendHint" : ""; ?><?php print ($cookie->answers[2][1]) ? " correct" : " incorrect"; ?>"><?php print $cookie->answers[2][0]; ?></label>
                        <input type="radio" name="answer" id="answer4" value="<?php print $cookie->answers[3][0]; ?>" class="radioInput" required>
                        <label for="answer4" id="answer4-btn" class="answerButton<?php print ($cookie->friendChoice == 4) ? " friendHint" : ""; ?><?php print ($cookie->answers[3][1]) ? " correct" : " incorrect"; ?>"><?php print $cookie->answers[3][0]; ?></label>
                    </div>
                    <?php } ?>
                </form>
            </main>
        </div>
    </body>
</html>
    <?php
} 
?>