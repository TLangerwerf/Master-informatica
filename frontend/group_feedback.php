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

$user = current_user();
$teacherId = (int)$user['id'];

$pdo = db();

// --- Groepen ophalen waar deze docent een site voor heeft (via sites.created_by_teacher_id) ---
$groups = $pdo->prepare("
  SELECT DISTINCT
    g.id,
    g.name,
    c.name AS class_name
  FROM sites s
  JOIN `groups` g ON g.id = s.group_id
  LEFT JOIN classes c ON c.id = s.class_id
  WHERE s.created_by_teacher_id = :tid
  ORDER BY c.name, g.name
");
$groups->execute([':tid' => $teacherId]);
$groups = $groups->fetchAll(PDO::FETCH_ASSOC);

// gekozen groep
$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

// Scherm aanpassen grootte
$previewSize = $_GET['preview_size'] ?? '600';

if ($previewSize === 'new') {
  header('Location: ' . $site['url']);
  exit;
}

$previewSize = in_array($previewSize, ['400', '600', '800'], true)
  ? (int)$previewSize
  : 600;

// --- Site + reviews ophalen voor gekozen groep (simpel: nieuwste site van die groep) ---
$site = null;
$reviews = [];

if ($groupId > 0) {
  $stmt = $pdo->prepare("
    SELECT
      s.*
    FROM sites s
    WHERE s.group_id = :gid
      AND s.created_by_teacher_id = :tid
    ORDER BY s.created_at DESC
    LIMIT 1
  ");
  $stmt->execute([':gid' => $groupId, ':tid' => $teacherId]);
  $site = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($site) {
    $rev = $pdo->prepare("
      SELECT
        r.id,
        r.score,
        r.comment,
        r.created_at,
        u.display_name,
        u.username,
        u.role
      FROM reviews r
      LEFT JOIN users u ON u.id = r.student_id
      WHERE r.site_id = :sid
      ORDER BY r.created_at DESC
    ");
    $rev->execute([':sid' => (int)$site['id']]);
    $reviews = $rev->fetchAll(PDO::FETCH_ASSOC);
  }
}
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Feedback per groep</title>
  <link rel="stylesheet" href="assets/css/ui.css">
  <link rel="stylesheet" href="assets/css/group-feedback.css">
</head>
<body>
<div class="wrap">

  <div class="topbar">
    <div>
      <h1 style="margin:0 0 6px;">Feedback per groep</h1>
      <div class="muted">Kies een groep en bekijk de website + alle feedback (met feedbackgever).</div>
    </div>
    <!-- Menu -->
       <div class="menu">
       <?php if (is_teacher()): ?>
        <a href="users_admin.php" class="btn primary">
          Gebruikersbeheer
        </a>
        <a href="group_feedback.php" class="btn primary">
          Alle feedback
        </a>
        <a href="grou.php" class="btn primary">
          Websites
        </a>
      <?php endif; ?>
       </div>
  </div>
<div class = "keuzemenu">
  <!-- klas kiezen -->
  <div class="panel">
    <form method="get" action="group_feedback.php" class="btnrow">
      <div style="min-width:320px; flex:1;">
        <div class="label">Kies groep</div>
          <select class="input" name="group_id" onchange="this.form.submit()">
            <option value="0">— Selecteer een groep —</option>
            <?php foreach ($groups as $g): ?>
              <option value="<?= (int)$g['id'] ?>" <?= ((int)$g['id'] === $groupId) ? 'selected' : '' ?>>
                <?= htmlspecialchars(($g['class_name'] ?? 'Onbekende klas') . ' · ' . ($g['name'] ?? ''), ENT_QUOTES) ?>
              </option>
            <?php endforeach; ?>
          </select>
      </div>

    </form>
  </div>

  
  <!--resolutie aanpassen -->
<div class="preview-controls">
  Kies een resolutie
  <form method="get" action="group_feedback.php" class="preview-form">
    <input type="hidden" name="group_id" value="<?= (int)$groupId ?>">

    <label>
      <select name="preview_size" class="input" onchange="this.form.submit()">
        <option value="400" <?= ($_GET['preview_size'] ?? '600') === '400' ? 'selected' : '' ?>>400 × 400</option>
        <option value="600" <?= ($_GET['preview_size'] ?? '600') === '600' ? 'selected' : '' ?>>600 × 600</option>
        <option value="800" <?= ($_GET['preview_size'] ?? '600') === '800' ? 'selected' : '' ?>>800 × 800</option>
        <option value="new">Open in nieuw venster</option>
      </select>
    </label>


  </form>
</div>
</div>

  <?php if ($groupId > 0 && !$site): ?>
    <div class="panel">
      <div class="empty">Geen website gevonden voor deze groep (of niet door jou aangemaakt).</div>
    </div>
  <?php endif; ?>

  <?php if ($site): ?>
    <div class="panel">
      <div class="split">
        <div>
            <div style="font-weight:800; font-size:16px; margin-bottom:6px;">
            <?= htmlspecialchars((string)$site['title'], ENT_QUOTES) ?>
            </div>
              <div class="muted" style="margin-bottom:10px;">
                <?= htmlspecialchars((string)($site['description'] ?? ''), ENT_QUOTES) ?>
              </div>

              <div class="btnrow" style="margin-bottom:12px;">
                <a class="btn" href="<?= htmlspecialchars((string)$site['url'], ENT_QUOTES) ?>" target="_blank" rel="noopener noreferrer">
                  Open website
                </a>
                <div class="muted" style="font-size:13px;">
                  URL: <?= htmlspecialchars((string)$site['url'], ENT_QUOTES) ?>
                </div>
              </div>
<div class="splitscreen">
              <div class="preview-wrap" style="width:<?= $previewSize ?>px; height:<?= $previewSize ?>px;">
                <iframe
                  loading="lazy"
                  src="<?= htmlspecialchars($site['url'], ENT_QUOTES) ?>"
                  title="Website preview"
                ></iframe>
              </div>
        

        <div>
          <div style="font-weight:800; font-size:16px; margin-bottom:10px;">Feedback</div>

          <?php if (count($reviews) === 0): ?>
            <div class="empty">Er is nog geen feedback gegeven op deze website.</div>
          <?php else: ?>
            <table class = "feedback-table">
              <thead>
                <tr>
                  <th style="width:10px;">Score</th>
                  <th style="width:50%;">Comment</th>
                  <th style="width:20%;">Feedbackgever</th>
                  <th style="width:20%;">Datum</th>

                </tr>
              </thead>
              <tbody>
                <?php foreach ($reviews as $r): ?>
                  <tr>
                    <td class="score"><?= (int)$r['score'] ?>/5</td>
                    <td><?= nl2br(htmlspecialchars((string)($r['comment'] ?? ''), ENT_QUOTES)) ?></td>
                    <td>
                      <div style="font-weight:700;">
                        <?= htmlspecialchars((string)($r['display_name'] ?? 'Onbekend'), ENT_QUOTES) ?>
                      </div>
                      <div class="muted" style="font-size:12px;">
                        <?= htmlspecialchars((string)($r['role'] ?? ''), ENT_QUOTES) ?>
                        <?php if (!empty($r['username'])): ?>
                          · <?= htmlspecialchars((string)$r['username'], ENT_QUOTES) ?>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="muted">
                      <?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>

        </div>
      </div>
    </div>
  <?php endif; ?>
                        </div>
</div>
</body>
</html>
