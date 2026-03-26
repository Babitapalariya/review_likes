<?php

namespace Drupal\role_based_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\mobile_number\Exception\MobileNumberException;


class ProfileCompletionForm extends FormBase {

  protected $user;
  protected $role;

  public function getFormId() {
    return 'profile_completion_form';
  }

  public static function access(AccountInterface $account, UserInterface $user = NULL) {
    // Check if current user can edit this profile
    return AccessResult::allowedIf($account->id() == $user->id());
    
  }

  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $this->user = $user;
    $this->role = $this->getUserPrimaryRole($user);

    $form['#theme'] = 'registration_steps';
    $form['#step'] = 2;

    // Role-specific fields (editable)
    if ($this->role === 'merchant') {
      $form['merchant_info'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('<h2 class="text-center mb-4">Merchant Registration Form</h2>'),
      ];
      // Add merchant specific fields here if needed
    }
    elseif ($this->role === 'game_changer') {
      $form['game_changer_info'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('<h2 class="text-center mb-4">Game Changer Registration Form</h2>'),
      ];
      // Add game changer specific fields here if needed
    }
      elseif ($this->role === 'tester') {
      $form['tester_info'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('<h2 class="text-center mb-4">Tester Registration Form</h2>'),
      ];
      // Add Tester specific fields here if needed
    }

    // Personal Information Section - Only email disabled
    $form['personal_info'] = [
      '#type' => 'container',
        '#attributes' => ['class' => ['row']],
        'header' => [
            '#markup' => '<div class="mb-4"><h4>Section 1: Personal Information</h4></div>',
        ],
    ];

    // First Name (editable)
    $form['personal_info']['first_name'] = [
      '#prefix' => ' <div class="col-md-6">',
       'label' => [
            '#markup' => '',
        ],  
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#default_value' => $user->get('name')->value,
      '#attributes' => [
      'class' => ['form-control', 'mb-3'],
      'placeholder' => 'First Name',
    ],
      '#required' => TRUE,
         '#suffix' => '</div>',
    ];

    // Last Name (editable)
    $form['personal_info']['last_name'] = [
      '#prefix' => '<div class="col-md-6">',
       'label' => [
            '#markup' => '<label for="fname" class="required mb-2">Last Name</label>',
        ],  
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => $user->get('field_last_name')->value,
      '#attributes' => [
                'class' => ['form-control', 'mb-3'],
                'placeholder' => 'Last Name',
            ],
      '#required' => TRUE,
       '#suffix' => '</div>',
    ];

   $form['contact_info'] = [
  '#type' => 'container',
  '#attributes' => ['class' => ['row']],
];

$form['contact_info']['country'] = [
  '#prefix' => '<div class="col-md-4">',
  '#type' => 'select',
  '#title' => $this->t('Country'),
  '#options' => \Drupal::service('country.field.manager')->getList(),
  '#default_value' => $user->get('field_country')->value,
  '#required' => TRUE,
  '#suffix' => '</div>',
  '#attributes' => [
    'class' => ['form-select'],
    'id' => 'country',
  ],
  '#name' => 'country',
];

    $form['contact_info']['language'] = [
  '#prefix' => '<div class="col-md-4">',
  'label' => [
    '#markup' => '<label for="language" class="required mb-2">Language</label>',
  ],
  '#type' => 'select',
  '#title' => $this->t('Language'),
  '#options' => $this->getLanguageOptions(),
  '#default_value' => $user->get('field_language')->target_id, // Use target_id for entity reference
  '#required' => TRUE,
  '#suffix' => '</div>',
  '#name' => 'language', // Add name attribute
];

    
    // Email field (disabled)
    $form['contact_info']['email'] = [
      '#prefix' => '<div class="col-md-4">',
      'label' => [
            '#markup' => '<label for="email" class="required mb-2">Email address</label>',
        ],
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#default_value' => $user->getEmail(),
      '#disabled' => TRUE, // This makes the field non-editable
      '#attributes' => [
                'class' => ['form-control', 'mb-3'],
            ],
      '#required' => TRUE,
       '#suffix' => '</div>',
    ];

