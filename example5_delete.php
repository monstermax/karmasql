<?php

// php -S 0.0.0.0:8080 example5_delete.php

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
        ['id' => 1, 'name' => 'pierre' , 'email' => 'pierre@email.com' , 'age' => 42, 'sexe' => 'm'],
        ['id' => 2, 'name' => 'paul'   , 'email' => 'paul@email.com'   , 'age' => 49, 'sexe' => 'm'],
        ['id' => 3, 'name' => 'jacques', 'email' => 'jacques@email.com', 'age' => 53, 'sexe' => 'm'],
        ['id' => 4, 'name' => 'alain'  , 'email' => 'alain@email.com', 'age' => 59, 'sexe' => 'm'],
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


// FIRST QUERY (DELETE)
if (true) {

    echo '<h2>DELETE</h2>';

    $sql = "delete from users where sexe = 'm' and age <> 49";
    $parser = new SqlQueryParser($sql, $database);

    // display query (with colors)
    $parser->getParsedSql(true);

    // execute query
    $rows = $parser->execute();

    // display results in an HTML table
    $parser->showResults();
    
    pre(['parse_duration' => $parser->parse_duration, 'execute_duration' => $parser->execute_duration]);
}


// SECOND QUERY (SELECT)
if (true) {
    echo '<br /><hr /><br />';
    echo '<h2>SELECT</h2>';

    $sql = "select * from users order by id";
    $parser = new SqlQueryParser($sql, $database);
    $rows = $parser->execute();

    // display query (with colors)
    $parser->getParsedSql(true);
    
    // display results in an HTML table
    $parser->showResults();

    pre(['parse_duration' => $parser->parse_duration, 'execute_duration' => $parser->execute_duration]);
}



echo '
    </div>
</body>
</html>';

//echo '<hr /><pre>table users: ' . print_r($database['users'], true) . '</pre>';
//echo '<hr /><pre>table sexes: ' . print_r($database['sexes'], true) . '</pre>';
