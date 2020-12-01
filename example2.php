<?php

// php -S 0.0.0.0:8080 example2.php

require __DIR__ . '/vendor/autoload.php';

use \SqlParser\SqlParser;


function pre($var, $exit=false) {
	echo "<pre>" . print_r($var, true) . "</pre>";
	if ($exit) {
		exit;
	}
}



echo '
<html>
<head>
	<title>SqlParser Demo</title>
</head>
<body>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
	<style>.table thead {font-weight:bold; text-align:center;}</style>

	<div class="container-fluid">
		<div class="m-3"></div>
';




$database = [
	'users' => [
		['id' => 1, 'name' => 'pierre' , 'email' => 'pierre@email.com' , 'age' => 42, 'sexe' => 'm'],
		['id' => 2, 'name' => 'paul'   , 'email' => 'paul@email.com'   , 'age' => 49, 'sexe' => 'm'],
		['id' => 3, 'name' => 'jacques', 'email' => 'jacques@email.com', 'age' => 53, 'sexe' => 'm'],
		['id' => 4, 'name' => 'alain'  , 'email' => 'alain@email.com'  , 'age' => 59, 'sexe' => 'm'],
		['id' => 5, 'name' => 'elodie' , 'email' => 'elodie@email.com' , 'age' => 38, 'sexe' => 'f'],
		['id' => 6, 'name' => 'marion' , 'email' => 'marion@email.com' , 'age' => 42, 'sexe' => 'f'],
	],
	'sexes' => [
		['langue' => 'fr', 'code' => 'f', 'name' => 'Femme'],
		['langue' => 'fr', 'code' => 'm', 'name' => 'Homme'],
		['langue' => 'en', 'code' => 'f', 'name' => 'Woman'],
		['langue' => 'en', 'code' => 'm', 'name' => 'Man'],
	],
];



$sql = "select sexe
			, count(*) as nb_users
			, group_concat(u.name) as names
			, avg(age) as ages_avg
			, sum(age) as ages_sum
			, min(age) as age_min
			, max(age) as age_max
			, s.name as sexe_fr
			, s_en.name as sexe_en
		from users as u
		inner join sexes s on s.code = u.sexe and s.langue = 'fr' 
		inner join sexes s_en on s_en.code = u.sexe and s_en.langue = 'en'
		group by sexe";
			


//#### quick usage

//$rows = (new SqlParser($sql, $database))->execute();
//pre($rows, 1);


//#### detailed usage

$parser = new SqlParser($sql, $database);

// retrieve rewritten query string (without colors)
//pre( $parser->getParsedSql() );

$parser->showInputSql();

// display rewritten query (with colors)
$parser->showParsedSql();

// execute query
$rows = $parser->execute();

// display results in an HTML table
$parser->showResults();


// show database (at its final state)
$parser->showDatabase();



echo '
	</div>
</body>
</html>';

