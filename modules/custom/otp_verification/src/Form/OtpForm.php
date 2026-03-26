<?php
namespace Drupal\otp_verification\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class OtpForm extends FormBase {

  public function getFormId() {
    return 'otp_verification_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('otp_verification');
    $email = $tempstore->get('pending_email');

    if (empty($email)) {
      // Handle case where email isn't in tempstore (direct access to page)
      return [
        '#markup' => $this->t('Invalid access. Please start the verification process again.'),
      ];
    }

    $form['email'] = [
      '#type' => 'hidden',
      '#value' => $email,
    ];

    $form['otp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter OTP'),
      '#required' => TRUE,
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Verify OTP'),
    ];

    $form['resend'] = [
      '#type' => 'submit',
      '#value' => $this->t('Resend OTP'),
      '#submit' => ['::resendOtp'],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $otp = $form_state->getValue('otp');

    $status = \Drupal::service('otp_verification.otp_service')
      ->verifyOtp($email, $otp);

    if ($status === 'verified') {
      $user = user_load_by_mail($email);
      if ($user) {
        $user->activate()->save();
        user_login_finalize($user);
        $this->messenger()->addStatus($this->t('Your account has been verified.'));
        // $form_state->setRedirect('<front>');
        $form_state->setRedirect('role_based_registration.profile_completion', [
      'user' => $this->user->id()
    ]);
      }
    }
    elseif ($status === 'invalid') {
      $this->messenger()->addError($this->t('Invalid OTP. Please try again.'));
    }
    elseif ($status === 'expired') {
      $this->messenger()->addError($this->t('OTP expired. Please resend.'));
    }
    elseif ($status === 'blocked') {
      $this->messenger()->addError($this->t('Too many failed attempts. Please try again later.'));
    }
    
  }

  public function resendOtp(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    \Drupal::service('otp_verification.otp_service')
      ->generateAndSendOtp($email);
    $this->messenger()->addStatus($this->t('A new OTP has been sent to your email.'));
  }
}