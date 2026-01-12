<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/db.php';

//Onhoud laatst gekozen klas//
if (isset($_POST['class_id'])) {
    $_SESSION['class_id'] = $_POST['class_id'];
}

$val = $_SESSION['class_id'] ?? '';


if (!is_teacher()) {
  http_response_code(403);
  echo "403 - Alleen docenten hebben toegang.";
  exit;
}

$pdo = db();
$flashOk = '';
$flashErr = '';




// -------------------------
// Form persistence (SESSION)
// -------------------------
// In create-mode willen we refresh kunnen overleven.
$form = $_SESSION['user_form'] ?? [];

// -------------------------
// Users table describe
// -------------------------
$cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);

$hasPasswordHash = false;
$fields = []; // invulbare velden (excl id/password_hash/created_at)

foreach ($cols as $c) {
  $f = $c['Field'];
  if ($f === 'id') continue;
  if ($f === 'password_hash') { $hasPasswordHash = true; continue; }
  if ($f === 'created_at') continue; // auto NOW()
  $fields[] = $c;
}
$fieldNames = array_map(fn($x) => $x['Field'], $fields);

// -------------------------
// Roles (distinct values)
// -------------------------
$roles = [];
try {
  $roles = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role <> '' ORDER BY role")
               ->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
  $roles = [];
}
$defaultRole = in_array('leerling', $roles, true) ? 'leerling' : 'student';
if (!in_array($defaultRole, $roles, true)) {
  $roles[] = $defaultRole;
}

