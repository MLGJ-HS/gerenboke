<?php
require_once 'db.php';

// 获取作品ID
$work_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($work_id <= 0) {
    // 如果没有提供有效的作品ID，重定向到作品集页面
    header('Location: portfolio.php');
    exit;
}

// 获取指定作品
$stmt = $pdo->prepare("SELECT * FROM portfolio WHERE id = ?");
$stmt->execute([$work_id]);
$work = $stmt->fetch();

if (!$work) {
    // 如果作品不存在，重定向到作品集页面
    header('Location: portfolio.php');
    exit;
}

// 获取相关作品（同创建时间附近的其他作品）
$stmt = $pdo->prepare("SELECT id, title, image_url FROM portfolio WHERE id != ? ORDER BY create_time DESC LIMIT 3");
$stmt->execute([$work_id]);
$related_works = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($work['title']); ?> - 作品详情</title>
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

        /* 作品详情页样式 */
        .portfolio-detail {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
        }

        .portfolio-detail-main {
            flex: 2;
            min-width: 300px;
        }

        .portfolio-detail-sidebar {
            flex: 1;
            min-width: 250px;
        }

        .portfolio-detail-image {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .portfolio-detail-header {
            margin-bottom: 30px;
        }

        .portfolio-detail-title {
            font-size: 32px;
            color: #000;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .portfolio-detail-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
            color: #777;
            margin-bottom: 20px;
        }

        .portfolio-detail-date, .portfolio-detail-category {
            display: flex;
            align-items: center;
        }

        .portfolio-detail-date i, .portfolio-detail-category i {
            margin-right: 8px;
        }

        .portfolio-detail-tag {
            display: inline-block;
            padding: 4px 12px;
            background-color: #f0f0f0;
            color: #555;
            border-radius: 20px;
            font-size: 13px;
            margin-right: 8px;
        }

        .portfolio-detail-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .portfolio-detail-description {
            color: #444;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 16px;
            white-space: pre-line;
        }

        .portfolio-detail-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .portfolio-detail-button {
            display: inline-block;
            padding: 12px 25px;
            background-color: #000;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .portfolio-detail-button:hover {
            background-color: #333;
            transform: translateY(-3px);
        }

        .portfolio-detail-button.secondary {
            background-color: #fff;
            color: #000;
            border: 1px solid #e0e0e0;
        }

        .portfolio-detail-button.secondary:hover {
            background-color: #f5f5f5;
        }

        .sidebar-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .sidebar-title {
            font-size: 20px;
            color: #000;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .project-info {
            margin-bottom: 20px;
        }

        .project-info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .project-info-item:last-child {
            border-bottom: none;
        }

        .project-info-label {
            color: #888;
            font-weight: bold;
        }

        .project-info-value {
            color: #444;
            text-align: right;
        }

        .related-works-title {
            font-size: 20px;
            color: #000;
            margin-bottom: 20px;
        }

        .related-work-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .related-work-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .related-work-thumb {
            width: 70px;
            height: 70px;
            border-radius: 4px;
            object-fit: cover;
            margin-right: 15px;
        }

        .related-work-info {
            flex: 1;
        }

        .related-work-title {
            color: #333;
            font-size: 15px;
            margin-bottom: 3px;
            display: block;
            text-decoration: none;
            transition: color 0.3s;
        }

        .related-work-title:hover {
            color: #000;
        }

        /* 返回按钮 */
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #555;
            text-decoration: none;
            margin-bottom: 30px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #000;
        }

        .back-arrow {
            margin-right: 5px;
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

    <div class="banner"></div>

    <main>
        <section class="content-section">
            <div class="container">
                <!-- 返回链接 -->
                <a href="portfolio.php" class="back-link"><span class="back-arrow">←</span> 返回作品集</a>
                
                <div class="portfolio-detail">
                    <!-- 主要内容区 -->
                    <div class="portfolio-detail-main">
                        <img src="<?php echo htmlspecialchars($work['image_url']); ?>" alt="<?php echo htmlspecialchars($work['title']); ?>" class="portfolio-detail-image">
                        
                        <div class="portfolio-detail-header">
                            <h1 class="portfolio-detail-title"><?php echo htmlspecialchars($work['title']); ?></h1>
                            <div class="portfolio-detail-meta">
                                <span class="portfolio-detail-date"><i>📅</i> <?php echo date("Y年m月d日", strtotime($work['create_time'])); ?></span>
                            </div>
                            <div>
                                <span class="portfolio-detail-tag">Web开发</span>
                                <span class="portfolio-detail-tag">设计</span>
                            </div>
                        </div>
                        
                        <div class="portfolio-detail-content">
                            <p class="portfolio-detail-description"><?php echo $work['description']; ?></p>
                            
                            <div class="portfolio-detail-actions">
                                <a href="portfolio.php" class="portfolio-detail-button">查看更多作品</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 侧边栏 -->
                    <div class="portfolio-detail-sidebar">
                        <!-- 项目信息卡片 -->
                        <div class="sidebar-card">
                            <h3 class="sidebar-title">项目信息</h3>
                            <div class="project-info">
                                <div class="project-info-item">
                                    <span class="project-info-label">创建日期</span>
                                    <span class="project-info-value"><?php echo date("Y年m月d日", strtotime($work['create_time'])); ?></span>
                                </div>
                                <div class="project-info-item">
                                    <span class="project-info-label">使用技术</span>
                                    <span class="project-info-value">HTML5, CSS3, JavaScript</span>
                                </div>
                                <div class="project-info-item">
                                    <span class="project-info-label">客户</span>
                                    <span class="project-info-value">个人项目</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 相关作品 -->
                        <?php if (!empty($related_works)): ?>
                        <div class="sidebar-card">
                            <h3 class="sidebar-title">相关作品</h3>
                            <?php foreach ($related_works as $related): ?>
                            <div class="related-work-item">
                                <img src="<?php echo htmlspecialchars($related['image_url']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="related-work-thumb">
                                <div class="related-work-info">
                                    <a href="portfolio-detail.php?id=<?php echo $related['id']; ?>" class="related-work-title"><?php echo htmlspecialchars($related['title']); ?></a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
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