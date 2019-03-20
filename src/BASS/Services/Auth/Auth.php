<?php

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

    public function authenticateUser(Request $request, $user, $response) {
      $requestUser = $this->_requestUser($request);

      if(is_null($requestUser) || $user != $requestUser[0]['userID'])
        return $response->withJson([], 401);

      return true;
    }

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