  // Initialize mobile_number_util
  $mobile_number_util = NULL;
  
  // Check if mobile_number module is enabled and service exists
  if (\Drupal::moduleHandler()->moduleExists('mobile_number')) {
    try {
      $mobile_number_util = \Drupal::service('mobile_number.util');
    } catch (\Exception $e) {
      \Drupal::logger('role_based_registration')->warning('Mobile Number service could not be loaded: @error', ['@error' => $e->getMessage()]);
    }
  }
  
  // Get the mobile number value from user field
  $mobile_number_value = $user->get('field_mobile_number')->value;
  
  // Set default structure
  $default_mobile = [
    'value' => '',
    'country' => 'us',
    'local_number' => '',
  ];
  
  // If service is available, try to populate with proper format
  if ($mobile_number_util && !empty($mobile_number_value)) {
    try {
      $mobile_obj = $mobile_number_util->getMobileNumber($mobile_number_value);
      if ($mobile_obj) {
        $default_mobile = [
          'value' => $mobile_number_util->getCallableNumber($mobile_obj),
          'country' => $mobile_number_util->getCountry($mobile_obj), // Correct method
          'local_number' => $mobile_number_util->getLocalNumber($mobile_obj), // Correct method
        ];
      }
    } catch (MobileNumberException $e) {
      \Drupal::logger('mobile_number')->error('Invalid mobile number in user profile: @error', ['@error' => $e->getMessage()]);
    }
  } elseif (!empty($mobile_number_value)) {
    // Fallback: just use the raw value if service is not available
    $default_mobile['value'] = $mobile_number_value;
  }

  $form['contact_info']['mobile'] = [
    // '#prefix' => '<div class="col-md-4">',
    // '#type' => 'mobile_number',
    '#type' => $mobile_number_util ? 'mobile_number' : 'tel',
    '#title' => $this->t('Mobile Number'),
    '#default_value' => $default_mobile,
    '#required' => TRUE,
    '#attributes' => ['class' => ['col-md-4', 'form-control', 'mb-3']],
    '#mobile_number' => [
      'verify' => 'optional',
      'countries' => ['us', 'in', 'gb', 'au', 'nz'],
      'default_country' => 'us',
    ],
    // '#suffix' => '</div>',
  ];
  // Add mobile_number configuration only if module is available
  if ($mobile_number_util) {
    $form['contact_info']['mobile']['#mobile_number'] = [
      'verify' => 'optional',
      'countries' => ['us', 'in', 'gb', 'au', 'nz'],
      'default_country' => 'us',
      'message' => 'Verification code sent to your mobile.',
      'token_data' => [],
    ];
  }
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      // '#prefix' => '<div class="col-md-2 mt-2">',
      '#type' => 'submit',
      '#attributes' => ['class' => ['btn', 'btn-default', 'mt-3']],
      '#value' => $this->t('Complete Profile'),
      '#button_type' => 'primary',
      // '#suffix' => '</div>',
    ];
 $form['#attached']['library'][] = 'mobile_otp/otp-verification';
    return $form;
}




  protected function getUserPrimaryRole(UserInterface $user) {
    $roles = $user->getRoles();
    // Remove authenticated role
    $roles = array_diff($roles, ['authenticated']);
    
    return !empty($roles) ? reset($roles) : 'authenticated';
  }

  // protected function getCountryOptions() {
  //   return [
  //     'us' => $this->t('United States'),
  //     'uk' => $this->t('United Kingdom'),
  //     'ca' => $this->t('Canada'),
  //     'in' => $this->t('India'),
  //   ];
  // }


