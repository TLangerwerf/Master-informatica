<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/db.php';

$user = current_user();
if (!$user) {
  header('Location: index.php');
  exit;
}

$pdo = db();

// Check of users.group_id bestaat
$hasGroupId = false;
try {
  $cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($cols as $c) {
    if (($c['Field'] ?? '') === 'group_id') { $hasGroupId = true; break; }
  }
} catch (Throwable $e) {
  // laat hieronder foutmelding zien
}

// Haal group_id van user uit DB
$groupId = 0;
$classId = (int)($user['class_id'] ?? 0);

if ($hasGroupId) {
  $stmt = $pdo->prepare("SELECT group_id, class_id FROM users WHERE id = :id LIMIT 1");
  $stmt->execute([':id' => (int)$user['id']]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $groupId = (int)($row['group_id'] ?? 0);
  $classId = (int)($row['class_id'] ?? $classId);
}

// Preview options
$previewSize = $_GET['preview_size'] ?? '600';
if (!in_array($previewSize, ['400','600','800'], true)) $previewSize = '600';
$previewPx = (int)$previewSize;

// Groep + klas info (optioneel)
$groupInfo = null;
if ($groupId > 0) {
  $gi = $pdo->prepare("
    SELECT g.id, g.name, c.name AS class_name
    FROM `groups` g
    LEFT JOIN classes c ON c.id = :cid
    WHERE g.id = :gid
    LIMIT 1
  ");
  $gi->execute([':gid' => $groupId, ':cid' => $classId]);
  $groupInfo = $gi->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Sites van deze groep (toon desnoods meerdere)
$sites = [];
if ($groupId > 0) {
  $st = $pdo->prepare("
    SELECT id, title, url, description, created_at
    FROM sites
    WHERE group_id = :gid
    ORDER BY created_at DESC
  ");
  $st->execute([':gid' => $groupId]);
  $sites = $st->fetchAll(PDO::FETCH_ASSOC);
}

// Helper: reviews ophalen per site (ZONDER feedbackgever)
function fetch_reviews(PDO $pdo, int $siteId): array {
  $q = $pdo->prepare("
    SELECT score, comment, created_at
    FROM reviews
    WHERE site_id = :sid
    ORDER BY created_at DESC
  ");
  $q->execute([':sid' => $siteId]);
  return $q->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Feedback van mijn groep</title>
  <link rel="stylesheet" href="assets/css/ui.css">
  <link rel="stylesheet" href="assets/css/my_group_reviews.css">
</head>
<body>
<div class="wrap">

  <div class="topbar">
    <div class="brand">
      <h1>Feedback van mijn groep</h1>
      <div class="sub">
        <?php if ($groupInfo): ?>
          <?= htmlspecialchars((string)($groupInfo['class_name'] ?? ''), ENT_QUOTES) ?> ·
          Groep: <?= htmlspecialchars((string)($groupInfo['name'] ?? ''), ENT_QUOTES) ?>
        <?php else: ?>
          Overzicht van reviews die jullie websites hebben gekregen.
        <?php endif; ?>
      </div>
    </div>

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

  <?php if (!$hasGroupId): ?>
    <div class="panel">
      <div class="empty">
        Deze pagina kan je groep niet bepalen, omdat <strong>users.group_id</strong> niet bestaat.
        Voeg een <code>group_id</code>-kolom toe aan <code>users</code> of koppel groep-lidmaatschap op een andere manier.
      </div>
    </div>
  <?php elseif ($groupId <= 0): ?>
    <div class="panel">
      <div class="empty">
        Je hebt nog geen <strong>groep</strong> gekoppeld aan je account (group_id = 0).
        Vraag je docent om je aan een groep te koppelen.
      </div>
    </div>
  <?php elseif (count($sites) === 0): ?>
    <div class="panel">
      <div class="empty">
        Er zijn nog geen websites gevonden voor jullie groep.
      </div>
    </div>
  <?php else: ?>

    <div class="panel">
      <form method="get" action="my_group_reviews.php" class="controls">
        <div>
          <div class="label">Preview grootte</div>
          <select class="input" name="preview_size" onchange="this.form.submit()">
            <option value="400" <?= $previewSize === '400' ? 'selected' : '' ?>>400 × 400</option>
            <option value="600" <?= $previewSize === '600' ? 'selected' : '' ?>>600 × 600</option>
            <option value="800" <?= $previewSize === '800' ? 'selected' : '' ?>>800 × 800</option>
          </select>
        </div>
      </form>
    </div>

    <?php foreach ($sites as $s): ?>
      <?php $reviews = fetch_reviews($pdo, (int)$s['id']); ?>

      <div class="panel">
        <div class="split">
          <div>
            <div class="site-title"><?= htmlspecialchars((string)$s['title'], ENT_QUOTES) ?></div>
            <?php if (!empty($s['description'])): ?>
              <div class="muted" style="margin-bottom:10px;"><?= htmlspecialchars((string)$s['description'], ENT_QUOTES) ?></div>
            <?php endif; ?>

            <div class="btnrow">
              <a class="btn" href="<?= htmlspecialchars((string)$s['url'], ENT_QUOTES) ?>" target="_blank" rel="noopener noreferrer">
                Open in nieuw venster
              </a>
            </div>

            <div class="label" style="margin-top:12px;">Preview (<?= $previewPx ?>×<?= $previewPx ?>)</div>
            <div class="previewWrap" style="width:<?= $previewPx ?>px; height:<?= $previewPx ?>px;">
              <iframe loading="lazy" src="<?= htmlspecialchars((string)$s['url'], ENT_QUOTES) ?>"></iframe>
            </div>

            <div class="muted" style="margin-top:8px; font-size:12px;">
              Als een website embedden blokkeert (X-Frame-Options), gebruik “Open in nieuw venster”.
            </div>
          </div>

          <div>
            <div class="section-title">Ontvangen feedback</div>

            <?php if (count($reviews) === 0): ?>
              <div class="empty">Nog geen reviews ontvangen.</div>
            <?php else: ?>
              <table class="feedback-table">
                <thead>
                  <tr>
                    <th style="width:90px;">Score</th>
                    <th>Comment</th>
                    <th style="width:170px;">Datum</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($reviews as $r): ?>
                    <tr>
                      <td class="score"><?= (int)$r['score'] ?>/5</td>
                      <td class="comment"><?= nl2br(htmlspecialchars((string)($r['comment'] ?? ''), ENT_QUOTES)) ?></td>
                      <td class="muted"><?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>

          </div>
        </div>
      </div>

    <?php endforeach; ?>
  <?php endif; ?>

</div>
</body>
</html>
