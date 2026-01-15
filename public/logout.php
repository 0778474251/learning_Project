<?php // public/logout.php
require_once __DIR__ . '/../includes/auth.php';
logout_user();
header('Location: /exam_system/public/index.php');
