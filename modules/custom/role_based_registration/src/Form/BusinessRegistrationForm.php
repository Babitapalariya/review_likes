<?php

namespace Drupal\role_based_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\user\UserInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mobile_number\Exception\MobileNumberException;

/**
 * Provides a Business Registration Form.
 */
class BusinessRegistrationForm extends FormBase {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The user's primary role.
   *
   * @var string
   */
  protected $role;

  /**
   * Constructs a new BusinessRegistrationForm.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $route_match, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_based_registration_business_form';
  }

  /**
   * Access check for the form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $roles = $account->getRoles();
    if (in_array('merchant', $roles) || in_array('administrator', $roles)) {
      return AccessResult::allowed()->addCacheContexts(['user.roles']);
    }
    return AccessResult::forbidden()->addCacheContexts(['user.roles']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load user entity.
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    if (!$user instanceof UserInterface) {
      $form['error'] = ['#markup' => $this->t('User not found.')];
      return $form;
    }

    // Store user and role for later use.
    $this->user = $user;
    $this->role = $this->getUserPrimaryRole($user);

    // Check if user has merchant role.
    if (!$this->userHasMerchantRole($user)) {
      $form['access_denied'] = [
        '#markup' => '<div class="messages messages--error">' . $this->t('You must have a merchant account to access this form.') . '</div>',
      ];
      return $form;
    }

    // Load existing merchant profile.
    $existing_profile = $this->getExistingMerchantProfile($user);

    // Set form theme and step.
    $form['#theme'] = 'registration_steps';
    $form['#step'] = 2;

    // Role-specific header.
    if ($this->role === 'merchant') {
      $form['merchant_info'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('<h2 class="text-center mb-4">My Details</h2>'),
        '#attributes' => ['class' => ['mb-4']],
      ];
    }
    elseif ($this->role === 'game_changer') {
      $form['game_changer_info'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('<h2 class="text-center mb-4">Game Changer Registration Form</h2>'),
        '#attributes' => ['class' => ['mb-4']],
      ];
    }
    elseif ($this->role === 'tester') {
      $form['tester_info'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('<h2 class="text-center mb-4">Tester Registration Form</h2>'),
        '#attributes' => ['class' => ['mb-4']],
      ];
    }


    /** ------------------------------------------------------------------------
     * BUSINESS INFORMATION SECTION
     * ---------------------------------------------------------------------- */
    $form['business_info'] = [
      '#type' => 'container',
      // '#attributes' => ['class' => ['mb-4']],
    ];

    $form['business_info']['header'] = [
      '#markup' => '<div class="mb-4">
                                       <h4>Business Details</h4>
                                    </div>',
        '#prefix' => '<div class="row">',
      
    ];

    // Legal Business Name.
    $form['business_info']['legal_business_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Legal Business Name'),
      '#required' => TRUE,
      // '#default_value' => $existing_profile ? $existing_profile->get('field_legal_business_name')->value : '',
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-6 legal-section"><div class="col-md-8">',
      '#suffix' => '</div></div>',
    ];

    /** ------------------------------------------------------------------------
     * BRAND NAMES (Dynamic)
     * ---------------------------------------------------------------------- */
    if ($form_state->get('brand_count') === NULL) {
      $count = $existing_profile ? count($existing_profile->get('field_brand_name')) : 1;
      $form_state->set('brand_count', $count);
    }

    $form['business_info']['brand_names'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Brand Name(s) '),
      '#prefix' => '<div class="col-md-6 brand-section" id="brand-names-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => ['class' => ['mb-3']],
    ];

    $brand_count = $form_state->get('brand_count');
    for ($i = 0; $i < $brand_count; $i++) {
      $default = $existing_profile && isset($existing_profile->get('field_brand_name')[$i]) ? 
        $existing_profile->get('field_brand_name')[$i]->value : '';
      
      $form['business_info']['brand_names']['brand_' . $i] = [
        '#type' => 'textfield',
        '#title' => $i == 0 ? $this->t('Brand Name / Doing Business As') : $this->t('Brand Name / Doing Business As'),
        '#required' => $i == 0,
        // '#default_value' => $default,
        '#attributes' => ['class' => ['form-control', 'mb-2']],
        '#prefix' => '<div class="row brand-section">
                        <div class="col-md-8">',
        '#suffix' => '</div>'
      ];

      if ($i > 0) {
        $form['business_info']['brand_names']['remove_brand_' . $i] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_brand_' . $i,
          '#ajax' => [
            'callback' => '::ajaxBrandsCallback',
            'wrapper' => 'brand-names-wrapper',
          ],
          '#submit' => ['::removeBrand'],
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['btn danger mt-4 remove-btn', 'btn-sm', 'btn-outline-danger', 'mb-3']],
          '#prefix' => '<div class= "col-md-4">',
          '#suffix' => '</div>',
        ];
      }
    }

    if ($brand_count < 3) {
      $form['business_info']['brand_names']['add_brand'] = [
        '#type' => 'submit',
        '#value' => $this->t('+ Add Brand'),
        '#ajax' => [
          'callback' => '::ajaxBrandsCallback',
          'wrapper' => 'brand-names-wrapper',
        ],
        '#submit' => ['::addBrand'],
        '#limit_validation_errors' => [],
        '#attributes' => ['class' => ['btn addBrandBtn mt-2 ms-3', 'btn-sm', 'btn-outline-primary']],
        '#prefix' => '<div class= "row">',
        '#suffix' => '</div>',
      ];
    }
