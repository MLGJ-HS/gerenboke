<?php
require_once 'db.php';

// 获取文章ID
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id <= 0) {
    // 如果没有提供有效的文章ID，重定向到博客首页
    header('Location: blog.php');
    exit;
}

// 获取指定文章
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    // 如果文章不存在，重定向到博客首页
    header('Location: blog.php');
    exit;
}

// 增加文章浏览量
$stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
$stmt->execute([$post_id]);

// 获取文章分类名称
$category_name = '';
if (!empty($post['category_id'])) {
    $cat_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $cat_stmt->execute([$post['category_id']]);
    $category_name = $cat_stmt->fetchColumn();
} elseif (!empty($post['category'])) {
    $category_name = $post['category'];
}

// 获取相关文章（同分类的其他文章）
$related_posts = [];
if (!empty($category_name)) {
    // 先尝试按category_id查找
    if (!empty($post['category_id'])) {
        $stmt = $pdo->prepare("SELECT id, title, create_time FROM posts WHERE category_id = ? AND id != ? ORDER BY create_time DESC LIMIT 5");
        $stmt->execute([$post['category_id'], $post_id]);
        $related_posts = $stmt->fetchAll();
    } 
    // 如果没有找到或没有category_id，按category字段查找
    if (empty($related_posts) && !empty($post['category'])) {
        $stmt = $pdo->prepare("SELECT id, title, create_time FROM posts WHERE category = ? AND id != ? ORDER BY create_time DESC LIMIT 5");
        $stmt->execute([$post['category'], $post_id]);
        $related_posts = $stmt->fetchAll();
    }
}

// 获取文章评论
$stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY create_time DESC");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

