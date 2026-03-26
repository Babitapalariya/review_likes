<?php

namespace Drupal\role_based_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

class RoleBasedRegisterForm extends FormBase {

  public function getFormId() {
    return 'role_based_register_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Initialize counters for dynamic entries
    $counters = [
      'num_platforms', 
      'num_products',
      'num_socials',
      'num_tester_platforms'
    ];
    
    foreach ($counters as $counter) {
      if (!$form_state->has($counter)) {
        $form_state->set($counter, 1);
      }
    }

    $role = $form_state->getValue('role', '');

    // Role select with AJAX
    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Role'),
      '#options' => [
        '' => $this->t('- Select Role -'),
        'authenticated' => $this->t('Authenticated user'),
        'merchant' => $this->t('Merchant'),
        'gamechanger' => $this->t('Game changer'),
        'tester' => $this->t('Tester'),
      ],
      '#ajax' => [
        'callback' => '::updateRoleFields',
        'wrapper' => 'role-fields-wrapper',
      ],
      '#required' => TRUE,
      '#default_value' => $role,
    ];

    // Role-specific fields container
    $form['role_fields'] = [
      '#type' => 'container',
      '#prefix' => '<div id="role-fields-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    if ($role) {
      // User details fieldset
      $form['role_fields']['user_details'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('User Details'),
      ];
      
      $form['role_fields']['user_details']['first_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('First Name'),
        '#required' => TRUE,
      ];
      
      $form['role_fields']['user_details']['last_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Last Name'),
        '#required' => TRUE,
      ];
      
      $form['role_fields']['user_details']['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
        '#required' => TRUE,
      ];
      
      $form['role_fields']['user_details']['password'] = [
        '#type' => 'password_confirm',
        '#title' => $this->t('Password'),
        '#required' => TRUE,
      ];
      $form['role_fields']['user_details']['phone_number'] = [
        '#type' => 'tel',
        '#title' => $this->t('Phone Number'),
        '#required' => TRUE,
      ];


      // Role-specific fields
      switch ($role) {
        case 'merchant':
          $this->buildMerchantFields($form, $form_state);
          break;

        case 'tester':
          $form['role_fields']['test_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Tester Code'),
            '#required' => TRUE,
          ];
          
          // Dynamic tester platform details
          $n = $form_state->get('num_tester_platforms');
          $form['role_fields']['tester_platforms'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Platform Details'),
            '#tree' => TRUE,
          ];
          for ($i = 0; $i < $n; $i++) {
            $form['role_fields']['tester_platforms'][$i] = [
              'platform_name' => [
                '#type' => 'textfield',
                '#title' => $this->t('Platform Name'),
                '#required' => TRUE,
              ],
              'username' => [
                '#type' => 'textfield',
                '#title' => $this->t('Username / Handle'),
                '#required' => TRUE,
              ],
              'followers' => [
                '#type' => 'number',
                '#title' => $this->t('Number of Followers'),
                '#required' => TRUE,
                '#min' => 0,
              ],
            ];
          }
          $form['role_fields']['add_tester_platform'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add More Platforms'),
            '#submit' => ['::addMoreTesterPlatforms'],
            '#ajax' => [
              'callback' => '::updateRoleFields',
              'wrapper' => 'role-fields-wrapper',
            ],
            '#limit_validation_errors' => [],
            '#name' => 'add_tester_platform',
          ];
          break;

