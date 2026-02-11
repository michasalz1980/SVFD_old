<?php
	// Der Pfad zur ursprünglichen JPG-Datei
	$originalFile = 'bad_original.jpg';

	// Lade das Bild
	$sourceImage = imagecreatefromjpeg($originalFile);

	// Bestimme die Breite und Höhe des ursprünglichen Bildes
	$sourceWidth = imagesx($sourceImage);
	$sourceHeight = imagesy($sourceImage);

	// Setze die neue Breite und Höhe
	$newWidth = 1280;
	$newHeight = 720;

	// Erstelle ein neues Bild mit der neuen Größe
	$resizedImage = imagecreatetruecolor($newWidth, $newHeight);

	// Skaliere das ursprüngliche Bild auf die neue Größe
	imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

	// Speichere das skalierte Bild
	imagejpeg($resizedImage, 'bad.jpg');

	// Bereinige den Speicher
	imagedestroy($sourceImage);
	imagedestroy($resizedImage);

	echo "Das Bild wurde erfolgreich skaliert.";
?>