$form['business_info']['brand_names']['brand_hint'] = [
  '#markup' => '<span class="hint mt-2 mb-3">Add up to 3 brands you operate under.</span>',
];
    /** ------------------------------------------------------------------------
     * STORE LINK
     * ---------------------------------------------------------------------- */
    $form['business_info']['store_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Store Link (Primary Marketplace URL) *'),
      '#required' => TRUE,
      // '#default_value' => $existing_profile ? $existing_profile->get('field_store_link')->uri : '',
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-12 mb-3">',
      '#suffix' => '</div>',
    ];

    /** ------------------------------------------------------------------------
     * PLATFORM LINKS (Dynamic)
     * ---------------------------------------------------------------------- */
    if ($form_state->get('platform_count') === NULL) {
      $count = $existing_profile ? count($existing_profile->get('field_platform_links')) : 1;
      $form_state->set('platform_count', $count);
    }

    $form['business_info']['platform_links'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Online Selling Platform Links *'),
      '#prefix' => '<div class="col-md-12" id="platform-links-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => ['class' => ['mb-3']],
    ];

    $platform_count = $form_state->get('platform_count');
    for ($i = 0; $i < $platform_count; $i++) {
      $default = $existing_profile && isset($existing_profile->get('field_platform_links')[$i]) ? 
        $existing_profile->get('field_platform_links')[$i]->uri : '';
      
      $form['business_info']['platform_links']['platform_' . $i] = [
        
        '#prefix'=> '<div class="adminplatform-section mt-2" >',
        '#type' => 'url',
        '#title' => $i == 0 ? $this->t('Platform Link ') : $this->t('Platform Link'),
        '#required' => $i == 0,
        // '#default_value' => $default,
        '#attributes' => ['class' => ['form-control me-4']],

       
      ];

      if ($i > 0) {
        $form['business_info']['platform_links']['remove_platform_' . $i] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_platform_' . $i,
          '#ajax' => [
            'callback' => '::ajaxPlatformsCallback',
            'wrapper' => 'platform-links-wrapper',
          ],
          '#submit' => ['::removePlatform'],
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['d-flex storelink-section', 'btn danger remove-btn', 'btn-sm', 'btn-outline-danger', 'mb-3']],
          '#prefix' => '<div class="d-flex storelink-section">',
          '#suffix' => '</div></div>',
        ];
      }
    }

    if ($platform_count < 5) {
      $form['business_info']['platform_links']['add_platform'] = [
        '#type' => 'submit',
        '#value' => $this->t('+ Add Platform Link'),
        '#ajax' => [
          'callback' => '::ajaxPlatformsCallback',
          'wrapper' => 'platform-links-wrapper',
        ],
        '#submit' => ['::addPlatform'],
        '#limit_validation_errors' => [],
        '#attributes' => ['class' => ['btn adminaddPlatformBtn mt-2', 'btn-sm', 'btn-outline-primary']],
        '#prefix' => '<div class= "row ms-1 mt-2">',
        '#suffix' => '</div>',
      ];
    }
    $form['business_info']['platform_links']['links_hint'] = [
  '#markup' => '<span class="hint mt-2 mb-3">Add up to 20 Platform Links you operate under.</span>',
];

    /** ------------------------------------------------------------------------
     * SOCIAL LINKS (Dynamic)
     * ---------------------------------------------------------------------- */
    if ($form_state->get('social_count') === NULL) {
      $count = $existing_profile ? count($existing_profile->get('field_platform_link')) : 1;
      $form_state->set('social_count', $count);
    }

    $form['business_info']['social_links'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Social Links (Link + Coupon Code)'),
      '#prefix' => '<div class="col-md-12" id="social-links-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => ['class' => ['mb-3']],
    ];

    $social_count = $form_state->get('social_count');
    for ($i = 0; $i < $social_count; $i++) {
      $form['business_info']['social_links']['social_container_' . $i] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['row', 'mb-2']],
      ];

      $form['business_info']['social_links']['social_container_' . $i]['link_' . $i] = [
        '#prefix' => '<div class="col-md-4">',
        '#type' => 'textfield',
        '#title' => $this->t('Platform Name'),
        '#attributes' => ['class' => ['form-control']],
        '#suffix' => '</div>',
      ];

      $form['business_info']['social_links']['social_container_' . $i]['coupon_' . $i] = [
        '#prefix' => '<div class="col-md-6">',
        '#type' => 'textfield',
        '#title' => $this->t('Page/Profile Link'),
        '#attributes' => ['class' => ['form-control']],
        '#suffix' => '</div>',
      ];

      if ($i > 0) {
        $form['business_info']['social_links']['social_container_' . $i]['remove_social_' . $i] = [
          '#prefix' => '<div class="col-md-2">',
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_social_' . $i,
          '#ajax' => [
            'callback' => '::ajaxSocialCallback',
            'wrapper' => 'social-links-wrapper',
          ],
          '#submit' => ['::removeSocial'],
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['btn danger remove-btn mt-4', 'btn-sm', 'btn-outline-danger', 'mt-4']],
          '#suffix' => '</div>',
        ];
      }
    }

    if ($social_count < 5) {
      $form['business_info']['social_links']['add_social'] = [
        '#type' => 'submit',
        '#value' => $this->t('+ Add Social Link'),
        '#ajax' => [
          'callback' => '::ajaxSocialCallback',
          'wrapper' => 'social-links-wrapper',
        ],
        '#submit' => ['::addSocial'],
        '#limit_validation_errors' => [],
         '#attributes' => ['class' => ['btn adminaddPlatformBtn mt-2', 'btn-sm', 'btn-outline-primary']],
        '#prefix' => '<div class= "row">',
        '#suffix' => '</div>',
      ];
    }
      $form['business_info']['social_links']['social_hint'] = [
  '#markup' => '<span class="hint mt-2 mb-5">Add your Facebook Page, Instagram Profile, YouTube Channel, Pinterest, etc.</span>',
];

    /** ------------------------------------------------------------------------
     * ADDITIONAL BUSINESS LINKS
     * ---------------------------------------------------------------------- */
    $form['business_info']['own_website'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Own Website Link'),
      // '#default_value' => $existing_profile ? $existing_profile->get('field_external_id')->uri : '',
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-6">',
      '#suffix' => '</div>',
    ];

    $form['business_info']['whatsapp_business_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WhatsApp Business Link'),
      // '#default_value' => $existing_profile ? $existing_profile->get('field_product_url')->uri : '',
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-6">',
      '#suffix' => '</div></div>',
    ];

    /** ------------------------------------------------------------------------
     * PERSONAL INFORMATION SECTION
     * ---------------------------------------------------------------------- */
    $form['personal_info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row', 'mb-4']],
    ];

    $form['personal_info']['header'] = [
      '#markup' => '<div class="col-12 mb-3"><h4>Section 1: Personal Information</h4></div>',
    ];

    // First Name.
    $form['personal_info']['first_name'] = [
      '#prefix' => '<div class="col-md-6">',
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#default_value' => $user->get('field_first_name')->value ?: $user->get('name')->value,
      '#attributes' => [
        'class' => ['form-control'],
        'placeholder' => 'First Name',
      ],
      '#required' => TRUE,
      '#suffix' => '</div>',
    ];

    // Last Name.
    $form['personal_info']['last_name'] = [
      '#prefix' => '<div class="col-md-6">',
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => $user->get('field_last_name')->value,
      '#attributes' => [
        'class' => ['form-control'],
        'placeholder' => 'Last Name',
      ],
      '#required' => TRUE,
      '#suffix' => '</div>',
    ];

    /** ------------------------------------------------------------------------
     * CONTACT INFORMATION SECTION
     * ---------------------------------------------------------------------- */
    $form['contact_info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row', 'mb-4']],
    ];

    // Country.
    $form['contact_info']['country'] = [
      '#prefix' => '<div class="col-md-6 mb-4">',
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $this->getCountryOptions(),
      '#default_value' => $user->get('field_country')->value,
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-select']],
      '#suffix' => '</div>',
    ];

    // Language.
    // $form['contact_info']['language'] = [
    //   '#prefix' => '<div class="col-md-4">',
    //   '#type' => 'select',
    //   '#title' => $this->t('Language'),
    //   '#options' => $this->getLanguageOptions(),
    //   '#default_value' => $user->get('field_language')->target_id,
    //   '#required' => TRUE,
    //   '#attributes' => ['class' => ['form-select']],
    //   '#suffix' => '</div>',
    // ];

    // Email (disabled).
    $form['contact_info']['email'] = [
      '#prefix' => '<div class="col-md-6 mb-4">',
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#default_value' => $user->getEmail(),
      '#disabled' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#required' => TRUE,
      '#suffix' => '</div>',
    ];

    // Mobile Number.
    $mobile_number_util = NULL;
    $default_mobile = ['value' => '', 'country' => 'us', 'local_number' => ''];
    
    if (\Drupal::moduleHandler()->moduleExists('mobile_number')) {
      try {
        $mobile_number_util = \Drupal::service('mobile_number.util');
        $mobile_number_value = $user->get('field_mobile_number')->value;
        
        if (!empty($mobile_number_value)) {
          $mobile_obj = $mobile_number_util->getMobileNumber($mobile_number_value);
          if ($mobile_obj) {
          $default_mobile = [
          'value' => $mobile_number_util->getCallableNumber($mobile_obj),
          'country' => $mobile_number_util->getCountry($mobile_obj), // Correct method
          'local_number' => $mobile_number_util->getLocalNumber($mobile_obj), // Correct method
        ];
          }
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('role_based_registration')->warning('Mobile Number service error: @error', ['@error' => $e->getMessage()]);
      }
    }

    $form['contact_info']['mobile'] = [
      '#prefix' => '<div class="col-md-6 mb-4">',
      '#type' => $mobile_number_util ? 'mobile_number' : 'tel',
      '#title' => $this->t('Mobile Number'),
      '#default_value' => $default_mobile,
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#suffix' => '</div>',
    ];


       $form['contact_info']['address'] = [
      '#prefix' => '<div class="col-md-6 mb-4">',
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
      '#default_value' => $user->get('field_address')->value,
      '#attributes' => [
        'class' => ['form-control'],
        'placeholder' => 'Address',
      ],
      '#required' => TRUE,
      '#suffix' => '</div>',
    ];
    
    //    $form['contact_info']['city'] = [
    //   '#prefix' => '<div class="col-md-6 mb-4">',
    //   '#type' => 'textfield',
    //   '#title' => $this->t('City'),
    //   '#default_value' => $user->get('field_city')->value,
    //   '#attributes' => [
    //     'class' => ['form-control'],
    //     'placeholder' => 'City',
    //   ],
    //   '#required' => TRUE,
    //   '#suffix' => '</div>',
    // ];
    $form['contact_info']['state'] = [
      '#prefix' => '<div class="col-md-6 mb-4">',
      '#type' => 'textfield',
      '#title' => $this->t('State'),
      '#default_value' => $user->get('field_state')->value,
      '#attributes' => [
        'class' => ['form-control'],
        'placeholder' => 'State',
      ],
      '#required' => TRUE,
      '#suffix' => '</div>',
    ];
    $form['contact_info']['zip'] = [
      '#prefix' => '<div class="col-md-6 mb-4">',
      '#type' => 'textfield',
      '#title' => $this->t('Zip'),
      '#default_value' => $user->get('field_zip')->value,
      '#attributes' => [
        'class' => ['form-control'],
        'placeholder' => 'Zip',
      ],
      '#required' => TRUE,
      '#suffix' => '</div>',
    ];
    if ($mobile_number_util) {
      $form['contact_info']['mobile']['#mobile_number'] = [
        'verify' => 'optional',
        'countries' => ['us', 'in', 'gb', 'au', 'nz'],
        'default_country' => 'us',
        'message' => 'Verification code sent to your mobile.',
      ];
    }
    /** ------------------------------------------------------------------------
     * SUBMIT BUTTON
     * ---------------------------------------------------------------------- */
    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['mt-4']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Complete Profile'),
      '#button_type' => 'primary',
      '#attributes' => ['class' => ['btn', 'btn-primary', 'btn-lg']],
    ];

    // Add existing profile ID if updating.
    if ($existing_profile) {
      $form['existing_profile_id'] = [
        '#type' => 'hidden',
        '#value' => $existing_profile->id(),
      ];
    }

    // Attach libraries.
    // if (\Drupal::moduleHandler()->moduleExists('mobile_otp')) {
      $form['#attached']['library'][] = 'mobile_otp/otp-verification';
    // }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate mobile number if mobile_number module is available.
    if (\Drupal::moduleHandler()->moduleExists('mobile_number')) {
      try {
        $mobile_number_util = \Drupal::service('mobile_number.util');
        $mobile_value = $form_state->getValue('mobile');
        
        if (!empty($mobile_value['value'])) {
          $mobile_obj = $mobile_number_util->getMobileNumber($mobile_value['value']);
          
          // if (!$mobile_number_util->isValid($mobile_obj)) {
            // $form_state->setErrorByName('mobile', $this->t('Please enter a valid mobile number.'));
          // }
        }
      }
      catch (MobileNumberException $e) {
        $form_state->setErrorByName('mobile', $this->t('Invalid mobile number format.'));
      }
      catch (\Exception $e) {
        \Drupal::logger('role_based_registration')->error('Mobile number validation error: @error', ['@error' => $e->getMessage()]);
      }
    }

    // Validate URLs.
    $url_fields = ['store_link', 'own_website', 'whatsapp_business_link'];
    foreach ($url_fields as $field) {
      $value = $form_state->getValue($field);
      if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
        $form_state->setErrorByName($field, $this->t('Please enter a valid URL for @field.', ['@field' => $field]));
      }
    }

    // Validate platform links.
    $platform_count = $form_state->get('platform_count');
    for ($i = 0; $i < $platform_count; $i++) {
      $value = $form_state->getValue('platform_' . $i);
      if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
        $form_state->setErrorByName('platform_' . $i, $this->t('Please enter a valid URL for platform link @number.', ['@number' => $i + 1]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->user;
    $existing_profile_id = $form_state->getValue('existing_profile_id');

    // Update user profile fields.
    $user->set('field_first_name', $form_state->getValue('first_name'));
    $user->set('field_last_name', $form_state->getValue('last_name'));
    $user->set('field_address', $form_state->getValue('address'));
    $user->set('field_state', $form_state->getValue('state'));
    $user->set('field_zip', $form_state->getValue('zip'));
    $user->set('field_country', $form_state->getValue('country'));
    $user->set('field_language', $form_state->getValue('language'));

    // Update mobile number if module exists.
    if (\Drupal::moduleHandler()->moduleExists('mobile_number')) {
      try {
        $mobile_number_util = \Drupal::service('mobile_number.util');
        $mobile_value = $form_state->getValue('mobile');
        
        if (!empty($mobile_value['value'])) {
          $mobile_obj = $mobile_number_util->getMobileNumber($mobile_value['value']);
          $formatted_number = $mobile_number_util->getCallableNumber($mobile_obj);
          $user->set('field_mobile_number', $formatted_number);
        }
      }
      catch (MobileNumberException $e) {
        $this->messenger()->addError($this->t('Could not save mobile number: @error', ['@error' => $e->getMessage()]));
      }
    }

    $user->save();

    // Collect brand names.
    $brand_names = [];
    for ($i = 0; $i < $form_state->get('brand_count'); $i++) {
      $val = $form_state->getValue('brand_' . $i);
      if (!empty($val)) {
        $brand_names[] = $val;
      }
    }

    // Collect platform links.
    $platform_links = [];
    for ($i = 0; $i < $form_state->get('platform_count'); $i++) {
      $val = $form_state->getValue('platform_' . $i);
      if (!empty($val)) {
        $platform_links[] = ['uri' => $val];
      }
    }

    // Collect social links as paragraphs.
    $social_items = [];
    for ($i = 0; $i < $form_state->get('social_count'); $i++) {
      $link = $form_state->getValue('link_' . $i);
      $coupon = $form_state->getValue('coupon_' . $i);

      if (!empty($link) || !empty($coupon)) {
        $paragraph = Paragraph::create([
          'type' => 'business_social_media',
          'field_platform_link' => $link,
          'field_coupon_code' => $coupon,
        ]);
        $paragraph->save();
        $social_items[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
    }

    // Create or update merchant node.
    if ($existing_profile_id) {
      $node = $this->entityTypeManager->getStorage('node')->load($existing_profile_id);
      $message = $this->t('Your business registration has been updated successfully.');
    }
    else {
      $node = Node::create([
        'type' => 'merchant',
        // 'uid' => $user->id(),
      ]);
      $message = $this->t('Your business registration has been submitted successfully.');
    }

    $node->set('title', $form_state->getValue('legal_business_name'));
    $node->set('field_legal_business_name', $form_state->getValue('legal_business_name'));
    $node->set('field_brand_name', $brand_names);
    $node->set('field_store_link', ['uri' => $form_state->getValue('store_link')]);
    $node->set('field_platform_links', $platform_links);
    
    if (!empty($social_items)) {
      $node->set('field_platform_link', $social_items);
    }
    
    $node->set('field_external_id', ['uri' => $form_state->getValue('own_website')]);
    $node->set('field_product_url', ['uri' => $form_state->getValue('whatsapp_business_link')]);

    $node->save();

    $this->messenger()->addMessage($message);
    $form_state->setRedirect('<current>');
  }

  /** ------------------------------------------------------------------------
   *  AJAX CALLBACKS
   * ---------------------------------------------------------------------- */

  /**
   * AJAX callback for brand names.
   */
  public function ajaxBrandsCallback(array &$form, FormStateInterface $form_state) {
    return $form['business_info']['brand_names'];
  }

  /**
   * AJAX callback for platform links.
   */
  public function ajaxPlatformsCallback(array &$form, FormStateInterface $form_state) {
    return $form['business_info']['platform_links'];
  }

  /**
   * AJAX callback for social links.
   */
  public function ajaxSocialCallback(array &$form, FormStateInterface $form_state) {
    return $form['business_info']['social_links'];
  }

  /** ------------------------------------------------------------------------
   *  ADD/REMOVE HANDLERS
   * ---------------------------------------------------------------------- */

  /**
   * Add a brand field.
   */
  public function addBrand(array &$form, FormStateInterface $form_state) {
    $form_state->set('brand_count', $form_state->get('brand_count') + 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Remove a brand field.
   */
  public function removeBrand(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $index = (int) str_replace('remove_brand_', '', $triggering_element['#name']);
    $form_state->set('brand_count', max(1, $form_state->get('brand_count') - 1));
    $form_state->setRebuild(TRUE);
  }

  /**
   * Add a platform field.
   */
  public function addPlatform(array &$form, FormStateInterface $form_state) {
    $form_state->set('platform_count', $form_state->get('platform_count') + 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Remove a platform field.
   */
  public function removePlatform(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $index = (int) str_replace('remove_platform_', '', $triggering_element['#name']);
    $form_state->set('platform_count', max(1, $form_state->get('platform_count') - 1));
    $form_state->setRebuild(TRUE);
  }

  /**
   * Add a social field.
   */
  public function addSocial(array &$form, FormStateInterface $form_state) {
    $form_state->set('social_count', $form_state->get('social_count') + 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Remove a social field.
   */
  public function removeSocial(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $index = (int) str_replace('remove_social_', '', $triggering_element['#name']);
    $form_state->set('social_count', max(1, $form_state->get('social_count') - 1));
    $form_state->setRebuild(TRUE);
  }

  /** ------------------------------------------------------------------------
   *  HELPER METHODS
   * ---------------------------------------------------------------------- */

  /**
   * Check if user has merchant role.
   */
  protected function userHasMerchantRole(UserInterface $user) {
    $roles = $user->getRoles();
    return in_array('merchant', $roles) || in_array('administrator', $roles);
  }

  /**
   * Get existing merchant profile for user.
   */
  protected function getExistingMerchantProfile(UserInterface $user) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'merchant')
      ->condition('uid', $user->id())
      ->accessCheck(FALSE);
    $nids = $query->execute();
    
    if (!empty($nids)) {
      return $this->entityTypeManager->getStorage('node')->load(reset($nids));
    }
    return FALSE;
  }

  /**
   * Get user's primary role.
   */
  protected function getUserPrimaryRole(UserInterface $user) {
    $roles = $user->getRoles();
    $roles = array_diff($roles, ['authenticated']);
    return !empty($roles) ? reset($roles) : 'authenticated';
  }

  /**
   * Get country options.
   */
  protected function getCountryOptions() {
    $country_manager = \Drupal::service('country_manager');
    return $country_manager->getList();
  }

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

}