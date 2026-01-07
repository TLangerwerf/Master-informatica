<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/db.php';

// Logout (simpel)
if (isset($_GET['logout'])) {
  logout_user();
  header('Location: index.php');
  exit;
}

$loginError = false;

// Login (simpel, via dezelfde pagina)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = $_POST['username'] ?? '';
  $p = $_POST['password'] ?? '';
  if (!login_attempt($u, $p)) {
    $loginError = true;
  } else {
    header('Location: index.php');
    exit;
  }
}

$user = current_user();

$user = current_user();
$canReview = $user && in_array(($user['role'] ?? ''), ['student', 'teacher'], true);

$userId = $user ? (int)$user['id'] : 0;

//*SQL info ophalen*//
$sql = "
SELECT
  s.id,
  s.title,
  s.url,
  s.description,
  g.name AS group_name,
  c.name AS class_name,
  COUNT(r.id) AS review_count,
  AVG(r.score) AS avg_score,

  ur.score   AS my_score,
  ur.comment AS my_comment

FROM sites s
LEFT JOIN `groups` g ON g.id = s.group_id
LEFT JOIN classes  c ON c.id = s.class_id
LEFT JOIN reviews  r ON r.site_id = s.id

LEFT JOIN reviews ur
  ON ur.site_id = s.id
 AND ur.student_id = {$userId}

GROUP BY s.id
ORDER BY s.created_at DESC
";
$sites = db()->query($sql)->fetchAll();



## Login shit regelen.##
$canReview = $user && in_array(($user['role'] ?? ''), ['student', 'teacher'], true);

$reviewOk = false;
$reviewErr = '';

$reviewOk = false;
$reviewErr = '';

$modalOpen = false;
$modalSiteId = 0;
$modalScore = '';
$modalComment = '';

