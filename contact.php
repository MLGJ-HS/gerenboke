<?php
require_once 'db.php';

// 检查用户是否已登录
session_start();
$logged_in = isset($_SESSION['user_id']);

// 处理表单提交
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // 简单验证
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = '请填写所有字段';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的电子邮箱地址';
    } else {
        try {
            // 检查contact_messages表是否存在
            $tableExists = false;
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('contact_messages', $tables)) {
                $tableExists = true;
            } else {
                // 创建表
                $createTableSQL = "CREATE TABLE `contact_messages` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                    `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                    `subject` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                    `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                    `create_time` datetime NOT NULL,
                    `is_read` tinyint(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`) USING BTREE
                ) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic";
                
                $pdo->exec($createTableSQL);
                $tableExists = true;
            }
            
            if ($tableExists) {
                // 插入数据
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, create_time) VALUES (?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$name, $email, $subject, $message]);
                
                if ($result) {
                    $success = true;
                    // 清空表单数据
                    $name = $email = $subject = $message = '';
                } else {
                    $error = '提交失败，请稍后再试';
                }
            } else {
                $error = '系统错误，请稍后再试';
            }
        } catch (PDOException $e) {
            $error = '系统错误，请稍后再试';
        }
    }
}

// 获取所有文章，按创建时间降序排序
$stmt = $pdo->query("SELECT * FROM posts ORDER BY create_time DESC");
$all_posts = $stmt->fetchAll();

// 从categories表获取所有分类
try {
    $cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // 出错时使用空数组
    $categories = [];
}

// 获取友情链接数据
try {
    $links_stmt = $pdo->query("SELECT id, title, url FROM links ORDER BY id");
    $links = $links_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // 出错时使用空数组
    $links = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>联系方式 - 个人博客</title>
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
            height: 250px;
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

        /* 联系页面样式 */
        .contact-container {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 60px;
        }

        /* 联系信息和表单布局 */
        .contact-content {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
        }

        .contact-info {
            flex: 1;
            min-width: 300px;
        }

        .contact-form {
            flex: 2;
            min-width: 400px;
        }

        /* 联系信息项目样式 */
        .info-item {
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
        }

        .info-icon {
            font-size: 20px;
            margin-right: 15px;
            color: #000;
            min-width: 25px;
            text-align: center;
        }

        .info-text h4 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #000;
        }

        .info-text p {
            color: #555;
            line-height: 1.5;
        }

        /* 社交媒体链接 */
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .social-link {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #000;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 18px;
            transition: background-color 0.3s;
        }

        .social-link:hover {
            background-color: #333;
        }

        /* 地图容器 */
        .map-container {
            margin-top: 40px;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }

        /* 替换为图片展示区域 */
        .office-gallery {
            margin-top: 40px;
        }

        .office-gallery h4 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #000;
        }

        .gallery-images {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            border-radius: 8px;
            overflow: hidden;
        }

        .gallery-image {
            width: 100%;
            height: 120px;
            background-color: #f5f5f5;
            border: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            position: relative;
            overflow: hidden;
        }

        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .gallery-image:hover img {
            transform: scale(1.05);
        }

        .gallery-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 5px;
            font-size: 12px;
            text-align: center;
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

        .footer-contact-info {
            list-style: none;
        }

        .footer-contact-info li {
            margin-bottom: 10px;
            color: #ddd; /* 浅灰白色文字 */
        }

        .footer-contact-info i {
            margin-right: 10px;
            color: #fff; /* 白色图标 */
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

        /* 响应式调整 */
        @media (max-width: 1200px) {
            .container {
                width: 95%;
            }
            
            .contact-content {
                flex-direction: column;
            }
            
            .contact-info, .contact-form {
                width: 100%;
            }
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
        <!-- 联系我部分 -->
        <section class="content-section">
            <div class="container">
                <h2 class="section-title">与我们取得联系</h2>
                
                <div class="contact-container">
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        感谢您的留言！我们将尽快回复您。
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="contact-content">
                        <div class="contact-info">
                            <h3 style="margin-bottom: 25px;">联系信息</h3>
                            
                            <div class="info-item">
                                <div class="info-icon">📍</div>
                                <div class="info-text">
                                    <h4>地址</h4>
                                    <p>中国上海市浦东新区张江高科技园区</p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon">📱</div>
                                <div class="info-text">
                                    <h4>电话</h4>
                                    <p>+86 (021) 123-4567</p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon">📧</div>
                                <div class="info-text">
                                    <h4>电子邮箱</h4>
                                    <p>contact@example.com</p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon">🕒</div>
                                <div class="info-text">
                                    <h4>工作时间</h4>
                                    <p>周一至周五: 9:00 - 18:00<br>周末: 休息</p>
                                </div>
                            </div>
                            
                            <h4 style="margin: 30px 0 15px;">友情链接</h4>
                            <div class="friend-links" style="display: flex; flex-wrap: wrap; gap: 10px;">
                                <?php if (empty($links)): ?>
                                    <p>暂无友情链接</p>
                                <?php else: ?>
                                    <?php foreach ($links as $link): ?>
                                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="social-link" title="<?php echo htmlspecialchars($link['title']); ?>"><?php echo htmlspecialchars(mb_substr($link['title'], 0, 2, 'UTF-8')); ?></a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        
                        </div>
                        
                        <div class="contact-form">
                            <h3 style="margin-bottom: 25px;">给我们发送消息</h3>
                            
                            <form action="contact.php" method="POST">
                                <div class="form-group">
                                    <label class="form-label" for="name">您的姓名</label>
                                    <input type="text" id="name" name="name" class="form-control" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="email">电子邮箱</label>
                                    <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="subject">主题</label>
                                    <input type="text" id="subject" name="subject" class="form-control" required value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="message">消息内容</label>
                                    <textarea id="message" name="message" class="form-control" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                                </div>
                                
                                <button type="submit" class="submit-btn">发送消息</button>
                            </form>
                        </div>
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