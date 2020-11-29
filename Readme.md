
# SQL Engine made in PHP

### Parse and execute SQL query against PHP arrays
 
 

No dependencies.


```php
use \SqlParser\SqlParser;


$database = [
    'users' => [
        ['id' => 1, 'name' => 'pierre', 'age' => 42, 'sexe' => 'm'],
        ['id' => 2, 'name' => 'paul', 'age' => 49, 'sexe' => 'm'],
        ['id' => 3, 'name' => 'jacques', 'age' => 53, 'sexe' => 'm'],
        ['id' => 4, 'name' => 'alain', 'age' => 59, 'sexe' => 'm'],
        ['id' => 5, 'name' => 'elodie', 'age' => 38, 'sexe' => 'f'],
        ['id' => 6, 'name' => 'marion', 'age' => 42, 'sexe' => 'f'],
    ],
    'sexes' => [
        ['langue' => 'fr', 'code' => 'f', 'name' => 'Femme'],
        ['langue' => 'fr', 'code' => 'm', 'name' => 'Homme'],
        ['langue' => 'en', 'code' => 'f', 'name' => 'Woman'],
        ['langue' => 'en', 'code' => 'm', 'name' => 'Man'],
    ],
];


$sql = "select * from users order by age, name desc";

$parser = new SqlParser($sql, $database);
$parser->parse();

```

### Result
|id|name|age|sexe|
|--|:--:|:-:|:--:|
|5|elodie|38|f|
|1|pierre|42|m|
|6|marion|42|f|
|2|paul|49|m|
|3|jacques|53|m|
|4|alain|59|m|





## TODO next

#### Features

- subqueries
- variables SQL @myvar
- distinct
- jointures (à améliorer)
- order by grouped field or by alias
- order by 1
- other actions (update, delete, insert, set, desc)




#### Data Structure ( a revoir)

  - SqlParser
    - SqlAction
        - SqlActionPart & SqlActionPart*
          - SqlExpr & SqlParenthese
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


