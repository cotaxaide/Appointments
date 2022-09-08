<?PHP
//Version 8.01
//	Fix opendb.php so it works with PHP v8

if (file_exists("opendb.php")) {
	$linearray = [];
	$line = 0;
	$filein = fopen("opendb.php", "r");
	while ($linearray[$line++] = fgets($filein)) {}
    fclose($filein);
	$fileout = fopen("opendb.php", "w");
	for ($line = 0; $line < sizeof($linearray); $line++) {
		$lineout = str_replace("(\$dbcon)", "()", $linearray[$line]);
		fwrite($fileout, $lineout);
	}
}
