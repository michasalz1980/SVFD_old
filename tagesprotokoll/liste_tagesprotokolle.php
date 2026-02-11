<title>Freibad Dabringhausen -Liste Tagesprotokolle</title>
<!--main css-->
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<br>
		<meta name="robots" content="noindex, nofollow">
			<link rel="preconnect" href="https://fonts.googleapis.com">
				<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
				<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet">
					<link href="freibad.css" rel="stylesheet" type="text/css">

<?php
	include "mva_functions_v02.php";
	include "connectDBva-r.php";
	include 'config.php';
		
	$dbPrefix = DB_TABLE_PREFIX;	

	$action = $_GET['action'];
	$id = $_GET['id'];
	
	//   D A T E N S A T Z   L Ö S C H E N

	if ($action == "delete") {
		$sql = "DELETE FROM ".$dbPrefix."Tagesprotokoll WHERE id = ".$id;
		$res = mysqli_query($dbcnx, $sql);		
	}

	//   I N   D A T E N B A N K   E I N T R A G E N   O D E R   A K T U A L I S I E R E N
	if ($action == "insert" || $action == "update") {
		
		$datum = SQLfromDateStr($_POST['Datum']);
		$wetter_s = $_POST['Wetter_S'];
		$wetter_h = $_POST['Wetter_H'];
		$wetter_b = $_POST['Wetter_B'];
		$wetter_r = $_POST['Wetter_R'];
		$wetter_g = $_POST['Wetter_G'];		
		$lufttemperatur = $_POST['Lufttemperatur'];
		$wasserleitungsnetz = $_POST['Zaehlerstand_Wasserleitungsnetz'];
		$abwasser = $_POST['Zaehlerstand_Abwasser'];
		$temperatur_MZB = $_POST['Temperatur_MZB'];
		$temperatur_NSB = $_POST['Temperatur_NSB'];
		$temperatur_KKB = $_POST['Temperatur_KKB'];
		// $filterspuelungen = $_POST['Filterspuelungen']; // CHANGED: 05.06.2023 11:30
		// Änderungen hier: Neue Variablen für die Checkboxen // CHANGED: 05.06.2023 11:30
		$filterspuelungen_MZB = isset($_POST['Filterspuelung_MZB']) ? '1' : '0';
		$filterspuelungen_NSB = isset($_POST['Filterspuelung_NSB']) ? '1' : '0';
		$filterspuelungen_KKB = isset($_POST['Filterspuelung_KKB']) ? '1' : '0';
		$filterspuelungen_Bachbett = isset($_POST['Filterspuelung_Bachbett']) ? '1' : '0';
		
		
		$bemerkungen = $_POST['Bemerkungen'];
		$tagesbesucherzahl = $_POST['Tagesbesucherzahl'];
		$protokollunterzeichner = $_POST['Protokollunterzeichner'];
	
		if ($action == "insert") {$x = "INSERT INTO"; $w = "";}
		if ($action == "update") {$x = "UPDATE"; $w = " WHERE id=".$id; }
				
		$sql = $x. " " . $dbPrefix . "Tagesprotokoll SET 
		Datum = '$datum',
		Wetter_S = '$wetter_s',
		Wetter_H = '$wetter_h',
		Wetter_B = '$wetter_b',
		Wetter_R = '$wetter_r',
		Wetter_G = '$wetter_g',

		Lufttemperatur = '$lufttemperatur',
		Zaehlerstand_Wasserleitungsnetz = '$wasserleitungsnetz',
		Zaehlerstand_Abwasser = '$abwasser',

		Temperatur_MZB = '$temperatur_MZB',
		Temperatur_NSB = '$temperatur_NSB',
		Temperatur_KKB = '$temperatur_KKB',

		Filter_MZB = '$filterspuelungen_MZB', -- Neue Felder für die Checkboxen CHANGED: 05.06.2023 11:30
		Filter_NSB = '$filterspuelungen_NSB', -- Neue Felder für die Checkboxen CHANGED: 05.06.2023 11:30
		Filter_KKB = '$filterspuelungen_KKB', -- Neue Felder für die Checkboxen CHANGED: 05.06.2023 11:30
		Filter_Bachbett = '$filterspuelungen_Bachbett', -- Neue Felder für die Checkboxen CHANGED: 05.06.2023 11:30
		Bemerkungen = '$bemerkungen',
		Tagesbesucherzahl = '$tagesbesucherzahl',
		Protokollunterzeichner = '$protokollunterzeichner'".$w;
		$res = mysqli_query($dbcnx, $sql);
		$error_message = mysqli_error($dbcnx);

		if($error_message == ""){
			echo "<div class='success'>Protokoll erfolgreich gespeichert.</div>";
		} else {
			echo "<div class='error'>Protokoll konnte nicht gespeichert werden. Fehler: <i>".$error_message;
			echo "</i><br>Bitte beachten, dass pro Tag nur ein Tagesprotokoll eingegeben werden kann. Wenn bereits eines existiert, dieses bitte bearbeiten!</div>";
		}
 	}
	
	?>
	</head>
	<body>
		<p>
			<h3>Freibad Dabringhausen Tagesprotokolle</h3>
			<?php
				$sql = "SELECT SUM(Tagesbesucherzahl) AS Tagesbesucherzahl FROM Tagesprotokoll WHERE YEAR(Datum) = YEAR(CURDATE())";
				$res = mysqli_query($dbcnx, $sql);
				$tagesBesucherZahl = 0;
				while($row = mysqli_fetch_assoc($res)) {
					$tagesBesucherZahl = $row['Tagesbesucherzahl'];
				}
				
			?>
			<div>
				Anzahl Tagesbesucher (aktuelle Saison): <?php echo number_format($tagesBesucherZahl, 0, ',', '.'); ?>
			</div>

			<br>
				<div>
					<input class="ButtonClass" type="button" value="Menü" onclick="window.location.href='index.php'"
				</input>&nbsp;&nbsp;&nbsp;<input class="ButtonClass" type="button" value="Neues Tagesprotokoll anlegen" onclick="window.location.href='input_tag.php'"
			</input>&nbsp;&nbsp;&nbsp;&nbsp;Es kann nur ein Protokoll pro Tag angelegt werden!</div>
		<br>
		<table width="100%" border="1" cellspacing="0" cellpadding="2" bordercolor="#DDDDDD">
			<tr bgcolor="007a96" color="'ffffff">
				<td class="legende">id</td>
				<td class="legende">Datum</td>
				<td class="legende">Wetterlage</td>
				<td class="legende">Lufttemperatur</td>
				<td class="legende">Zähler Wasser</td>
				<td class="legende">Zähler Abwasser</td>
				<td class="legende">MZB °C</td>
				<td class="legende">NSB °C</td>
				<td class="legende">KKB °C</td>
				<td class="legende">Filterspülungen MZB</td>
				<td class="legende">Filterspülungen NSB</td>
				<td class="legende">Filterspülungen KKB</td>
				<td class="legende">Filterspülungen Bachbett</td>
				<td class="legende">Bemerkungen</td>
				<td class="legende">Tagesbesucher</td>
				<td class="legende">Unterschrift</td>
				<td class="legende">$nbsp;</td>
				<td class="legende">$nbsp;</td>
			</tr>
		  
			<?php
				$sql = "SELECT * FROM ".$dbPrefix."Tagesprotokoll ORDER BY Datum DESC";
				$res = mysqli_query($dbcnx, $sql);
				
				
				while($row = mysqli_fetch_assoc($res)) {
					
					$id = $row['id'];
					$datum = dateStrfromMySQL($row['Datum']);
					
					$wetter_s = $row['Wetter_S'];
					$wetter_h = $row['Wetter_H'];
					$wetter_b = $row['Wetter_B'];
					$wetter_r = $row['Wetter_R'];
					$wetter_g = $row['Wetter_G'];
					$lufttemperatur = $row['Lufttemperatur'];
					$wasserleitungsnetz = $row['Zaehlerstand_Wasserleitungsnetz'];
					$abwasser = $row['Zaehlerstand_Abwasser'];
					
					$temperatur_MZB = $row['Temperatur_MZB'];
					$temperatur_NSB = $row['Temperatur_NSB'];
					$temperatur_KKB = $row['Temperatur_KKB'];
					$filterspuelungen_MZB = $row['Filter_MZB'];
					$filterspuelungen_NSB = $row['Filter_NSB'];
					$filterspuelungen_KKB = $row['Filter_KKB'];
					$filterspuelungen_Bachbett = $row['Filter_Bachbett'];
					$bemerkungen = $row['Bemerkungen'];
					$tagesbesucherzahl = $row['Tagesbesucherzahl'];
					$protokollunterzeichner = $row['Protokollunterzeichner'];
			?>
			
			<tr>
				<td align="center"><?php echo $id ?></td>
				<td align="center"><?php echo $datum ?></td>
				<td><?php echo getWetterIcon($wetter_s, $wetter_h, $wetter_b, $wetter_r, $wetter_g) ?></td>
				<td align="center"><?php echo $lufttemperatur ?>°C</td>
				<td align="center"><?php echo $wasserleitungsnetz ?></td>
				<td align="center"><?php echo $abwasser ?></td>
				<td align="center"><?php echo $temperatur_MZB ?>°C</td>
				<td align="center"><?php echo $temperatur_NSB ?>°C</td>
				<td align="center"><?php echo $temperatur_KKB ?>°C</td>
				<td align="center"><?php echo ($filterspuelungen_MZB == 1) ? "Ja" : "Nein"; ?></td>
				<td align="center"><?php echo ($filterspuelungen_NSB == 1) ? "Ja" : "Nein"; ?></td>
				<td align="center"><?php echo ($filterspuelungen_KKB == 1) ? "Ja" : "Nein"; ?></td>
				<td align="center"><?php echo ($filterspuelungen_Bachbett == 1) ? "Ja" : "Nein"; ?></td>
				<td><?php echo $bemerkungen ?></td>
				<td align="center"><?php echo $tagesbesucherzahl ?></td>
				<td><?php echo $protokollunterzeichner ?></td>
				<td><a href="input_tag.php?action=edit&id=<?php echo $id ?>">Ändern</a></td>
				<td><a href="druckversion_tagesprotokoll.php?id=<?php echo $id ?>">Druckversion</a></td>
			</tr>
		  
			<?php
				}
				mysqli_close($dbcnx);
			?>
		  
		</table>
	</p>
</body>
</html>
