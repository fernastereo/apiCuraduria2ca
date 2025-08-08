<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$errorMessage = null;
$infoMessage = null;
$tableName = null;
$rows = [];

try {
    $database = new Database();
    $pdo = $database->connect();

    // Detectar una tabla cualquiera
    $tablesStmt = $pdo->query('SHOW TABLES');
    $firstTableRow = $tablesStmt->fetch(PDO::FETCH_NUM);

    if (!$firstTableRow) {
        $infoMessage = 'No hay tablas en la base de datos para consultar.';
    } else {
        $tableName = $firstTableRow[0];
        $query = "SELECT * FROM `{$tableName}` LIMIT 10";
        $stmt = $pdo->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Prueba de conexión a BD</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 24px; }
    .ok { color: #0a7; }
    .err { color: #c00; }
    table { border-collapse: collapse; margin-top: 16px; width: 100%; max-width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; font-size: 14px; }
    th { background: #f6f8fa; text-align: left; }
    caption { text-align: left; font-weight: 600; margin-bottom: 8px; }
    .muted { color: #666; }
  </style>
  </head>
<body>
  <h1>Prueba de conexión a base de datos</h1>

  <?php if ($errorMessage): ?>
    <p class="err">Error: <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
  <?php else: ?>
    <p class="ok">Conexión establecida correctamente.</p>
    <?php if ($infoMessage): ?>
      <p class="muted"><?php echo htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php else: ?>
      <p>Mostrando hasta 10 filas de la tabla: <strong><?php echo htmlspecialchars((string)$tableName, ENT_QUOTES, 'UTF-8'); ?></strong></p>
      <?php if (empty($rows)): ?>
        <p class="muted">La tabla no tiene registros.</p>
      <?php else: ?>
        <table>
          <caption>Resultados</caption>
          <thead>
            <tr>
              <?php foreach (array_keys($rows[0]) as $col): ?>
                <th><?php echo htmlspecialchars((string)$col, ENT_QUOTES, 'UTF-8'); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <?php foreach ($r as $val): ?>
                  <td><?php echo htmlspecialchars((string)(is_scalar($val) || $val === null ? (string)$val : json_encode($val)), ENT_QUOTES, 'UTF-8'); ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    <?php endif; ?>
  <?php endif; ?>

</body>
</html>


