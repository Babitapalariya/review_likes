<?php
// File: src/Form/TesterProfileForm.php

namespace Drupal\tester_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a Tester Profile Form.
 */
class TesterProfileEditForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new TesterProfileForm.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, FileSystemInterface $file_system) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tester_profile_edit_form';
  }

  /**
   * Access check for the form.
   */
  public function access(AccountInterface $account) {
    $roles = $account->getRoles();
    if (in_array('tester', $roles) || in_array('administrator', $roles)) {
      return AccessResult::allowed()->addCacheContexts(['user.roles', 'user.permissions']);
    }
    return AccessResult::forbidden()->addCacheContexts(['user.roles', 'user.permissions']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid = NULL) {
    // Load existing tester profile.
    $existing_profile = $this->getExistingTesterProfile();
    
    // $form['#theme'] = 'tester_profile_form';
    $form['#attached']['library'][] = 'tester_dashboard/tester_form';
 if ($nid) {
    $node = Node::load($nid);
    if ($node) {
    $form['profile_section'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container', 'my-5']],
    ];

    $form['profile_section']['title'] = [
      '#markup' => '<h2 class="mb-5 text-center">Tester Profile</h2>',
    ];

    $form['profile_section']['container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['col-md-10', 'm-auto']],
    ];

    // Full Name
    $form['profile_section']['container']['full_name'] = [
      '#prefix' => '<div class="row mt-4"><div class="col-md-6 mb-4">',
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
      '#default_value' => $existing_profile ? $existing_profile->get('title')->value : '',
      '#attributes' => ['class' => ['form-control'], 'placeholder' => 'John Doe'],
      '#suffix' => '</div>',
    ];

    // Profile Picture
    $default_picture = '';
    if ($existing_profile && !$existing_profile->get('field_image')->isEmpty()) {
      $picture_fid = $existing_profile->get('field_image')->target_id;
      $picture_file = File::load($picture_fid);
      if ($picture_file) {
        $default_picture = $picture_file->id();
      }
    }

    $form['profile_section']['container']['profile_picture_container'] = [
      '#prefix' => '<div class="col-md-6 mb-4 text-center">',
      '#suffix' => '</div></div>',
    ];

    // $form['profile_section']['container']['profile_picture_container']['picture_preview'] = [
    //   '#markup' => $default_picture ? 
    //     $this->getImagePreview($default_picture) : 
    //     '<img src="/' . drupal_get_path('module', 'tester_dashboard') . '/images/tester.jpg" alt="Tester Profile" class="img-thumbnail mb-2" style="max-width: 150px;">',
    // ];

    $form['profile_section']['container']['profile_picture_container']['profile_picture'] = [
      '#prefix' => '<div class="row align-items-center"><div class="col-md-9">',
      '#type' => 'managed_file',
      '#title' => $this->t('Profile Picture'),
      '#upload_location' => 'public://tester_profile/',
      '#default_value' => $default_picture ? [$default_picture] : [],
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif'],
        'file_validate_size' => [2097152], // 2MB
      ],
      '#suffix' => '</div>',
    ];

    // $form['profile_section']['container']['profile_picture_container']['upload_button'] = [
    //   '#prefix' => '<div class="col-md-3">',
    //   '#type' => 'button',
    //   '#value' => $this->t('Upload'),
    //   '#attributes' => ['class' => ['btn', 'btn-default', 'px-4']],
    //   '#ajax' => [
    //     'callback' => '::ajaxUploadCallback',
    //     'wrapper' => 'picture-preview-wrapper',
    //   ],
    //   '#suffix' => '</div></div>',
    // ];

    // Bio
    $form['profile_section']['container']['bio'] = [
      '#prefix' => '<div class="row"><div class="col-md-12 mb-4">',
      '#type' => 'textarea',
      '#title' => $this->t('Bio'),
      '#required' => TRUE,
      '#default_value' => $existing_profile ? $existing_profile->get('body')->value : '',
      '#attributes' => ['class' => ['form-control'], 'rows' => 4, 'placeholder' => 'Tell us about yourself...'],
      '#suffix' => '</div></div>',
    ];

    // Country
    $form['profile_section']['container']['country'] = [
      '#prefix' => '<div class="row"><div class="col-md-6 mb-4">',
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#required' => TRUE,
      '#options' => $this->getCountryOptions(),
     '#default_value' =>$node->get('field_country')->value,
      '#attributes' => ['class' => ['form-select', 'mb-3']],
      '#suffix' => '</div>',
    ];

    // Portfolio Link
    $form['profile_section']['container']['portfolio_link'] = [
      '#prefix' => '<div class="col-md-6 mb-4">',
      '#type' => 'url',
      '#title' => $this->t('Portfolio Link'),
      '#default_value' => $existing_profile ? $existing_profile->get('field_store_link')->uri : '',
      '#attributes' => ['class' => ['form-control'], 'placeholder' => 'https://yourportfolio.com'],
      '#suffix' => '</div></div>',
    ];

    // YouTube Channel
    $form['profile_section']['container']['youtube_link'] = [
      '#prefix' => '<div class="row"><div class="col-md-6 mb-4">',
      '#type' => 'url',
      '#title' => $this->t('YouTube Channel'),
      '#default_value' => $existing_profile ? $existing_profile->get('field_youtube_channel')->uri : '',
      '#attributes' => ['class' => ['form-control'], 'placeholder' => 'https://youtube.com/youryoutubechannel'],
      '#suffix' => '</div>',
    ];

    // ID Verification
    $default_id_files = [];
    if ($existing_profile && !$existing_profile->get('field_id_verification_image')->isEmpty()) {
      foreach ($existing_profile->get('field_id_verification_image') as $item) {
        $default_id_files[] = $item->target_id;
      }
    }

    $form['profile_section']['container']['id_verification'] = [
      '#prefix' => '<div class="col-md-6 mb-4">',
      '#type' => 'managed_file',
      '#title' => $this->t('ID Verification (Upload)'),
      '#description' => $this->t('Upload Front and Back Images for Verification'),
      '#upload_location' => 'public://tester_id_verification/',
      '#default_value' => $default_id_files,
      '#multiple' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg pdf'],
        'file_validate_size' => [5242880], // 5MB
      ],
      '#attributes' => ['class' => ['form-control']],
      '#suffix' => '</div></div>',
    ];

    // Previous Jobs
    $form['profile_section']['container']['previous_jobs'] = [
      '#prefix' => '<div class="row"><div class="col-md-6 mb-4">',
      '#type' => 'textfield',
      '#title' => $this->t('Provide Previous Jobs'),
      '#default_value' => $existing_profile ? $existing_profile->get('field_external_id')->value : '',
      '#attributes' => ['class' => ['form-control'], 'placeholder' => 'Provide Previous Jobs'],
      '#suffix' => '</div>',
    ];

    // Instagram Account
    $form['profile_section']['container']['instagram_account'] = [
      '#prefix' => '<div class="col-md-6 mb-4">',
      '#type' => 'textfield',
      '#title' => $this->t('Instagram Account'),
      '#default_value' => $existing_profile ? $existing_profile->get('field_testing_type_other')->value : '',
      '#attributes' => ['class' => ['form-control'], 'placeholder' => 'Provide Instagram Account'],
      '#suffix' => '</div></div>',
    ];

    // Facebook Account
    $form['profile_section']['container']['facebook_account'] = [
      '#prefix' => '<div class="row"><div class="col-md-6 mb-4">',
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Account'),
      '#default_value' => $existing_profile ? $existing_profile->get('field_sports_specification')->value : '',
      '#attributes' => ['class' => ['form-control'], 'placeholder' => 'Provide Facebook Account'],
      '#suffix' => '</div>',
    ];

    // X Account
    $form['profile_section']['container']['x_account'] = [
      '#prefix' => '<div class="col-md-6 mb-4">',
      '#type' => 'textfield',
      '#title' => $this->t('X Account'),
      '#default_value' => $existing_profile ? $existing_profile->get('field_specific_regions')->value : '',
      '#attributes' => ['class' => ['form-control'], 'placeholder' => 'Provide X Account'],
      '#suffix' => '</div></div>',
    ];

    // Blog URL
    $form['profile_section']['container']['blog_url'] = [
      '#prefix' => '<div class="row"><div class="col-md-6 mb-4">',
      '#type' => 'url',
      '#title' => $this->t('Blog URL'),
      '#default_value' => $existing_profile ? $existing_profile->get('field_blog_url')->uri : '',
      '#attributes' => ['class' => ['form-control'], 'placeholder' => 'Provide Blog URL'],
      '#suffix' => '</div>',
    ];

    

$work = $this->getTaxonomyTerms('work_type');

$form['profile_section']['container']['work_type'] = [
  '#prefix' => '<div class="col-md-6 mb-4">',
  '#type' => 'select',
  '#title' => $this->t('Work Type'),
  '#options' => $work,
  '#default_value' =>  $node && !$node->get('field_filter')->isEmpty()
    ? [$node->get('field_filter')->target_id]
    : NULL,
    // '#default_value' => get('field_filter')->target_id, 
    // : '',
  '#attributes' => ['class' => ['form-select']],
  '#suffix' => '</div></div>',
];


    // Skills
    $form['profile_section']['container']['skills'] = [
      '#prefix' => '<div class="row"><div class="col-md-6 mb-4">',
      '#type' => 'textfield',
      '#title' => $this->t('Skills'),
      '#default_value' => $existing_profile ? $existing_profile->get('field_licenses_other')->value : '',
      '#attributes' => ['class' => ['form-control'], 'placeholder' => 'E.g. QA Testing, Selenium, Automation'],
      '#suffix' => '</div>',
    ];

 
    $default_language = NULL;

if (!empty($node) && !$node->get('field_language')->isEmpty()) {
  $default_language = $node->get('field_language')->target_id;
}

$languages_spoken_options = $this->getTaxonomyTerms('languages');

$form['profile_section']['container']['languages_known'] = [
  '#prefix' => '<div class="col-md-6 mb-4">',
  '#type' => 'radios',
  '#title' => $this->t('Languages Known'),
  '#options' => $languages_spoken_options,
  '#default_value' => $default_language,
  '#suffix' => '</div></div>',
];


    // Other Language
    $form['profile_section']['container']['other_language'] = [
      '#prefix' => '<div class="row"><div class="col-md-6 mb-4 offset-md-6">',
      '#type' => 'textfield',
      '#title' => $this->t('Other Language'),
      '#default_value' => $node->get('field_platform_other')->value,
      '#attributes' => [
    'class' => ['language-knownr-wrapper', 'js-form-wrapper', 'form-wrapper', 'placeholder' => 'Other language...'],
  ],
      '#suffix' => '</div></div>',
    ];
  $form['nid'] = [
  '#type' => 'hidden',
  '#value' => $node ? $node->id() : NULL,
];

    // Submit Button
    $form['profile_section']['container']['submit'] = [
      '#prefix' => '<div class="row"><div class="col-md-12 my-5 text-center">',
      '#type' => 'submit',
      '#value' => $this->t('Save Profile'),
      '#attributes' => ['class' => ['btn', 'btn-default', 'px-4']],
      '#suffix' => '</div></div>',
    ];
    }
}
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate URLs
    $url_fields = ['portfolio_link', 'youtube_link', 'blog_url'];
    foreach ($url_fields as $field) {
      $value = $form_state->getValue($field);
      if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
        $form_state->setErrorByName($field, $this->t('Please enter a valid URL for @field.', ['@field' => $field]));
      }
    }

    // Validate file uploads
    if (!empty($_FILES['files']['name']['profile_picture'])) {
      $file_info = $_FILES['files']['name']['profile_picture'];
      $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif'];
      $extension = strtolower(pathinfo($file_info, PATHINFO_EXTENSION));
      
      if (!in_array($extension, $allowed_extensions)) {
        $form_state->setErrorByName('profile_picture', $this->t('Only PNG, JPG, JPEG, and GIF files are allowed.'));
      }
    }
  }


