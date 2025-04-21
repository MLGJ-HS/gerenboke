<?php
require_once 'db.php';

try {
    // 检查字段是否已存在
    $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'category'");
    $column_exists = $stmt->fetchColumn();
    
    if (!$column_exists) {
        // 添加分类字段
        $pdo->exec("ALTER TABLE posts ADD COLUMN category VARCHAR(50) DEFAULT '技术' AFTER content");
        
        // 随机分配一些分类值到现有文章
        $categories = ['技术', '生活', '学习', '资源', '其他'];
        $posts = $pdo->query("SELECT id FROM posts")->fetchAll();
        
        foreach ($posts as $post) {
            $random_category = $categories[array_rand($categories)];
            $stmt = $pdo->prepare("UPDATE posts SET category = ? WHERE id = ?");
            $stmt->execute([$random_category, $post['id']]);
        }
        
        echo "成功添加分类字段，并为现有文章分配了分类。";
    } else {
        echo "分类字段已存在，无需添加。";
    }
} catch(PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?> 