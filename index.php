<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Query Builder</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <style>
        i {
            color: #708;
            font-style: normal;
            font-weight: bold;
        }

        .query {
            color: #05a;
            background-color: #fff;
            box-shadow: 0 0 5px;
        }
    </style>
</head>

<body>
    <div class="container">
    <tbody>
        <?php
            require_once ('./QueryBuilder.php');
            $query = new QueryBuilder();


            $query->select('art.id', 'art.title')
                  ->from('article', 'art')
                  ->where('art.title = :title');
            $pdoStatement2 = $query->pdo->prepare($query);
            $pdoStatement2->execute([
                'title' => 'article_title_2', 
                ]
            );
            $user = $pdoStatement2->fetch(\PDO::FETCH_ASSOC);
            echo('<br>'.$query.'<br>');
            $query->reset();
            print_r($user);
            echo '<hr>';

            $query->select('id', 'title')
                  ->from('article');
            $pdoStatement = $query->pdo->prepare($query);
            $pdoStatement->execute();      
            $users = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
            echo('<br>'.$query.'<br>');
            $query->reset();
            print_r($users);
        ?>
        
                    </tbody>
        </table>
    </div>
</body>

</html>