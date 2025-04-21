<?php
require_once 'db.php';

// 启动会话并检查用户是否已登录
session_start();
$logged_in = isset($_SESSION['user_id']);

// 获取所有文章，按创建时间降序排序
$stmt = $pdo->query("SELECT * FROM posts ORDER BY create_time DESC");
$all_posts = $stmt->fetchAll();

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
    <title>个人简介 - 大学生活</title>
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

        /* 关于我部分样式 - 改为垂直布局 */
        .about-content {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0; /* 淡灰色边框 */
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* 个人照片样式 - 居中显示 */
        .about-image {
            max-width: 600px;
            margin: 30px auto;
            text-align: center;
        }

        .about-image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            filter: none; /* 移除黑白滤镜 */
            background-color: #fff; /* 白色背景 */
            border: 1px solid #e0e0e0; /* 淡灰色边框 */
        }

        .about-text {
            max-width: 800px;
            margin: 0 auto;
        }

        .about-text p {
            color: #444; /* 深灰色文字 */
            line-height: 1.8;
            margin-bottom: 25px;
            font-size: 16px;
            text-align: justify;
        }

        .about-text p:last-child {
            margin-bottom: 0;
        }

        /* 特色文本样式 */
        .highlight-text {
            font-weight: bold;
            color: #000;
        }

        /* 分割线 */
        .divider {
            width: 70%;
            height: 1px;
            background-color: #eee;
            margin: 40px auto;
        }

        /* 小标题 */
        .subtitle {
            font-size: 22px;
            color: #000;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
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
        <!-- 关于我部分 -->
        <section class="content-section">
            <div class="container">
                <h2 class="section-title">大学生活</h2>
                
                <div class="about-content">
                    <div class="about-text">
                        <p>大学时光，是人生中最为珍贵的阶段之一。踏入大学校门的那一刻，面对崭新的环境、陌生的面孔，心中既充满期待，又不免有些忐忑。从懵懂的大一新生，到即将毕业的学长学姐，每个人都在这个小小的象牙塔中经历着成长、蜕变，书写着属于自己的青春故事。</p>
                        
                        <p>在大学校园里，学习是我们的首要任务。从早晨的第一缕阳光照进教室，到深夜图书馆里的灯光依然明亮，莘莘学子们为了知识而努力着。专业课程的学习让我们逐渐接触到更深层次的知识，实验室里的探索则激发了我们的创新思维。期中考、期末考，每一次的考试都是对自己努力的检验，也是成长路上必不可少的经历。</p>
                    </div>
                    
                    <div class="about-image">
                        <img src="images/university-life.jpg" alt="大学校园生活">
                    </div>
                    
                    <div class="about-text">
                        <h3 class="subtitle">丰富多彩的课外活动</h3>
                        <p>大学生活远不止课堂和图书馆。各种学生社团为我们提供了展示自己的舞台：学生会锻炼了我们的组织能力，志愿者协会培养了我们的奉献精神，文学社激发了我们的创作灵感，运动社团则让我们拥有健康的体魄。每年的社团招新、文化节、运动会等活动，都是校园生活中不可或缺的精彩片段。</p>
                        
                        <p>在各类比赛中，我们尽情挥洒汗水和才华。编程大赛上，同学们熬夜写代码；辩论赛场上，言辞犀利的交锋；创新创业大赛中，天马行空的创意；艺术表演舞台上，动人心弦的演出。这些经历都成为了大学时光中最闪亮的回忆。</p>
                        
                        <h3 class="subtitle">宿舍生活与友谊</h3>
                        <p>宿舍是大学生的第二个家。六人间或四人间的集体生活，让来自天南海北的陌生人逐渐成为亲密无间的朋友。熄灯后的窃窃私语，期末周一起熬夜复习，周末结伴出游，一起分享零食、分担忧愁。宿舍生活教会我们包容与理解，也让我们收获了最真挚的友谊。</p>
                        
                        <p>友情是大学生活中最珍贵的礼物。在这里，我们遇到了志同道合的伙伴，一起学习、一起成长。一起参加社团活动，一起完成小组作业，一起策划班级活动，一起度过无数个难忘的日日夜夜。这些在大学中结下的友谊，往往能够延续一生。</p>
                        
                        <div class="divider"></div>
                        
                        <h3 class="subtitle">专业学习与实践</h3>
                        <p>随着专业课程的深入，我们逐渐从理论学习过渡到实践应用。计算机专业的同学们在实验室里调试程序；建筑系的学生熬夜赶制模型；医学院的学子在模拟病房中练习诊断；文学院的才子佳人挥毫泼墨，创作佳作。通过实践，我们将理论知识转化为实际能力，为未来的职业发展打下坚实基础。</p>
                        
                        <p>校外实习是大学生活的另一个重要环节。走出校园，进入企业，我们有机会接触社会，了解行业，锻炼自己的专业技能和适应能力。实习经历不仅丰富了我们的简历，更让我们对未来的职业发展有了更清晰的规划。</p>
                        
                        <h3 class="subtitle">自我成长与蜕变</h3>
                        <p>大学四年，最重要的收获莫过于自我的成长与蜕变。从依赖父母的高中生，到独立自主的成年人；从迷茫困惑的新生，到目标明确的毕业生。我们学会了独立生活，学会了时间管理，学会了面对挫折，也学会了坚持自己的梦想。</p>
                        
                        <p>成长的过程并非一帆风顺。学习上的挫折、人际关系的困扰、未来规划的迷茫，每个大学生都会面临各种各样的挑战。但正是这些挑战，让我们变得更加坚强、更加成熟。每一次克服困难的经历，都是宝贵的人生财富。</p>
                        
                        <div class="divider"></div>
                        
                        <p>大学时光稍纵即逝，但它所带给我们的影响却是终身的。在这个特殊的人生阶段，我们不仅获取了知识，更重要的是学会了如何思考、如何与人相处、如何规划人生。无论未来如何，大学的美好回忆都将成为我们心中最珍贵的宝藏，激励我们在人生的道路上不断前行、不断超越。</p>
                        
                        <p class="highlight-text">愿每一位大学生都能珍惜这段美好时光，在知识的海洋中尽情遨游，在青春的舞台上尽情绽放，书写属于自己的精彩篇章！</p>
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