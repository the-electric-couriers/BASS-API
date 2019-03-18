<?php

namespace BASS\Services\Auth;

use DateTime;
use Firebase\JWT\JWT;
use Illuminate\Database\Capsule\Manager;
use Slim\Collection;
use Slim\Http\Request;
use PDO;

class Auth {

    const SUBJECT_IDENTIFIER = 'email';

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
}
