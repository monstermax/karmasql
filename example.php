<?php

// php -S 0.0.0.0:8080 example.php

require __DIR__ . '/vendor/autoload.php';

use \SqlParser\SqlQueryParser;


function pre($var, $exit=false) {
    echo "<pre>" . print_r($var, true) . "</pre>";
    if ($exit) {
        exit;
    }
}


$database = [
    'users' => [
        ['id' => 1, 'name' => 'pierre' , 'age' => 42, 'sexe' => 'm'],
        ['id' => 2, 'name' => 'paul'   , 'age' => 49, 'sexe' => 'm'],
        ['id' => 3, 'name' => 'jacques', 'age' => 53, 'sexe' => 'm'],
    ],
];




$sql = "select * from users order by age, name desc";


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


if ($sql) {
    $parser = new SqlQueryParser($sql, $database);

    $display_query = true;
    if ($display_query) {
        // parse query
        $parser->parse(); // optionnal (called automatically by SqlPaser::getParsedSql and SqlPaser::execute)
        
        // display query (with colors)
        $display_query = true;
        $sql_parsed = $parser->getParsedSql($display_query);

        // display query string (without colors)
        //pre($sql_parsed);
    }

    // execute query
    $rows = $parser->execute();

    // display an HTML table
    $parser->showResults();
}

echo '
    </div>
</body>
</html>';

//echo '<hr /><pre>table users: ' . print_r($database['users'], true) . '</pre>';
//echo '<hr /><pre>table sexes: ' . print_r($database['sexes'], true) . '</pre>';
