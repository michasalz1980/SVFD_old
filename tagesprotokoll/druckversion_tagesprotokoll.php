<title>Freibad Dabringhausen - Druckversion Tagesprotokoll</title>
<!--main css-->

<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"><br>
 <meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet">

<link href="freibad.css" rel="stylesheet" type="text/css">

<style type="text/css">

body { margin-left:100px; }
</style>


<?php
	include "mva_functions_v02.php";
	include "connectDBva-r.php";
	include 'config.php';
		
	$dbPrefix = DB_TABLE_PREFIX;	
	$id = $_GET['id'];
	$sql = "SELECT * FROM ".$dbPrefix."Tagesprotokoll WHERE id=".$id;
	
			
	$res = mysqli_query($dbcnx, $sql);
   	
	while ( $row = mysqli_fetch_array($res) )
  {

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
		
	$bemerkungen = $row['Bemerkungen'];	
	$tagesbesucherzahl = $row['Tagesbesucherzahl'];	
	$protokollunterzeichner = $row['Protokollunterzeichner'];	
		
	
  }	
?>






</head>
<body>
<p>
<h3>Freibad Dabringhausen</h3>
	<h2>Tagesprotokoll vom <?=$datum?></h2>

	<p>
	<table width="40%" height="58" border="0">
	  <tbody>
	    <tr width="50%">
	      <td class=tp_legende>Wetterlage</td>
	      <td class="tp_legende">Lufttemperatur</td>	    
        </tr>
		  	    <tr>
	      <td>
				<?php
	if ($wetter_s == "1") { echo("<img src='img/wetter_s.png' width='48' height='48' />"); }
	if ($wetter_h== "1") { echo("<img src='img/wetter_h.png' width='48' height='48' />"); }
	if ($wetter_b == "1") { echo("<img src='img/wetter_b.png' width='48' height='48' />"); }
	if ($wetter_r == "1") { echo("<img src='img/wetter_r.png' width='48' height='48' />"); }
	if ($wetter_g == "1") { echo("<img src='img/wetter_g.png' width='48' height='48' />"); }
		?>
			</td>
	      <td class="tp_value"><?=$lufttemperatur." °C"?></td>
	       </tr>
		      <tr width="50%">
	      <td class=tp_legende span=2>Tagesbesucherzahl</td>	    
        </tr>
		  		      <tr width="50%">
	    <td class="tp_value"><?=$tagesbesucherzahl?></td>    
			      </tr>
		    <tr width="50%">
	      <td>&nbsp;</td>
	      <td>&nbsp;</td>	    
        </tr>			
        </tr>
		    <tr width="50%">
	      <td>&nbsp;</td>
	      <td>&nbsp;</td>	    
        </tr>
        </tr>
		    <tr width="50%">
	      <td class=tp_legende>Zählerstand Wasserleitungsnetz</td>
	      <td class=tp_legende>Zählerstand Abwasserzähler</td>	    
        </tr>
	    <tr>
	      <td class=tp_value_small><?=$wasserleitungsnetz?></td>
	      <td class=tp_value_small><?=$abwasser?></td>	     
        </tr>
      </tbody>
</table>
</p>	
	<br>
	<div class=tp_legende>Beckentemperaturen:</div>
	
	<table width="40%" height="58" border="0">
	  <tbody>
	    <tr>
	      <td class=tp_legende_small>Mehrzweckbecken</td>
	      <td class=tp_legende_small>Nichtschwimmerbecken</td>
	      <td class=tp_legende_small>Kleinkindbecken</td>
        </tr>
	    <tr>
	      <td class="tp_value_small"><?=$temperatur_MZB." °C"?></td>
	      <td class="tp_value_small"><?=$temperatur_NSB." °C"?></td>
	      <td class="tp_value_small"><?=$temperatur_KKB." °C"?></td>
        </tr>
      </tbody>
</table>
	 <p class=tp_legende>Filterspülungen:</p>
	<!-- <p class="tp_value_text"><?=$filterspuelungen?></p><br> -->
	<ul>
	  <li>MZB: <?php echo ($filterspuelungen_MZB == "1") ? "Ja" : "Nein"; ?></li>
	  <li>NSB: <?php echo ($filterspuelungen_NSB == "1") ? "Ja" : "Nein"; ?></li>
	  <li>KKB: <?php echo ($filterspuelungen_KKB == "1") ? "Ja" : "Nein"; ?></li>
	</ul>

	<p class=tp_legende>Bemerkungen:</p>
	<p class="tp_value_text"><?=$bemerkungen?></p><br>
	
	<p class=tp_legende>Protokollunterzeichner:</p>
	<p class="tp_value_text"><?=$protokollunterzeichner?></p>
	<p><hr></p>

	 <p class=tp_legende>Messungen Wasserqualität am <?=$datum?>:</p>

<table  width="40%" border="1" cellspacing="0" cellpadding="2" bordercolor="#DDDDDD">
<tr bgcolor="007a96" color="'ffffff"> 
 <td class="legende">Uhrzeit</td>
 <td class="legende">Becken</td>
 <td class="legende">Cl frei</td>
 <td class="legende">Cl gebunden</td>
 <td class="legende">Cl gesamt</td>
 <td class="legende">pH-Wert</td>
 <td class="legende">Redox-Wert</td>
</tr>
	
	 
<?php
  
	$sql = "SELECT * FROM ".$dbPrefix."Wasserqualitaet WHERE Datum='".SQLfromDateStr($datum)."' ORDER BY Uhrzeit ASC";
		
	$res = mysqli_query($dbcnx, $sql);
    $row_cnt = mysqli_num_rows($res);
		
			  
	while ( $row = mysqli_fetch_array($res) )
  {
	
   
	$uhrzeit = substr($row['Uhrzeit'], 0, 5);
	$becken = $row['Becken'];
	$cl_frei = $row['Cl_frei'];
	$cl_gesamt = $row['Cl_gesamt'];
	$cl_gebunden = $cl_gesamt - $cl_frei;
	$phwert = $row['pH_Wert'];
	$redoxwert = $row['Redox_Wert'];
		
		
	echo("<tr bgcolor='$bgcolor'>\n");
   
	echo("<td>$uhrzeit</td>");
	echo("<td>$becken</td>");
	echo("<td>$cl_frei</td>");
	echo("<td>$cl_gebunden</td>");
	echo("<td>$cl_gesamt</td>");
	echo("<td>$phwert</td>");
	echo("<td>$redoxwert</td>");
			
    echo("</tr>\n");
		
	}
	 echo("</table>");
			  
	 if ($row_cnt == 0) { echo "<br><div class=tp_value_text>Keine Messungen an diesem Tag vorhanden.</div>"; }

	mysqli_close($dbcnx);		   
?>
 
 

</body>
</html>

