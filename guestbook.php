<?php
require_once 'db.php';

// 检查用户是否已登录
session_start();
$logged_in = isset($_SESSION['user_id']);
$current_user = $logged_in ? $_SESSION['username'] : '';
$is_admin = $logged_in && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// 处理管理员回复
if ($is_admin && isset($_POST['reply_id']) && isset($_POST['reply_content'])) {
    $reply_id = $_POST['reply_id'];
    $reply_content = trim($_POST['reply_content']);
    
    if (!empty($reply_content)) {
        try {
            // 更新留言的回复内容
            $stmt = $pdo->prepare("UPDATE guestbook SET reply = ?, reply_time = NOW() WHERE id = ?");
            $stmt->execute([$reply_content, $reply_id]);
            // 成功后重定向
            header("Location: guestbook.php?reply=success");
            exit;
        } catch (PDOException $e) {
            $error = '保存回复失败，请稍后再试';
        }
    } else {
        $error = '回复内容不能为空';
    }
}

// 如果用户已登录并提交了留言
if ($logged_in && isset($_POST['content']) && !isset($_POST['reply_id'])) {
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    if (!empty($content)) {
        try {
            // 插入留言到数据库
            $stmt = $pdo->prepare("INSERT INTO guestbook (user_id, username, content, create_time) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $_SESSION['username'], $content]);
            // 成功后重定向到留言页，避免刷新重复提交
            header("Location: guestbook.php?message=success");
            exit;
        } catch (PDOException $e) {
            $error = '保存留言失败，请稍后再试: ' . $e->getMessage();
        }
    } else {
        $error = '留言内容不能为空';
    }
}

// 检查表是否存在，如果不存在则创建
try {
    $pdo->query("SELECT 1 FROM guestbook LIMIT 1");
} catch (PDOException $e) {
    // 表不存在，创建它
    $pdo->exec("CREATE TABLE IF NOT EXISTS guestbook (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        username VARCHAR(50) NOT NULL,
        content TEXT NOT NULL,
        create_time DATETIME NOT NULL,
        reply TEXT NULL,
        reply_time DATETIME NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
}

// 检查username, reply和reply_time字段是否存在，如果不存在则添加
try {
    // 检查username字段
    $check_username_column = $pdo->query("SHOW COLUMNS FROM guestbook LIKE 'username'");
    if (!$check_username_column->fetchColumn()) {
        $pdo->exec("ALTER TABLE guestbook ADD COLUMN username VARCHAR(50) NOT NULL AFTER user_id");
        
        // 如果没有username字段，从users表中获取用户名并更新
        $pdo->exec("UPDATE guestbook g 
                   JOIN users u ON g.user_id = u.id 
                   SET g.username = u.username 
                   WHERE g.username IS NULL OR g.username = ''");
    }
    
    // 检查reply字段
    $check_reply_column = $pdo->query("SHOW COLUMNS FROM guestbook LIKE 'reply'");
    if (!$check_reply_column->fetchColumn()) {
        $pdo->exec("ALTER TABLE guestbook ADD COLUMN reply TEXT NULL");
    }
    
    // 检查reply_time字段
    $check_reply_time_column = $pdo->query("SHOW COLUMNS FROM guestbook LIKE 'reply_time'");
    if (!$check_reply_time_column->fetchColumn()) {
        $pdo->exec("ALTER TABLE guestbook ADD COLUMN reply_time DATETIME NULL");
    }
} catch (PDOException $e) {
    // 字段添加失败的处理
    $error = '数据库结构更新失败: ' . $e->getMessage();
}

// 获取所有留言，按创建时间降序排序
$stmt = $pdo->query("SELECT * FROM guestbook ORDER BY create_time DESC");
$messages = $stmt->fetchAll();

// 尝试获取分类，如果不存在则使用默认分类
try {
    // 检查字段是否已存在
    $check_stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'category'");
    $column_exists = $check_stmt->fetchColumn();
    
    if ($column_exists) {
        // 如果存在分类字段，则查询distinct值
        $cat_stmt = $pdo->query("SELECT DISTINCT category FROM posts ORDER BY category");
        $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // 如果不存在分类字段，使用默认值
        $categories = ['技术', '生活', '学习', '其他'];
    }
} catch(PDOException $e) {
    // 出错时使用默认分类
    $categories = ['技术', '生活', '学习', '其他'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在线留言 - 个人博客</title>
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
            height: 450px;
            background-image: url('images/banner.jpg');
            background-size: cover;
            background-position: center;
            margin-top: 80px;
            position: relative;
            filter: none; /* 移除黑白滤镜 */
            background-color: #fff; /* 白色背景 */
        }

        .banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: transparent; /* 移除遮罩 */
        }

        /* 内容区域通用样式 */
        .content-section {
            padding: 60px 0;
        }

        /* 标题样式 - 黑色标题 */
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            color: #000; /* 黑色标题 */
            font-size: 28px;
            position: relative;
        }

        /* 标题下划线 */
        .section-title::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: #000; /* 黑色下划线 */
            margin: 15px auto;
        }

        /* 留言板样式 */
        .guestbook-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 60px;
        }

        /* 留言表单样式 */
        .message-form {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .form-control:focus {
            border-color: #000;
            outline: none;
        }

        .submit-btn {
            background-color: #000;
            color: #fff;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #333;
        }

        /* 留言列表样式 */
        .message-list {
            margin-top: 30px;
        }

        .message {
            background: #f9f9f9;
            border-left: 3px solid #000;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }

        .message-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #777;
            font-size: 14px;
        }

        .message-author {
            font-weight: bold;
            color: #333;
        }

        .message-date {
            color: #888;
        }

        .message-content {
            color: #333;
            line-height: 1.6;
        }

        /* 提示消息样式 */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #f0f8f0;
            color: #2c7c2c;
            border: 1px solid #c8e5c8;
        }

        .alert-error {
            background-color: #fff0f0;
            color: #cc0000;
            border: 1px solid #ffd7d7;
        }

        .login-message {
            text-align: center;
            padding: 30px;
            background: #f9f9f9;
            border-radius: 4px;
            margin-bottom: 30px;
        }

        .login-message a {
            color: #000;
            text-decoration: underline;
            font-weight: bold;
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

        /* 管理员回复样式 */
        .admin-reply {
            margin-top: 15px;
            padding: 15px;
            background-color: #f0f0f0;
            border-left: 3px solid #007bff;
            border-radius: 0 4px 4px 0;
        }

        .reply-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .reply-title {
            font-weight: bold;
            color: #007bff;
        }

        .reply-date {
            color: #888;
        }

        .reply-content {
            color: #333;
            line-height: 1.5;
        }

        /* 管理员回复表单 */
        .admin-reply-form {
            margin-top: 15px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px dashed #ccc;
            border-radius: 4px;
        }

        .reply-btn {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 8px 15px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .reply-btn:hover {
            background-color: #0056b3;
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
                    <?php if ($logged_in): ?>
                        <li><a href="logout.php">退出登录</a></li>
                    <?php else: ?>
                        <li><a href="login.php">登录</a></li>
                        <li><a href="register.php">注册</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="banner"></div>

    <main>
        <!-- 留言板内容部分 -->
        <section class="content-section">
            <div class="container">
                <h2 class="section-title">在线留言</h2>
                
                <div class="guestbook-container">
                    <?php if (isset($_GET['message']) && $_GET['message'] === 'success'): ?>
                    <div class="alert alert-success">
                        留言发布成功！感谢您的分享。
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['reply']) && $_GET['reply'] === 'success'): ?>
                    <div class="alert alert-success">
                        回复发布成功！
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($logged_in): ?>
                        <!-- 留言表单，仅登录用户可见 -->
                        <div class="message-form">
                            <h3>发表新留言</h3>
                            <p style="margin-bottom: 20px;">当前用户：<?php echo htmlspecialchars($current_user); ?></p>
                            
                            <form action="guestbook.php" method="POST">
                                <div class="form-group">
                                    <label class="form-label" for="content">留言内容</label>
                                    <textarea id="content" name="content" class="form-control" required></textarea>
                                </div>
                                
                                <button type="submit" class="submit-btn">发布留言</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- 未登录用户提示 -->
                        <div class="login-message">
                            <p>您需要 <a href="login.php">登录</a> 后才能发布留言。</p>
                            <p>还没有账号？<a href="register.php">立即注册</a></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 留言列表 -->
                    <div class="message-list">
                        <h3>所有留言</h3>
                        
                        <?php if (empty($messages)): ?>
                            <p style="text-align: center; padding: 30px; color: #777;">暂无留言，快来发表第一条留言吧！</p>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                            <div class="message">
                                <div class="message-info">
                                    <span class="message-author"><?php echo htmlspecialchars($message['username']); ?></span>
                                    <span class="message-date"><?php echo date("Y年m月d日 H:i", strtotime($message['create_time'])); ?></span>
                                </div>
                                <div class="message-content"><?php echo nl2br(htmlspecialchars($message['content'])); ?></div>
                                
                                <?php if (!empty($message['reply'])): ?>
                                <!-- 管理员回复 -->
                                <div class="admin-reply">
                                    <div class="reply-header">
                                        <span class="reply-title">管理员回复：</span>
                                        <span class="reply-date"><?php echo date("Y年m月d日 H:i", strtotime($message['reply_time'])); ?></span>
                                    </div>
                                    <div class="reply-content"><?php echo nl2br(htmlspecialchars($message['reply'])); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($is_admin && empty($message['reply'])): ?>
                                <!-- 管理员回复表单 -->
                                <div class="admin-reply-form">
                                    <form action="guestbook.php" method="POST">
                                        <input type="hidden" name="reply_id" value="<?php echo $message['id']; ?>">
                                        <div class="form-group">
                                            <label class="form-label">管理员回复</label>
                                            <textarea name="reply_content" class="form-control" required></textarea>
                                        </div>
                                        <button type="submit" class="reply-btn">提交回复</button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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