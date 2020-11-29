
# SQL Engine made in PHP

### Parse and execute SQL query against PHP arrays
 
 

No dependencies.


```
use \SqlParser\SqlParser;


$database = [
    'users' => [
        ['id' => 1, 'name' => 'pierre', 'email' => 'pierre@email.com', 'age' => 42, 'sexe' => 'm'],
        ['id' => 2, 'name' => 'paul', 'email' => 'paul@email.com', 'age' => 49, 'sexe' => 'm'],
        ['id' => 3, 'name' => 'jacques', 'email' => 'jacques@email.com', 'age' => 53, 'sexe' => 'm'],
        ['id' => 4, 'name' => 'alain', 'email' => 'jacques@email.com', 'age' => 59, 'sexe' => 'm'],
        ['id' => 5, 'name' => 'elodie', 'email' => 'elodie@email.com', 'age' => 38, 'sexe' => 'f'],
    ],
    'sexes' => [
        ['langue' => 'fr', 'code' => 'f', 'name' => 'Femme'],
        ['langue' => 'fr', 'code' => 'm', 'name' => 'Homme'],
        ['langue' => 'en', 'code' => 'f', 'name' => 'Woman'],
        ['langue' => 'en', 'code' => 'm', 'name' => 'Man'],
    ],
];


$sql = "select * from users";

$parser = new SqlParser($sql, $database);
$parser->parse();

```


```

TODO:
=======
- subqueries
- variables SQL @myvar
- distinct
- jointures (à améliorer)
- order by grouped field or by alias
- order by 1




Structure ( a revoir)
=======================
  - SqlParser
    - SqlAction
        - SqlActionPart
          - SqlExpr & SQlParenthese
            - SqlField
            - SqlWord
            - SqlString
            - SqlNumeric
            - SqlJoker
            - SqlOperator
            - SqlComparator
            - SqlSpace
            - SqlComma
            - SqlComment

```
