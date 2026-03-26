<?php
namespace Drupal\otp_verification\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;


class OtpController {

//   public function verify(Request $request) {
//     $email = $request->request->get('email');
//     $otp = $request->request->get('otp');

//     $status = \Drupal::service('otp_verification.otp_service')
//       ->verifyOtp($email, $otp);

//     if ($status === 'verified') {
//       $user = user_load_by_mail($email);
//       if ($user) {
//         $user->activate()->save();
//         user_login_finalize($user);
//       }
//       return new JsonResponse(['status' => 'success']);
//     }
//     return new JsonResponse(['status' => $status]);
//   }

// src/Controller/OtpController.php

public function verify(Request $request) {
  $email = $request->request->get('email');
  $otp = $request->request->get('otp');

  // Get your OTP service (via dependency injection)
  $otpService = \Drupal::service('otp_verification.otp_service');
  $status = $otpService->verifyOtp($email, $otp);

  // Return proper JSON response
  return new JsonResponse([
    'status' => $status,
    'message' => $this->getStatusMessage($status)
  ]);
}
private function getStatusMessage($status) {
  $messages = [
    'verified' => 'OTP verified successfully',
    'invalid' => 'Invalid OTP code',
    'expired' => 'OTP has expired',
    'blocked' => 'Too many attempts. Try again later.'
  ];
  
  return $messages[$status] ?? 'Verification failed';
}
  public function resend(Request $request) {
    $email = $request->request->get('email');
    \Drupal::service('otp_verification.otp_service')
      ->generateAndSendOtp($email);
    return new JsonResponse(['status' => 'resent']);
  }
  public function sendOtp(Request $request) {
  $email = $request->request->get('email');
  $otp = \Drupal::service('otp_verification.otp_service')->generateAndSendOtp($email);
  return new JsonResponse(['status' => 'sent']);
} 
}

