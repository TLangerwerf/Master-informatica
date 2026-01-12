<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/db.php';

if (!is_teacher()) {
  http_response_code(403);
  echo "403 - Alleen docenten hebben toegang.";
  exit;
}

$pdo = db();

/**
 * Alle leerlingen + aantal gegeven reviews (0 ook zichtbaar)
 * Let op: rol kan 'student' of 'leerling' zijn in jouw project.
 */
$rows = $pdo->query("
  SELECT
    u.id,
    u.display_name,
    u.username,
    u.class_id,
    c.name AS class_name,
    COUNT(r.id) AS review_count,
    MAX(r.created_at) AS last_review_at
  FROM users u
  LEFT JOIN classes c ON c.id = u.class_id
  LEFT JOIN reviews r ON r.student_id = u.id
  WHERE u.role IN ('student','leerling')
  GROUP BY u.id
  ORDER BY review_count DESC, u.display_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$max = 0;
foreach ($rows as $r) {
  $cnt = (int)$r['review_count'];
  if ($cnt > $max) $max = $cnt;
}
if ($max <= 0) $max = 1;

/** eenvoudige kleurenschaal (wit -> donkerblauw tint) */
function heat_bg(int $count, int $max): string {
  $t = max(0.0, min(1.0, $count / $max)); // 0..1
  // Interpoleer naar donkerblauw (RGB: 30,58,138) met transparantie
  // Hoe meer count, hoe donkerder.
  $alpha = 0.06 + ($t * 0.28); // 0.06..0.34
  $r = 30; $g = 58; $b = 138;
  return "rgba($r,$g,$b," . number_format($alpha, 3, '.', '') . ")";
}

function h(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES);
}
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Feedback teller (tabel)</title>
  <link rel="stylesheet" href="assets/css/feedback_table.css">
  <link rel="stylesheet" href="assets/css/ui.css">
</head>
<body>
      <div class='wrap'>
<div class="brand">
        <h1>Websites</h1>
        <div class="sub">Overzicht van ingezonden websites</div>
      </div>

      <div class="topbar">
      <!-- Deze lege menu-div staat hier als "spacer" (laten staan voor layout) -->
      <div class="menu">
        
      </div>

      <!-- Menu -->

      <div class="menu">
        <?php if (is_teacher()): ?>
          <a href="users_admin.php" class="btn primary">
            Gebruikersbeheer
          </a>
          <a href="group_feedback.php" class="btn primary">
            Feedback per groep
          </a>
          <a href="feedback-counts.php" class="btn primary">
            Feedback per leerling
          </a>
        <?php endif; ?>
      </div>

        </div>

<table class="ftable" id="feedbackTable">
  <thead>
    <tr>
      <th data-type="text">Naam</th>
      <th data-type="text">Klas</th>
      <th data-type="number" class="num">Aantal feedback</th>
      <th data-type="text">Laatste feedback</th>
    </tr>
  </thead>

  <tbody>
    <?php foreach ($rows as $r): 
      $count = (int)$r['review_count'];
      $bg = heat_bg($count, $max);
    ?>
      <tr
        style="background: <?= $bg ?>;"
        data-name="<?= h((string)$r['display_name']) ?>"
        data-class="<?= h((string)($r['class_name'] ?? '')) ?>"
        data-count="<?= $count ?>"
        data-last="<?= h((string)($r['last_review_at'] ?? '')) ?>"
        title="Klik op kolomkop om te sorteren"
      >
        <td>
          <div class="name"><?= h((string)$r['display_name']) ?></div>
          <div class="muted"><?= h((string)($r['username'] ?? '')) ?></div>
        </td>

        <td>
          <?php if (!empty($r['class_name'])): ?>
            <span class="badge"><?= h((string)$r['class_name']) ?></span>
          <?php else: ?>
            <span class="muted">class_id <?= (int)($r['class_id'] ?? 0) ?></span>
          <?php endif; ?>
        </td>

        <td class="num strong"><?= $count ?></td>

        <td class="muted">
          <?= h((string)($r['last_review_at'] ?? '—')) ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</div

<script src="assets/scripts/feedback_table.js"></script>
</body>
</html>
