<?php // includes/guard.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

function guard_admin() {
    if (!is_admin()) redirect('/exam_system/public/admin/login.php');
}

function guard_student() {
    if (!is_student()) redirect('/exam_system/public/login.php');
}
