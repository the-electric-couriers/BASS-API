<?php
/**
 * UserController
 *
 * Controller Class containing all methods regarding a existing user.
 *
 * @copyright  Thomas Hopstaken
 * @since      18 - 03 - 2019
 */

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

    /**
     * Method for logging in the user
     * @param  ArrayObject $request POST API object
     * @return JSON return
     */
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

    /**
     * Method for inserting user device info
     * @param  ArrayObject $request POST API object
     * @return JSON return
     */
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

    /**
     * Method for returing the user card
     * @param  ArrayObject $request POST API object
     * @param  ArrayObject $response POST response object
     * @param  ArrayObject $args request arguments object
     * @return Image/PNG return
     */
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

    /**
     * Method for validating the user card
     * @param  Integer $cardNumber card number
     * @param  Integer $user userID
     * @return JSON return
     */
    private function _validateCard($cardNumber, $user) {
      $sql = "SELECT accessCode FROM AccessCard WHERE userID = " . $user;

      try {
        $dbCard = $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN)[0];
      } catch(\PDOException $e) { }

      if(is_null($dbCard) || is_null($cardNumber) || $dbCard != $cardNumber)
        return false;

      return true;
    }

    /**
     * Method for updating the new user auth token
     * @param  Integer $user userID
     * @param  Integer $token user token
     * @return JSON return
     */
    private function __updateUserToken($user, $token) {
      $updatesql = "UPDATE Login SET token = '" . $token . "' WHERE email = '" . $user . "';";
      $this->db->query($updatesql);
    }
}
