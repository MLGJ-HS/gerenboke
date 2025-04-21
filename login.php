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

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // 简单验证
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } else {
        try {
            // 查询用户
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // 验证密码
            if ($user && $user['password'] === $password) { // 实际应用中应使用password_hash和password_verify
                // 登录成功，设置会话变量
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // 重定向到首页
                header("Location: index.php");
                exit;
            } else {
                $error = '用户名或密码错误';
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
    <title>用户登录 - 个人博客</title>
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

        /* 登录表单容器 */
        .login-container {
            max-width: 500px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .login-title {
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

        /* 注册链接样式 */
        .register-link {
            text-align: center;
            margin-top: 20px;
        }

        .register-link a {
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
        <h1>用户登录</h1>
    </div>

    <main>
        <section class="content-section">
            <div class="container">
                <div class="login-container">
                    <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <h2 class="login-title">登录您的账号</h2>
                    
                    <form action="login.php" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="username">用户名</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password">密码</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="submit-btn">登录</button>
                    </form>
                    
                    <div class="register-link">
                        <p>还没有账号？<a href="register.php">立即注册</a></p>
                    </div>
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