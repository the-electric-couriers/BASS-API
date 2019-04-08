<?php
/**
 * RouteController
 *
 * Controller Class containing all methods regarding the route logic.
 *
 * @copyright  Thomas Hopstaken
 * @since      20 - 03 - 2019
 */

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

    /**
     * Method for registering new route
     * @param  ArrayObject $request POST API object
     * @param  ArrayObject $response POST response object
     * @return JSON return
     */
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

    /**
     * Method for returning list of all route points
     * @return JSON return
     */
    function getRoutePoints() {
      $sql = "SELECT * FROM RoutePoint";
      echo json_encode($this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Method for checking in/out
     * @param  ArrayObject $request POST API object
     * @param  ArrayObject $response POST response object
     * @return JSON return
     */
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

    /**
     * Method for returning list of user specific route history
     * @param  ArrayObject $request POST API object
     * @param  ArrayObject $response POST response object
     * @return JSON return
     */
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


    /**
     * Method for validating a card bound to a user
     * @param  Integer $cardNumber card number
     * @param  Integer $user userID
     * @return JSON return
     */
    private function _validateCard($cardNumber, $user) {
      $sql = "SELECT accessCode FROM AccessCard WHERE userID = " . $user;

      try {
        $dbCard = $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN)[0];
      } catch(\PDOException $e) { }

      if(is_null($dbCard) || is_null($cardNumber) || trim($dbCard, " ") != trim($cardNumber, " "))
        return false;

      return true;
    }

    /**
     * Method for updating route status
     * @param  Integer $routeID routeID
     * @return JSON return
     */
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

    /**
     * Method for returning error
     * @param  Error $e error object
     * @return JSON return
     */
    private function _returnError($e) {
      echo '{"success": false, "message": "' . $e->getMessage() . '"}';
    }
}
