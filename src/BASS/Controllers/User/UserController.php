<?php

namespace BASS\Controllers\User;

use PDO;
use Interop\Container\ContainerInterface;
use \Firebase\JWT\JWT;

class UserController {

    protected $auth;

    public function __construct(ContainerInterface $container)   {
        $this->db = $container->get('db');
        $this->auth = $container->get('auth');
    }

    function login($request) {
        $emp = $request->getParsedBody();
        $sql = "SELECT * FROM Login JOIN User ON User.userID = Login.userID JOIN Company ON Company.companyID = User.companyID WHERE email = '" . $emp['email'] . "'";
        $userData = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC)[0];

        if(password_verify($emp['password'], $userData['password'])) {
          $token = $this->auth->generateToken($userData['userID']);
          $userData['token'] = $token;
          $this->__updateUserToken($userData['email'], $token);

          echo json_encode($userData);
        } else {
          echo '{"error": true, "message": "Wrong username & password combination"}';
        }
    }

    function device($request) {
        $emp = $request->getParsedBody();
        $updatesql = "INSERT INTO devices (userid, notificationid, model, dversion) VALUES (:userid, :notificationid, :model, :dversion) ON DUPLICATE KEY UPDATE notificationid = :notificationid, model = :model, dversion = :dversion";
        try {
            $stmt = $this->db->prepare($updatesql);
            $stmt->bindParam("userid", $emp['userid']);
            $stmt->bindParam("notificationid", $emp['notificationid']);
            $stmt->bindParam("model", $emp['model']);
            $stmt->bindParam("dversion", $emp['dversion']);

            $stmt->execute();
            echo '{"success": true, "message": ""}';
        } catch(PDOException $e) {
            echo '{"success": false, "message": "' . $e . '"}';
        }
    }

    private function __updateUserToken($user, $token) {
      $updatesql = "UPDATE Login SET token = '" . $token . "' WHERE email = '" . $user . "';";
      $this->db->query($updatesql);
    }
}
