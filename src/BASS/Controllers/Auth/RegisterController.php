<?php
/**
 * RegisterController
 *
 * Controller Class containing all methods for registering a new user.
 *
 * @copyright  Thomas Hopstaken
 * @since      18 - 03 - 2019
 */

namespace BASS\Controllers\Auth;

use PDO;
use Interop\Container\ContainerInterface;

use mikehaertl\pdftk\Pdf;

class RegisterController {

    public function __construct(ContainerInterface $container)   {
        $this->db = $container->get('db');
    }

    /**
     * Method for registering new user
     * @param  ArrayObject $request POST API object
     * @return JSON return
     */
    public function register($request) {
      $emp = $request->getParsedBody();
      $userSQL = "INSERT INTO User (userID, firstname, lastname, companyID) VALUES (NULL, :firstname, :lastname, :companyID)";
      $cardSQL = "INSERT INTO AccessCard (cardID, userID, accessCode, active) VALUES (NULL, :userID, :cardCode, '1')";

      try {
          $stmt = $this->db->prepare($userSQL);
          $stmt->bindParam("firstname", $emp['firstname']);
          $stmt->bindParam("lastname", $emp['lastname']);
          $stmt->bindParam("companyID", $emp['company']);
          $stmt->execute();

          $lastID = $this->db->lastInsertId();
          $this->__createLoginUser($lastID, $emp['email'], $emp['password']);

          if(array_key_exists('card', $emp)) {
            $cardCode = rand(0000000000000001, 9999999999999999);

            $this->__createUserCard($lastID, $cardCode);
            $this->__generateCard($cardCode, $emp['firstname'] . ' ' . $emp['lastname']);
          }
      } catch(PDOException $e) {
          echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
    }

    /**
     * Method for returning list of all companies
     * @return JSON return object list with companies
     */
    public function getCompanies() {
      $sql = "SELECT companyID, name FROM Company";
      $stmt = $this->db->query($sql);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Method for creating a new card
     * @param  Integer $userID ID of user
     * @param  Integer $cardCode unique card ID
     * @return JSON return
     */
    private function __createUserCard($userID, $cardCode) {
      $cardSQL = "INSERT INTO AccessCard (cardID, userID, accessCode, active) VALUES (NULL, :userID, :cardCode, '1')";

      $stmt = $this->db->prepare($cardSQL);
      $stmt->bindParam("userID", $userID);
      $stmt->bindParam("cardCode", $cardCode);
      $stmt->execute();
    }

    /**
     * Method for creating a new login user
     * @param  Integer $userID ID of user
     * @param  String $email user email
     * @param  String $password user password
     * @return JSON return
     */
    private function __createLoginUser($userID, $email, $password) {
      $loginSQL = "INSERT INTO Login (userID, email, password, token, admin, lastLogin) VALUES (:userID, :email, :password, NULL, 0, CURRENT_TIMESTAMP)";
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

      $stmt = $this->db->prepare($loginSQL);
      $stmt->bindParam("userID", $userID);
      $stmt->bindParam("email", $email);
      $stmt->bindParam("password", $hashedPassword);
      $stmt->execute();
    }

    /**
     * Method for generating a custom card PDF
     * @param  Integer $cardCode unique card ID
     * @param  String $user full user name
     * @return PDF return newly created PDF
     */
    private function __generateCard($cardCode, $user) {
      $pdf = new Pdf('img/cards/basecard.pdf');
      $pdf->fillForm([
        'untitled1' => chunk_split($cardCode, 4, ' '),
        'untitled2' => $user,
      ])
      ->needAppearances()
      ->saveAs('img/cards/' . $user . '.pdf');

      echo "<iframe src=\"http://borchwerfshuttle.tk/public/img/cards/" . $user . ".pdf\" width=\"100%\" style=\"height:100%\"></iframe>";
    }
}
