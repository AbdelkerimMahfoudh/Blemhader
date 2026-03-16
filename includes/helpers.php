<?php
/**
 * Blemhader - Helper functions
 */

/**
 * Handle video file upload. Returns web-relative path (e.g. uploads/videos/xxx.mp4) or empty string on failure.
 * @return string Path to use in video_url, or ''
 */
function handle_video_upload() {
    if (empty($_FILES['video_file']['name']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    $allowed = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['video_file']['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed, true)) {
        return '';
    }
    if ($_FILES['video_file']['size'] > (defined('MAX_VIDEO_SIZE') ? MAX_VIDEO_SIZE : 100 * 1024 * 1024)) {
        return '';
    }
    $dir = defined('UPLOADS_DIR') ? UPLOADS_DIR : (__DIR__ . '/../uploads/videos');
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION) ?: 'mp4';
    $ext = preg_match('/^[a-z0-9]+$/i', $ext) ? strtolower($ext) : 'mp4';
    $name = uniqid('vid_', true) . '.' . $ext;
    $path = $dir . '/' . $name;
    if (!move_uploaded_file($_FILES['video_file']['tmp_name'], $path)) {
        return '';
    }
    return 'uploads/videos/' . $name;
}

/**
 * Handle image file upload (thumbnails). Returns web-relative path (e.g. uploads/images/xxx.jpg) or empty string.
 * @return string Path to use in image_url, or ''
 */
function handle_image_upload() {
    if (empty($_FILES['image_file']['name']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['image_file']['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed, true)) return '';
    $max = defined('MAX_IMAGE_SIZE') ? MAX_IMAGE_SIZE : (5 * 1024 * 1024);
    if ($_FILES['image_file']['size'] > $max) return '';
    $dir = defined('UPLOADS_IMAGES_DIR') ? UPLOADS_IMAGES_DIR : (__DIR__ . '/../uploads/images');
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION) ?: 'jpg');
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) $ext = 'jpg';
    if ($ext === 'jpeg') $ext = 'jpg';
    $name = uniqid('img_', true) . '.' . $ext;
    if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $dir . '/' . $name)) return '';
    return 'uploads/images/' . $name;
}

/**
 * Get thumbnail URL for video (YouTube/Vimeo) or image for posts. Returns empty for direct video URLs.
 */
function item_thumb_url($item) {
    if (!empty($item['image_url'])) return $item['image_url'];
    $url = $item['video_url'] ?? '';
    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]+)#', $url, $m)) {
        return 'https://img.youtube.com/vi/' . $m[1] . '/mqdefault.jpg';
    }
    return '';
}

/**
 * Get preview text: excerpt if set, otherwise first paragraph of body (strip HTML, max ~200 chars).
 */
function post_preview($excerpt, $body, $max_len = 200) {
    if (!empty(trim($excerpt))) return trim($excerpt);
    $text = strip_tags($body);
    $text = preg_replace('/\s+/', ' ', trim($text));
    if (mb_strlen($text) <= $max_len) return $text;
    return mb_substr($text, 0, $max_len) . '…';
}

/**
 * Generate HTML for embedding a video from URL (YouTube, Vimeo, Facebook, direct .mp4, or local upload path)
 */
function embed_video($url) {
    if (empty($url)) return '';
    $url = trim($url);
    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]+)#', $url, $m)) {
        $id = $m[1];
        return '<iframe src="https://www.youtube.com/embed/' . htmlspecialchars($id) . '" frameborder="0" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" loading="lazy"></iframe>';
    }
    if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
        $id = $m[1];
        return '<iframe src="https://player.vimeo.com/video/' . (int)$id . '" frameborder="0" allowfullscreen allow="autoplay; fullscreen; picture-in-picture" loading="lazy"></iframe>';
    }
    if (preg_match('#(?:facebook\.com|fb\.com|fb\.watch|fburl\.com)#i', $url)) {
        $embed_url = 'https://www.facebook.com/plugins/video.php?href=' . rawurlencode($url) . '&show_text=false&width=560';
        return '<iframe src="' . htmlspecialchars($embed_url) . '" frameborder="0" allowfullscreen allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" loading="lazy" style="width:100%;height:100%;min-height:315px;"></iframe>';
    }
    if (preg_match('#\.(mp4|webm|ogg)(\?|$)#i', $url)) {
        return '<video src="' . htmlspecialchars($url) . '" controls playsinline preload="metadata" style="width:100%;height:100%;object-fit:cover;"></video>';
    }
    return '';
}
