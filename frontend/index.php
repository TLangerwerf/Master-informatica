<?php
declare(strict_types=1);
session_start();

/* ---- DB config (pas aan voor XAMPP) ---- */
$dbHost = 'localhost';
$dbName = 'wrp';
$dbUser = 'root';
$dbPass = '';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

function db(string $dsn, string $user, string $pass): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function csrf_verify(string $token): void {
  if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
    http_response_code(400);
    exit('Bad Request (CSRF)');
  }
}

/* ---- Al ingelogd? -> redirect ---- */
if (!empty($_SESSION['user'])) {
  $role = $_SESSION['user']['role'] ?? '';
  header('Location: ' . ($role === 'teacher' ? '/teacher-sites.php' : '/sites.php'));
  exit;
}

/* ---- Login verwerken ---- */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify($_POST['csrf'] ?? '');

  $username = trim((string)($_POST['username'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  if ($username === '' || $password === '') {
    $error = 'Vul gebruikersnaam en wachtwoord in.';
  } else {
    $stmt = db($dsn, $dbUser, $dbPass)->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
      session_regenerate_id(true);
      $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'class_id' => (int)$user['class_id'],
        'role' => $user['role'],
        'display_name' => $user['display_name'],
      ];
      header('Location: ' . ($user['role'] === 'teacher' ? '/teacher-sites.php' : '/sites.php'));
      exit;
    }
    $error = 'Onjuiste gebruikersnaam of wachtwoord.';
  }
}
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Website Feedback Platform – Login</title>

  <link rel="stylesheet" href="/assets/css/ui.css" />
  <link rel="stylesheet" href="/assets/css/login.css" />

  <script defer src="/assets/scrips/login.js"></script>
</head>

<body>
<header>
  <div class="topbar">
    <div class="brand">
      <div class="logo">WFP</div>
      <div>
        <div>Website Feedback Platform</div>
        <div class="muted">Homepage / Login</div>
      </div>
    </div>
    <div class="actions">
      <span class="pill info">Niet ingelogd</span>
    </div>
  </div>
</header>

<div class="wrap">

  <section class="panel">
    <div class="title">Inloggen</div>
    <div class="muted">
      Log in met je docent- of leerlingaccount. Na inloggen word je automatisch doorgestuurd naar de juiste omgeving.
    </div>

    <?php if ($error): ?>
      <div class="notice"><?= h($error) ?></div>
    <?php else: ?>
      <div class="notice">
        Tip: dit is de MVP-homepage. Later kun je hier ook een korte uitleg of instructie plaatsen.
      </div>
    <?php endif; ?>
  </section>

  <div class="login-wrap">

    <section class="panel">
      <div class="title" style="font-size:16px;">Accountgegevens</div>

      <form method="post" autocomplete="on">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

        <label class="muted" for="username">Gebruikersnaam</label>
        <input id="username" name="username" autocomplete="username" required>

        <label class="muted" for="password">Wachtwoord</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>

        <div class="nav login-actions">
          <button class="btn primary" type="submit">Inloggen</button>
          <button class="btn" type="reset">Leegmaken</button>
          <button class="btn" type="button" id="togglePw">Toon wachtwoord</button>
        </div>
      </form>
    </section>

    <aside class="panel dashed">
      <div class="title" style="font-size:16px;">Wat gebeurt er na login?</div>
      <ul class="muted" style="margin:10px 0 0 18px;">
        <li><strong>Docent</strong> → websites beheren + reviews inzien</li>
        <li><strong>Leerling</strong> → websites bekijken + review geven</li>
        <li>Rechten zijn gekoppeld aan <strong>rol</strong> en <strong>klas</strong></li>
      </ul>
      <div class="hr"></div>
      <div class="muted">
        Volgende stap: maak <code>teacher-sites.php</code> en <code>sites.php</code> aan en gebruik <code>$_SESSION['user']</code>.
      </div>
    </aside>

  </div>
</div>

<footer>Homepage / Login </footer>
</body>
</html>
