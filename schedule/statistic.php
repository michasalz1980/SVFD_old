<html lang="de">
	<head>
		<?php
			use \RedBeanPHP\R as R;
			require 'vendor/autoload.php';

			$config = include 'config.php';

			// Database configuration
			$dbConfig = $config['database'];        
			R::setup(
				"mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}",
				$dbConfig['username'],
				$dbConfig['password']
			);

			$aUsers = R::getAll('SELECT * FROM view_scheduleAushilfe WHERE type = "aushilfe"', array());
			R::close();
			$dataPoints = array();
			foreach($aUsers as $obj) {
				array_push($dataPoints, array("label"=> $obj["firstname"], "y"=> $obj["total"]));
			}
		?>
		<TITLE>Freibad Dabringhausen - Dienstplan - Auswertung</TITLE>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
		<!-- Optional theme -->
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
		<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.css">
		<!-- Latest compiled and minified JavaScript -->
		<script type="text/javascript" src="//code.jquery.com/jquery-1.11.0.min.js"></script>
		<script type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
		<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.js"></script>
		<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
		<script type="text/javascript" src="js/md5.js"></script>
		<script type="text/javascript" src="js/main.js"></script>
		<link rel="stylesheet" href="css/signin.css">
		<link rel="stylesheet" href="css/general.css">
		<script>
			$(document).ready( function () {
				$('#tbl_auswertung').DataTable( {
					"order": [[ 2, "asc" ]],
					"lengthMenu": [[25, 10, 50, -1], [25, 10, 50, "All"]]
				} );
				var chart = new CanvasJS.Chart("chartContainer", {
					animationEnabled: true,
					theme: "light2", // "light1", "light2", "dark1", "dark2"
					title: {
					text: "Auswertung"
					},
					axisY: {
						
					title: "Anzahl an Schichten",
					includeZero: false
					},
					data: [{
						type: "column",
						indexLabel: "{y}",
						dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
					}]
				});
				chart.render();
			});	
			
		</script>
	</head>
	<body>

	<div style="width:75%; margin: 25px;">
	<table width="100%" id="tbl_auswertung" class="table table-striped table-bordered">
		<thead>
		<tr>
			<th>Name</th>
			<th>Vorname</th>
			<th>Typ</th>
			<th>Frühschicht</th>
			<th>Spätschicht</th>
			<th>Bereitschaft</th>
		</tr>
		</thead>
		<tbody>
		<?php
			foreach($aUsers as $obj) {
				echo "\t\t<tr>\n";
				echo "\t\t\t<td>" . $obj["surname"] . "</td>\n";
				echo "\t\t\t<td>" . $obj["firstname"] . "</td>\n";
				echo "\t\t\t<td>" . $obj["type"] . "</td>\n";
				echo "\t\t\t<td>" . $obj["earlyShift"] . "</td>\n";
				echo "\t\t\t<td>" . $obj["lateShift"] . "</td>\n";
				echo "\t\t\t<td>" . $obj["standby"] . "</td>\n";
				echo "\t\t</tr>\n";
			}
		?>
		</tbody>
	</table>
	</div>
	<div id="chartContainer" style="height: 370px; width: 75%; margin: 25px;"></div>
</body>
</html>