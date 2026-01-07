<?php
declare(strict_types=1);
$hash = null;
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $password = (string)($_POST['password'] ?? '');
  if ($password !== '') {
    $hash = password_hash($password, PASSWORD_DEFAULT);
  }
}
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <title>Password hash generator</title>
  <style>
    body{ font-family: Arial, sans-serif; padding:20px; }
    input, textarea, button{ width:100%; padding:10px; margin-top:8px; }
    textarea{ height:120px; font-family: monospace; }
  </style>
</head>
<body>
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
<h2>Password hash generator (dev only)</h2>

<form method="post">
  <label>Wachtwoord (plain text)</label>
  <input type="text" name="password" value="<?= htmlspecialchars($password) ?>" required>

  <button type="submit">Genereer hash</button>
</form>

<?php if ($hash): ?>
  <h3>Resultaat (kopieer dit naar <code>users.password_hash</code>)</h3>
  <textarea readonly><?= htmlspecialchars($hash) ?></textarea>
<?php endif; ?>

<p><strong>Let op:</strong> gebruik deze pagina alleen lokaal en verwijder hem vóór deployment.</p>

</body>
</html>
