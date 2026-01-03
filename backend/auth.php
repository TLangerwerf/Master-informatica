<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function is_teacher(): bool {
  return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'teacher';
}

function login_attempt(string $username, string $password): bool {
  $stmt = db()->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
  $stmt->execute([':u' => $username]);
  $user = $stmt->fetch();

  if (!$user) return false;
  if (!password_verify($password, $user['password_hash'])) return false;

  session_regenerate_id(true);
  $_SESSION['user'] = [
    'id' => (int)$user['id'],
    'class_id' => (int)$user['class_id'],
    'role' => $user['role'], 
    'display_name' => $user['display_name'],
  ];
  return true;
}

function logout_user(): void {
  $_SESSION = [];
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
  }
}
