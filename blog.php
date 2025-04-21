<?php
require_once 'db.php';

// 启动会话并检查用户是否已登录
session_start();
$logged_in = isset($_SESSION['user_id']);

// 获取查询参数（分类和搜索）
$category_name = isset($_GET['category']) ? $_GET['category'] : null;
$search_query = isset($_GET['search']) ? $_GET['search'] : null;

// 准备查询条件
$where_conditions = [];
$params = [];

// 构建查询SQL
$sql = "SELECT * FROM posts";

// 如果有分类过滤
if ($category_name) {
    // 先查找分类ID
    $cat_stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $cat_stmt->execute([$category_name]);
    $category_id = $cat_stmt->fetchColumn();
    
    if ($category_id) {
        $where_conditions[] = "category_id = ?";
        $params[] = $category_id;
    } else {
        // 兼容处理 - 直接使用category字段
        $where_conditions[] = "category = ?";
        $params[] = $category_name;
    }
}

// 如果有搜索查询
if ($search_query) {
    $where_conditions[] = "(title LIKE ? OR content LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

// 添加WHERE条件
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// 添加排序
$sql .= " ORDER BY create_time DESC";

// 执行查询
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$all_posts = $stmt->fetchAll();

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
        <!-- 博客内容部分 -->
        <section class="content-section">
            <div class="container">
                <h2 class="section-title">博客文章</h2>
                
                <div class="blog-container">
                    <!-- 左侧文章列表 -->
                    <div class="blog-main">
                        <!-- 搜索框 -->
                        <div class="search-box">
                            <form class="search-form" action="" method="GET">
                                <input type="text" class="search-input" name="search" placeholder="搜索文章..." value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                                <button type="submit" class="search-button">搜索</button>
                            </form>
                        </div>
                        
                        <!-- 如果有分类过滤，显示当前分类 -->
                        <?php if ($category_name): ?>
                        <div style="margin-bottom: 20px;">
                            <p>当前分类: <strong><?php echo htmlspecialchars($category_name); ?></strong></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- 文章列表 -->
                        <?php if (empty($all_posts)): ?>
                            <p>暂无文章</p>
                        <?php else: ?>
                            <?php foreach ($all_posts as $post): ?>
                            <div class="blog-post">
                                <div class="blog-post-content">
                                    <h3 class="blog-post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                    <div class="blog-post-meta">
                                        <span><i>📅</i> <?php echo date("Y-m-d", strtotime($post['create_time'])); ?></span>
                                        <?php 
                                        // 显示分类信息
                                        $category_name = '';
                                        
                                        // 优先使用category_id关联
                                        if (!empty($post['category_id'])) {
                                            foreach ($categories as $cat) {
                                                if ($cat['id'] == $post['category_id']) {
                                                    $category_name = $cat['name'];
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // 如果没有找到对应的分类名称，则使用category字段
                                        if (empty($category_name) && array_key_exists('category', $post)) {
                                            $category_name = $post['category'];
                                        }
                                        
                                        // 如果仍然没有分类，则显示默认值
                                        if (empty($category_name)) {
                                            $category_name = '未分类';
                                        }
                                        ?>
                                        <span><i>📂</i> <a href="blog.php?category=<?php echo urlencode($category_name); ?>"><?php echo htmlspecialchars($category_name); ?></a></span>
                                    </div>
                                    <div class="blog-post-excerpt">
                                        <?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 300)) . '...'; ?>
                                    </div>
                                    <a href="post.php?id=<?php echo $post['id']; ?>" class="read-more">阅读全文</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- 分页 -->
                            <div class="pagination">
                                <a href="#" class="active">1</a>
                                <a href="#">2</a>
                                <a href="#">3</a>
                                <a href="#">下一页 &raquo;</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 右侧边栏 -->
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
                                <?php foreach ($recent_posts as $post): ?>
                                <li><a href="post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></li>
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