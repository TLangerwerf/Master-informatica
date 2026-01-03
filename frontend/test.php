<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

try {
  $pdo = db();
  echo "<h2>✅ Databaseverbinding werkt</h2>";

  // Optionele extra test: simpele query
  $stmt = $pdo->query("SELECT NOW() AS tijd");
  $row = $stmt->fetch();

  echo "<p>Server tijd: " . $row['tijd'] . "</p>";

} catch (Throwable $e) {
  echo "<h2>❌ Databaseverbinding mislukt</h2>";
  echo "<pre>" . $e->getMessage() . "</pre>";
}
