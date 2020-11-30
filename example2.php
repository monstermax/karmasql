<?php

// php -S 0.0.0.0:8080 example2.php

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



$sql = "select id, ucfirst(name) as prenom, sexe, age, count(*) as nb_users
, avg(age) as ages_avg
, sum(age) as ages_sum
, min(age) as age_min
, max(age) as age_max
, get_class(\$this) as class
-- , file_get_contents('https://min-api.cryptocompare.com/data/price?fsym=BTC&tsyms=EUR') as prix_btc
, s.name as sexe_fr
, s_en.name as sexe_en
from users u
inner join sexes s on s.code = u.sexe and s.langue = 'fr' 
inner join sexes s_en on s_en.code = u.sexe and s_en.langue = 'en'
where 1
-- and age < 53
-- and sexe = 'm'
group by sexe
order by sexe
--  limit 1";
    

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

    //#### quick usage

    //$rows = (new SqlQueryParser($sql, $database))->execute();
    //pre($rows, 1);


    //#### detailed usage

    $parser = new SqlQueryParser($sql, $database);
    
    $display_query = true;
    if ($display_query) {
        // display query (with colors)
        $display_query = true;
        $sql_parsed = $parser->getParsedSql($display_query);
    }

    // execute query
    $rows = $parser->execute();

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

if (isset($parser)) {
    echo '<hr /><pre>DATABASE: ' . print_r($database, true) . '</pre>';
}
