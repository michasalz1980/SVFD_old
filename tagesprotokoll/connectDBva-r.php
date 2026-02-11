<?php
	include 'config.php';

	// Mit MySQL Datenbankserver verbinden
	$host_name = DB_HOST;
	$user_name = DB_USER;
	$password = DB_PASS;
	$database = DB_NAME;

	$dbcnx = new mysqli($host_name, $user_name, $password, $database);

	if ($dbcnx->connect_error) {
		die('<p>Verbindung zum MySQL Server fehlgeschlagen: '. $dbcnx->connect_error .'</p>');
	} else {
		// echo '<p>Verbindung zum MySQL Server erfolgreich aufgebaut.</p>';
	}



	if (!$dbcnx) {
		echo( "<p>Verbindung zum Datenbankserver zur Zeit nicht möglich.</p>" );
		exit();
	}


	if (! mysqli_select_db($dbcnx, $database)) {
		echo("<p>Auswahl der Datenbank ".$database." fehlgeschlagen</p>");
		exit();
	} 

mysqli_set_charset($dbcnx, "utf8mb4");
?>


