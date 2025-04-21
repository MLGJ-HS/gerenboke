<?php
require_once 'db.php';

// 启动会话并检查用户是否已登录
session_start();
$logged_in = isset($_SESSION['user_id']);

// 获取所有作品集，按创建时间降序排序
$stmt = $pdo->query("SELECT * FROM portfolio ORDER BY create_time DESC");
$all_works = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人博客</title>
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
            background-color: rgba(25, 193, 235, 0.82); /* 半透明白色背景 */
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

        /* 最新文章网格布局 */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        /* 文章卡片样式 */
        .post-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border: 1px solid #e0e0e0; /* 淡灰色边框 */
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .post-content {
            padding: 20px;
        }

        .post-title {
            font-size: 18px;
            color: #000; /* 黑色标题 */
            margin-bottom: 10px;
        }

        .post-excerpt {
            color: #555; /* 深灰色文字 */
            font-size: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* 作品展示网格布局 */
        .works-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        /* 作品卡片样式 */
        .work-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0; /* 淡灰色边框 */
        }

        /* 作品图片样式 - 白色背景 */
        .work-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            filter: none; /* 移除黑白滤镜 */
            background-color: #fff; /* 白色背景 */
            border: 1px solid #e0e0e0; /* 淡灰色边框 */
        }

        .work-content {
            padding: 20px;
        }

        .work-title {
            font-size: 18px;
            color: #000; /* 黑色标题 */
            margin-bottom: 10px;
        }

        .work-description {
            color: #555; /* 深灰色文字 */
            font-size: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* 关于我部分样式 */
        .about-content {
            display: flex;
            gap: 40px;
            align-items: flex-start;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0; /* 淡灰色边框 */
        }

        .about-image {
            flex: 0 0 300px;
        }

        /* 个人照片样式 - 白色背景 */
        .about-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            filter: none; /* 移除黑白滤镜 */
            background-color: #fff; /* 白色背景 */
            border: 1px solid #e0e0e0; /* 淡灰色边框 */
        }

        .about-text {
            flex: 1;
        }

        .about-text p {
            color: #555; /* 深灰色文字 */
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 16px;
            text-align: justify;
        }

        .about-text p:last-child {
            margin-bottom: 0;
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

        /* 作品集页面专用样式 */
        .portfolio-container {
            margin-bottom: 40px;
        }

        /* 分类筛选导航 */
        .portfolio-filter {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 40px;
        }

        .filter-item {
            padding: 8px 20px;
            margin: 0 10px 10px 0;
            background-color: #fff;
            color: #555;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 14px;
        }

        .filter-item:hover, .filter-item.active {
            background-color: #000;
            color: #fff;
            border-color: #000;
        }

        /* 作品集网格 - 改进版 */
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 60px;
        }

        /* 作品卡片 - 改进版 */
        .portfolio-item {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .portfolio-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .portfolio-image-container {
            position: relative;
            overflow: hidden;
            height: 220px;
        }

        .portfolio-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .portfolio-item:hover .portfolio-image {
            transform: scale(1.05);
        }

        .portfolio-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .portfolio-item:hover .portfolio-overlay {
            opacity: 1;
        }

        .view-project {
            display: inline-block;
            padding: 10px 20px;
            background-color: #fff;
            color: #000;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }

        .view-project:hover {
            background-color: #f0f0f0;
            transform: scale(1.05);
        }

        .portfolio-content {
            padding: 20px;
        }

        .portfolio-title {
            font-size: 18px;
            color: #000;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .portfolio-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .portfolio-meta {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #f0f0f0;
            padding-top: 15px;
            color: #777;
            font-size: 13px;
        }

        .portfolio-date {
            display: flex;
            align-items: center;
        }

        .portfolio-date i {
            margin-right: 5px;
        }

        .portfolio-category {
            padding: 3px 10px;
            background-color: #f5f5f5;
            border-radius: 20px;
            color: #555;
        }

        /* 空状态提示 */
        .empty-state {
            text-align: center;
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        .empty-state p {
            color: #777;
            margin-bottom: 20px;
            font-size: 16px;
        }

        /* 响应式调整 */
        @media (max-width: 992px) {
            .portfolio-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .portfolio-grid {
                grid-template-columns: 1fr;
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
        <!-- 作品集内容部分 -->
        <section class="content-section">
            <div class="container">
                <h2 class="section-title">作品集展示</h2>
                
                <div class="portfolio-container">
                    <?php if (empty($all_works)): ?>
                        <!-- 空状态提示 -->
                        <div class="empty-state">
                            <p>暂无作品</p>
                        </div>
                    <?php else: ?>
                        <!-- 作品集网格 -->
                        <div class="portfolio-grid">
                            <?php foreach ($all_works as $work): ?>
                            <div class="portfolio-item">
                                <div class="portfolio-image-container">
                                    <img src="<?php echo htmlspecialchars($work['image_url']); ?>" alt="<?php echo htmlspecialchars($work['title']); ?>" class="portfolio-image">
                                    <div class="portfolio-overlay">
                                        <div style="display: flex; gap: 10px;">
                                            <a href="portfolio-detail.php?id=<?php echo $work['id']; ?>" class="view-project" style="background-color: #000; color: #fff;">查看详情</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="portfolio-content">
                                    <h3 class="portfolio-title">
                                        <a href="portfolio-detail.php?id=<?php echo $work['id']; ?>" style="color: inherit; text-decoration: none;">
                                            <?php echo htmlspecialchars($work['title']); ?>
                                        </a>
                                    </h3>
                                    <p class="portfolio-description"><?php echo $work['description'], 0, 150 . '...'; ?></p>
                                    <div class="portfolio-meta">
                                        <span class="portfolio-date"><i>📅</i> <?php echo date("Y-m-d", strtotime($work['create_time'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
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