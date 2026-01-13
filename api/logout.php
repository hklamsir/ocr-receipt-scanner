<?php
// 登出 API
session_start();
session_destroy();

header('Location: ../login.php');
exit;
