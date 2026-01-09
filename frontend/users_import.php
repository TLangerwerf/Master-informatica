<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/db.php';

if (!is_teacher()) {
  http_response_code(403);
  echo "403 – Alleen docenten";
  exit;
}

$pdo = db();

$ok = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {

  if ($_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Upload mislukt.';
  } else {
    $handle = fopen($_FILES['csv']['tmp_name'], 'r');

    if (!$handle) {
      $errors[] = 'Kan CSV niet openen.';
    } else {
      $headers = fgetcsv($handle);
      if (!$headers) {
        $errors[] = 'CSV is leeg.';
      } else {

        $headers = array_map('trim', $headers);
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
          $line++;
          // Zorg dat aantal kolommen altijd klopt
$row = array_pad($row, count($headers), '');

// Te veel kolommen? Afkappen
$row = array_slice($row, 0, count($headers));

$data = array_combine($headers, $row);


          if (!$data || empty($data['username']) || empty($data['display_name'])) {
            $errors[] = "Regel $line: ontbrekende username of display_name.";
            continue;
          }

          $username = trim($data['username']);
          $display  = trim($data['display_name']);
          $classId  = (int)($data['class_id'] ?? 0);
          $groupId  = (int)($data['group_id'] ?? 0);
          $role     = $data['role'] ?? 'student';
          $password = $data['password'] ?? 'welkom123';

          // Bestaat username al?
          $check = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
          $check->execute([':u' => $username]);
          if ($check->fetch()) {
            $errors[] = "Regel $line: username '$username' bestaat al.";
            continue;
          }

          $stmt = $pdo->prepare("
            INSERT INTO users
              (username, display_name, password_hash, role, class_id, group_id, created_at)
            VALUES
              (:u, :d, :p, :r, :c, :g, NOW())
          ");

          $stmt->execute([
            ':u' => $username,
            ':d' => $display,
            ':p' => password_hash($password, PASSWORD_DEFAULT),
            ':r' => $role,
            ':c' => $classId,
            ':g' => $groupId,
          ]);

          $ok[] = $username;
        }

        fclose($handle);
      }
    }
  }
}
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <title>Gebruikers importeren</title>
  <link rel="stylesheet" href="assets/css/ui.css">
</head>
<body>

<div class="wrap">

  <h1>Gebruikers importeren (CSV)</h1>
  <p class="muted">
    Upload een CSV-bestand om meerdere gebruikers tegelijk aan te maken.
  </p>

  <?php if ($errors): ?>
    <div class="alert error">
      <strong>Fouten:</strong>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($ok): ?>
    <div class="alert ok">
      <?= count($ok) ?> gebruiker(s) aangemaakt.
    </div>
  <?php endif; ?>

  <div class="panel">
    <form method="post" enctype="multipart/form-data">
      <label class="field">
        <div class="label">CSV bestand</div>
        <input type="file" name="csv" accept=".csv" required>
      </label>

      <div class="actionsRow">
        <button class="btn" type="submit">Importeren</button>
        <a class="btn secondary" href="users_admin.php">Annuleren</a>
      </div>
    </form>
  </div>

  <div class="panel">
    <strong>CSV voorbeeld</strong>
<pre>username,display_name,class_id,group_id
j.jansen,Jan Jansen,1,3
p.pieters,Piet Pieters,1,3</pre>
  </div>

</div>

</body>
</html>
