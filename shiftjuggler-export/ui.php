<form method="POST">
  <button name="export">Dienste exportieren</button>
  <button name="reset">Alle Dienste zurücksetzen</button>
</form>

<?php
if (isset($_POST['export'])) include 'export.php';
if (isset($_POST['reset'])) include 'reset.php';
?>
