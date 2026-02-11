<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Freibad Dabringhausen Überwachung Wasserqualität</title>
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
	include "connectDBva-r.php";
	
	$dbPrefix = DB_TABLE_PREFIX;	

	$id = $_GET['id'];

	date_default_timezone_set('Europe/Berlin');
	$datum = date('d.m.Y');    // Vorgabe ist das aktuelle Datum
	$uhrzeit =date('H:i');     // Vorgabe ist die aktuelle Zeit
	$becken = "";
	$cl_frei = "";
	$cl_gesamt = "";
	$phwert = "";
	$redoxwert = "";
	$wasserhaerte = "";
	
	if ($id > 0)
	{	
	$sql = "SELECT * FROM `".$dbPrefix."Wasserqualitaet`  WHERE `id` LIKE '$id'";
	
	$res = mysqli_query($dbcnx, $sql);
    $row = mysqli_fetch_array($res) ;
	
	$datum = dateStrfromMySQL($row['Datum']);
	$uhrzeit = substr($row['Uhrzeit'], 0, 5);
	$becken = $row['Becken'];
	$cl_frei = $row['Cl_frei'];
	$cl_gesamt = $row['Cl_gesamt'];
	$phwert = $row['pH_Wert'];
	$redoxwert = $row['Redox_Wert'];
	$wasserhaerte = $row['Wasserhaerte'];
	}
	
?>

	
<body>

	
	<div class="container">
		<?php 
		if ($id == "") 
		{ echo "<form action='liste_messungen.php?action=insert' method='post' class='m-auto' style='max-width:600px'>"; }
		else
	    { echo "<form action='liste_messungen.php?action=update&id=".$id."' method='post' class='m-auto' style='max-width:600px'>"; }
					
	?>
		<h3 class="my-4">Dateneingabe Wasserqualität</h3>
				<div class="form-group mb-3 row">&nbsp;&nbsp;&nbsp;<strong>Achtung: Dezimalpunkt statt Komma verwenden, also 7.5 statt 7,5</strong></div>
		<hr class="my-4" />
		<div class="form-group mb-3 row"><label for="datum" class="col-md-5 col-form-label">Datum</label>
			<div class="col-md-7"><input type="text" class="form-control form-control-lg" id="Datum" name="Datum" value="<?=$datum?>"></div>
		</div>
		<div class="form-group mb-3 row"><label for="uhrzeit" class="col-md-5 col-form-label">Uhrzeit</label>
			<div class="col-md-7"><input type="text" class="form-control form-control-lg" id="Uhrzeit" name="Uhrzeit" value="<?=$uhrzeit?>"></div>
		</div>
		<div class="form-group mb-3 row"><label for="becken3" class="col-md-5 col-form-label">Becken</label>
			<div class="col-md-7"><select class="form-select custom-select custom-select-lg" id="Becken" name="Becken">
				<?php 

switch ($becken) {
    case "MZB":
        echo "<option selected value = 'MZB'>Mehrzweckbecken</option>"; 
        break;
    case "KKB":
        echo "<option selected value = 'KKB'>Kleinkindbecken</option>"; 
        break;
    case "NSB":
   		echo "<option selected value = 'NSB'>Nichtschwimmerbecken</option>"; 
        break;
	default;
		echo "<option selected value = ''>bitte ausw&auml;hlen</option>"; 
        break;
}
?>
			
				<option value='MZB'>MZB Mehrzweckbecken</option>
				<option value='KKB'>KKB Kleinkindbecken</option>
				<option value='NSB'>NSB Nichtschwimmerbecken</option>
				</select></div>
		</div>
		<div class="form-group mb-3 row"><label for="cl-frei4" class="col-md-5 col-form-label">Cl frei</label>
			<div class="col-md-7"><input type="text" class="form-control form-control-lg" id="Cl_frei" name="Cl_frei" value="<?=$cl_frei?>">
			</div>
		</div>
		<div class="form-group mb-3 row"><label for="cl-gesamt5" class="col-md-5 col-form-label">Cl gesamt</label>
			<div class="col-md-7"><input type="text" class="form-control form-control-lg" id="Cl_gesamt" name="Cl_gesamt" value="<?=$cl_gesamt?>"></div>
		</div>

		<div class="form-group mb-3 row"><label for="ph-wert7" class="col-md-5 col-form-label">pH-Wert</label>
			<div class="col-md-7"><input type="text" class="form-control form-control-lg" id="pH_Wert" name="pH_Wert" value="<?=$phwert?>"></div>
		</div>
		<div class="form-group mb-3 row"><label for="ph-wert7" class="col-md-5 col-form-label">Wasserhärte</label>
			<div class="col-md-7"><input type="text" class="form-control form-control-lg" id="pH_Wert" name="Wasserhaerte" value="<?=$wasserhaerte?>"></div> 
		</div>
		<div class="form-group mb-3 row">
  <label for="redox_wert8" class="col-md-5 col-form-label">Redox_Wert</label>
		
  <div class="col-md-7"><input type="text" class="form-control form-control-lg" id="Redox_Wert" name="Redox_Wert" value="<?=$redoxwert?>""></div>
		</div>
		<hr class="bg-transparent border-0 py-1" />
		<hr class="my-4" />
		<div class="form-group mb-3 row"><label for="speichern11" class="col-md-5 col-form-label"></label>
			<div class="col-md-7"><button class="btn btn-primary btn-lg" type="submit">Speichern</button></div>
		</div>
	</form>
</div>
	
	
	
</body>
</html>
