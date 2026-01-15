<?php // includes/auth.php
session_start();

function login_user($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['student_id'] = $user['student_id'];
    $_SESSION['name'] = $user['name'];
}

function logout_user() {
    session_unset();
    session_destroy();
}

function current_user() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'student_id' => $_SESSION['student_id'] ?? null,
        'name' => $_SESSION['name'] ?? null,
    ];
}

function is_admin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function is_student() {
    return ($_SESSION['role'] ?? '') === 'student';
}
