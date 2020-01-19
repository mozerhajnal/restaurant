<?php

$routes = [
    'GET' =>[
                '/'             => 'homeHandler',
        '/admin/uj-etel'        => 'getAllDishesHandler',
        '/admin/etelek'         => 'editSingleDishHandler',
        '/admin/uj-etel-tipus'  => 'getAllDishTypesHandler',
        '/admin/etel-tipusok'   => 'editSingleDishTypeHandler'
    ],
    'POST' =>[
        '/dishes'           => 'createDishHandler',
        '/dishes/delete'    => 'deleteDishHandler',
        '/dishes/edit'      => 'updateDishHandler',
        '/dishTypes'        => 'createDishTypeHandler',
        '/dishTypes/delete' => 'deleteDishTypeHandler',
        '/dishTypes/edit'   => 'updateDishTypeHandler'
    ]
];

$url = $_SERVER['REQUEST_URI'];
$path = parse_url($url)['path'];
$method = $_SERVER['REQUEST_METHOD'];

$handler = $routes[$method][$path]??'';
if($handler && is_callable($handler)){
    $conn = new mysqli('localhost','root','','restaurant',3306);
    $conn -> set_charset('utf8');
    $handler($conn,$_GET,$_POST);
}else{
    echo '404';
}

function homeHandler(mysqli $conn, $query,$body)
{
    $result = $conn ->query('SELECT * FROM dishTypes');
    $dishTypes = [];
    while($data = $result->fetch_assoc()){
        $dishTypes [] = $data;
    }

    $firstFiveDish = [];
    foreach($dishTypes as $dishType){
        $queryString2 = "SELECT * FROM dishes WHERE isActive=true and dishTypeId=? ORDER by id LIMIT 5";
        $statement2 = $conn->prepare($queryString2);
        $statement2->bind_param('i',$dishType['id']);
        $statement2-> execute();
    
        $result2 = $statement2->get_result();
        while ($data = $result2->fetch_assoc()) {
            $firstFiveDish[] = $data;
        }
    
    
    }

    $result2 = $conn ->query('SELECT * FROM dishes WHERE isActive=true order by dishTypeId, name');
    $dishes = [];
    while($data = $result2->fetch_assoc()){
        $dishes [] = $data;
    }

    require 'index.phtml';
}

function getAllDishesHandler(mysqli $conn, $query,$body)
{
    $dishes = getAllEntities($conn,'dishes');
    $dishTypes = getAllEntities($conn,'dishTypes');
    require 'admin-food.phtml';
}

function editSingleDishHandler(mysqli $conn, $query,$body)
{
    $dishes = getAllEntities($conn,'dishes');
    $dishTypes = getAllEntities($conn,'dishTypes');
 
    $queryString ="SELECT * FROM dishes WHERE id = ?";
    $statement = $conn->prepare($queryString);

    $id = $query['id'];
    $statement->bind_param('s',$id);
    $statement-> execute();

    $dish = $statement->get_result()->fetch_assoc();

    require 'admin-food.phtml';
}

function getAllDishTypesHandler(mysqli $conn, $query,$body)
{
    $dishTypes = getAllEntities($conn,'dishTypes');
    require 'admin-food-type.phtml';
}

function editSingleDishTypeHandler(mysqli $conn, $query,$body)
{
    $dishTypes = getAllEntities($conn,'dishTypes');

    $queryString ="SELECT * FROM dishTypes WHERE id = ?";
    $statement = $conn->prepare($queryString);

    $id = $query['id'];
    $statement->bind_param('s',$id);
    $statement-> execute();

    $dishType = $statement->get_result()->fetch_assoc();

    require 'admin-food-type.phtml';

}

function createDishHandler(mysqli $conn, $query,$body)
{
     $name = $body['name'];
     $description = $body['description'];
     $price = $body['price'];
     $typeId = $body['type'];
     isset($body['isActive'])?$isActive=1:$isActive=0;  
     
     $queryString = "INSERT INTO `dishes`( `name`, `description`, `price`, `isActive`, `dishTypeId`) VALUES (?,?,?,?,?)";
     $statement = $conn->prepare($queryString);

     $statement->bind_param('ssiii', $name, $description, $price,$isActive,$typeId);
     $isSuccess = $statement->execute();

     if(!$isSuccess){
         echo 'Nem sikerült';
     }

     header('Location:/admin/uj-etel');
}

function deleteDishHandler(mysqli $conn, $query,$body)
{
    $queryString = "DELETE FROM dishes where id=?";

    $statement = $conn->prepare($queryString);

    $id=$query['id'];
    $statement->bind_param('s',$id);

    $statement->execute();

    if(!$statement->affected_rows){
        echo 'hiba';
        exit;
    }
    header('Location: /admin/uj-etel');
}

function updateDishHandler(mysqli $conn, $query,$body)
{
    $queryString = "UPDATE `dishes` SET `name`= ?,`description`= ?,`price`= ?,`isActive`= ?,`dishTypeId`= ? WHERE `id`= ?";
    $statement = $conn -> prepare($queryString);

    $name = $body['name'];
    $description = $body['description'];
    $price = $body['price'];
    isset($body['isActive'])?$isActive=1:$isActive=0;
    $typeId = $body['type'];
    $id = $query['id'];

    $statement->bind_param('ssiiii',$name,$description,$price,$isActive,$typeId,$id);

    $statement->execute();

    if(!$statement->affected_rows){
        echo 'hiba';
        exit;
    }

    header('Location: /admin/uj-etel');

}

function createDishTypeHandler(mysqli $conn, $query,$body)
{
    $name = $body['name'];
    $description = $body['description']; 
    
    $queryString = "INSERT INTO `dishTypes`(`name`, `description`) VALUES (?,?)";
    $statement = $conn->prepare($queryString);

    $statement->bind_param('ss', $name, $description);
    $isSuccess = $statement->execute();

    if(!$isSuccess){
        echo 'Nem sikerült';
    }

    header('Location:/admin/uj-etel-tipus');

}

function deleteDishTypeHandler(mysqli $conn, $query,$body)
{
    $queryString = "DELETE FROM dishTypes where id=?";

    $statement = $conn->prepare($queryString);

    $id=$query['id'];
    $statement->bind_param('s',$id);

    $statement->execute();

    if(!$statement->affected_rows){
        echo 'hiba';
        exit;
    }
    header('Location: /admin/uj-etel-tipus');

}

function updateDishTypeHandler(mysqli $conn, $query,$body)
{
    $queryString = "UPDATE `dishtypes` SET `name`= ?,`description`= ? WHERE id = ?";
    $statement = $conn -> prepare($queryString);

    $name = $body['name'];
    $description = $body['description'];
    $id = $query['id'];

    $statement->bind_param('ssi',$name,$description,$id);

    $statement->execute();

    if(!$statement->affected_rows){
        echo 'hiba';
        exit;
    }

    header('Location: /admin/uj-etel-tipus');

}

function getAllEntities(mysqli $conn, string $resourceName)
{
 $result = $conn ->query('SELECT * FROM '.$resourceName);
 $ret = [];
 while($data = $result->fetch_assoc()){
     $ret [] = $data;
 }
 return $ret;
}



