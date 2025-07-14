<?php
require __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
  $users  = load_users();
  $scores = load_scores();
  $act    = $_POST['action'];

  if ($act === 'signup') {
    $u = trim($_POST['username']);
    $p = $_POST['password'];
    
    foreach ($users as $existing) {
      if ($existing['username'] === $u) {
        $_SESSION['signupError'] = 'That username is already taken.';
        header('Location: index.php');
        exit;
      }
    }
    $id   = next_id($users);
    $hash = password_hash($p, PASSWORD_DEFAULT);
    $users[] = [
      'id'            => $id,
      'username'      => $u,
      'password_hash' => $hash
    ];
    save_users($users);
    $_SESSION['user_id']     = $id;
    $_SESSION['username']    = $u;

  } elseif ($act === 'login') {
    foreach ($users as $u) {
      if ($u['username'] === trim($_POST['username'])
          && password_verify($_POST['password'], $u['password_hash'])) {
        $_SESSION['user_id']     = $u['id'];
        $_SESSION['username'] = $u['username'];
        break;
      }
    }

  } elseif ($act === 'submit_score' && !empty($_SESSION['user_id'])) {
    $scores[] = [
      'id'         => next_id($scores),
      'user_id'    => $_SESSION['user_id'],
      'score'      => (int)$_POST['score'],
      'time_taken' => date('c'),
    ];
    save_scores($scores);
  }

  header('Location: index.php');
  exit;
}

if (isset($_GET['action']) && $_GET['action']==='logout') {
  session_destroy();
  header('Location: index.php');
  exit;
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Who Wants To Be A Millionaire</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <input type="checkbox" id="menu-toggle" class="menu-toggle radioInput">
        <label id="topLeftBtn" for="menu-toggle">
            <img src="menu.svg" height="100%" width="100%">
        </label>
        <?php
          $showSignup = ! empty($_SESSION['signupError']);
        ?>
        <div class="menu overlay">
            <div class="menuTab">
                <label for="menu-toggle" class="close-btn">
                    <img src="x.svg" height="100%" width="100%">
                </label>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <p>
                        Logged In As:<br>
                        <em><?= htmlspecialchars($_SESSION['username']) ?></em>
                    </p>
                    <a href="?action=logout" class="viewLabel">Log Out</a>
                <?php else: ?>
                    <form id="login-form" method="post" class="auth-form">
                         <input type="hidden" name="action" value="login">
                         <p>Username</p>
                         <input type="text" name="username" required>
                         <p>Password</p>
                         <input type="password" name="password" required>
                         <button type="submit" class="viewLabel">Log In</button>
                         <p class="toggle-link">
                             No account?
                             <a href="#"
                                onclick="document.getElementById('login-form').style.display='none';
                                         document.getElementById('signup-form').style.display='block';
                                         return false;">
                               Sign Up
                             </a>
                         </p>
                    </form>
                    <?php if (!empty($_SESSION['signupError'])): ?>
                      <p class="error"><?= htmlspecialchars($_SESSION['signupError']) ?></p>
                      <?php unset($_SESSION['signupError']); ?>
                    <?php endif; ?>
                    <form id="signup-form" method="post" class="auth-form" style="display:none;">
                        <input type="hidden" name="action" value="signup">
                        <p>Username</p>
                        <input type="text" name="username" required>
                        <p>Password</p>
                        <input type="password" name="password" required>
                        <button type="submit" class="viewLabel">Create Account</button>
                        <p class="toggle-link">
                          Have an account?
                          <a href="#"
                             onclick="document.getElementById('signup-form').style.display='none';
                                      document.getElementById('login-form').style.display='block';
                                      return false;">
                            Log In
                          </a>
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <main class="homepage">
            <input type="radio" name="view" id="view1" class="radioInput" checked>
            <input type="radio" name="view" id="view2" class="radioInput">
            <input type="radio" name="view" id="view3" class="radioInput">

            <div class="view home">
                <img src="logo.png" id="logo">
                <div class="homeButtons">
                    <label for="view2" class="viewLabel">Start Game</label>
                    <label for="view3" class="viewLabel">Leaderboard</label>
                </div>
            </div>
            <div class="view start">
                <form class="startContainer" action="game.php" method="post">
                    <input type="hidden" name="action" value="start">
                    <div class="startSpace">
                        <label id="backButton" for="view1" class="viewLabel">
                            <img src="backArrow.svg" height="100%" width="100%">
                        </label>
                    </div>
                    <div class="startDialogue">
                        <h1>New Game</h1>
                        <?php if(!empty($_SESSION['user_id'])){ ?>
                        <p>Display Name</p>
                        <input type="text" name="displayName" value="" style="width: 90%;">
                        <?php } else { ?>
                        <input type="hidden" name="displayName" id="displayName" value="">
                        <?php } ?>
                        <p>Difficulty</p>
                        <div id="difficultyContainer">
                            <input type="radio" name="difficulty" id="easy" value="easy" class="radioInput">
                            <label for="easy" id="easyButton" class="difficulty">Easy</label>
                            <input type="radio" name="difficulty" id="normal" value="normal" class="radioInput" checked>
                            <label for="normal" id="normalButton" class="difficulty">Normal</label>
                            <input type="radio" name="difficulty" id="hard" value="hard" class="radioInput">
                            <label for="hard" id="hardButton" class="difficulty">Hard</label>
                        </div>
                    </div>
                    <div class="startSpace">
                        <button type="submit" id="goButton">
                            <img src="forwardArrow.svg" height="100%" width="100%">
                        </button>
                    </div>
                </form>
            </div>
            <div class="view leaderboard">
                <div class="leaderboardContainer">
                    <div class="leaderboardSpace">
                        <label id="backButton" for="view1" class="viewLabel">
                            <img src="backArrow.svg" height="100%" width="100%">
                        </label>
                    </div>
                    <?php 
                      $scores = load_scores();
                      function sortByScoreThenTime($a, $b) {
                        if ($a['money'] == $b['money']) {
                          return $a['time'] <=> $b['time'];
                        }
                        return $b['money'] <=> $a['money'];
                      }
                      usort($scores, 'sortByScoreThenTime');
                    ?>
                    <div class="leaderboardBox">
                        <h2>Top Scores</h2>
                        <div class="leaderboardTableContainer">
                          <table>
                            <?php 
                              for($i = 0; $i < count($scores); $i++){
                                ?>
                                <tr>
                                  <td class="leaderboardPlace"><?php print $i + 1; ?></td>
                                  <td class="leaderboardName" title="<?php print $scores[$i]['username'] ?>"><?php print $scores[$i]['displayName']; ?></td>
                                  <td class="leaderboardMoney">$ <?php print $scores[$i]['money']; ?></td>
                                  <td class="leaderboardTime"><?php print sprintf("<td>%02d:%02d:%02d</td>", floor($scores[$i]['time'] / 3600), floor(($scores[$i]['time'] % 3600) / 60), $scores[$i]['time'] % 60); ?></td>
                                </tr>
                                <?php
                              }
                            ?>
                          </table>
                        </div>
                    </div>
                    <div class="leaderboardSpace"></div>
                </div>
            </div>
        </main>
    </body>
</html>