// 从categories表获取所有分类
try {
    $cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // 出错时使用空数组
    $categories = [];
}
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

        /* 添加博客页面专用样式 */
        .blog-container {
            display: flex;
            gap: 30px;
            margin-top: 30px;
        }

        /* 左侧文章列表 */
        .blog-main {
            flex: 2;
        }

        /* 右侧边栏 */
        .blog-sidebar {
            flex: 1;
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            align-self: flex-start;
        }

        /* 侧边栏标题 */
        .sidebar-title {
            font-size: 18px;
            color: #000;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        /* 分类列表 */
        .category-list {
            list-style: none;
        }

        .category-list li {
            margin-bottom: 10px;
        }

        .category-list li a {
            color: #555;
            text-decoration: none;
            transition: color 0.3s;
            display: block;
            padding: 5px 0;
        }

        .category-list li a:hover {
            color: #000;
        }

        /* 文章列表样式 */
        .blog-post {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        .blog-post-content {
            padding: 25px;
        }

        .blog-post-title {
            font-size: 22px;
            color: #000;
            margin-bottom: 15px;
        }

        .blog-post-meta {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #777;
            font-size: 14px;
        }

        .blog-post-meta span {
            margin-right: 15px;
        }

        .blog-post-meta i {
            margin-right: 5px;
        }

        .blog-post-excerpt {
            color: #555;
            margin-bottom: 20px;
            line-height: 1.8;
        }

        .read-more {
            display: inline-block;
            padding: 8px 20px;
            background-color: #000;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .read-more:hover {
            background-color: #333;
        }

        /* 分页样式 */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .pagination a {
            display: inline-block;
            padding: 8px 15px;
            margin: 0 5px;
            background-color: #fff;
            color: #555;
            border-radius: 4px;
            text-decoration: none;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }

        .pagination a:hover, .pagination a.active {
            background-color: #000;
            color: #fff;
            border-color: #000;
        }

        /* 搜索框样式 */
        .search-box {
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
        }

        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px 0 0 4px;
            font-size: 14px;
            outline: none;
        }

        .search-button {
            background-color: #000;
            color: #fff;
            border: none;
            padding: 0 20px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-button:hover {
            background-color: #333;
        }

        /* 文章页专用样式 */
        .single-post-container {
            display: flex;
            gap: 30px;
            margin-top: 30px;
        }

        .post-main {
            flex: 3;
        }

        .post-header {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        .post-title-large {
            font-size: 32px;
            color: #000;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .post-meta-details {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
            color: #777;
            font-size: 14px;
        }

        .post-meta-details span {
            margin-right: 20px;
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .post-meta-details i {
            margin-right: 5px;
        }

        .post-body {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        /* 文章内容样式增强 - 适应富文本编辑器 */
        .post-content {
            color: #333;
            line-height: 1.8;
            font-size: 16px;
        }

        .post-content p {
            margin-bottom: 20px;
        }

        .post-content h2, .post-content h3, .post-content h4, .post-content h5, .post-content h6 {
            margin-top: 30px;
            margin-bottom: 15px;
            color: #000;
        }

        .post-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
            display: block;
        }

        .post-content a {
            color: #0066cc;
            text-decoration: none;
            border-bottom: 1px solid #ddd;
            transition: all 0.3s;
        }

        .post-content a:hover {
            color: #004080;
            border-bottom-color: #0066cc;
        }

        .post-content ul, .post-content ol {
            margin-bottom: 20px;
            padding-left: 20px;
        }

        .post-content li {
            margin-bottom: 8px;
        }

        .post-content blockquote {
            border-left: 4px solid #ddd;
            padding: 15px 20px;
            margin: 20px 0;
            background-color: #f9f9f9;
            font-style: italic;
            color: #555;
        }

        .post-content table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }

        .post-content table th, .post-content table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .post-content table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .post-content code {
            background-color: #f5f5f5;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: Consolas, monospace;
            color: #333;
        }

        .post-content pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 20px 0;
        }

        .post-content pre code {
            background-color: transparent;
            padding: 0;
        }

        .post-tags {
            margin-top: 30px;
        }

        .post-tag {
            display: inline-block;
            background-color: #f0f0f0;
            color: #555;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            margin-right: 8px;
            margin-bottom: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .post-tag:hover {
            background-color: #000;
            color: #fff;
        }

        /* 评论区样式 */
        .comments-section {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        .comments-title {
            font-size: 22px;
            color: #000;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .comment {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #f0f0f0;
        }

        .comment:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .comment-author {
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }

        .comment-date {
            font-size: 12px;
            color: #888;
            margin-bottom: 10px;
        }

        .comment-content {
            color: #555;
            line-height: 1.6;
        }

        /* 评论表单 */
        .comment-form {
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.5;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #888;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #000;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #333;
        }

        /* 相关文章样式 */
        .related-posts {
            margin-top: 20px;
        }

        .related-post-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .related-post-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .related-post-link {
            color: #555;
            text-decoration: none;
            display: block;
            transition: color 0.3s;
        }

        .related-post-link:hover {
            color: #000;
        }

        .related-post-date {
            font-size: 12px;
            color: #888;
            margin-top: 3px;
        }

        /* 返回按钮 */
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #555;
            text-decoration: none;
            margin-bottom: 20px;
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
        <!-- 博客文章详情页 -->
        <section class="content-section">
            <div class="container">
                <!-- 返回链接 -->
                <a href="blog.php" class="back-link"><span class="back-arrow">←</span> 返回博客列表</a>
                
                <div class="single-post-container">
                    <!-- 文章主体部分 -->
                    <div class="post-main">
                        <!-- 文章头部信息 -->
                        <div class="post-header">
                            <h1 class="post-title-large"><?php echo htmlspecialchars($post['title']); ?></h1>
                            <div class="post-meta-details">
                                <span><i>📅</i> 发布日期：<?php echo date("Y年m月d日", strtotime($post['create_time'])); ?></span>
                                <?php if (isset($post['update_time']) && $post['update_time'] != $post['create_time']): ?>
                                <span><i>🔄</i> 更新日期：<?php echo date("Y年m月d日", strtotime($post['update_time'])); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($category_name)): ?>
                                <span><i>📂</i> 分类：<a href="blog.php?category=<?php echo urlencode($category_name); ?>"><?php echo htmlspecialchars($category_name); ?></a></span>
                                <?php endif; ?>
                                <span><i>👁️</i> 阅读：<?php echo $post['views']; ?>次</span>
                            </div>
                        </div>
                        
                        <!-- 文章内容 -->
                        <div class="post-body">
                            <div class="post-content">
                                <?php echo $post['content']; ?>
                            </div>
                            
                            <!-- 文章标签 -->
                            <div class="post-tags">
                                <?php if (!empty($category_name)): ?>
                                <a href="blog.php?category=<?php echo urlencode($category_name); ?>" class="post-tag"><?php echo htmlspecialchars($category_name); ?></a>
                                <?php endif; ?>
                                <a href="#" class="post-tag">Web开发</a>
                                <a href="#" class="post-tag">学习笔记</a>
                            </div>
                        </div>
                        
                        <!-- 评论区 -->
                        <div class="comments-section">
                            <h3 class="comments-title">评论 (<?php echo count($comments); ?>)</h3>
                            
                            <?php if (isset($_GET['comment']) && $_GET['comment'] === 'success'): ?>
                            <div style="background-color: #f0f8f0; color: #2c7c2c; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                                评论提交成功！
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['error'])): ?>
                            <div style="background-color: #fff0f0; color: #cc0000; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                                <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (empty($comments)): ?>
                                <p>暂无评论，快来发表您的看法吧！</p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                <div class="comment">
                                    <div class="comment-author"><?php echo htmlspecialchars($comment['author']); ?></div>
                                    <div class="comment-date"><?php echo date("Y年m月d日 H:i", strtotime($comment['create_time'])); ?></div>
                                    <div class="comment-content"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>
                                    
                                    <?php if ($comment['reply']): ?>
                                    <div class="comment-reply" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 3px solid #000;">
                                        <div style="color: #000; font-weight: bold;">管理员回复：</div>
                                        <div><?php echo nl2br(htmlspecialchars($comment['reply'])); ?></div>
                                        <div style="color: #888; font-size: 12px; margin-top: 5px;">
                                            <?php echo date("Y年m月d日 H:i", strtotime($comment['reply_time'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                                    <div class="admin-reply-form" style="margin-top: 10px;">
                                        <form action="admin/reply_comment.php" method="POST">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                            <textarea name="reply" class="form-control" style="margin-bottom: 10px;" placeholder="输入回复内容..."><?php echo $comment['reply'] ?? ''; ?></textarea>
                                            <button type="submit" class="submit-btn" style="padding: 5px 15px; font-size: 14px;">提交回复</button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- 评论表单 -->
                            <form class="comment-form" action="add_comment.php" method="POST">
                                <h4 style="margin-bottom: 20px;">发表评论</h4>
                                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                
                                <div class="form-group">
                                    <label class="form-label" for="author">您的昵称</label>
                                    <input type="text" id="author" name="author" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="content">评论内容</label>
                                    <textarea id="content" name="content" class="form-control" required></textarea>
                                </div>
                                
                                <button type="submit" class="submit-btn">提交评论</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- 侧边栏 -->
                    <div class="blog-sidebar">
                        <!-- 分类 -->
                        <h3 class="sidebar-title">文章分类</h3>
                        <ul class="category-list">
                            <?php if (empty($categories)): ?>
                                <li>暂无分类</li>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                <li><a href="blog.php?category=<?php echo urlencode($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        
                        <!-- 相关文章 -->
                        <h3 class="sidebar-title" style="margin-top: 30px;">相关文章</h3>
                        <div class="related-posts">
                            <?php if (empty($related_posts)): ?>
                                <p>暂无相关文章</p>
                            <?php else: ?>
                                <?php foreach ($related_posts as $related): ?>
                                <div class="related-post-item">
                                    <a href="post.php?id=<?php echo $related['id']; ?>" class="related-post-link"><?php echo htmlspecialchars($related['title']); ?></a>
                                    <div class="related-post-date"><?php echo date("Y年m月d日", strtotime($related['create_time'])); ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 最新文章 -->
                        <h3 class="sidebar-title" style="margin-top: 30px;">最新文章</h3>
                        <ul class="category-list">
                            <?php 
                            // 获取最新5篇文章
                            $stmt = $pdo->query("SELECT id, title FROM posts ORDER BY create_time DESC LIMIT 5");
                            $recent_posts = $stmt->fetchAll();
                            
                            if (empty($recent_posts)): 
                            ?>
                                <li>暂无文章</li>
                            <?php else: ?>
                                <?php foreach ($recent_posts as $recent): ?>
                                <li><a href="post.php?id=<?php echo $recent['id']; ?>"><?php echo htmlspecialchars($recent['title']); ?></a></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
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