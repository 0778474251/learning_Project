<?php // includes/helpers.php
function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function json_to_array($json) {
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}

function now() {
    return date('Y-m-d H:i:s');
}
