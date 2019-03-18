<?php

namespace BASS\Controllers\Auth;

use PDO;
use Interop\Container\ContainerInterface;

use mikehaertl\pdftk\Pdf;

class RegisterController {

    public function __construct(ContainerInterface $container)   {
        $this->db = $container->get('db');
    }

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

          if(array_key_exists('card', $emp)) {
            $lastID = $this->db->lastInsertId();
            $cardCode = rand(0000000000000001, 9999999999999999);

            $stmt = $this->db->prepare($cardSQL);
            $stmt->bindParam("userID", $lastID);
            $stmt->bindParam("cardCode", $cardCode);
            $stmt->execute();

            $this->__generateCard($cardCode, $emp['firstname'] . ' ' . $emp['lastname']);
          }
      } catch(PDOException $e) {
          echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
    }

    public function getCompanies() {
      $sql = "SELECT companyID, name FROM Company";
      $stmt = $this->db->query($sql);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
