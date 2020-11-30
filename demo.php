<?php

// php -S 0.0.0.0:8080 demo.php

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




$sql = isset($_GET['sql']) ? $_GET['sql'] : '';

if (empty($sql)) {

    $default_sql = "select *
from users
where age >= 30
order by age, name desc";
    
    $form_sql = $default_sql;

} else {
    $form_sql = $sql;
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

            <form>
                <textarea name="sql" class="form-control" style="width:100%; height:400px;">' . htmlspecialchars($form_sql) . '</textarea>
                <br />
                <input type="submit" value="Exécuter" class="btn btn-primary" />
            </form>
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



    if (true) {
        // SECOND QUERY
        echo '<br /><hr /><br />';
        echo '<h2>SELECT</h2>';

        $sql = "select *, @demo from users order by id";
        $parser = new SqlQueryParser($sql, $database);
        $rows = $parser->execute();

        // display query (with colors)
        $parser->getParsedSql(true);
        
        // display results in an HTML table
        $parser->showResults();

        pre(['parse_duration' => $parser->parse_duration, 'execute_duration' => $parser->execute_duration]);
    }



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
