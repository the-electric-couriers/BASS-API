<?php
/**
 * Auth
 *
 * Controller Class for handling and authenticating user login tokens.
 *
 * @copyright  Thomas Hopstaken
 * @since      18 - 03 - 2019
 */

namespace BASS\Services\Auth;

use DateTime;
use Firebase\JWT\JWT;
use Illuminate\Database\Capsule\Manager;
use Slim\Collection;
use Slim\Http\Request;
use PDO;

class Auth {

    const SUBJECT_IDENTIFIER = 'userID';

    private $db;
    private $appConfig;

    public function __construct(PDO $db, Collection $appConfig) {
      $this->db = $db;
      $this->appConfig = $appConfig;
    }

    /**
     * Method for logging in the user
     * @param  Integer $userid userID
     * @return Integer token return
     */
    public function generateToken($userid) {
      $now = new DateTime();
      $future = new DateTime("now +2 hours");

      $payload = [
          "iat" => $now->getTimeStamp(),
          "jti" => base64_encode(random_bytes(16)),
          'iss' => $this->appConfig['app']['url'],
          self::SUBJECT_IDENTIFIER => $userid,
      ];

      $secret = $this->appConfig['jwt']['secret'];
      $token = JWT::encode($payload, $secret, "HS256");

      return $token;
    }

    /**
     * Method for authenticating the user against a token
     * @param  ArrayObject $request POST API object
     * @param  Integer $user userID
     * @param  ArrayObject $response POST response object
     * @return HTTP 401 return
     * @return Boolean success return
     */
    public function authenticateUser(Request $request, $user, $response) {
      $requestUser = $this->_requestUser($request);

      if(is_null($requestUser) || $user != $requestUser[0]['userID'])
        return $response->withJson([], 401);

      return true;
    }


    /**
     * Method for requesting a user based on the token
     * @param  ArrayObject $request POST API object
     * @return JSON return
     */
    private function _requestUser(Request $request) {
      if($token = $request->getAttribute('token')) {
        $sql = "SELECT " . self::SUBJECT_IDENTIFIER . " FROM Login WHERE userID = " . $token[self::SUBJECT_IDENTIFIER];
        try {
            $stmt = $this->db->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        } catch(PDOException $e) {
            return '{"error":{"text":'. $e->getMessage() .'}}';
        }
      };
    }
}
