<?php
namespace Drupal\otp_verification\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Component\Datetime\TimeInterface;

class OtpService {
  protected $database;
  protected $mailManager;
  protected $time;

  public function __construct(Connection $database, MailManagerInterface $mail_manager, TimeInterface $time) {
    $this->database = $database;
    $this->mailManager = $mail_manager;
    $this->time = $time;
  }

  /**
   * Generates and sends OTP.
   */
 // In OtpService.php
public function generateAndSendOtp($email) {
  // Delete existing OTPs
  $this->database->delete('otp_verification')
    ->condition('email', $email)
    ->execute();

  // Generate 6-digit OTP
  $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

  // Store with expiry time
  $this->database->insert('otp_verification')
    ->fields([
      'email' => $email,
      'otp_code' => $otp,
      'created' => $this->time->getRequestTime(),
      'expires' => $this->time->getRequestTime() + 3600, // 1 hour
      'verified' => 0,
      'attempts' => 0,
    ])
    ->execute();

  // Send email
  $this->sendOtpEmail($email, $otp);

  return $otp;
}

  /**
   * Sends OTP email.
   */
  protected function sendOtpEmail($email, $otp) {
    $params = [
      'subject' => 'Your OTP for Account Verification',
      'body' => [
        'Your OTP is: ' . $otp,
        'This code expires in 1 hour.',
      ],
    ];

    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $this->mailManager->mail('otp_verification', 'otp_email', $email, $langcode, $params);
  }

  /**
   * Verifies OTP.
   */
//  public function verifyOtp($email, $otp) {
//     $query = $this->database->select('otp_verification', 'o')
//       ->fields('o', ['id', 'expires', 'attempts', 'verified'])
//       ->condition('email', $email)
//       ->condition('otp_code', $otp)
//       ->range(0, 1);

//     $result = $query->execute()->fetchAssoc();

//     if (!$result) {
//       // Increment failed attempts
//       $this->database->update('otp_verification')
//         ->expression('attempts', 'attempts + 1')
//         ->condition('email', $email)
//         ->execute();
//       return 'invalid';
//     }

//     if ($result['attempts'] >= 3) {
//       return 'blocked';
//     }

//     if ($result['expires'] < $this->time->getRequestTime()) {
//       return 'expired';
//     }

//     if ($result['verified'] == 1) {
//       return 'invalid'; // Already used OTP
//     }

//     // Mark as verified
//     $this->database->update('otp_verification')
//       ->fields(['verified' => 1])
//       ->condition('id', $result['id'])
//       ->execute();

//     return 'verified';
// }


public function verifyOtp($email, $otp) {
    $query = $this->database->select('otp_verification', 'o')
      ->fields('o', ['id', 'expires', 'attempts', 'verified'])
      ->condition('email', $email)
      ->condition('otp_code', $otp)
      ->range(0, 1);

    $result = $query->execute()->fetchAssoc();

    if (!$result) {
      // Increment failed attempts
      $this->database->update('otp_verification')
        ->expression('attempts', 'attempts + 1')
        ->condition('email', $email)
        ->execute();
      return 'invalid';
    }

    if ($result['attempts'] >= 3) {
      return 'blocked';
    }

    if ($result['expires'] < $this->time->getRequestTime()) {
      return 'expired';
    }

    if ($result['verified'] == 1) {
      return 'invalid'; // Already used OTP
    }

    // Mark as verified
    $this->database->update('otp_verification')
      ->fields(['verified' => 1])
      ->condition('id', $result['id'])
      ->execute();

    // Activate the user
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['mail' => $email]);
    
    if ($user = reset($users)) {
      $user->activate();
      $user->save();
    }

    return 'verified';
}
}