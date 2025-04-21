<?php
require_once 'db.php';

// 启动会话并检查用户是否已登录
session_start();
$logged_in = isset($_SESSION['user_id']);

//获取最新文章（从posts表中按创建时间降序获取n条记录）
$stmt = $pdo->query("SELECT * FROM posts ORDER BY create_time DESC LIMIT 3");
$latest_posts = $stmt->fetchAll();

// 获取最新作品（从portfolio表中按创建时间降序获取n条记录）
$stmt = $pdo->query("SELECT * FROM portfolio ORDER BY create_time DESC LIMIT 5");
$latest_works = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人博客</title>
    <style>
        /* CSS重置 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Microsoft YaHei', sans-serif;
            line-height: 1.6;
            background-color: #f0f0f0;
            color: #212121;
        }

        .container {
            width: 1200px;
            margin: 0 auto;
        }

        header {
            background-color: #000;
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

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 20px;
        }

        nav ul li a {
            text-decoration: none;
            color: #ddd;
            font-size: 15px;
            transition: color 0.3s, background-color 0.3s;
            padding: 5px 10px;
        }

        nav ul li a:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .banner {
            height: 450px;
            margin-top: 80px;
            position: relative;
            overflow: hidden;
        }

        .slideshow {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-size: cover;
            background-position: center;
        }

        .slide.active {
            opacity: 1;
        }

        .slide-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #fff;
            z-index: 2;
        }

        .slide-content h2 {
            font-size: 36px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .slide-content p {
            font-size: 18px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .slide-nav {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 2;
        }

        .slide-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: background 0.3s;
        }

        .slide-dot.active {
            background: #fff;
        }

        .slide-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(0,0,0,0.5);
            color: #fff;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            transition: background 0.3s;
        }

        .slide-arrow:hover {
            background: rgba(0,0,0,0.8);
        }

        .prev {
            left: 20px;
        }

        .next {
            right: 20px;
        }

        .content-section {
            padding: 60px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
            color: #000;
            font-size: 28px;
            position: relative;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: #000;
            margin: 15px auto;
        }

        .posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .post-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border: 1px solid #e0e0e0;
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
            color: #000;
            margin-bottom: 10px;
        }

        .post-excerpt {
            color: #555;
            font-size: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .works-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .work-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        .work-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            
        }

        .work-content {
            padding: 20px;
        }

        .work-title {
            font-size: 18px;
            color: #000;
            margin-bottom: 10px;
        }

        .work-description {
            color: #555;
            font-size: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .about-content {
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }

        .about-image {
            flex: 0 0 300px;
        }

        .about-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 8px;
          
        }

        .about-text {
            flex: 1;
        }

        .about-text p {
            color: #555;
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 16px;
            text-align: justify;
        }

        footer {
            background-color: #000;
            color: #fff;
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
            color: #fff;
            font-size: 18px;
            margin-bottom: 20px;
            position: relative;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 30px;
            height: 2px;
            background-color: #fff;
        }

        .footer-section p {
            color: #ddd;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #333;
            color: #aaa;
        }

        .contact-info {
            list-style: none;
        }

        .contact-info li {
            margin-bottom: 10px;
            color: #ddd;
        }

        .contact-info i {
            margin-right: 10px;
            color: #fff;
        }

        
    </style>
</head>
<body>
<header>
        <div class="container header-content">
            <!-- 网站logo -->
            <a href="index.php" class="logo">个人博客</a>
            <nav>
                <ul>
                    <li><a href="index.php">首页</a></li>
                    <li><a href="blog.php">博客</a></li>
                    <li><a href="portfolio.php">作品集</a></li>
                    <li><a href="about.php">个人简介</a></li>
                    <li><a href="guestbook.php">在线留言</a></li>
                    <li><a href="contact.php">联系方式</a></li>

                    <!-- 判断登录 -->
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

    <!-- Banner轮播图 -->
    <div class="banner">
        <div class="slideshow">
            <div class="slide active" style="background-image: url('images/banner1.jpg');">
                <div class="slide-content">
                    <h2>欢迎来到我的博客</h2>
                    <p>分享技术，记录生活</p>
                </div>
            </div>
            <div class="slide" style="background-image: url('images/banner2.jpg');">
                <div class="slide-content">
                    <h2>技术分享</h2>
                    <p>探索编程的无限可能</p>
                </div>
            </div>
            <div class="slide" style="background-image: url('images/banner3.jpg');">
                <div class="slide-content">
                    <h2>作品展示</h2>
                    <p>展示我的项目成果</p>
                </div>
            </div>
            <!-- 轮播图控制按钮，左右箭头按钮(prev/next)，导航圆点(slide-dot) -->
            <button class="slide-arrow prev">❮</button>
            <button class="slide-arrow next">❯</button>
            <div class="slide-nav">
                <div class="slide-dot active"></div>
                <div class="slide-dot"></div>
                <div class="slide-dot"></div>
            </div>
        </div>
    </div>

    <main>
        <!-- 首页 -->
        <section class="content-section">
            <div class="container">
                <h2 class="section-title">关于我</h2>
                <div class="about-content">
                    <div class="about-image">
                        <img src="images/about-me.jpg" alt="个人照片">
                    </div>
                    <div class="about-text">
                        <p>大学时光是人生中最美好的阶段，在这里，我度过了充实而有意义的四年时光。作为一名计算机专业的学生，我不仅认真学习专业课程，更注重将理论知识与实践相结合。</p>
                        <p>在校期间，我积极参与各类技术社团活动，加入了学校的编程兴趣小组，和志同道合的同学一起探讨技术问题，参与项目开发。我们一起熬夜写代码，一起解决bug，一起为项目的成功欢呼雀跃。</p>
                        <p>课余时间，我也积极参与校园文化活动，加入了摄影协会，用镜头记录校园的美好瞬间。同时，我也热衷于运动，每周都会和室友一起打篮球，既锻炼了身体，也增进了友谊。</p>
                        <p>通过这个博客，我希望能够记录下学习和生活的点点滴滴，分享技术心得，也分享生活感悟。欢迎志同道合的朋友一起交流，共同进步！</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 最新文章 -->
        <section class="content-section">
            <div class="container">
                <h2 class="section-title">最新文章</h2>
                <div class="posts-grid">
                    <?php foreach ($latest_posts as $post): ?>
                    <div class="post-card">
                        <div class="post-content">
                            <!-- htmlspecialchars()防止XSS攻击substr()截取内容前150字符 -->
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="post-excerpt"><?php echo $post['content'], 0, 150 . '...'; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- 最新作品 -->
        <section class="content-section" style="background-color: #f0f0f0;">
            <div class="container">
                <h2 class="section-title">作品展示</h2>



             <div class="works-grid">
                    <?php foreach ($latest_works as $work): ?>
                    <div class="work-card">
                        <img src="<?php echo htmlspecialchars($work['image_url']); ?>" alt="<?php echo htmlspecialchars($work['title']); ?>" class="work-image">
                        <div class="work-content">
                            <h3 class="work-title"><?php echo htmlspecialchars($work['title']); ?></h3>
                            <p class="work-description"><?php echo $work['description'], 0, 100 . '...'; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化变量和元素选择
            const slides = document.querySelectorAll('.slide');
            const dots = document.querySelectorAll('.slide-dot');
            const prevBtn = document.querySelector('.prev');
            const nextBtn = document.querySelector('.next');
            let currentSlide = 0;

            // 核心函数：显示指定索引的幻灯片
            function showSlide(n) {
                 // 移除所有active类
                slides.forEach(slide => slide.classList.remove('active'));
                dots.forEach(dot => dot.classList.remove('active'));
                // 计算并设置新的当前幻灯片(循环处理)
                currentSlide = (n + slides.length) % slides.length;
                // 添加active类
                slides[currentSlide].classList.add('active');
                dots[currentSlide].classList.add('active');
            }

            function nextSlide() {
                showSlide(currentSlide + 1);
            }

            function prevSlide() {
                showSlide(currentSlide - 1);
            }

            // 自动轮播(每5秒切换一次)
            let slideInterval = setInterval(nextSlide, 5000);

            // 点击导航点切换
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    clearInterval(slideInterval);
                    showSlide(index);
                    slideInterval = setInterval(nextSlide, 5000);
                });
            });

            // 点击箭头切换
            prevBtn.addEventListener('click', () => {
                clearInterval(slideInterval);
                prevSlide();
                slideInterval = setInterval(nextSlide, 5000);
            });

            nextBtn.addEventListener('click', () => {
                clearInterval(slideInterval);
                nextSlide();
                slideInterval = setInterval(nextSlide, 5000);
            });

            // 鼠标悬停时暂停轮播
            const slideshow = document.querySelector('.slideshow');
            slideshow.addEventListener('mouseenter', () => {
                clearInterval(slideInterval);
            });

            slideshow.addEventListener('mouseleave', () => {
                slideInterval = setInterval(nextSlide, 5000);
            });
        });
    </script>
</body>
</html> 