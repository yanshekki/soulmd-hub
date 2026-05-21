<?php
session_start();
session_unset();
session_destroy();

// 刪除 Remember Me Cookie
setcookie('remember_token', '', time() - 3600, '/');

header('Location: /');
exit;