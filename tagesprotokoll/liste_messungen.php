<title>Freibad Dabringhausen - Messungen der Wasserqualität</title>
<!--main css-->

<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet">

<link href="freibad.css" rel="stylesheet" type="text/css">

<style>
  .red {
    color: red;
  }
</style>


<?php
	include "mva_functions_v02.php";
	include "connectDBva-r.php";
	include 'config.php';
		
	$dbPrefix = DB_TABLE_PREFIX;	
	
	$action = $_GET['action'];
	$id = $_GET['id'];
	$becken = $_GET['becken'];
	if (isset($becken)) {$w2 = " WHERE becken = '".$becken."'"; } else {$w2 = ""; }
	
	//   D A T E N S A T Z   L Ö S C H E N
	if ($action == "delete") {
		$sql = "DELETE FROM ".$dbPrefix."Wasserqualitaet WHERE id = ".$id;
		 $res = mysqli_query($dbcnx, $sql);		
	}

	//   I N   D A T E N B A N K   E I N T R A G E N   O D E R   A K T U A L I S I E R E N
	if ($action == "insert" || $action == "update") { 
		$datum = SQLfromDateStr($_POST['Datum']);
		$uhrzeit = $_POST['Uhrzeit'];
		$becken = $_POST['Becken'];
		$cl_frei = $_POST['Cl_frei'];
		$cl_gesamt = $_POST['Cl_gesamt'];
		$phwert = $_POST['pH_Wert'];
		$redoxwert = $_POST['Redox_Wert'];
		$wasserhaerte = $_POST['Wasserhaerte'];


		if ($action == "insert") {$x = "INSERT INTO"; $w = "";}
		if ($action == "update") {$x = "UPDATE"; $w = " WHERE id=".$id; }
		
		$sql = $x." ".$dbPrefix."Wasserqualitaet SET 
		 Datum = '$datum',
		 Uhrzeit = '$uhrzeit',
		 Becken = '$becken',
		 Cl_frei = '$cl_frei',
		 Cl_gesamt = '$cl_gesamt',
		 pH_Wert = '$phwert',
		 Wasserhaerte = '$wasserhaerte',
		 Redox_Wert = '$redoxwert'".$w;
		
		$res = mysqli_query($dbcnx, $sql);		
	}
	
	?>






</head>
<body>
	
<p>
<h3>Freibad Dabringhausen - Messungen der Wasserqualität</h3>
	

<div><input class="ButtonClass" type="button" value="Menü" onclick="window.location.href='index.php'"</input>&nbsp;&nbsp;&nbsp;<input class="ButtonClass" type="button" value="Neue Messung eingeben" onclick="window.location.href='input_wasser.php'"</input></div>
	<br>
	<?php if ($becken=="MZB") 
{ $mzb = "<a href='liste_messungen.php?becken=MZB'><strong>MZB</strong></a>"; } else { $mzb = "<a href='liste_messungen.php?becken=MZB'>MZB</a>"; }
	
	if ($becken=="KKB") 
{ $kkb = "<a href='liste_messungen.php?becken=KKB'><strong>KKB</strong></a>"; } else { $kkb = "<a href='liste_messungen.php?becken=KKB'>KKB</a>"; }
	
		if ($becken=="NSB") 
{ $nsb = "<a href='liste_messungen.php?becken=NSB'><strong>NSB</strong></a>"; } else { $nsb = "<a href='liste_messungen.php?becken=NSB'>NSB</a>"; }
	
			if ($becken=="") 
{ $alle = "<a href='liste_messungen.php'><strong>alle</strong></a>"; } else { $alle = "<a href='liste_messungen.php'>alle</a>"; }
	
	echo "<div>Becken:&nbsp;&nbsp;&nbsp;".$mzb."&nbsp;&nbsp;&nbsp;".$kkb."&nbsp;&nbsp;&nbsp;".$nsb."&nbsp;&nbsp;&nbsp;".$alle."</div>";
		?>
	<br>
	<table  width="100%" border="1" cellspacing="0" cellpadding="2" bordercolor="#DDDDDD">
<tr bgcolor="007a96" color="'ffffff"> 
 <!--<td class="legende">id</td>-->
 <td class="legende">Datum</td>
 <td class="legende">Uhrzeit</td>
 <td class="legende">Becken</td>
 <td class="legende">Cl frei <br/>(0,3 - 0,6 mg/l)</td><br>
 <td class="legende">Cl gesamt <br/> (mg/l)</td>
 <td class="legende">Cl gebunden <br/>(0,0 - 0,2 mg/l)</td>
 <td class="legende">pH-Wert <br/>(6,8 - 7,2)</td>
 <td class="legende">Redox-Wert <br/>(650-900mV)</td>
 <td class="legende">Wasserhärte <br/>(° dH)</td>
 <td class="legende">&nbsp;</td>
</tr>
	
	
  
<?php
  
	$sql = "SELECT * FROM ".$dbPrefix."Wasserqualitaet".$w2." ORDER BY Datum DESC, Uhrzeit DESC";
	
	$res = mysqli_query($dbcnx, $sql);
   	
	while ( $row = mysqli_fetch_array($res) )
  {
	
    $id = $row['id'];
	$datum = dateStrfromMySQL($row['Datum']);
	$uhrzeit = substr($row['Uhrzeit'], 0, 5);
	$becken = $row['Becken'];
	$cl_frei = $row['Cl_frei'];
	$cl_gesamt = $row['Cl_gesamt'];
	$cl_gebunden = $cl_gesamt - $cl_frei;
	$phwert = $row['pH_Wert'];
	$redoxwert = $row['Redox_Wert'];
	$wasserhaerte = $row['Wasserhaerte'];
		
		
	echo("<tr bgcolor='$bgcolor'>\n");
    // echo("<td>$id</td>");
																									   
	echo("<td>$datum</td>");
	echo("<td>$uhrzeit</td>");
	echo("<td>$becken</td>");
	if ($cl_frei < 0.3 || $cl_frei > 0.6) {
	  echo("<td class='red'>$cl_frei</td>");
	} else {
	  echo("<td>$cl_frei</td>");
	}

	if ($cl_gesamt < 0) {
	  echo("<td class='red'>$cl_gesamt</td>");
	} else {
	  echo("<td>$cl_gesamt</td>");
	}

	if ($cl_gebunden < 0.0 || $cl_gebunden > 0.2) {
	  echo("<td class='red'>$cl_gebunden</td>");
	} else {
	  echo("<td>$cl_gebunden</td>");
	}

	if ($phwert < 6.8 || $phwert > 7.5) {
	  echo("<td class='red'>$phwert</td>");
	} else {
	  echo("<td>$phwert</td>");
	}

	if ($redoxwert < 650 || $redoxwert > 900) {
	  echo("<td class='red'>$redoxwert</td>");
	} else {
	  echo("<td>$redoxwert</td>");
	}
	if ($wasserhaerte < 0) {
	  echo("<td class='red'>$wasserhaerte</td>");
	} else {
	  echo("<td>$wasserhaerte</td>");
	}
	echo("<td><a href='input_wasser.php?id=".$id."''>bearbeiten</a></td>");		
		
    echo("</tr>\n");
		
	}

?>
 
</table>

</body>
</html>

