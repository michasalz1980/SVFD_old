 <?php

	
	function getWetterIcon($wetter_s, $wetter_h, $wetter_b, $wetter_r, $wetter_g) {
		$icons = "";
		if ($wetter_s == "1") {
			$icons .= "<img src='img/wetter_s.png' width='48' height='48' />";
		}
		if ($wetter_h == "1") {
			$icons .= "<img src='img/wetter_h.png' width='48' height='48' />";
		}
		if ($wetter_b == "1") {
			$icons .= "<img src='img/wetter_b.png' width='48' height='48' />";
		}
		if ($wetter_r == "1") {
			$icons .= "<img src='img/wetter_r.png' width='48' height='48' />";
		}
		if ($wetter_g == "1") {
			$icons .= "<img src='img/wetter_g.png' width='48' height='48' />";
		}
		return $icons;	
	}

	function SQLfromDateStr($myDateStr) {
		$sqlStr = substr($myDateStr, 6, 4)."-". substr($myDateStr, 3, 2) ."-". substr($myDateStr, 0, 2); 
		return $sqlStr;
	}


	function datetimeStrfromMySQL($mySQLstr) {
		if ($mySQLstr != "0000-00-00 00:00:00") { 
			$dStr = substr($mySQLstr, 8, 2).".". substr($mySQLstr, 5, 2) .".". substr($mySQLstr, 0, 4) . " " . substr($mySQLstr, 11, 2) .":". substr($mySQLstr, 14, 2); 
		} 
		else { $dStr = ""; }
		return $dStr;
	}


function dateStrfromMySQL($mySQLstr) {
		  
   
 if ($mySQLstr != "0000-00-00")
 { $dStr = substr($mySQLstr, 8, 2).".". substr($mySQLstr, 5, 2) .".". substr($mySQLstr, 0, 4); }
  else
  { $dStr = ""; }

return $dStr;
}



function commaweight ($w) {
 $w = number_format($w, 1);
 $weight_with_comma = str_replace(".", ",", $w);	
 return $weight_with_comma;
}

function datefromindex ($idx) {		
	$d = date_create_from_format("d.m.Y", "09.02.1981");
	date_add($d, date_interval_create_from_date_string($idx.' days'));
	return $d;		
}

function datestrfromindex($idx) {
	$d = date_create_from_format("d.m.Y", "09.02.1981");
	date_add($d, date_interval_create_from_date_string($idx.' days'));
	$dStr = date_format($d, "d.m.Y");
	return $dStr;
}


// ein php-Datumsobjekt wird übergeben und zurückgeben wird der dazu gehörige Index in $weightL
function datetoindex ($date) {		
	global $start_date;
	$diff = date_diff($start_date, $date);
	$days_cnt = $diff->format("%a");
	return $days_cnt;
}


// ein normal formatiertes Datum ("18.05.2021") wird übergeben und zurückgeben wird der dazu gehörige Index in $weightL
function datestrtoindex ($dateStr) {		
	global $start_date;
	$date = date_create_from_format("d.m.Y", $dateStr);
	$diff = date_diff($start_date, $date);
	$days_cnt = $diff->format("%a");
	return $days_cnt;
}



function myimageellipse($image, $x, $y, $rx, $ry, $color)
  {
    // We don't use imageellipse because the imagesetthickness function has
    // no effect. So the better workaround is to use imagearc.
    imagearc($image,$x,$y,$rx,$ry, 0,359,$color);

    // If we stop here, we don't have a properly closed ellipse.
    // Using imagefill at this point will flood outside the ellipse (actually arc).

    // We have to close the arc to make it a real ellipse.
    $cos359=0.99984769;
    $sin359=-0.01745240;

    $x1=round($x+$rx/2*$cos359);
    $y1=round($y+$ry/2*$sin359);
   
    $x2=round($x+$rx/2);
    $y2=round($y);

    // imageline is sensitive to imagesetthickness as well.
    imageline($image,$x1,$y1,$x2,$y2,$color);

  }


function get_bmi ($weight, $height) {
	return $weight /($height * $height);
	
}

function get_weight ($bmi, $height) {
	return $bmi * $height * $height;
}

?>