public function submitForm(array &$form, FormStateInterface $form_state) {
  $user = $this->currentUser;
 // Get node id (assume you pass it as hidden field in form)
  $nid = $form_state->getValue('nid');
  $node = Node::load($nid);

  if ($node) {
  // -----------------------------
  // 1. Handle file uploads
  // -----------------------------
  // Profile picture (single)
  $profile_picture = $form_state->getValue('profile_picture');
  $profile_picture_fid = NULL;

  if (!empty($profile_picture[0])) {
    $file = File::load($profile_picture[0]);
    if ($file) {
      $file->setPermanent();
      $file->save();
      $profile_picture_fid = $file->id();
    }
  }

  // ID verification (multiple)
  $id_verification_items = [];
  $id_files = $form_state->getValue('id_verification');

  if (!empty($id_files)) {
    foreach ($id_files as $fid) {
      if ($fid) {
        $file = File::load($fid);
        if ($file) {
          $file->setPermanent();
          $file->save();
          $id_verification_items[] = ['target_id' => $fid];
        }
      }
    }
  }

  // -----------------------------
  // 2. Create NEW NODE always
  // -----------------------------
//   $node = Node::create([
//     'type' => 'tester_profile',
//     'uid'  => $user->id(),
//     'status' => 1,
//   ]);

  // -----------------------------
  // 3. Set all fields
  // -----------------------------
  $node->set('title', $form_state->getValue('full_name'));
  $node->set('body', $form_state->getValue('bio'));

  // Profile picture
  if ($profile_picture_fid) {
    $node->set('field_image', [
      'target_id' => $profile_picture_fid,
    ]);
  }

  // ID verification files
  if (!empty($id_verification_items)) {
    $node->set('field_id_verification_image', $id_verification_items);
  }

  // Text fields
  $node->set('field_external_id', $form_state->getValue('previous_jobs'));
  $node->set('field_testing_type_other', $form_state->getValue('instagram_account'));
  $node->set('field_sports_specification', $form_state->getValue('facebook_account'));
  $node->set('field_specific_regions', $form_state->getValue('x_account'));
  $node->set('field_licenses_other', $form_state->getValue('skills'));
 //   $node->set('field_other_language', $form_state->getValue('other_language'));

  // Select fields
  $node->set('field_country', $form_state->getValue('country'));
  $node->set('field_filter', $form_state->getValue('work_type'));
  $node->set('field_platform_other', $form_state->getValue('other_language'));

  // URL fields
  if ($form_state->getValue('portfolio_link')) {
    $node->set('field_store_link', [
      'uri' => $form_state->getValue('portfolio_link'),
    ]);
  }

  if ($form_state->getValue('youtube_link')) {
    $node->set('field_youtube_channel', [
      'uri' => $form_state->getValue('youtube_link'),
    ]);
  }

  if ($form_state->getValue('blog_url')) {
    $node->set('field_blog_url', [
      'uri' => $form_state->getValue('blog_url'),
    ]);
  }

  // Checkbox (languages known)
  $languages = $form_state->getValue('languages_known');
//   $selected_languages = array_filter($languages);
  $node->set('field_language', $languages);

  // -----------------------------
  // SAVE ALWAYS AS NEW NODE
  // -----------------------------
  $node->save();

  $this->messenger()->addMessage($this->t('Profile updated successfully.'));
 }
}