// -------------------------
// Classes dropdown
// -------------------------
$classes = [];
try {
  $classes = $pdo->query("SELECT id, name FROM classes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $classes = [];
}

// -------------------------
// Edit mode
// -------------------------
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$mode = $editId > 0 ? 'edit' : 'create';

// In edit-mode: DB is leidend (dus session form negeren)


if ($editId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
  $stmt->execute([':id' => $editId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    $flashErr = 'Gebruiker niet gevonden.';
    $editId = 0;
    $mode = 'create';
  } else {
    $form = $row;
  }
}

// -------------------------
// POST handling
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');

  // Onthoud form altijd in session (alleen nuttig in create mode)
  $_SESSION['user_form'] = $_POST;

  // Cancel: terug naar create + session leeg
  if ($action === 'cancel') {
    unset($_SESSION['user_form']);
    header('Location: users_admin.php');
    exit;
  }

  // Helper om waarde te normaliseren
  $norm = function($val) {
    if (is_string($val)) return trim($val);
    return $val;
  };

  // CREATE
  if ($action === 'create_user') {
    // created_at automatisch NOW()
    $insertCols = [];
    $placeholders = [];
    $params = [];

    // password is verplicht bij create
    if ($hasPasswordHash) {
      $pw = (string)($_POST['password'] ?? '');
      if ($pw === '') {
        $flashErr = 'Password is verplicht.';
      } else {
        $insertCols[] = 'password_hash';
        $placeholders[] = ':password_hash';
        $params[':password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
      }
    }

    // basis checks
    if ($flashErr === '' && in_array('username', $fieldNames, true) && trim((string)($_POST['username'] ?? '')) === '') {
      $flashErr = 'Username is verplicht.';
    }
    if ($flashErr === '' && in_array('display_name', $fieldNames, true) && trim((string)($_POST['display_name'] ?? '')) === '') {
      $flashErr = 'Display name is verplicht.';
    }

    if ($flashErr === '') {
      foreach ($fields as $c) {
        $field = $c['Field'];
        $nullable = (($c['Null'] ?? '') === 'YES');
        $type = strtolower((string)$c['Type']);

        $val = $norm($_POST[$field] ?? null);

        // role default
        if ($field === 'role' && ($val === '' || $val === null)) {
          $val = $defaultRole;
        }

        // null handling
        if ($val === '' || $val === null) {
          $val = $nullable ? null : (str_contains($type, 'int') ? 0 : '');
        }

        $insertCols[] = $field;
        $ph = ':' . $field;
        $placeholders[] = $ph;
        $params[$ph] = $val;
      }

      // created_at
      $insertCols[] = 'created_at';
      $placeholders[] = 'NOW()';

      try {
        $sql = "INSERT INTO users (" . implode(', ', $insertCols) . ")
                VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // ✅ na succes: session form leeg (zodat velden leeg zijn)
        unset($_SESSION['user_form']);
        header('Location: users_admin.php?ok=1');
        exit;
      } catch (Throwable $e) {
        $flashErr = 'Opslaan mislukt: ' . $e->getMessage();
      }
    }
  }

  // UPDATE
  if ($action === 'update_user') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      $flashErr = 'Ongeldige gebruiker.';
    } else {
      // basis checks
      if (in_array('username', $fieldNames, true) && trim((string)($_POST['username'] ?? '')) === '') {
        $flashErr = 'Username is verplicht.';
      }
      if ($flashErr === '' && in_array('display_name', $fieldNames, true) && trim((string)($_POST['display_name'] ?? '')) === '') {
        $flashErr = 'Display name is verplicht.';
      }

      if ($flashErr === '') {
        $setParts = [];
        $params = [':id' => $id];

        foreach ($fields as $c) {
          $field = $c['Field'];
          $nullable = (($c['Null'] ?? '') === 'YES');
          $type = strtolower((string)$c['Type']);

          $val = $norm($_POST[$field] ?? null);

          if ($field === 'role' && ($val === '' || $val === null)) {
            $val = $defaultRole;
          }

          if ($val === '' || $val === null) {
            $val = $nullable ? null : (str_contains($type, 'int') ? 0 : '');
          }

          $setParts[] = "$field = :$field";
          $params[":$field"] = $val;
        }

        // password optioneel bij update
        if ($hasPasswordHash) {
          $pw = (string)($_POST['password'] ?? '');
          if ($pw !== '') {
            $setParts[] = "password_hash = :password_hash";
            $params[':password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
          }
        }

        try {
          $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = :id";
          $stmt = $pdo->prepare($sql);
          $stmt->execute($params);

          // edit mode: session form is niet nodig
          unset($_SESSION['user_form']);
          header('Location: users_admin.php?edit=' . $id . '&saved=1');
          exit;
        } catch (Throwable $e) {
          $flashErr = 'Update mislukt: ' . $e->getMessage();
          // form laten staan (POST) zodat gebruiker niets kwijt is
          $form = $_POST;
          $mode = 'edit';
          $editId = $id;
        }
      } else {
        // validation fail: form laten staan
        $form = $_POST;
        $mode = 'edit';
        $editId = $id;
      }
    }
  }
}

if (isset($_GET['ok'])) $flashOk = 'Gebruiker aangemaakt.';
if (isset($_GET['saved'])) $flashOk = 'Wijzigingen opgeslagen.';

// -------------------------
// User list (server-side sort)
// -------------------------
$sort = (string)($_GET['sort'] ?? 'name');
$dir  = strtolower((string)($_GET['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

$orderSql = "u.display_name $dir";
if ($sort === 'username') $orderSql = "u.username $dir";
if ($sort === 'role')     $orderSql = "u.role $dir";
if ($sort === 'class')    $orderSql = "c.name $dir, u.display_name $dir";

try {
  $users = $pdo->query("
    SELECT u.id, u.display_name, u.username, u.role, u.class_id, c.name AS class_name
    FROM users u
    LEFT JOIN classes c ON c.id = u.class_id
    ORDER BY $orderSql
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $users = $pdo->query("
    SELECT id, display_name, username, role, class_id
    FROM users
    ORDER BY $orderSql
  ")->fetchAll(PDO::FETCH_ASSOC);
}

function sort_link(string $key, string $currentSort, string $currentDir): string {
  $dir = 'asc';
  if ($currentSort === $key && strtolower($currentDir) === 'asc') $dir = 'desc';
  return 'users_admin.php?sort=' . urlencode($key) . '&dir=' . urlencode($dir);
}

function hv($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES);
}

function fv(array $form, string $key, string $fallback = ''): string {
  return (string)($form[$key] ?? $fallback);
}
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gebruikersbeheer</title>
  <link rel="stylesheet" href="assets/css/ui.css">
  <link rel="stylesheet" href="assets/css/user-admin.css">
  <style>
    .toprow{display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:16px;}
    .panel{background:#fff;border:1px solid #e6e8ef;border-radius:14px;box-shadow:0 6px 18px rgba(17,24,39,0.06);padding:14px;}
    .grid2{display:grid; grid-template-columns: 1fr 1fr; gap:12px;}
    .field .label{font-size:12px; opacity:.7; margin-bottom:6px; font-weight:600;}
    .input{width:100%; box-sizing:border-box;}
    .actionsRow{display:flex; gap:10px; justify-content:flex-end; margin-top:12px; flex-wrap:wrap;}
    .alert{border-radius:12px; padding:10px 12px; margin:0 0 12px; font-size:13px;}
    .alert.ok{background:#ecfdf3;border:1px solid #abefc6;color:#067647;}
    .alert.err{background:#fff1f1;border:1px solid #ffd6d6;color:#b42318;}
    table{width:100%; border-collapse:collapse;}
    th,td{padding:10px 10px; border-bottom:1px solid #eef0f4; font-size:13px; text-align:left; vertical-align:top;}
    th a{color:inherit; text-decoration:none;}
    th a:hover{text-decoration:underline;}
    tr:hover{background:#fafbff;}
    .muted{opacity:.7;}
    .rowlink{color:inherit; text-decoration:none; display:block;}
    @media (max-width: 900px){
      .toprow{flex-direction:column;}
      .grid2{grid-template-columns: 1fr;}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="toprow">
      <div>
        <h1>Gebruikersbeheer</h1>
        <div class="muted">Alleen docenten kunnen hier gebruikers aanmaken en bewerken.</div>
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
        <a href="feedback-counts.php" class="btn primary">
          Websites
        </a>
      <?php endif; ?>
       </div>
    </div>

    <?php if ($flashOk): ?><div class="alert ok"><?= hv($flashOk) ?></div><?php endif; ?>
    <?php if ($flashErr): ?><div class="alert err"><?= hv($flashErr) ?></div><?php endif; ?>

    <?php if ($mode === 'edit'): ?>
  
    <div class="mode-banner edit">
    ✏️ Je bent een <strong>bestaande gebruiker aan het bewerken</strong>
    <span class="muted">(ID <?= (int)$editId ?>)</span>
  

<?php else: ?>
  <div class="mode-banner create">
  <strong>Nieuwe gebruiker aanmaken</strong>
  
<?php endif; ?>





      <form method="post" action="users_admin.php<?= $mode === 'edit' ? '?edit='.(int)$editId : '' ?>">
        <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update_user' : 'create_user' ?>">
        <?php if ($mode === 'edit'): ?>
          <input type="hidden" name="id" value="<?= (int)$editId ?>">
        <?php endif; ?>

        <div class="grid2">
          <?php foreach ($fields as $c):
            $field = $c['Field'];
            $type  = strtolower((string)$c['Type']);
            $val   = fv($form, $field);

            // role dropdown
            if ($field === 'role'):
              if ($val === '') $val = $defaultRole;
          ?>
              <label class="field">
                <div class="label">role</div>
                <select class="input" name="role">
                  <?php foreach ($roles as $r): ?>
                    <option value="<?= hv($r) ?>" <?= ($r === $val) ? 'selected' : '' ?>><?= hv($r) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
          <?php
              continue;
            endif;

            // class_id dropdown if possible
            if ($field === 'class_id' && count($classes) > 0):
          ?>
              <label class="field">
                <div class="label">class_id</div>
                <select class="input" name="class_id">
                  <option value="">Kies klas…</option>
                  <?php foreach ($classes as $cl): ?>
                    <option value="<?= (int)$cl['id'] ?>" <?= ((string)$cl['id'] === $val) ? 'selected' : '' ?>>
                      <?= hv($cl['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
          <?php
              continue;
            endif;

            // input type
            $inputType = 'text';
            if (str_contains($type, 'int')) $inputType = 'number';
            if (str_contains($type, 'datetime') || str_contains($type, 'timestamp')) $inputType = 'datetime-local';
          ?>
            <label class="field">
              <div class="label"><?= hv($field) ?></div>
              <input class="input" type="<?= $inputType ?>" name="<?= hv($field) ?>" value="<?= hv($val) ?>">
            </label>
          <?php endforeach; ?>
        </div>

        <?php if ($hasPasswordHash): ?>
          <div style="margin-top:12px;">
            <label class="field">
              <div class="label"><?= $mode === 'edit' ? 'password (alleen invullen om te wijzigen)' : 'password' ?></div>
              <input class="input" type="password" name="password" value="">
            </label>
          </div>
        <?php endif; ?>

        <div class="actionsRow">

  <?php if ($mode === 'create'): ?>
    <button class="btn secondary" type="reset">
      Velden leegmaken
    </button>
  <?php endif; ?>

  <?php if ($mode === 'edit'): ?>
    <button class="btn secondary" type="submit" name="action" value="cancel">
      Annuleren
    </button>
  <?php else: ?>
    <button class="btn secondary" type="submit" name="action" value="cancel">
      Annuleren
    </button>
  <?php endif; ?>

  <button class="btn <?= $mode === 'edit' ? 'warning' : 'primary' ?>" type="submit">
    <?= $mode === 'edit' ? 'Wijzigingen opslaan' : 'Gebruiker aanmaken' ?>
  </button>

</div>

      </form>

      <div class="muted" style="margin-top:10px;">
      </div>
    </div>
  </div>
  </div>

    <div class="panel">
      <div style="font-weight:800; margin-bottom:10px;">Alle gebruikers</div>

      <table>
        <thead>
          <tr>
            <th><a href="<?= sort_link('name', $sort, $dir) ?>">Naam</a></th>
            <th><a href="<?= sort_link('username', $sort, $dir) ?>">Username</a></th>
            <th><a href="<?= sort_link('class', $sort, $dir) ?>">Klas</a></th>
            <th><a href="<?= sort_link('role', $sort, $dir) ?>">Rol</a></th>
          </tr>
        </thead>
        <tbody>
  <?php foreach ($users as $u): ?>
    <tr
      class="clickable-row"
      onclick="window.location='users_admin.php?edit=<?= (int)$u['id'] ?>'"
      role="link"
      tabindex="0"
      aria-label="Bewerk gebruiker <?= htmlspecialchars((string)($u['display_name'] ?? ''), ENT_QUOTES) ?>"
    >
      <td><?= htmlspecialchars((string)($u['display_name'] ?? ''), ENT_QUOTES) ?></td>
      <td><?= htmlspecialchars((string)($u['username'] ?? ''), ENT_QUOTES) ?></td>
      <td>
        <?php if (!empty($u['class_name'])): ?>
          <?= htmlspecialchars((string)$u['class_name'], ENT_QUOTES) ?>
        <?php else: ?>
          <span class="muted">class_id <?= (int)($u['class_id'] ?? 0) ?></span>
        <?php endif; ?>
      </td>
      <td><?= htmlspecialchars((string)($u['role'] ?? ''), ENT_QUOTES) ?></td>
    </tr>
  <?php endforeach; ?>
</tbody>
      </table>

      <div class="muted" style="margin-top:10px;">
        Klik op de naam om te bewerken. Kolomkoppen sorteren (server-side).
      </div>
    </div>

  </div>
</body>
</html>
