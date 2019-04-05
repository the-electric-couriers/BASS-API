<?php

namespace BASS\Controllers\User;

use PDO;
use Interop\Container\ContainerInterface;
use \Firebase\JWT\JWT;
use Imagick;

class UserController {

    protected $auth;

    public function __construct(ContainerInterface $container)   {
        $this->db = $container->get('db');
        $this->auth = $container->get('auth');
    }

    function login($request) {
        $emp = $request->getParsedBody();
        $sql = "SELECT * FROM Login JOIN User ON User.userID = Login.userID JOIN Company ON Company.companyID = User.companyID JOIN AccessCard ON AccessCard.userID = User.userID WHERE email = '" . $emp['email'] . "'";
        $userData = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC)[0];

        if(!password_verify($emp['password'], $userData['password'])) {
          echo '{"error": true, "message": "Wrong username & password combination"}';
          return;
        }

        $token = $this->auth->generateToken($userData['userID']);
        $userData['token'] = $token;
        $this->__updateUserToken($userData['email'], $token);

        echo json_encode($userData);
    }

    function device($request) {
        $emp = $request->getParsedBody();
        $updatesql = "INSERT INTO Device (userid, notificationid, model, dversion) VALUES (:userid, :notificationid, :model, :dversion) ON DUPLICATE KEY UPDATE notificationid = :notificationid, model = :model, dversion = :dversion";
        try {
            $stmt = $this->db->prepare($updatesql);
            $stmt->bindParam("userid", $emp['userID']);
            $stmt->bindParam("notificationid", $emp['notificationID']);
            $stmt->bindParam("model", $emp['model']);
            $stmt->bindParam("dversion", $emp['dversion']);

            $stmt->execute();
            echo '{"success": true, "message": ""}';
        } catch(PDOException $e) {
            echo '{"success": false, "message": "' . $e . '"}';
        }
    }

    function card($request, $response, $args) {
      $cardNum = $args['card'];
      $user = $args['userID'];
      $userName = $args['userName'];

      $this->auth->authenticateUser($request, $user, $response);

      if(!$this->_validateCard($cardNum, $user)) {
        echo '{"success": false, "message": "No valid card provided"}';
        return;
      }

      $myurl = '../public/img/cards/' . $userName . '.pdf';
      $image = new Imagick($myurl);
      $image->setResolution( 10, 10 );
      $image->setImageFormat("png");

      header('Content-Type: image/' . $image->getImageFormat());
      echo $image->getimageblob();

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

    private function __updateUserToken($user, $token) {
      $updatesql = "UPDATE Login SET token = '" . $token . "' WHERE email = '" . $user . "';";
      $this->db->query($updatesql);
    }
}
