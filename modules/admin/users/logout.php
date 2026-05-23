<?php
require_once '../../../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

redirect_to('modules/admin/users/login.php');


