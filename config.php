<?php
session_start();

define('USERS_FILE', __DIR__ . '/data/users.json');
define('LEADERBOARD_FILE', __DIR__ . '/data/leaderboard.json');

function load_users(): array {
    $json = file_get_contents(USERS_FILE);
    return $json ? json_decode($json, true) : [];
}
function save_users(array $users): void {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}
function load_scores(): array {
    $json = file_get_contents(LEADERBOARD_FILE);
    return $json ? json_decode($json, true) : [];
}
function save_scores(array $scores): void {
    file_put_contents(LEADERBOARD_FILE, json_encode($scores, JSON_PRETTY_PRINT));
}
function next_id(array $items): int {
    $max = 0;
    foreach ($items as $i) {
        if (isset($i['id']) && $i['id'] > $max) $max = $i['id'];
    }
    return $max + 1;
}
