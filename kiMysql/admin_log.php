<?php
$logFile = __DIR__ . '/log/chatbot.log';

if (!file_exists($logFile)) {
    die("Logdatei nicht gefunden.");
}

$filter = $_GET['filter'] ?? '';
$logContent = file_get_contents($logFile);
$lines = explode("\n", $logContent);

if ($filter) {
    $lines = array_filter($lines, fn($line) => stripos($line, $filter) !== false);
}

// Dynamisch Datumsliste erstellen
$dateOptions = [];
foreach ($lines as $line) {
    if (preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $match)) {
        $dateOptions[$match[1]] = true;
    }
}
ksort($dateOptions);
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Chatbot Log Viewer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #e0f7fa;
      padding: 30px;
      font-family: "Segoe UI", sans-serif;
    }
    pre {
      background-color: #ffffff;
      border: 1px solid #ccc;
      padding: 15px;
      border-radius: 8px;
      max-height: 80vh;
      overflow-y: scroll;
    }
    h1 {
      color: #0277bd;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1 class="mb-4">📜 Log-Ansicht: chatbot.log</h1>

    <form class="mb-4" method="get">
      <div class="row g-2">
        <div class="col-md-8">
          <input type="text" class="form-control" name="filter" placeholder="Suche nach Text, ID oder Datum..." value="<?= htmlspecialchars($filter) ?>">
        </div>
        <div class="col-md-3">
          <select class="form-select" onchange="this.form.filter.value=this.value; this.form.submit();">
            <option value="">Datum wählen…</option>
            <?php foreach ($dateOptions as $date => $_): ?>
              <option value="<?= $date ?>" <?= $date === $filter ? 'selected' : '' ?>><?= $date ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-1">
          <button class="btn btn-primary w-100" type="submit">🔍</button>
        </div>
      </div>
    </form>

    <pre><?php
foreach ($lines as $line) {
    echo htmlspecialchars($line) . "\n";
}
?></pre>
  </div>
</body>
</html>
