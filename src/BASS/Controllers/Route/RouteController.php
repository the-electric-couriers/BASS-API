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
        $this->auth->authenticateUser($request, $user);

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

    function checkIn($request) {
      $route = $request->getParsedBody()['routeID'];
      $updatesql = "UPDATE Route SET checkInTime = CURRENT_TIMESTAMP WHERE routeID = " . $route;
      try { $this->db->query($updatesql); } catch(\PDOException $e) {
          $this->_returnError($e);
      }
    }

    function checkOut($request) {
      $route = $request->getParsedBody()['routeID'];
      $updatesql = "UPDATE Route SET checkOutTime = CURRENT_TIMESTAMP WHERE routeID = " . $route;
      try { $this->db->query($updatesql); } catch(\PDOException $e) {
          $this->_returnError($e);
      }
    }

    private function _returnError($e) {
      echo '{"success": false, "message": "' . $e->getMessage() . '"}';
    }
}