##Voorkomt dat overlay blijft hangen bij fouten.##
if (!empty($_SESSION['review_flash'])) {
  $f = $_SESSION['review_flash'];
  unset($_SESSION['review_flash']);

  $modalOpen    = true;
  $reviewErr    = (string)($f['err'] ?? '');
  $modalSiteId  = (int)($f['site_id'] ?? 0);
  $modalScore   = (string)($f['score'] ?? '');
  $modalComment = (string)($f['comment'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_review') {

  $modalOpen = true;
  $modalSiteId  = (int)($_POST['site_id'] ?? 0);
  $modalScore   = (string)($_POST['score'] ?? '');
  $modalComment = (string)($_POST['comment'] ?? '');

  if (!$canReview) {
    $reviewErr = 'Log eerst in.';
  } else {
    $siteId  = $modalSiteId;
    $score   = (int)$modalScore;
    $comment = trim($modalComment);

    if ($siteId <= 0) $reviewErr = 'Ongeldige website.';
    else if ($score < 1 || $score > 5) $reviewErr = 'Score moet 1 t/m 5 zijn.';
    else if ($comment === '') $reviewErr = 'Comment is verplicht.';
    else {
      $check = db()->prepare("SELECT id FROM reviews WHERE site_id = :site AND student_id = :sid LIMIT 1");
      $check->execute([':site' => $siteId, ':sid' => (int)$user['id']]);
      $existing = $check->fetch();

      if ($existing) {
        $upd = db()->prepare("
          UPDATE reviews
          SET score = :score, comment = :comment
          WHERE id = :id
        ");
        $upd->execute([
          ':score' => $score,
          ':comment' => $comment,
          ':id' => (int)$existing['id'],
        ]);
      } else {
        $ins = db()->prepare("
          INSERT INTO reviews (site_id, student_id, score, comment, created_at)
          VALUES (:site, :sid, :score, :comment, NOW())
        ");
        $ins->execute([
          ':site' => $siteId,
          ':sid' => (int)$user['id'],
          ':score' => $score,
          ':comment' => $comment,
        ]);
      }

      $reviewOk = true;
            header('Location: index.php?review=added');
      exit;
    }
      if ($reviewErr !== '') {
    $_SESSION['review_flash'] = [
      'err'     => $reviewErr,
      'site_id' => $modalSiteId,
      'score'   => $modalScore,
      'comment' => $modalComment,
    ];

    header('Location: index.php');
    exit;
  }
  }
}



?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Websites</title>
  <link rel="stylesheet" href="assets/css/ui.css">
</head>

<body>

  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <h1>Websites</h1>
        <div class="sub">Overzicht van ingezonden websites</div>
      </div>
      <div class="menu">
        <?php if ($user): ?>
          <a href="my_group_reviews.php" class="btn primary">Mijn groepsfeedback</a>
        <?php endif; ?>
      </div>

      <!-- Login  -->
      <div class="auth">
        <?php if (!$user): ?>
          <div class="auth-title">Inloggen</div>

          <form class="auth-form" method="post" action="index.php">
            <input class="input" type="text" name="username" placeholder="username" required>
            <input class="input" type="password" name="password" placeholder="password" required>
            <button class="btn" type="submit">Login</button>
          </form>

          <?php if ($loginError): ?>
            <div class="auth-error">Login mislukt. Probeer opnieuw.</div>
          <?php endif; ?>

        <?php else: ?>
          <div class="auth-row">
            <div>
              <div class="auth-who">Ingelogd als <?= $user['display_name'] ?></div>
              <div class="auth-small"><?= $user['role'] ?> · class_id <?= (int)$user['class_id'] ?></div>
            </div>
            <a class="btn secondary" href="index.php?logout=1">Logout</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Grid met 200x200 previews -->
    <div class="grid">
      <?php foreach ($sites as $s): ?>
        <div class="card">

          <a class="preview" href="<?= $s['url'] ?>" target="_blank" rel="noopener">
            <iframe loading="lazy" src="<?= $s['url'] ?>"></iframe>
          </a>

          <div class="content">
            <div class="title"><?= $s['title'] ?></div>

            <div class="meta">
              <?php if (!empty($s['class_name'])): ?>
                <span class="badge">Klas: <?= $s['class_name'] ?></span>
              <?php endif; ?>
              <?php if (!empty($s['group_name'])): ?>
                <span class="badge">Groep: <?= $s['group_name'] ?></span>
              <?php endif; ?>
            </div>

            <?php if (!empty($s['description'])): ?>
              <div class="desc"><?= $s['description'] ?></div>
            <?php else: ?>
              <div class="desc muted">Geen beschrijving.</div>
            <?php endif; ?>
              
            <!--Review count en avg!-->
            <div class="stats">
              <span>
                Reviews: <?= (int)$s['review_count'] ?>
                <?php if ((int)$s['review_count'] > 0): ?>
                  · Gem.: <?= number_format((float)$s['avg_score'], 1) ?>/5
                <?php endif; ?>
              </span>
                </div>
              <!--Knoppen REVIEW en Bekijk-->  
              
              <div class="actions fullwidth">
                <?php if ($canReview): ?>
                    <button
                      class="btnlink primary js-review"
                      type="button"
                      data-site-id="<?= (int)$s['id'] ?>"
                      data-site-title="<?= htmlspecialchars($s['title'] ?? '', ENT_QUOTES) ?>"
                      data-my-score="<?= isset($s['my_score']) ? (int)$s['my_score'] : '' ?>"
                      data-my-comment="<?= htmlspecialchars($s['my_comment'] ?? '', ENT_QUOTES) ?>"
                    >
                      Review
                    </button>
                <?php else: ?>
                  <span class="btnlink disabled" aria-disabled="true" title="Log in om te reviewen">Review</span>
                <?php endif; ?>

                <a class="btnlink primary" href="<?= $s['url']; ?>" target="_blank" rel="noopener noreferrer">Bekijk</a>
                
            </div>

            </div>

        </div>
      <?php endforeach; ?>
    </div>

<div class="modal" id="reviewModal" aria-hidden="true">
  <div class="modal-backdrop" data-close="1"></div>

  <div class="modal-card" role="dialog" aria-modal="true">
    <div class="modal-head">
      <div>
        <div class="modal-title">Review</div>
        <div class="modal-sub" id="modalSub"></div>
      </div>
      <button class="modal-x" type="button" data-close="1">✕</button>
    </div>

    <?php if ($reviewErr): ?>
      <div class="modal-alert error"><?= $reviewErr ?></div>
    <?php elseif ($reviewOk): ?>
      <div class="modal-alert ok">Review opgeslagen!</div>
    <?php endif; ?>

    <form method="post" action="index.php" class="modal-form">
  <input type="hidden" name="action" value="add_review">
  <input type="hidden" name="site_id" id="modalSiteId" value="<?= (int)$modalSiteId ?>">

  <label class="field">
    <div class="label">Score (1–5)</div>
    <select class="input" name="score" required>
      <option value="">Kies…</option>
      <?php for ($i = 1; $i <= 5; $i++): ?>
        <option value="<?= $i ?>" <?= ((string)$i === (string)$modalScore) ? 'selected' : '' ?>>
          <?= $i ?>
        </option>
      <?php endfor; ?>
    </select>
  </label>

  <label class="field">
    <div class="label">Comment</div>
    <textarea
      class="input"
      name="comment"
      rows="10"
      placeholder="Schrijf je feedback…"
      required
    ><?= htmlspecialchars($modalComment) ?></textarea>
  </label>

  <div class="modal-actions">
    <button class="btn secondary" type="button" data-close="1">Annuleren</button>
    <button class="btn" type="submit">Opslaan</button>
  </div>
</form>

  </div>
</div>

<script src="assets/scripts/review.js" data-open="<?= $modalOpen ? '1' : '0' ?>"></script>

  </div>
</body>
</html>
