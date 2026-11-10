<?php
/**
 * Blemhader - Autonomous news ingestion endpoint.
 *
 * POST JSON, authenticated with a secret token, used by the scheduled hourly
 * agent to publish news without the monitor UI. It ONLY ever inserts into the
 * `news` category as a published article attributed to the system bot account.
 * It never updates or deletes, and de-duplicates on `source_ref`.
 *
 *   curl -X POST https://blemhader.com/api/ingest_news.php \
 *     -H "X-Api-Key: <INGEST_TOKEN>" -H "Content-Type: application/json" \
 *     -d '{"scope":"local","title_ar":"...","title_fr":"...","body_ar":"...",
 *          "body_fr":"...","excerpt_ar":"...","excerpt_fr":"...",
 *          "image_url":"https://...","source_ref":"https://source/article"}'
 */
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

function out($code, $payload) {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Method + kill-switch + auth -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(405, ['ok' => false, 'error' => 'Method not allowed']);
}
if (!INGEST_ENABLED) {
    out(503, ['ok' => false, 'error' => 'Ingestion disabled']);
}
$provided = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (INGEST_TOKEN === '' || !is_string($provided) || !hash_equals(INGEST_TOKEN, $provided)) {
    out(403, ['ok' => false, 'error' => 'Forbidden']);
}

global $pdo;
if (!$pdo) {
    out(500, ['ok' => false, 'error' => 'Database unavailable']);
}

// --- Parse + validate -------------------------------------------------------
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    out(400, ['ok' => false, 'error' => 'Invalid JSON']);
}

// --- Admin actions (token-guarded): reclassify or delete a news/economy item.
// Only touches articles in the news/economy categories. Used to fix mis-filed
// stories (e.g. move an economy story out of news) or remove duplicates.
$action = $data['action'] ?? '';
if ($action === 'recategorize' || $action === 'delete') {
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) out(400, ['ok' => false, 'error' => 'id (positive int) required']);
    $cids = [];
    foreach ($pdo->query('SELECT id, slug FROM categories WHERE slug IN ("news","economy")') as $r) {
        $cids[$r['slug']] = (int)$r['id'];
    }
    if (!$cids) out(500, ['ok' => false, 'error' => 'categories missing']);
    $in = implode(',', array_map('intval', array_values($cids)));
    if ($action === 'delete') {
        $st = $pdo->prepare("DELETE FROM articles WHERE id = ? AND category_id IN ($in)");
        $st->execute([$id]);
        out(200, ['ok' => true, 'deleted' => $id, 'rows' => $st->rowCount()]);
    }
    $target = $data['category'] ?? '';
    if (!isset($cids[$target])) out(400, ['ok' => false, 'error' => 'category must be news|economy']);
    $st = $pdo->prepare("UPDATE articles SET category_id = ? WHERE id = ? AND category_id IN ($in)");
    $st->execute([$cids[$target], $id]);
    out(200, ['ok' => true, 'moved' => $id, 'category' => $target, 'rows' => $st->rowCount()]);
}

// Target category for an insert: 'news' (default) or 'economy'.
$category = in_array($data['category'] ?? 'news', ['news', 'economy'], true) ? $data['category'] : 'news';

$scope = in_array($data['scope'] ?? '', ['local', 'regional', 'global'], true) ? $data['scope'] : null;
$title_ar = trim($data['title_ar'] ?? '');
$title_fr = trim($data['title_fr'] ?? '');
$body_ar  = trim($data['body_ar'] ?? '');
$body_fr  = trim($data['body_fr'] ?? '');
$excerpt_ar = trim($data['excerpt_ar'] ?? '');
$excerpt_fr = trim($data['excerpt_fr'] ?? '');
$image_url  = trim($data['image_url'] ?? '');
$source_ref = trim($data['source_ref'] ?? '');
// Optional: caller may request immediate publication. Defaults to draft.
$status     = (($data['status'] ?? 'draft') === 'published') ? 'published' : 'draft';

