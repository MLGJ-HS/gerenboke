<?php
require_once 'db.php';

// 启动会话
session_start();

// 如果用户已经登录，重定向到首页
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = false;

// 处理注册表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // 验证
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
        $error = '所有字段都不能为空';
    } elseif ($password !== $confirm_password) {
        $error = '两次输入的密码不匹配';
    } elseif (strlen($password) < 6) {
        $error = '密码长度不能少于6个字符';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的电子邮箱地址';
    } else {
        try {
            // 检查用户名是否已被使用
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error = '用户名已被使用，请选择其他用户名';
            } else {
                // 检查邮箱是否已被使用
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error = '邮箱已被使用，请使用其他邮箱或尝试找回密码';
                } else {
                    // 注册新用户
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, create_time) VALUES (?, ?, ?, NOW())");
                    $success = $stmt->execute([$username, $password, $email]); // 实际应用中应使用password_hash
                    
                    if ($success) {
                        // 获取新用户ID
                        $user_id = $pdo->lastInsertId();
                        
                        // 注册成功，自动登录
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        
                        // 设置成功标记，用于显示成功信息
                        $success = true;
                    } else {
                        $error = '注册失败，请稍后再试';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = '数据库错误：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册 - 个人博客</title>
    <style>
        /* 全局重置样式 - 重置所有元素的边距和内边距 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* 主体样式 - 黑白配色主题 */
        body {
            font-family: 'Microsoft YaHei', sans-serif;
            line-height: 1.6;
            background-color: #f0f0f0; /* 浅灰色背景 */
            color: #212121; /* 深灰色文字 */
        }

        .container {
            width: 1200px;
            margin: 0 auto;
        }

        /* 头部样式 - 黑色背景白色文字 */
        header {
            background-color: #000; /* 黑色背景 */
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        /* Logo样式 - 白色突出显示 */
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #fff; /* 白色logo */
            text-decoration: none;
        }

        /* 导航菜单样式 */
        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 20px;
        }

        /* 导航链接样式 */
        nav ul li a {
            text-decoration: none;
            color: #ddd; /* 浅灰白色链接 */
            font-size: 15px;
            transition: color 0.3s, background-color 0.3s;
            padding: 5px 10px;
        }

        /* 导航链接悬停效果 */
        nav ul li a:hover {
            color: #fff; /* 纯白色 */
            background-color: rgba(255, 255, 255, 0.1); /* 半透明白色背景 */
            border-radius: 4px;
        }

        /* Banner样式 - 白色背景 */
        .banner {
            height: 150px;
            background-color: #000;
            margin-top: 80px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .banner h1 {
            color: #fff;
            font-size: 2.5rem;
            text-align: center;
        }

        /* 内容区域通用样式 */
        .content-section {
            padding: 60px 0;
        }

        /* 注册表单容器 */
        .register-container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .register-title {
            text-align: center;
            margin-bottom: 30px;
            color: #000;
            font-size: 24px;
        }

        /* 表单样式 */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #000;
            outline: none;
        }

        .submit-btn {
            width: 100%;
            background-color: #000;
            color: #fff;
            border: none;
            padding: 15px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background-color: #333;
        }

        /* 错误提示样式 */
        .error-message {
            color: #cc0000;
            background-color: #fff0f0;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #ffd7d7;
        }

        /* 成功提示样式 */
        .success-message {
            color: #2c7c2c;
            background-color: #f0f8f0;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c8e5c8;
        }

        /* 登录链接样式 */
        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #000;
            text-decoration: underline;
        }

        /* 页脚样式 - 黑白配色 */
        footer {
            background-color: #000; /* 黑色背景 */
            color: #fff; /* 白色文字 */
            padding: 40px 0;
            margin-top: 60px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .footer-section {
            flex: 1;
            padding: 0 20px;
        }

        .footer-section h3 {
            color: #fff; /* 白色标题 */
            font-size: 18px;
            margin-bottom: 20px;
            position: relative;
        }

        /* 页脚标题下划线 */
        .footer-section h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 30px;
            height: 2px;
            background-color: #fff; /* 白色下划线 */
        }

        .footer-section p {
            color: #ddd; /* 浅灰白色文字 */
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #333; /* 深灰色边框 */
            color: #aaa; /* 浅灰色文字 */
        }

        .contact-info {
            list-style: none;
        }

        .contact-info li {
            margin-bottom: 10px;
            color: #ddd; /* 浅灰白色文字 */
        }

        .contact-info i {
            margin-right: 10px;
            color: #fff; /* 白色图标 */
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <a href="index.php" class="logo">个人博客</a>
            <nav>
                <ul>
                    <li><a href="index.php">首页</a></li>
                    <li><a href="blog.php">博客</a></li>
                    <li><a href="portfolio.php">作品集</a></li>
                    <li><a href="about.php">个人简介</a></li>
                    <li><a href="guestbook.php">在线留言</a></li>
                    <li><a href="contact.php">联系方式</a></li>
                    <li><a href="login.php">登录</a></li>
                    <li><a href="register.php">注册</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="banner">
        <h1>用户注册</h1>
    </div>

    <main>
        <section class="content-section">
            <div class="container">
                <div class="register-container">
                    <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="success-message">
                        注册成功！您已自动登录，即将跳转到首页...
                        <script>
                            setTimeout(function() {
                                window.location.href = 'index.php';
                            }, 3000);
                        </script>
                    </div>
                    <?php else: ?>
                        <h2 class="register-title">创建新账号</h2>
                        
                        <form action="register.php" method="POST">
                            <div class="form-group">
                                <label class="form-label" for="username">用户名</label>
                                <input type="text" id="username" name="username" class="form-control" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="email">电子邮箱</label>
                                <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="password">密码</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                                <small style="color: #666; display: block; margin-top: 5px;">密码长度不少于6个字符</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">确认密码</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="submit-btn">注册</button>
                        </form>
                        
                        <div class="login-link">
                            <p>已有账号？<a href="login.php">立即登录</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                
                <div class="footer-section">
                    <h3>关于我们</h3>
                    <p>专注用户体验设计，致力于为用户提供优质的网站体验</p>
                </div>

                <div class="footer-section">
                    <h3>联系方式</h3>
                    <ul class="contact-info">
                        <li><i>📧</i> Email: 123@example.com</li>
                        <li><i>📱</i> 电话: (123) 456-7890</li>
                        <li><i>📍</i> 地址: 中国湖南省</li>
                        <li><i>💕</i>欢迎多多支持</li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>关注我们</h3>
                    <p>欢迎通过以下方式关注我：</p>
                    <p>微信公众号：123456</p>
                    <p>QQ：123456@qq.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 个人博客. All Rights Reserved. | 云ICP备12345678号</p>
            </div>
        </div>
    </footer>
</body>
</html> 