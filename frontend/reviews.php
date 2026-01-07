<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

header('Content-Type: application/json; charset=utf-8');

$siteId = (int)($_GET['site_id'] ?? 0);
if ($siteId <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'site_id ontbreekt']);
  exit;
}

$sql = "
SELECT
  score,
  comment,
  created_at
FROM reviews
WHERE site_id = :site_id
ORDER BY created_at DESC
LIMIT 50
";

$stmt = db()->prepare($sql);
$stmt->execute([':site_id' => $siteId]);
$rows = $stmt->fetchAll();

echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
