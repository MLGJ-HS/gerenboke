<?php
require_once 'db.php';

// 检查是否是POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $author = isset($_POST['author']) ? trim($_POST['author']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    // 简单验证
    $errors = [];
    
    if ($post_id <= 0) {
        $errors[] = '无效的文章ID';
    }
    
    if (empty($author)) {
        $errors[] = '请输入您的昵称';
    }
    
    if (empty($content)) {
        $errors[] = '请输入评论内容';
    }
    
    // 如果没有错误，保存评论
    if (empty($errors)) {
        try {
            // 检查文章是否存在
            $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();
            
            if (!$post) {
                $errors[] = '文章不存在';
            } else {
                // 插入评论
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, author, content, create_time) VALUES (?, ?, ?, NOW())");
                $success = $stmt->execute([$post_id, $author, $content]);
                
                if ($success) {
                    // 评论成功，重定向回文章页
                    header("Location: post.php?id=$post_id&comment=success");
                    exit;
                } else {
                    $errors[] = '评论保存失败，请稍后再试';
                }
            }
        } catch (PDOException $e) {
            $errors[] = '数据库错误：' . $e->getMessage();
        }
    }
    
    // 如果有错误，重定向回文章页并显示错误
    if (!empty($errors)) {
        $error_str = urlencode(implode(', ', $errors));
        header("Location: post.php?id=$post_id&error=$error_str");
        exit;
    }
} else {
    // 如果不是POST请求，重定向到首页
    header('Location: index.php');
    exit;
}
?> 