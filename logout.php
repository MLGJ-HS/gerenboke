<?php
// 启动会话
session_start();

// 清除所有会话变量
$_SESSION = array();

// 如果使用了会话Cookie，则删除会话Cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// 销毁会话
session_destroy();

// 重定向到首页
header("Location: index.php");
exit;
?> 