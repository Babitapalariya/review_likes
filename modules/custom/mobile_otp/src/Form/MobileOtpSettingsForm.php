<?php

namespace Drupal\mobile_otp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MobileOtpSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mobile_otp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mobile_otp_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mobile_otp.settings');

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General Settings'),
    ];

    $form['general']['default_country_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Country Code'),
      '#default_value' => $config->get('default_country_code') ?? '+91',
      '#description' => $this->t('Default country code to prepend to mobile numbers (e.g., +91 for India)'),
      '#required' => TRUE,
    ];

    $form['general']['otp_expiry'] = [
      '#type' => 'number',
      '#title' => $this->t('OTP Expiry Time (seconds)'),
      '#default_value' => $config->get('otp_expiry') ?? 300,
      '#description' => $this->t('Time in seconds before OTP expires (300 = 5 minutes)'),
      '#required' => TRUE,
      '#min' => 60,
    ];

    $form['general']['max_attempts'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Verification Attempts'),
      '#default_value' => $config->get('max_attempts') ?? 3,
      '#description' => $this->t('Maximum number of failed OTP attempts allowed'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 10,
    ];

    $form['sms'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SMS Gateway Settings'),
    ];

    $form['sms']['sms_gateway'] = [
      '#type' => 'select',
      '#title' => $this->t('SMS Gateway'),
      '#options' => [
        'log' => $this->t('Log to database (testing)'),
        'twilio' => $this->t('Twilio'),
      ],
      '#default_value' => $config->get('sms_gateway') ?? 'log',
      '#description' => $this->t('Select which SMS gateway to use'),
    ];

    $form['sms']['sms_template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('SMS Template'),
      '#default_value' => $config->get('sms_template') ?? 'Your verification code is: @otp. Valid for 5 minutes.',
      '#description' => $this->t('SMS message template. Use @otp for OTP code and @form_id for form ID.'),
    ];

    $form['twilio'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Twilio Settings'),
      '#states' => [
        'visible' => [
          ':input[name="sms_gateway"]' => ['value' => 'twilio'],
        ],
      ],
    ];

    $form['twilio']['twilio_account_sid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account SID'),
      '#default_value' => $config->get('twilio_account_sid'),
    ];

    $form['twilio']['twilio_auth_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Auth Token'),
      '#default_value' => $config->get('twilio_auth_token'),
    ];

    $form['twilio']['twilio_phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twilio Phone Number'),
      '#default_value' => $config->get('twilio_phone_number'),
      '#description' => $this->t('Your Twilio phone number in E.164 format (e.g., +1234567890)'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('mobile_otp.settings')
      ->set('default_country_code', $form_state->getValue('default_country_code'))
      ->set('otp_expiry', $form_state->getValue('otp_expiry'))
      ->set('max_attempts', $form_state->getValue('max_attempts'))
      ->set('sms_gateway', $form_state->getValue('sms_gateway'))
      ->set('sms_template', $form_state->getValue('sms_template'))
      ->set('twilio_account_sid', $form_state->getValue('twilio_account_sid'))
      ->set('twilio_auth_token', $form_state->getValue('twilio_auth_token'))
      ->set('twilio_phone_number', $form_state->getValue('twilio_phone_number'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}