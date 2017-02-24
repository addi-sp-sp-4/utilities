<style>
 table, th, td {
    outline: 1px solid black;
}
</style>



<?php
error_reporting(E_ERROR);
$user = 'root';
$pass = 'pass';
$host = '127.0.0.1';
$db = 'dump';

$conn = new mysqli($host, $user, $pass, $db);

if($conn->connect_error)
{
	echo "Unable to connect to database, reason:  <b>$conn->connect_error</b><br>";
	exit();
}
if(!isset($_GET['query']))
{
	echo "Connected to <b>$db</b> at <b>$host</b> with user <b>$user</b><br><br><br>";
}
/*
TODO: 
FIX FUNCTIONS
CANT GET $conn OBJECT PASSING TO WORK PROPERLY 
*/

$function_type = $_GET['type'];

if(!isset($function_type) || $function_type == "")
{
	header("Location: db-extract.php?type=structure");
}

if($function_type == "structure")
{
	$table_result = $conn->query("SHOW TABLES");

	while($table_row = $table_result->fetch_assoc())
	{
		$current_table = $table_row["Tables_in_$db"];
		echo "TABLE: $current_table: <br>";

		$column_result = $conn->query("show columns from $current_table");

		while($column_row = $column_result->fetch_assoc())
		{
			$field_column = $column_row["Field"];
			$type_column = $column_row["Type"];
			$null_column = $column_row["Null"];
			$key_column = $column_row["Key"];
			$default_column = $column_row["Default"];
			$extra_column = $column_row["Extra"];

			if($key_column == "")
			{
				$key_column = "NONE";
			}

			if($default_column == "")
			{
				$default_column = "NONE";
			}

			if($extra_column == "")
			{
				$extra_column = "NONE";
			}

			$padding = '-'; 
			for($i = 1; $i < strlen($current_table) + strlen($current_table) / 1.5 + 7; $i++)
			{
				$padding .= "-";
			}

			echo " $padding COLUMN: $field_column <br>";

			for($i = 0; $i < strlen($field_column) + strlen($field_column) / 1.5 + 8; $i++)
			{
				$padding .= '-';
			}

			echo "$padding TYPE: $type_column <br>";
			echo "$padding NULL: $null_column <br>";
			echo "$padding KEY:  $key_column <br>";
			echo "$padding DEF:  $default_column <br>";
			echo "$padding EXTR: $extra_column <br>";

			echo "<br>";
		}
		echo "<br>";
	}
}

if($function_type == "dump")
{
	echo "<center>";

	$table_result = $conn->query("SHOW TABLES");

	while($table_row = $table_result->fetch_assoc())
	{
		$current_table = htmlentities($table_row["Tables_in_$db"]);
		echo "$current_table: <br>";

		$column_result = $conn->query("show columns from $current_table");

		echo "<table>
				<tr>";

		while($column_row = $column_result->fetch_assoc())
		{
			$field_column = htmlentities($column_row["Field"]);
			echo "<th>$field_column</th>";
		}
		echo "</tr>";
		
		$content_result = $conn->query("select * from $current_table");

		while($content_row = $content_result->fetch_assoc())
		{
			echo "<tr>";
			foreach($content_row as $content => $value)
			{
				$value = htmlentities($value);

				echo "<td>$value</td>";
			}
			echo "</tr>";
		}

	echo "</table>";
	}

}

if($function_type == "console")
{
	if(isset($_GET['query']))
	{
		if($_GET['response'] == 'true')
		{
			$result = $conn->query($_GET['query']);
			if($conn->error)
			{
				echo $conn->error;
				exit();

			}
			echo "<table>
					<tr>";
			foreach($result->fetch_assoc() as $key => $value)
			{
				echo "<th>$key</th>";
			}
			echo "</tr>";
			while($content = $result->fetch_assoc())
			{
				echo "<tr>";
				foreach($content as $key => $value)
				{
					echo "<td>$value</td>";
				}
				echo "</tr>";
			}
		}
		else
		{
			$conn->query($_GET['query']);
			exit();
		}
		
	}
	else
	{
		echo "<center> 
		Query:
		<input style='width: 75%;' type='text' id='query' />
		<br>
		Callback:
		<input type='checkbox' id='callback' />
		<br>
		<input type='button' value='Submit' onclick='sendrequest()'/><br><br>

		<div id='response'>
		</div>";
	}
}
?>

<script type="text/javascript">
function sendrequest() {
    var xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == XMLHttpRequest.DONE ) {
           if (xmlhttp.status == 200) {
               document.getElementById("response").innerHTML = xmlhttp.responseText;
           }
           else if (xmlhttp.status == 400) {
              alert('There was an error 400');
           }
           else {
               alert('something else other than 200 was returned');
           }
        }
    };

    xmlhttp.open("GET", "db-extract.php?type=console&query=" + document.getElementById('query').value + "&response=" + document.getElementById('callback').checked, true);
    xmlhttp.send();
}
</script>