public function validateForm(array &$form, FormStateInterface $form_state) {
  // Check if mobile number service is available
  if (\Drupal::hasService('mobile_number.util')) {
    $mobile_number_util = \Drupal::service('mobile_number.util');
    $mobile_value = $form_state->getValue('mobile');
    
    if (!empty($mobile_value['value'])) {
      try {
        $mobile_obj = $mobile_number_util->getMobileNumber($mobile_value['value']);
        
        // Use the correct validation method
        if (method_exists($mobile_number_util, 'testNumber') && !$mobile_number_util->testNumber($mobile_obj)) {
          $form_state->setErrorByName('mobile', $this->t('Please enter a valid mobile number.'));
        }
      } catch (MobileNumberException $e) {
        $form_state->setErrorByName('mobile', $this->t('Invalid mobile number format.'));
      }
    }
  }

  // Validate regular phone field
  $phone = $form_state->getValue('phone');
  if (!empty($phone) && !preg_match('/^[0-9]{10,15}$/', $phone)) {
    $form_state->setErrorByName('phone', $this->t('Please enter a valid phone number'));
  }
}



// public function validateForm(array &$form, FormStateInterface $form_state) {
//   // The mobile_number field type handles its own validation
//   // You only need custom validation for additional checks
  
//   $mobile_value = $form_state->getValue('mobile');
  
//   if (!empty($mobile_value['value'])) {
//     // Additional custom validation if needed
//     // For example, check if number is already registered to another user
//     try {
//       $mobile_number_util = \Drupal::service('mobile_number.util');
//       $mobile_obj = $mobile_number_util->getMobileNumber($mobile_value['value']);
      
//       // Check if this mobile number is already used by another user
//       $query = \Drupal::entityQuery('user')
//         ->condition('field_mobile_number', $mobile_value['value'])
//         ->condition('uid', $this->user->id(), '<>');
      
//       $results = $query->accessCheck(FALSE)->execute();
      
//       if (!empty($results)) {
//         $form_state->setErrorByName('mobile', $this->t('This mobile number is already registered to another account.'));
//       }
      
//     } catch (MobileNumberException $e) {
//       // Validation already handled by the field type
//     }
//   }

//   // Validate regular phone field
//   $phone = $form_state->getValue('phone');
//   if (!empty($phone) && !preg_match('/^[0-9]{10,15}$/', $phone)) {
//     $form_state->setErrorByName('phone', $this->t('Please enter a valid phone number'));
//   }
// }



protected function getLanguageOptions() {
  $options = [];
  
  // Load taxonomy terms from the Languages vocabulary
  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => 'languages']);
  
  foreach ($terms as $term) {
    $options[$term->id()] = $term->getName();
  }
  
  return $options;
}

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update user fields
  //    $country_value = $form_state->getValue('country');
  // $this->user->set('field_country', $country_value);
    $this->user->set('name', $form_state->getValue('first_name'));
    $this->user->set('field_last_name', $form_state->getValue('last_name'));
    $this->user->set('field_country', $form_state->getValue('country'));
    $this->user->set('field_language', $form_state->getValue('language'));
    // $this->user->set('field_phone', $form_state->getValue('phone'));
     $mobile_number_util = \Drupal::service('mobile_number.util');
  $mobile_value = $form_state->getValue('mobile');
  
  if (!empty($mobile_value['value'])) {
    try {
      $mobile_obj = $mobile_number_util->getMobileNumber($mobile_value['value']);
      $formatted_number = $mobile_number_util->getCallableNumber($mobile_obj);
      $this->user->set('field_mobile_number', $formatted_number);
    } catch (MobileNumberException $e) {
      $this->messenger()->addError($this->t('Could not save mobile number: @error', ['@error' => $e->getMessage()]));
    }
  }

    // Save role-specific fields
    if ($this->role === 'merchant') {
      // $this->user->set('field_company', $form_state->getValue('company'));
    }

    // Save user
    $this->user->save();
if ($this->user->hasRole('merchant')) {
    $form_state->setRedirect('role_based_registration.product_form');
  }elseif($this->user->hasRole('tester')){
  $form_state->setRedirect('tester_dashboard.dashboard');
  }
    else {
    // Optional: redirect others to profile or homepage
    // $form_state->setRedirect('entity.user.canonical', ['user' => $this->user->id()]);
    // OR
    $form_state->setRedirect('<current>');
  }

    $this->messenger()->addStatus($this->t('Your profile has been completed successfully.'));
  }
}