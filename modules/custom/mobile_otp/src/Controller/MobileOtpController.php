<?php

namespace Drupal\mobile_otp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MobileOtpController extends ControllerBase {

  protected $formBuilder;

  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Display OTP verification form.
   */
  public function verify($mobile_number, $form_id = 'default') {
    $form = $this->formBuilder->getForm('\Drupal\mobile_otp\Form\MobileOtpVerificationForm', $mobile_number, $form_id);
    
    return [
      '#theme' => 'mobile_otp_verification',
      '#form' => $form,
    ];
  }

  /**
   * API endpoint to generate OTP.
   */
  public function generateOtp(Request $request) {
    $mobile_number = $request->request->get('mobile_number');
    $form_id = $request->request->get('form_id', 'default');
    
    if (empty($mobile_number)) {
      return new JsonResponse(['error' => 'Mobile number is required'], 400);
    }

    try {
      $otp_service = \Drupal::service('mobile_otp.service');
      $otp = $otp_service->generateAndSendOtp($mobile_number, $form_id);
      
      return new JsonResponse([
        'success' => true,
        'message' => 'OTP sent successfully',
      ]);
    } catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Failed to send OTP: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * API endpoint to verify OTP.
   */
  public function verifyOtp(Request $request) {
    $mobile_number = $request->request->get('mobile_number');
    $otp_code = $request->request->get('otp_code');
    $form_id = $request->request->get('form_id', 'default');
    \Drupal::logger('otp')->notice('Verify request: number = @num, otp = @otp', [
  '@num' => $request->get('mobile_number'),
  '@otp' => $request->get('otp_code'),
]);

    if (empty($mobile_number) || empty($otp_code)) {
      return new JsonResponse(['error' => 'Mobile number and OTP code are required'], 400);
    }

    $otp_service = \Drupal::service('mobile_otp.service');
    $is_valid = $otp_service->verifyOtp($mobile_number, $otp_code, $form_id);
    
    if ($is_valid) {
      return new JsonResponse([
        'success' => true,
        'message' => 'OTP verified successfully',
      ]);
    } else {
      return new JsonResponse([
        'error' => 'Invalid OTP code',
        'attempts' => $otp_service->getAttempts($mobile_number, $form_id),
      ], 400);
    }
  }
}