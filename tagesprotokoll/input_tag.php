<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Freibad Dabringhausen Dateneingabe Tagesprotokoll</title>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"><br>
		<meta name="robots" content="noindex, nofollow">
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet">
		<link href="freibad.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/css/bootstrap.min.css" integrity="sha384-r4NyP46KrjDleawBgD5tp8Y7UzmLA05oM1iAEQ17CSuDqnUK2+k9luXQOfXJCJ4I" crossorigin="anonymous">
	</head>

	<?php
		include "mva_functions_v02.php";
		include "connectDBva-r.php";
		include 'config.php';
		
		$dbPrefix = DB_TABLE_PREFIX;	

		$id = $_GET['id'];

		
		$datum = date('d.m.Y');    // Vorgabe ist das aktuelle Datum
		$wetter_s = "";
		$wetter_h = "";
		$wetter_b = "";
		$wetter_r = "";
		$wetter_g = "";
		$lufttemperatur = "";
		$wasserleitungsnetz = "";
		$abwasser = "";
		$temperatur_mzb = "";
		$temperatur_nsb = "";
		$temperatur_kkb = "";
		$filterspuelungen = "";
		$filterspuelung_mzb = "";
		$filterspuelung_nsb = "";
		$filterspuelung_kkb = "";
		$filterspuelung_bachbett = "";
		$bemerkungen = "";
		$tagesbesucherzahl = "";
		$protokollunterzeichner = "";	
		
		if ($id > 0) {	
			$sql = "SELECT * FROM `".$dbPrefix."Tagesprotokoll`  WHERE `id` LIKE '$id'";
			
			$res = mysqli_query($dbcnx, $sql);
			$row = mysqli_fetch_array($res) ;
			
			$datum = dateStrfromMySQL($row['Datum']);
			$wetter_s = $row['Wetter_S'];
				if ($wetter_s == "1") {$c1 = "checked='checked'";}
			$wetter_h = $row['Wetter_H'];
				if ($wetter_h == "1") {$c2 = "checked='checked'";}
			$wetter_b = $row['Wetter_B'];
				if ($wetter_b == "1") {$c3 = "checked='checked'";}
			$wetter_r = $row['Wetter_R'];
				if ($wetter_r == "1") {$c4 = "checked='checked'";}
			$wetter_g = $row['Wetter_G'];
				if ($wetter_g == "1") {$c5 = "checked='checked'";}
			$lufttemperatur = $row['Lufttemperatur'];
			$wasserleitungsnetz = $row['Zaehlerstand_Wasserleitungsnetz'];
			$abwasser = $row['Zaehlerstand_Abwasser'];
			$temperatur_mzb = $row['Temperatur_MZB'];
			$temperatur_nsb = $row['Temperatur_NSB'];
			$temperatur_kkb = $row['Temperatur_KKB'];
			// $filterspuelungen = $row['Filterspuelungen'];
			$filterspuelung_mzb = $row['Filter_MZB'];
			if ($filterspuelung_mzb == "1") {$filterspuelung_mzb = "checked='checked'";}
			$filterspuelung_nsb = $row['Filter_NSB'];
			if ($filterspuelung_nsb == "1") {$filterspuelung_nsb = "checked='checked'";}
			$filterspuelung_kkb = $row['Filter_KKB'];
			if ($filterspuelung_kkb == "1") {$filterspuelung_kkb = "checked='checked'";}
			$filterspuelung_bachbett = $row['Filter_Bachbett'];
			if ($filterspuelung_bachbett == "1") {$filterspuelung_bachbett = "checked='checked'";}
			
			$bemerkungen = $row['Bemerkungen'];
			$tagesbesucherzahl = $row['Tagesbesucherzahl'];
			$protokollunterzeichner = $row['Protokollunterzeichner'];					
		}
	
	?>
	<body>
		<div class="container">
			<?php 
				if ($id == "") { echo "<form action='liste_tagesprotokolle.php?action=insert' method='post' class='m-auto' style='max-width:600px'>"; }
				else { echo "<form action='liste_tagesprotokolle.php?action=update&id=".$id."' method='post' class='m-auto' style='max-width:600px'>"; }						
			?>
				<h3 class="my-4">Dateneingabe Tagesprotokoll</h3>
				<div class="form-group mb-7 row">&nbsp;&nbsp;&nbsp;
					<strong>Achtung: Dezimalpunkt statt Komma verwenden, also 7.5 statt 7,5</strong>
				</div>
				<hr class="my-7" />
				<div class="form-group mb-3 row"><label for="datum" class="col-md-5 col-form-label">Datum</label>
					<div class="col-md-7">
						<input type="text" class="form-control form-control-lg" id="Datum" name="Datum" value="<?=$datum?>">
					</div>
				</div>
				<!--
				<legend class="form-group mb-3 row">Wetterlage</legend>
				<div>
					<input type="checkbox" id="Wetter_S" name="Wetter_S" value="1" <?=$c1?>>
					<label for="Wetter_S">schön&nbsp;&nbsp;&nbsp;</label>
					<input type="checkbox" id="Wetter_H" name="Wetter_H" value="1" <?=$c2?>>
					<label for="Wetter_H">heiter&nbsp;&nbsp;&nbsp;</label>
					<input type="checkbox" id="Wetter_B" name="Wetter_B" value="1" <?=$c3?>>
					<label for="Wetter_B">Bewölkung&nbsp;&nbsp;&nbsp;</label>
					<input type="checkbox" id="Wetter_R" name="Wetter_R" value="1" <?=$c4?>>
					<label for="Wetter_F">Regen&nbsp;&nbsp;&nbsp;</label>				  
					<input type="checkbox" id="wetter_g" name="Wetter_G" value="1" <?=$c5?>>
					<label for="Wetter_G">Gewitter&nbsp;&nbsp;&nbsp;</label> 
				</div>
				-->
				<div class="form-group mb-3 row">
					<label for="Wetterlage" class="col-md-5 col-form-label">Wetterlage</label>
					<div class="col-md-7">
						<div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" id="Wetter_S" name="Wetter_S" value="1" <?= ($c1) ? 'checked' : '' ?>>
								<label class="form-check-label" for="Wetter_S">schön&nbsp;&nbsp;&nbsp;</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" id="Wetter_H" name="Wetter_H" value="1" <?= ($c2) ? 'checked' : '' ?>>
								<label class="form-check-label" for="Wetter_H">heiter&nbsp;&nbsp;&nbsp;</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" id="Wetter_B" name="Wetter_B" value="1" <?= ($c3) ? 'checked' : '' ?>>
								<label class="form-check-label" for="Wetter_B">Bewölkung&nbsp;&nbsp;&nbsp;</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" id="Wetter_R" name="Wetter_R" value="1" <?= ($c4) ? 'checked' : '' ?>>
								<label class="form-check-label" for="Wetter_R">Regen&nbsp;&nbsp;&nbsp;</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" id="Wetter_G" name="Wetter_G" value="1" <?= ($c5) ? 'checked' : '' ?>>
								<label class="form-check-label" for="Wetter_G">Gewitter&nbsp;&nbsp;&nbsp;</label>
							</div>
						</div>
					</div>
				</div>

		
				<br>
				<div class="form-group mb-3 row">
					<label for="Lufttemperatur" class="col-md-5 col-form-label">Lufttemperatur °C</label>
					<div class="col-md-7">
						<input type="text" class="form-control form-control-lg" id="Lufttemperatur" name="Lufttemperatur" value="<?=$lufttemperatur?>">
					</div>
				</div>
				<!-- Wasserleitungsnetz TUT NICHT -->
				<div class="form-group mb-3 row">
					<label for="Zaehlerstand_Wasserleitungsnetz" class="col-md-5 col-form-label">Zählerstand Wasserleitungsnetz (wird automatisch eingetragen)</label>
					<div class="col-md-7">
						<input type="text" class="form-control form-control-lg" id="Zaehlerstand_Wasserleitungsnetz" name="Zaehlerstand_Wasserleitungsnetz" value="<?=$wasserleitungsnetz?>" readonly>
					</div>
				</div>
				<!-- Abwasser -->
				<div class="form-group mb-3 row">
					<label for="Zaehlerstand_Abwasser" class="col-md-5 col-form-label">Zählerstand Abwasserzähler </label>		  
					<div class="col-md-7">
						<input type="text" class="form-control form-control-lg" id="Zaehlerstand_Abwasser" name="Zaehlerstand_Abwasser" value="<?=$abwasser?>">
					</div>
				</div>
				<!-- Temperatur MZB -->	
				<div class="form-group mb-3 row">
					<label for="Temperatur_MZB" class="col-md-5 col-form-label">Temperatur MZB</label>
					<div class="col-md-7">
						<input type="text" class="form-control form-control-lg" id="Temperatur_MZB" name="Temperatur_MZB" value="<?=$temperatur_mzb?>">
					</div>
				</div>
				<!-- Temperatur MSB -->	
				<div class="form-group mb-3 row">
					<label for="Temperatur_NSB" class="col-md-5 col-form-label">Temperatur NSB</label>
					<div class="col-md-7">
						<input type="text" class="form-control form-control-lg" id="Temperatur_NSB" name="Temperatur_NSB" value="<?=$temperatur_nsb?>">
					</div>
				</div>
				<!-- Temperatur KKB -->	
				<div class="form-group mb-3 row">
					<label for="Temperatur_KKB" class="col-md-5 col-form-label">Temperatur KKB</label>
					<div class="col-md-7">
						<input type="text" class="form-control form-control-lg" id="Temperatur_KKB" name="Temperatur_KKB" value="<?=$temperatur_kkb?>">
					</div>
				</div>
				<!-- Filterspülungen -->
				<!--
					<div class="form-group mb-3 row">
						<label for="Filterspuelungen" class="col-md-5 col-form-label">Filterspülungen</label>
						<div class="col-md-7">
							<input type="text" class="form-control form-control-lg" id="Filterspuelungen" name="Filterspuelungen" value="<?=$filterspuelungen?>">
						</div>
					</div>
				-->
				<!-- Filterspülungen -->
				<div class="form-group mb-3 row">
					<label for="Filterspuelungen" class="col-md-5 col-form-label">Filterspülungen</label>
					<div class="col-md-7">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" id="Filterspuelung_MZB" name="Filterspuelung_MZB" <?=$filterspuelung_mzb?>>
							<label class="form-check-label" for="Filterspuelung_MZB">MZB</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" id="Filterspuelung_NSB" name="Filterspuelung_NSB" <?=$filterspuelung_nsb?>>
							<label class="form-check-label" for="Filterspuelung_NSB">NSB</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" id="Filterspuelung_KKB" name="Filterspuelung_KKB" <?=$filterspuelung_kkb?>>
							<label class="form-check-label" for="Filterspuelung_NSB">KKB</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" id="Filterspuelung_Bachbett" name="Filterspuelung_Bachbett" <?=$filterspuelung_bachbett?>>
							<label class="form-check-label" for="Filterspuelung_Backbett">Bachbett</label>
						</div>
					</div>
				</div>
				<!-- Bemerkungen -->		
				<div class="form-group mb-3 row">
					<label for="Bemerkungen" class="col-md-5 col-form-label">Bemerkungen</label>
					<div class="col-md-7">
						<input type="text" class="form-control form-control-lg" id="Bemerkungen" name="Bemerkungen" value="<?=$bemerkungen?>">
					</div>
				</div>
				<!-- Tagesbesucherzahl -->		
				<div class="form-group mb-3 row">
					<label for="Tagesbesucherzahl" class="col-md-5 col-form-label">Tagesbesucherzahl</label>
					<div class="col-md-7">
						<input type="text" class="form-control form-control-lg" id="Tagesbesucherzahl" name="Tagesbesucherzahl" value="<?=$tagesbesucherzahl?>">
					</div>
				</div>
				<!-- Protokollunterzeichner -->		
				<div class="form-group mb-3 row">
					<label for="Protokollunterzeichner" class="col-md-5 col-form-label">Protokollunterzeichner</label>
					<div class="col-md-7">
						<input type="text" class="form-control form-control-lg" id="Protokollunterzeichner" name="Protokollunterzeichner" value="<?=$protokollunterzeichner?>">
					</div>
				</div>
			</div>
			<hr class="bg-transparent border-0 py-1" />
			<hr class="my-4" />
			<div class="form-group mb-3 row"><label for="speichern11" class="col-md-5 col-form-label"></label>
				<div class="col-md-7">
					<button class="btn btn-primary btn-lg" type="submit">Speichern</button>
				</div>
			</div>
		</form>
	</body>
</html>