//  public function submitForm(array &$form, FormStateInterface $form_state) {

//   // Current user
//   $user = $this->currentUser;
//   $existing_profile = $this->getExistingTesterProfile();

//   // -----------------------------
//   // 1. Handle file uploads
//   // -----------------------------
//   // Profile picture (single)
//   $profile_picture = $form_state->getValue('profile_picture');
//   $profile_picture_fid = NULL;

//   if (!empty($profile_picture[0])) {
//     $file = File::load($profile_picture[0]);
//     if ($file) {
//       $file->setPermanent();
//       $file->save();
//       $profile_picture_fid = $file->id();
//     }
//   }

//   // ID verification (multiple)
//   $id_verification_items = [];
//   $id_files = $form_state->getValue('id_verification');

//   if (!empty($id_files)) {
//     foreach ($id_files as $fid) {
//       if ($fid) {
//         $file = File::load($fid);
//         if ($file) {
//           $file->setPermanent();
//           $file->save();
//           $id_verification_items[] = ['target_id' => $fid];
//         }
//       }
//     }
//   }

//   // -----------------------------
//   // 2. Create new or update existing profile
//   // -----------------------------
//   if ($existing_profile) {
//     $node = $existing_profile;
//     $message = $this->t('Profile updated successfully.');
//   }
//   else {
//     $node = Node::create([
//       'type' => 'tester_profile',
//       'uid'  => $user->id(),
//       'status' => 1,
//     ]);
//     $message = $this->t('Profile created successfully.');
//   }