if ($category === 'economy' && !$scope) $scope = 'local'; // scope is irrelevant for economy
if (!$scope)                       out(400, ['ok' => false, 'error' => 'scope must be local|regional|global']);
if ($title_ar === '' || $title_fr === '') out(400, ['ok' => false, 'error' => 'title_ar and title_fr are required']);
if ($body_ar === '' && $body_fr === '')   out(400, ['ok' => false, 'error' => 'at least one body is required']);
if ($source_ref === '')            out(400, ['ok' => false, 'error' => 'source_ref is required for de-duplication']);
if ($image_url !== '' && !preg_match('#^https?://#i', $image_url)) {
    out(400, ['ok' => false, 'error' => 'image_url must be an http(s) URL']);
}

// Every published body must be a substantial article: at least 5 paragraphs
// (separated by blank lines, i.e. "\n\n").
$MIN_PARAGRAPHS = 5;
foreach (['body_ar' => $body_ar, 'body_fr' => $body_fr] as $field => $b) {
    if ($b === '') continue;
    $paras = array_filter(array_map('trim', preg_split('/\n\s*\n+/', trim($b))), 'strlen');
    if (count($paras) < $MIN_PARAGRAPHS) {
        out(400, ['ok' => false, 'error' => "$field must have at least $MIN_PARAGRAPHS paragraphs (separate paragraphs with a blank line)"]);
    }
}

try {
    // De-dup: same source already ingested?
    $dup = $pdo->prepare('SELECT id FROM articles WHERE source_ref = ? LIMIT 1');
    $dup->execute([$source_ref]);
    if ($existing = $dup->fetch()) {
        out(200, ['ok' => true, 'skipped' => 'duplicate', 'id' => (int)$existing['id']]);
    }

    // Resolve target category (news|economy) + bot author.
    $cat = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
    $cat->execute([$category]);
    $category_id = (int)($cat->fetchColumn() ?: 0);
    if (!$category_id) out(500, ['ok' => false, 'error' => "$category category missing"]);

    $bot = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $bot->execute(['blemhader_bot']);
    $author_id = (int)($bot->fetchColumn() ?: 0) ?: null;

    // Regional rate limit: at most ONE bot article per day. Use created_at (set
    // on every insert) since drafts have no published_at.
    if ($category === 'news' && $scope === 'regional' && $author_id) {
        $rc = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE scope = "regional" AND author_id = ? AND DATE(created_at) = CURDATE()');
        $rc->execute([$author_id]);
        if ((int)$rc->fetchColumn() >= 1) {
            out(200, ['ok' => true, 'skipped' => 'regional_daily_limit']);
        }
    }

    // Insert. Defaults to a draft (editor adds image + publishes); when the
    // caller sets status="published", publish immediately with published_at=now.
    $published_at = $status === 'published' ? date('Y-m-d H:i:s') : null;
    $slug = 'news-bot-' . time() . '-' . substr(md5($source_ref), 0, 8);
    $stmt = $pdo->prepare('INSERT INTO articles (category_id, scope, author_id, show_author, source_ref, title_ar, title_fr, slug, excerpt_ar, excerpt_fr, body_ar, body_fr, image_url, status, published_at) VALUES (?,?,?,0,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $category_id, $scope, $author_id, $source_ref,
        $title_ar, $title_fr, $slug,
        $excerpt_ar ?: null, $excerpt_fr ?: null,
        $body_ar ?: null, $body_fr ?: null,
        $image_url ?: null,
        $status, $published_at,
    ]);
    out(201, ['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'category' => $category, 'scope' => $scope, 'status' => $status]);
} catch (PDOException $e) {
    // Unique race on source_ref → treat as duplicate.
    if ($e->getCode() == 23000) {
        out(200, ['ok' => true, 'skipped' => 'duplicate']);
    }
    out(500, ['ok' => false, 'error' => 'insert failed']);
}