        case 'gamechanger':
          $form['role_fields']['innovation_area'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Area of Innovation'),
            '#required' => TRUE,
          ];
          break;
      }

      // Submit button
   
      $form['role_fields']['actions'] = [
      '#type' => 'actions',
    ];
    $form['role_fields']['actions']['submit'] = [
      '#type' => 'submit',  
      '#value' => $this->t('Register'),
      '#button_type' => 'primary',
    ];
  }

  return $form;
}

  public function updateRoleFields(array &$form, FormStateInterface $form_state) {
    return $form['role_fields'];
  }

  private function buildMerchantFields(array &$form, FormStateInterface $form_state) {
    $form['role_fields']['business_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Business Name'),
      '#required' => TRUE,
    ];
    
    $form['role_fields']['brand_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Brand Name'),
      '#required' => TRUE,
    ];

    // Platforms list
    $n = $form_state->get('num_platforms');
    $form['role_fields']['online_platforms'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Online Selling Platforms'),
      '#tree' => TRUE,
    ];
    for ($i = 0; $i < $n; $i++) {
      $form['role_fields']['online_platforms'][$i] = [
        'platform_name' => [
          '#type' => 'textfield',
          '#title' => $this->t('Platform Name'),
          '#required' => TRUE,
        ],
        'store_link' => [
          '#type' => 'url',
          '#title' => $this->t('Store Link'),
          '#required' => TRUE,
        ],
      ];
    }
    $form['role_fields']['add_platform'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add More Platforms'),
      '#submit' => ['::addMorePlatforms'],
      '#ajax' => [
        'callback' => '::updateRoleFields',
        'wrapper' => 'role-fields-wrapper',
      ],
      '#limit_validation_errors' => [],
      '#name' => 'add_platform',
    ];

    // Products list
    $m = $form_state->get('num_products');
    $form['role_fields']['product_list'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Product List with Buying Links'),
      '#tree' => TRUE,
    ];
    for ($i = 0; $i < $m; $i++) {
      $form['role_fields']['product_list'][$i] = [
        'product_name' => [
          '#type' => 'textfield',
          '#title' => $this->t('Product Name'),
          '#required' => TRUE,
        ],
        'buying_link' => [
          '#type' => 'url',
          '#title' => $this->t('Buying Link'),
          '#required' => TRUE,
        ],
      ];
    }
    $form['role_fields']['add_product'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add More Products'),
      '#submit' => ['::addMoreProducts'],
      '#ajax' => [
        'callback' => '::updateRoleFields',
        'wrapper' => 'role-fields-wrapper',
      ],
      '#limit_validation_errors' => [],
      '#name' => 'add_product',
    ];

    // Social media handles
    $s = $form_state->get('num_socials');
    $form['role_fields']['social_media'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Social Media Handles'),
      '#tree' => TRUE,
    ];
    for ($i = 0; $i < $s; $i++) {
      $form['role_fields']['social_media'][$i] = [
        'platform' => [
          '#type' => 'textfield',
          '#title' => $this->t('Platform Name'),
          '#required' => TRUE,
        ],
        'profile_link' => [
          '#type' => 'url',
          '#title' => $this->t('Page/Profile Link'),
          '#required' => TRUE,
        ],
      ];
    }
    $form['role_fields']['add_social'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add More Social Links'),
      '#submit' => ['::addMoreSocial'],
      '#ajax' => [
        'callback' => '::updateRoleFields',
        'wrapper' => 'role-fields-wrapper',
      ],
      '#limit_validation_errors' => [],
      '#name' => 'add_social',
    ];

    $form['role_fields']['business_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Business Email'),
      '#required' => TRUE,
    ];
    
    $form['role_fields']['business_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Business Phone Number'),
      '#required' => TRUE,
    ];
    
    $form['role_fields']['business_address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Business Address'),
      '#required' => TRUE,
    ];
  }

  // Dynamic field adders
  public function addMorePlatforms(array &$form, FormStateInterface $form_state) {
    $form_state->set('num_platforms', $form_state->get('num_platforms') + 1);
    $form_state->setRebuild();
  }

  public function addMoreProducts(array &$form, FormStateInterface $form_state) {
    $form_state->set('num_products', $form_state->get('num_products') + 1);
    $form_state->setRebuild();
  }

  public function addMoreSocial(array &$form, FormStateInterface $form_state) {
    $form_state->set('num_socials', $form_state->get('num_socials') + 1);
    $form_state->setRebuild();
  }

  public function addMoreTesterPlatforms(array &$form, FormStateInterface $form_state) {
    $form_state->set('num_tester_platforms', $form_state->get('num_tester_platforms') + 1);
    $form_state->setRebuild();
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $role = $form_state->getValue('role');
    
    if ($role) {
      $user_details = $form_state->getValue(['role_fields', 'user_details']);
      
      // Validate email
      if (!empty($user_details['email'])) {
        if (!\Drupal::service('email.validator')->isValid($user_details['email'])) {
          $form_state->setErrorByName('role_fields][user_details][email', $this->t('Please enter a valid email address.'));
        }
        elseif (user_load_by_mail($user_details['email'])) {
          $form_state->setErrorByName('role_fields][user_details][email', $this->t('This email is already registered.'));
        }
      }
      
      // Validate first name and last name
      if (empty(trim($user_details['first_name']))) {
        $form_state->setErrorByName('role_fields][user_details][first_name', $this->t('First name is required.'));
      }
      
      if (empty(trim($user_details['last_name']))) {
        $form_state->setErrorByName('role_fields][user_details][last_name', $this->t('Last name is required.'));
      }
      
      // Validate password
      if (empty($user_details['password'])) {
        $form_state->setErrorByName('role_fields][user_details][password', $this->t('Password is required.'));
      }
      // Validate phone number
      if (empty(trim($user_details['phone_number']))) {
        $form_state->setErrorByName('role_fields][user_details][phone_number', $this->t('Phone number is required.'));
      }

      
      // Role-specific validations
      if ($role === 'tester') {
        $test_code = $form_state->getValue(['role_fields', 'test_code']);
        if ($test_code !== 'SECRET123') {
          $form_state->setErrorByName('role_fields][test_code', $this->t('Invalid tester code.'));
        }
      }
      
      // Validate merchant fields
      if ($role === 'merchant') {
        $business_email = $form_state->getValue(['role_fields', 'business_email']);
        if (!empty($business_email)) {
          if (!\Drupal::service('email.validator')->isValid($business_email)) {
            $form_state->setErrorByName('role_fields][business_email', $this->t('Please enter a valid business email address.'));
          }
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $role = $form_state->getValue('role');
    $user_details = $form_state->getValue(['role_fields', 'user_details']);
    
    // Generate username from first name and last name
    $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', 
      $user_details['first_name'] . '.' . $user_details['last_name']
    ));
    $username = $base_username;
    $counter = 1;
    
    // Ensure username is unique
    while (user_load_by_name($username)) {
      $username = $base_username . $counter;
      $counter++;
    }
    
    // Create user
    $user = User::create([
      'name' => $username,
      'mail' => $user_details['email'],
      'pass' => $user_details['password'],
      'status' => 1,
      'field_first_name' => $user_details['first_name'],
      'field_last_name' => $user_details['last_name'],
      'field_phone_number' => $user_details['phone_number'],
    ]);
    
    // Add role
    if ($role !== 'authenticated') {
      $user->addRole($role);
    }
    
    // Save role-specific fields
    $this->saveRoleSpecificFields($user, $form_state);
    
    // Save user
    $user->save();
    
    // Set message and redirect
    \Drupal::messenger()->addStatus($this->t('Registration successful. You may now log in.'));
    $form_state->setRedirect('user.login');
  }
  
  protected function saveRoleSpecificFields(User $user, FormStateInterface $form_state) {
    $role = $form_state->getValue('role');
    $values = $form_state->getValue('role_fields');
    
    switch ($role) {
      case 'merchant':
        $user->set('field_business_name', $values['business_name']);
        $user->set('field_brand_name', $values['brand_name']);
        $user->set('field_business_email', $values['business_email']);
        $user->set('field_business_phone_number', $values['business_phone']);
        $user->set('field_business_address', $values['business_address']);
        
        // Save platforms
        $platforms = [];
        foreach ($values['online_platforms'] as $platform) {
          if (!empty($platform['platform_name'])) {
            $platforms[] = [
              'platform_name' => $platform['platform_name'],
              'store_link' => $platform['store_link'],
            ];
          }
        }
        $user->set('field_platform_name', $platforms);
        
        // Save products
        $products = [];
        foreach ($values['product_list'] as $product) {
          if (!empty($product['product_name'])) {
            $products[] = [
              'product_name' => $product['product_name'],
              'buying_link' => $product['buying_link'],
            ];
          }
        }
        $user->set('field_product_name', $products);
        
        // Save social media
        $socials = [];
        foreach ($values['social_media'] as $social) {
          if (!empty($social['platform'])) {
            $socials[] = [
              'platform' => $social['platform'],
              'profile_link' => $social['profile_link'],
            ];
          }
        }
        $user->set('field_store_link', $socials);
        break;
        
      case 'tester':
        $user->set('field_tester_code', $values['test_code']);
        
        // Save tester platforms
        // $platforms = [];
        // foreach ($values['tester_platforms'] as $platform) {
        //   if (!empty($platform['platform_name'])) {
        //     $platforms[] = [
        //       'platform_name' => $platform['platform_name'],
        //       'username' => $platform['username'],
        //       'followers' => $platform['followers'],
        //     ];
        //   }
        // }
        // $user->set('field_tester_platforms', $platforms);
        // break;
        
      // case 'gamechanger':
      //   $user->set('field_innovation_area', $values['innovation_area']);
      //   break;
    }
  }
}