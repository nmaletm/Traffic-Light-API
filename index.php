<?php
require($_SERVER['DOCUMENT_ROOT'].'/api/thirdparty/pusher-php-server/lib/Pusher.php');

chdir('..');
include_once $_SERVER['DOCUMENT_ROOT']."/api/conf/database.php";
require $_SERVER['DOCUMENT_ROOT'].'/api/thirdparty/slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();


$app->get('/trafficLight/:id/', 'getTrafficLight');
$app->post('/trafficLight/:estat/', 'addTrafficLight');
$app->put('/trafficLight/:estat/', 'updateTrafficLight');
$app->delete('/trafficLight/:id/', 'deleteTrafficLight');
$app->post('/trafficLight/:id/message/', 'addTrafficLightMessage');

$app->run();

function getTrafficLight($id) {
  $sql = "select * FROM ".DB_TAULA_TRAFFICLIGHT." ";
  try {
    $db = getConnection();
    $stmt = $db->query($sql);
    $trafficlight = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;
    //echo '{"trafficlight": ' . json_encode($trafficlight) . '}';
    echo json_encode($trafficlight);
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
}

function addTrafficLight($estat) {
  $request = \Slim\Slim::getInstance()->request();
  //$trafficlight = json_decode($request->getBody());
  $sql = "INSERT INTO ".DB_TAULA_TRAFFICLIGHT." (estat) VALUES (:estat)";
  try {
    $db = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("estat", $estat);
    $stmt->execute();
    $trafficlight->id = $db->lastInsertId();
    $db = null;
    updateStatusPusher($trafficlight->id, $estat);
    echo json_encode($trafficlight);
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
}

function updateTrafficLight($id) {
  $request = \Slim\Slim::getInstance()->request();
  $body = $request->getBody();
  parse_str($body, $trafficlight);
  $sql = "UPDATE ".DB_TAULA_TRAFFICLIGHT." SET estat=:estat WHERE id=:id";
  try {
    $db = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("estat", $trafficlight['estat']);
    $stmt->bindParam("id", $id);
    $stmt->execute();
    $db = null;

    updateStatusPusher($id, $trafficlight['estat']);
    echo json_encode($trafficlight);
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
}

function deleteTrafficLight($id) {
  $sql = "DELETE FROM ".DB_TAULA_TRAFFICLIGHT." WHERE id=:id";
  try {
    $db = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("id", $id);
    $stmt->execute();
    $db = null;
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
}

function getConnection() {
  $dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $dbh;
}


function addTrafficLightMessage($id) {
  $request = \Slim\Slim::getInstance()->request();
  $body = $request->getBody();
  parse_str($body, $trafficlight);
  sendMessagePusher($id, $trafficlight['text']);
}


function updateStatusPusher($id, $estat){
  $pusher = new Pusher(PUSHER_COM_KEY, PUSHER_COM_SECRET, PUSHER_COM_APP_ID);
  $pusher->trigger('trafficLight', 'change', array('id' => $id, 'estat'=>$estat) );
}

function sendMessagePusher($id, $text){
  $pusher = new Pusher(PUSHER_COM_KEY, PUSHER_COM_SECRET, PUSHER_COM_APP_ID);
  $pusher->trigger('trafficLight', 'message', array('id' => $id, 'text'=>$text) );
}