//   // -----------------------------
//   // 3. Set all field values
//   // -----------------------------

//   $node->set('title', $form_state->getValue('full_name'));
//   $node->set('body', $form_state->getValue('bio'));

//   // Profile image
//   if ($profile_picture_fid) {
//     $node->set('field_image', [
//       'target_id' => $profile_picture_fid
//     ]);
//   }

//   // ID verification files
//   if (!empty($id_verification_items)) {
//     $node->set('field_id_verification', $id_verification_items);
//   }

//   // Text fields
//   $node->set('field_external_id', $form_state->getValue('previous_jobs'));
//   $node->set('field_testing_type_other', $form_state->getValue('instagram_account'));
//   $node->set('field_sports_specification', $form_state->getValue('facebook_account'));
//   $node->set('field_specific_regions', $form_state->getValue('x_account'));
//   $node->set('field_licenses_other', $form_state->getValue('skills'));
// //   $node->set('field_other_language', $form_state->getValue('other_language'));

//   // Select fields
// //   $node->set('field_country', $form_state->getValue('country'));
// //   $node->set('field_work_type', $form_state->getValue('work_type'));

//   // URL fields
//   if ($form_state->getValue('portfolio_link')) {
//     $node->set('field_store_link', [
//       'uri' => $form_state->getValue('portfolio_link')
//     ]);
//   }

