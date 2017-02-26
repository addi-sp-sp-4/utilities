
<?php
error_reporting(E_ERROR);

/*get DB credentials */
$host = $_GET['host'];
$user = $_GET['user'];
$pass = $_GET['pass'];
$db = $_GET['db'];
$name = $_GET['name'];

/* get function type */
$function_type = $_GET['type'];

/* To append to URL when we need to call this file internally */
$getstring = "&host=$host&user=$user&pass=$pass&db=$db";

/*Initializing connection */
$conn = new mysqli($host, $user, $pass, $db);

if($conn->connect_error)
{
	if($function_type != "docs")
	{
		echo "Unable to connect to database, reason:  <b>$conn->connect_error</b><br>";
	}
}



/* So we don't get unnecessary callback with SQL shell or copy-db*/ 
if(!$conn->connect_error && !isset($_GET['query']) && $function_type != "copy-db")
{
	echo "Connected to <b>$db</b> at <b>$host</b> with user <b>$user</b><br>";
	$jsonData = json_decode(file_get_contents('https://api.ipify.org?format=json'));
	echo "the IP this web server has is <b>$jsonData->ip</b><br><br>";
}

/*
TODO: 
FIX FUNCTIONS
CANT GET $conn OBJECT PASSING TO WORK PROPERLY 

TODO:
PROTECT FROM XSS
*/




/* Set default to structure */
if(!isset($function_type) || $function_type == "")
{
	header("Location: db-extract.php?type=structure$getstring");
}

/* get structure of tables */
if($function_type == "structure")
{
	/*Get all tables in database */
	$table_result = $conn->query("SHOW TABLES");

	/*loop over every table */
	while($table_row = $table_result->fetch_assoc())
	{

		/* which table we are working in */
		$current_table = $table_row["Tables_in_$db"];

		echo "TABLE: $current_table: <br>";

		/* get all columns in $current_table */
		$column_result = $conn->query("show columns from $current_table");

		/* Loop over columns */
		while($column_row = $column_result->fetch_assoc())
		{
			/* Get all fields from $column_row */

			/* TODO: use foreach for this */
			$field_column = $column_row["Field"];
			$type_column = $column_row["Type"];
			$null_column = $column_row["Null"];
			$key_column = $column_row["Key"];
			$default_column = $column_row["Default"];
			$extra_column = $column_row["Extra"];

			/* For better output */
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
			/* How much '-''s we need to print */
			/* padding is length of current table name + length of current table name divided by 1.5 + Length of "TABLE: " which is 7 */

			for($i = 1; $i < strlen($current_table) + strlen($current_table) / 1.5 + 7; $i++)
			{
				$padding .= "-";
			}

			echo " $padding COLUMN: $field_column <br>";

			/*padding is previous padding + length of current column name + length of current column name divided by 1.5 + Length of "COLUMN: " which is 8 */
			for($i = 0; $i < strlen($field_column) + strlen($field_column) / 1.5 + 8; $i++)
			{
				$padding .= '-';
			}

			/* print info */
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

/*dump whole DB */
if($function_type == "dump")
{
	echo "<center>";

	/* get all tables */
	$table_result = $conn->query("SHOW TABLES");

	/* Loop over every table */
	while($table_row = $table_result->fetch_assoc())
	{
		/* Which table we are in */
		$current_table = htmlentities($table_row["Tables_in_$db"]);
		echo "$current_table: <br>";

		/* get all columns from table */
		$column_result = $conn->query("show columns from $current_table");

		/* Prepare table */
		echo "<table>
				<tr>";

		/* Print field names as headers in table*/
		while($column_row = $column_result->fetch_assoc())
		{
			$field_column = htmlentities($column_row["Field"]);
			echo "<th>$field_column</th>";
		}
		echo "</tr>";

		/* Get all content from table */
		$content_result = $conn->query("select * from $current_table");

		/*Print all content as bodies in table */
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

/* SQL console */
if($function_type == "console")
{
	/*Check if query parameter exists */
	if(isset($_GET['query']))
	{
		/* Validiating */
		if($_GET['query'] == '')
		{
			echo "Please enter something first";
			exit();
		}

		/* if we want a response */
		if($_GET['response'] == 'true')
		{
			/*execute query */
			$result = $conn->query($_GET['query']);

			/* Troubleshooting help */
			if($conn->error)
			{
				echo $conn->error;
				exit();

			}
			/* Prepare table */
			echo "<table>
					<tr>";

			/* Loop over query result and print column names as header in table*/
			foreach($result->fetch_assoc() as $key => $value)
			{
				echo "<th>$key</th>";
			}

			echo "</tr>";

			/* Needed to reset fetch_assoc() */
			$result = $conn->query($_GET['query']);

			/*Print all content in table */
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

		/*If we don't want a response*/
		else
		{
			/* execute query */
			$conn->query($_GET['query']);
			exit();
		}
		
	}

	/* Print basic form otherwise */ 
	else
	{
		?>
		Query:
		<input style='width: 75%;' type='text' id='query' />
		<br>
		Callback:
		<input type='checkbox' id='callback' checked />
		<br>
		<input type='button' value='Submit' onclick='sendrequest()'/><br><br>
		<div id='response'>
		</div>";

		<?php
	}
}

/* Make copy of database */
if($function_type == "copy-db")
{
	
	/*execute mysqldump */
	$cmd = "mysqldump --host='$host' -u '$user' -p$pass '$db'";
	$dump = shell_exec($cmd);
	echo $dump;
	exit();
}
if($function_type == "docs")
{
	echo file_get_contents("https://davidbouman.github.io/utilities/php/index.html");
}
?>


<script type="text/javascript">

/* AJAX for console */
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

    xmlhttp.open("GET", "db-extract.php?type=console&query=" + document.getElementById('query').value + "&response=" + document.getElementById('callback').checked +  <?php echo '"' . $getstring . '"';?>, true);
    xmlhttp.send();
}

</script>

<style>
 table, th, td {
    outline: 1px solid black;
}
</style>
