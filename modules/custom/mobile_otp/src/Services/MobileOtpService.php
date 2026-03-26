<?php

namespace Drupal\mobile_otp\Services;

use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\TimeInterface;

class MobileOtpService {

  protected $database;
  protected $time;

  public function __construct(Connection $database, TimeInterface $time) {
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * Generates and stores OTP for mobile verification.
   */
  public function generateAndSendOtp($mobile_number, $form_id = 'default', $expiry = 300) {
    // Clean mobile number
    $mobile_number = $this->cleanMobileNumber($mobile_number);
    
    // Delete any existing OTPs for this mobile number and form
    $this->database->delete('mobile_otp_verification')
      ->condition('mobile_number', $mobile_number)
      ->condition('form_id', $form_id)
      ->execute();

    // Generate 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Store in database
    $this->database->insert('mobile_otp_verification')
      ->fields([
        'mobile_number' => $mobile_number,
        'otp_code' => $otp,
        'form_id' => $form_id,
        'created' => $this->time->getRequestTime(),
        'expires' => $this->time->getRequestTime() + $expiry,
        'verified' => 0,
        'attempts' => 0,
      ])
      ->execute();

    // Send OTP via SMS
    $this->sendOtpSms($mobile_number, $otp, $form_id);

    return $otp;
  }

  /**
   * Cleans and validates mobile number.
   */
  protected function cleanMobileNumber($mobile_number) {
    // Remove all non-digit characters except plus sign
    $cleaned = preg_replace('/[^0-9+]/', '', $mobile_number);
    
    // Add country code if missing (example: +91 for India)
    if (substr($cleaned, 0, 1) !== '+') {
      $config = \Drupal::config('mobile_otp.settings');
      $default_country_code = $config->get('default_country_code') ?? '+91';
      $cleaned = $default_country_code . $cleaned;
    }
    
    return $cleaned;
  }

  /**
   * Sends OTP via SMS.
   */
  protected function sendOtpSms($mobile_number, $otp, $form_id) {
    $config = \Drupal::config('mobile_otp.settings');
    $sms_gateway = $config->get('sms_gateway') ?? 'log';
    
    $message = $config->get('sms_template') ?? 
      "Your verification code is: @otp. Valid for 5 minutes.";
    $message = str_replace('@otp', $otp, $message);
    $message = str_replace('@form_id', $form_id, $message);

    switch ($sms_gateway) {
      case 'twilio':
        $this->sendViaTwilio($mobile_number, $message);
        break;
      case 'log':
      default:
        // Log to watchdog for testing
        // \Drupal::logger('mobile_otp')->notice('OTP @otp sent to @number for form @form_id. Message: @message', [
        //   '@otp' => $otp,
        //   '@number' => $mobile_number,
        //   '@form_id' => $form_id,
        //   '@message' => $message,
        // ]);
        break;
    }
  }

  /**
   * Send SMS via Twilio (example implementation).
   */
  protected function sendViaTwilio($mobile_number, $message) {
    $config = \Drupal::config('mobile_otp.settings');
    $account_sid = $config->get('twilio_account_sid');
    $auth_token = $config->get('twilio_auth_token');
    $twilio_number = $config->get('twilio_phone_number');

    if ($account_sid && $auth_token && $twilio_number) {
      try {
        $client = new \Twilio\Rest\Client($account_sid, $auth_token);
        $client->messages->create(
          $mobile_number,
          [
            'from' => $twilio_number,
            'body' => $message
          ]
        );
      } catch (\Exception $e) {
        \Drupal::logger('mobile_otp')->error('Twilio SMS sending failed: @error', 
          ['@error' => $e->getMessage()]);
      }
    }
  }

  /**
   * Verifies OTP for mobile number.
   */
  public function verifyOtp($mobile_number, $otp, $form_id = 'default') {
    $mobile_number = $this->cleanMobileNumber($mobile_number);
    
    $query = $this->database->select('mobile_otp_verification', 'm')
      ->fields('m')
      ->condition('mobile_number', $mobile_number)
      ->condition('otp_code', $otp)
      ->condition('form_id', $form_id)
      ->condition('expires', $this->time->getRequestTime(), '>=')
      ->condition('verified', 0)
      ->range(0, 1);

    $result = $query->execute()->fetchAssoc();

    if ($result) {
      // Mark as verified
      $this->database->update('mobile_otp_verification')
        ->fields(['verified' => 1])
        ->condition('id', $result['id'])
        ->execute();
      return TRUE;
    }

    // Increment attempts
    $this->database->update('mobile_otp_verification')
      ->expression('attempts', 'attempts + 1')
      ->condition('mobile_number', $mobile_number)
      ->condition('form_id', $form_id)
      ->execute();

    return FALSE;
  }

  /**
   * Checks if mobile number has a pending OTP verification.
   */
  public function hasPendingVerification($mobile_number, $form_id = 'default') {
    $mobile_number = $this->cleanMobileNumber($mobile_number);
    
    $count = $this->database->select('mobile_otp_verification', 'm')
      ->condition('mobile_number', $mobile_number)
      ->condition('form_id', $form_id)
      ->condition('expires', $this->time->getRequestTime(), '>=')
      ->condition('verified', 0)
      ->countQuery()
      ->execute()
      ->fetchField();

    return $count > 0;
  }

  /**
   * Gets the number of attempts for a mobile number.
   */
  public function getAttempts($mobile_number, $form_id = 'default') {
    $mobile_number = $this->cleanMobileNumber($mobile_number);
    
    $query = $this->database->select('mobile_otp_verification', 'm')
      ->fields('m', ['attempts'])
      ->condition('mobile_number', $mobile_number)
      ->condition('form_id', $form_id)
      ->orderBy('created', 'DESC')
      ->range(0, 1);

    return $query->execute()->fetchField() ?? 0;
  }
}