//   if ($form_state->getValue('youtube_link')) {
//     $node->set('field_youtube_channel', [
//       'uri' => $form_state->getValue('youtube_link')
//     ]);
//   }

//   if ($form_state->getValue('blog_url')) {
//     $node->set('field_blog_url', [
//       'uri' => $form_state->getValue('blog_url')
//     ]);
//   }

//   // Languages known (checkbox values)
//   $languages = $form_state->getValue('languages_known');
//   $selected_languages = array_filter($languages);

// //   $node->set('field_languages_known', $selected_languages);

//   // -----------------------------
//   // SAVE THE NODE
//   // -----------------------------
//   $node->save();

//   $this->messenger()->addMessage($message);
// }


  /**
   * Handle file upload.
   */
  private function handleFileUpload($file_ids, $field_name) {
    if (empty($file_ids)) {
      return NULL;
    }

    if ($field_name === 'profile_picture') {
      $file_id = reset($file_ids);
      $file = File::load($file_id);
      if ($file) {
        $file->setPermanent();
        $file->save();
        return $file_id;
      }
    } elseif ($field_name === 'id_verification') {
      $fids = [];
      foreach ($file_ids as $file_id) {
        $file = File::load($file_id);
        if ($file) {
          $file->setPermanent();
          $file->save();
          $fids[] = ['target_id' => $file_id];
        }
      }
      return $fids;
    }

    return NULL;
  }

  /**
   * Get existing tester profile for current user.
   */
  private function getExistingTesterProfile() {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'tester_profile')
      ->condition('uid', $this->currentUser->id())
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    
    if (!empty($nids)) {
      return $this->entityTypeManager->getStorage('node')->load(reset($nids));
    }
    
    return NULL;
  }

//   /**
//    * Get country options.
//    */
//   private function getCountryOptions() {
//     $countries = [
//       '' => $this->t('Select Country'),
//       'USA' => $this->t('USA'),
//       'India' => $this->t('India'),
//       'UK' => $this->t('UK'),
//       'Australia' => $this->t('Australia'),
//       'NewZealand' => $this->t('New Zealand'),
//       'EU' => $this->t('EU'),
//     ];
    
//     return $countries;
//   }


  /**
   * Get country options.
   */
  protected function getCountryOptions() {
    $country_manager = \Drupal::service('country_manager');
    return $country_manager->getList();
  }
  

   /**
   * Get taxonomy terms for a vocabulary.
   *
   * @param string $vocabulary_name
   *   The vocabulary machine name.
   *
   * @return array
   *   Array of term names keyed by term ID.
   */
  protected function getTaxonomyTerms($vocabulary_name) {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vocabulary_name);

    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    // Add "Other" option for radio buttons
    // $options['other'] = $this->t('Other');

    return $options;
  }

  /**
   * AJAX callback for file upload.
   */
  public function ajaxUploadCallback(array &$form, FormStateInterface $form_state) {
    return $form['profile_section']['container']['profile_picture_container']['picture_preview'];
  }
}