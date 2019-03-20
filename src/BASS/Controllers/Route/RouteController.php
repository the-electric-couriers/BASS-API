<?php

namespace BASS\Controllers\Route;

use PDO;
use Interop\Container\ContainerInterface;
use \Firebase\JWT\JWT;

class RouteController {

    protected $auth;

    public function __construct(ContainerInterface $container)   {
        $this->db = $container->get('db');
        $this->auth = $container->get('auth');
    }

    function new($request, $response) {
        $emp = $request->getParsedBody();
        $insertsql = "INSERT INTO Route (routeID, shuttleID, userID, checkInTime, checkOutTime, startPositionID, endPositionID) VALUES (NULL, :shuttleID, :userID, NULL, NULL, :startID, :endID)";
        $user = $emp['userID'];
        $this->auth->authenticateUser($request, $user, $response);

        try {
            $stmt = $this->db->prepare($insertsql);
            $stmt->bindParam("shuttleID", $emp['shuttleID']);
            $stmt->bindParam("userID", $user);
            $stmt->bindParam("startID", $emp['startPosition']);
            $stmt->bindParam("endID", $emp['endPosition']);

            $stmt->execute();
            echo '{"success": true, "message": "", "routeID": ' . $this->db->lastInsertId() . '}';
        } catch(\PDOException $e) {
            $this->_returnError($e);
        }
    }

    function getRoutePoints() {
      $sql = "SELECT * FROM RoutePoint";
      echo json_encode($this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

    function checkIn($request, $response) {
      $emp = $request->getParsedBody();
      $cardNum = $emp['card'];
      $user = $emp['userID'];
      $route = $emp['routeID'];

      $this->auth->authenticateUser($request, $user, $response);

      if(!$this->_validateCard($cardNum, $user)) {
        echo '{"success": false, "message": "No valid card provided"}';
        return;
      }

      if($this->_updateStatus($route))
        echo '{"success": true, "message": ""}';
    }

    private function _validateCard($cardNumber, $user) {
      $sql = "SELECT accessCode FROM AccessCard WHERE userID = " . $user;

      try {
        $dbCard = $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN)[0];
      } catch(\PDOException $e) { }

      if(is_null($dbCard) || is_null($cardNumber) || $dbCard != $cardNumber)
        return false;

      return true;
    }

    private function _updateStatus($routeID) {
      $selectsql = "SELECT checkInTime FROM Route WHERE routeID = " . $routeID;
      $statusValue = 'checkInTime';

      try { $checkInTime = $this->db->query($selectsql)->fetchAll(PDO::FETCH_COLUMN)[0]; } catch(\PDOException $e) {
          $this->_returnError($e);
          return;
      }

      if(!is_null($checkInTime)) {
        $statusValue = 'checkOutTime';
      }

      $updatesql = "UPDATE Route SET " . $statusValue . " = CURRENT_TIMESTAMP WHERE " . $statusValue . " IS NULL AND routeID = " . $routeID;
      try { $this->db->query($updatesql); } catch(\PDOException $e) {
          $this->_returnError($e);
      }

      return true;
    }

    private function _returnError($e) {
      echo '{"success": false, "message": "' . $e->getMessage() . '"}';
    }
}
