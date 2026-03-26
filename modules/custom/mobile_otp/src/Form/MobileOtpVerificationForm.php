<?php

namespace Drupal\mobile_otp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class MobileOtpVerificationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mobile_otp_verification_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mobile_number = NULL, $form_id = 'default') {
    $form['#prefix'] = '<div id="mobile-otp-verification-form">';
    $form['#suffix'] = '</div>';

    $form['mobile_number'] = [
      '#type' => 'hidden',
      '#value' => $mobile_number,
    ];

    $form['form_id'] = [
      '#type' => 'hidden',
      '#value' => $form_id,
    ];

    $form['otp_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Verification Code'),
      '#description' => $this->t('Enter the 6-digit code sent to your mobile.'),
      '#size' => 6,
      '#maxlength' => 6,
      '#attributes' => ['placeholder' => '000000'],
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['verify'] = [
      '#type' => 'submit',
      '#value' => $this->t('Verify'),
      '#ajax' => [
        'callback' => '::ajaxVerifyCallback',
        'wrapper' => 'mobile-otp-verification-form',
        'effect' => 'fade',
      ],
    ];

    $form['actions']['resend'] = [
      '#type' => 'submit',
      '#value' => $this->t('Resend OTP'),
      '#submit' => ['::resendOtp'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::ajaxVerifyCallback',
        'wrapper' => 'mobile-otp-verification-form',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mobile_number = $form_state->getValue('mobile_number');
    $otp_code = $form_state->getValue('otp_code');
    $form_id = $form_state->getValue('form_id');

    $otp_service = \Drupal::service('mobile_otp.service');
    
    // Check if OTP is valid
    if (!$otp_service->verifyOtp($mobile_number, $otp_code, $form_id)) {
      $attempts = $otp_service->getAttempts($mobile_number, $form_id);
      $max_attempts = \Drupal::config('mobile_otp.settings')->get('max_attempts') ?? 3;
      
      if ($attempts >= $max_attempts) {
        $form_state->setErrorByName('otp_code', $this->t('Too many failed attempts. Please request a new OTP.'));
      } else {
        $form_state->setErrorByName('otp_code', $this->t('Invalid verification code.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mobile_number = $form_state->getValue('mobile_number');
    $form_id = $form_state->getValue('form_id');
    
    // OTP verified successfully
    \Drupal::messenger()->addStatus($this->t('Mobile number verified successfully.'));
    
    // You can trigger custom actions here based on form_id
    // For example: complete user registration, process form submission, etc.
  }

  /**
   * Resend OTP submit handler.
   */
  public function resendOtp(array &$form, FormStateInterface $form_state) {
    $mobile_number = $form_state->getValue('mobile_number');
    $form_id = $form_state->getValue('form_id');
    
    $otp_service = \Drupal::service('mobile_otp.service');
    $otp_service->generateAndSendOtp($mobile_number, $form_id);
    
    \Drupal::messenger()->addStatus($this->t('A new OTP has been sent to your mobile.'));
  }

  /**
   * AJAX callback for the form.
   */
  public function ajaxVerifyCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }
}