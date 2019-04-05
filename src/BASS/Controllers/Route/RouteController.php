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
        $insertsql = "INSERT INTO Route (routeID, shuttleID, userID, checkInTime, checkOutTime, startPositionID, endPositionID) VALUES (NULL, 1, :userID, NULL, NULL, :startID, :endID)";
        $user = $emp['userID'];
        $this->auth->authenticateUser($request, $user, $response);

        try {
            $stmt = $this->db->prepare($insertsql);
            // $stmt->bindParam("shuttleID", $emp['shuttleID']);
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

    function routeHistory($request, $response) {
      $emp = $request->getParsedBody();
      $user = $emp['userID'];

      $this->auth->authenticateUser($request, $user, $response);

      $routeHistorySQL = "SELECT * FROM Route WHERE userID = " . $user;
      $routePointsSQL = "SELECT routePointID, pointName FROM RoutePoint";

      $routeHistory = $this->db->query($routeHistorySQL)->fetchAll(PDO::FETCH_ASSOC);
      $routePoints = $this->db->query($routePointsSQL)->fetchAll(PDO::FETCH_ASSOC);
      $routes = array();

      foreach ($routeHistory as $key => $value) {
        if($value['checkInTime'] == "") {
          continue;
        }

        $checkInRoute = array();
        $checkInRoute['status'] = 0;
        $checkInRoute['type'] = 1;
        $checkInRoute['timestamp'] = $value['checkInTime'];
        $checkInRoute['date'] = explode(" ", $value['checkInTime'])[0];
        $checkInRoute['time'] = explode(" ", $value['checkInTime'])[1];

        foreach ($routePoints as $key => $routePointsValue) {
          if($routePointsValue['routePointID'] == $value['startPositionID']) {
            $checkInRoute['routePointName'] = $routePointsValue['pointName'];
          }
        }

        if($checkInRoute['routePointName'] == "Station Roosendaal") {
          $checkInRoute['type'] = 0;
        }

        array_push($routes, $checkInRoute);

        if($value['checkOutTime'] == "") {
          continue;
        }

        $checkOutRoute = array();
        $checkOutRoute['status'] = 1;
        $checkOutRoute['type'] = 1;
        $checkOutRoute['timestamp'] = $value['checkOutTime'];
        $checkOutRoute['date'] = explode(" ", $value['checkOutTime'])[0];
        $checkOutRoute['time'] = explode(" ", $value['checkOutTime'])[1];

        foreach ($routePoints as $key => $routePointsValue) {
          if($routePointsValue['routePointID'] == $value['endPositionID']) {
            $checkOutRoute['routePointName'] = $routePointsValue['pointName'];
          }
        }

        if($checkOutRoute['routePointName'] == "Station Roosendaal") {
          $checkOutRoute['type'] = 0;
        }

        array_push($routes, $checkOutRoute);
      }

      echo json_encode($routes);
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
