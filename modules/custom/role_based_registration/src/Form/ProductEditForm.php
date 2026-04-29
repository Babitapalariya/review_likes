<?php

namespace Drupal\role_based_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductEditForm extends FormBase {

     /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->messenger = $container->get('messenger');
    $instance->fileSystem = $container->get('file_system');
    return $instance;
  }

  public function getFormId() {
    return 'role_based_registration_product_edit_form';
  }

  /**
   * Access check.
   */
  public static function access(AccountInterface $account) {
    $has_merchant_role = in_array('merchant', $account->getRoles());
    $has_permission = $account->hasPermission('create product content');
    // return AccessResult::allowedIf($has_merchant_role && $has_permission)
    //   ->addCacheContexts(['user.roles', 'user.permissions']);
    return AccessResult::allowedIf($has_merchant_role || $has_permission)
  ->addCacheContexts(['user.roles', 'user.permissions']);

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid = NULL) {
 if ($nid) {
    $node = Node::load($nid);
    if ($node) {
    // $node = \Drupal\node\Entity\Node::load($nid);
    // Extract Data Section
    $form['extract_section'] = [
      '#type' => 'fieldset',
      '#attributes' => ['class' => ['mb-4']],
    ];

//     $form['extract_section']['title'] = [
//       '#markup' => '<h3 class="mb-4 text-center">Extract Product Details From Platform</h3>',
//     ];
//   $form['elink'] = [
//   '#type' => 'textfield',
//   '#title' => $this->t('Product URL'),
//    '#attributes' => [ 'placeholder' => 'Provide the link','class' => ['row', 'justify-content-center', 'align-items-baseline', 'mb-3']], 
// //   '#required' => TRUE,
//       '#prefix' => '<div class="row justify-content-center"><div class="col-md-6 mb-3">',
//       '#suffix' => '</div>',
// ];

//     $form['extract_button'] = [
//   '#type' => 'submit',
//   '#value' => $this->t('Extract Data'),
//   '#submit' => ['::redirectToController'],
//   '#limit_validation_errors' => [['elink']],
//   '#attributes' => ['class' => ['btn', 'btn-default']],
//   '#prefix' => '<div class="col-md-2">',
//       '#suffix' => '</div>',
// ];

//     $form['hint'] = [
//       '#markup' => '<span class="g-title text-center"><i>Please provide the Amazon, ebay, flipkart links only to extract product data</i></span>',
//      '#suffix' => '</div>',
//     ];



    // Start platform rows
    if ($form_state->get('platform_items') === NULL) {
      $form_state->set('platform_items', [0]); // start with one row
    }
    $platform_items = $form_state->get('platform_items');

    $form['#tree'] = TRUE;
     $form['platform_items']['title'] = [
      '#markup' => '<h4 class="mb-4 text-center"> Edit Product details</h4>',
    ];


    // === Online Selling Platforms Section ===

    // === Product Fields ===




    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product Title'),
      '#title_display' => 'before',
      '#default_value' => $node ? $node->label() : '',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="row justify-content-center"><div class="col-md-10 mb-3">',
      '#suffix' => '</div>',
      // '#prefix' => '<div class="col-md-5 mb-3">',
    ];

      $form['product_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product URL'),
      #'#default_value' => $extracted_data['product_url'] ?? '',
      '#default_value' => $node && !$node->get('field_product_url_new')->isEmpty()
        ? $node->get('field_product_url_new')->uri
        : '',
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-10 mb-3">',
      '#suffix' => '</div>',
    ];


     $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Product Description'),
      '#title_display' => 'before',
      '#default_value' => $node ? $node->get('body')->value : '',
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control'], 'rows' => 3],
      '#prefix' => '<div class="col-md-5 mb-3">',
      '#suffix' => '</div>',
    ];

    

   /* $form['image'] = [      
  '#type' => 'managed_file',
  '#title' => $this->t('Upload Image'),
  '#title_display' => 'before',
  '#default_value' => $node && !$node->get('field_image')->isEmpty()
    ? [$node->get('field_image')->target_id]
    : NULL,
  '#upload_location' => 'public://products/',
  '#upload_validators' => [
    'file_validate_extensions' => ['png jpg jpeg gif'],
    'file_validate_size' => [2 * 1024 * 1024],
  ],
  '#required' => TRUE,
  '#suffix' => '</div>',
];*/


  // ✅ Multi-image wrapper
$form['images_wrapper'] = [
  '#type' => 'container',
  '#prefix' => '<div id="images-wrapper" class="col-md-5 mb-3">',
  '#suffix' => '</div>',
];

// Load existing images from node
$existing_fids = [];
if ($node && !$node->get('field_image')->isEmpty()) {
  foreach ($node->get('field_image') as $item) {
    $existing_fids[] = $item->target_id;
  }
}

$image_count = $form_state->get('image_count');
if ($image_count === NULL) {
  $image_count = max(1, count($existing_fids));
  $form_state->set('image_count', $image_count);
}

for ($i = 0; $i < $image_count; $i++) {
  $default = !empty($existing_fids[$i]) ? [$existing_fids[$i]] : [];
  $form['images_wrapper']['image'][$i] = [
    '#type' => 'managed_file',
    '#title' => $this->t('Upload Image ') . ($i + 1),
    '#upload_location' => 'public://products/',
    '#upload_validators' => [
      'file_validate_extensions' => ['png jpg jpeg gif svg'],
      'file_validate_size' => [5 * 1024 * 1024],
    ],
    '#default_value' => $default,
  ];
}

$form['images_wrapper']['add_more'] = [
  '#type' => 'submit',
  '#value' => $this->t('Add another image'),
  '#ajax' => [
    'callback' => '::addMoreImagesCallback',
    'wrapper' => 'images-wrapper',
  ],
  '#submit' => ['::addMoreImagesSubmit'],
  '#limit_validation_errors' => [],
];

// Hidden field to persist existing FIDs
$form['images_wrapper']['existing_fids_hidden'] = [
  '#type' => 'hidden',
  '#value' => implode(',', $existing_fids),
  '#name' => 'existing_fids_hidden',
];



$form['product_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product ID'),
      '#title_display' => 'before',
    //   '#default_value' => $form_state->getValue('product_id'),
      '#default_value' => $node ? $node->get('field_legal_business_name')->value : '',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-5 mb-3">',
      '#suffix' => '</div>',
    ];

   

     // Keywords
    $form['keywords'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Keywords'),
      '#title_display' => 'before',
      '#default_value' => $node ? $node->get('field_prices')->value : '',
      '#description' => $this->t('Separate keywords with commas'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control'], 'rows' => 2],
      '#prefix' => '<div class="col-md-5 mb-3">',
      '#suffix' => '</div>',
    ];

    // $form['price'] = [
    //   '#type' => 'number',
    //    '#title' => $this->t('Price'),
    //   '#title_display' => 'before',
    //   '#step' => 0.01,
    //   '#min' => 0,
    //   '#required' => TRUE,
    //   '#attributes' => ['class' => ['form-control', 'mb-3']],
    //   '#prefix' => '<div class="col-md-5 mb-3">',
    //   '#suffix' => '</div>',
    // ];


     // Sold By
    $form['sold_by'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sold By'),
      '#title_display' => 'before',
      '#default_value' => $node ? $node->get('field_sold_bys')->value : '',
    //   '#target_type' => 'user',
    //   '#default_value' => $user_entity,
      '#required' => TRUE,
    //   '#selection_settings' => ['include_anonymous' => FALSE],
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-5 mb-3">',
      '#suffix' => '</div>',
    ];


    // // Rating
    // $form['rating'] = [
    //   '#type' => 'select',
    //   '#title' => $this->t('Rating'),
    //   '#title_display' => 'before',
    //   '#options' => [
    //     '1' => $this->t('1 Star'),
    //     '2' => $this->t('2 Stars'),
    //     '3' => $this->t('3 Stars'),
    //     '4' => $this->t('4 Stars'),
    //     '5' => $this->t('5 Stars'),
    //   ],
    //   '#required' => TRUE,
    //   '#attributes' => ['class' => ['form-control', 'mb-3']],
    //   '#prefix' => '<div class="col-md-5 mb-3">',
    //   '#suffix' => '</div>',
    // ];

    // Number of tests done
    $form['tests_done'] = [
      '#type' => 'number',
      '#title' => $this->t('No.of tests done'),
      '#title_display' => 'before',
      '#min' => 0,
      '#required' => TRUE,
      '#default_value' => $node ? $node->get('field_tests_done')->value : '',
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-5 mb-3">',
      '#suffix' => '</div> ',
    ];
        // Add this to your form building function
        $form['testified_video'] = [
        '#type' => 'radios',
        '#title' => $this->t('Do Testified video of your product?'),
        '#title_display' => 'before',
        '#options' => [
            '1' => $this->t('Yes'),
            '0' => $this->t('No'),
        ],
     '#default_value' => $node && !$node->get('field_testified_video')->isEmpty()
    ? (string) $node->get('field_testified_video')->value  // cast to string
    : '',  // fallback to "No"
        '#required' => TRUE,
        '#prefix' => '<div class="col-md-5 mb-3">',
        '#suffix' => '<div class="my-2 text-danger">' . $this->t('If yes, you may have to add your video in Add own product video') . '</div></div>',
        // '#attributes' => [
        //     'class' => ['d-flex', 'align-items-center', 'gap-3'],
        // ],
        ];


$form['product_variation'] = [
  '#type' => 'radios',
  '#title' => $this->t('Is this product has Variation?'),
  '#options' => [
    '1' => $this->t('Yes'),
    '0' => $this->t('No'),
  ],
  '#default_value' => $node && !$node->get('field_product_variation')->isEmpty()
    ? (string) $node->get('field_product_variation')->value  // cast to string
    : '0',  // fallback to "No"
  '#required' => TRUE,
  '#prefix' => '<div class="col-md-5 mb-3">',
  '#suffix' => '</div> </div>',
];



        // // Add this to your form building function
        // $form['product_variation'] = [
        // '#type' => 'radios',
        // '#title' => $this->t('Is this product has Variation?'),
        // '#options' => [
        //     '1' => $this->t('Yes'),
        //     '0' => $this->t('No'),
        // ],
        // '#default_value' => $node ? $node->get('field_product_variation')->value : '',
        // '#required' => TRUE,
        // '#prefix' => '<div class="col-md-5 mb-3">',
        // '#suffix' => '</div> </div>',
        // // '#attributes' => [
        // //     'class' => ['d-flex', 'align-items-center', 'gap-3'],
        // // ],
        // ];
   $form['nid'] = [
  '#type' => 'hidden',
  '#value' => $node ? $node->id() : NULL,
];



$form['platforms_wrapper'] = [
  '#type' => 'container',
  '#attributes' => ['id' => 'platforms-wrapper'],
  '#prefix' => '<div id="platforms-wrapper"> <div class="row justify-content-center"> <div class="col-md-10"><div class="mb-3 adminplatform"> <div class="platform-wrapper">',
  '#suffix' => '</div> </div> </div> </div></div>',
];
$platform_items = [];
 
$platform_items = $form_state->get('platform_items_data');

if ($platform_items === NULL) {
  // First load — read from node
  $platform_items = [];
  if ($node && !$node->get('field_platform_link')->isEmpty()) {
    foreach ($node->get('field_platform_link')->referencedEntities() as $paragraph) {
      $platform_items[] = [
        'link'            => $paragraph->get('field_online_selling_platforms')->uri,
        'price'           => $paragraph->get('field_platform_price')->value,
        'rating'          => $paragraph->get('field_rating')->value,
        'coupon'          => $paragraph->get('field_coupon_code')->value,
        'date'            => $paragraph->get('field_coupon_expiry_date')->value,
        'affliate'        => $paragraph->get('field_affliate_link')->uri,
        'affiliate_toggle'=> !empty($paragraph->get('field_affliate_link')->value),
      ];
    }
  }
  if (empty($platform_items)) {
    $platform_items[] = [];
  }
  // Save to form state so it persists across rebuilds
  $form_state->set('platform_items_data', $platform_items);
}

// If no items exist, initialize at least one empty row
 
foreach ($platform_items as $delta => $item) {
  // Create a row container for each platform
  $form['platforms_wrapper']['platforms'][$delta] = [
    '#type' => 'container',
    '#attributes' => ['class' => ['row', 'mb-2', 'platform-row']],
    '#prefix' => '<div class="row platform-wrapper mt-3">',
    '#suffix' => '</div>',
  ];
    // $form['platforms_wrapper']['platforms'][$delta]['title'] = [
    //   '#markup' => '<h5 class="required mb-3">Online Selling Platforms (links)</h5>',
    //   '#title_display' => 'after',
    // ];

  // Link field
    $form['platforms_wrapper']['platforms'][$delta]['link'] = [
      '#type' => 'textfield',
      // '#title' => $this->t('Online Selling Platforms (links)'),
      '#required' => TRUE,
      '#attributes' => ['placeholder' => 'add more link same product', 'class' => ['form-control me-4']],
      '#default_value' => $item['link'] ?? '',
      '#prefix' => '<div class="col-md-5 mb-3"><h5 class="required mb-1">Online Selling Platforms (links)</h5>',
      '#suffix' => '</div>',
    ];

  // Price field
    $form['platforms_wrapper']['platforms'][$delta]['price'] = [
      '#type' => 'textfield',
      //  '#title' => $this->t('Price'),
      '#attributes' => ['placeholder' => 'Price', 'class' => ['form-control me-4', 'rating-field']],
      '#required' => TRUE,
       '#default_value' => $item['price'] ?? '',
      '#prefix' => '<div class="col-md-2 mb-3"><h5 class="required mb-1">Price</h5>',
      '#suffix' => '</div>',
    ];

   // Rating field
      $form['platforms_wrapper']['platforms'][$delta]['rating'] = [
        '#type' => 'textfield',
        // '#title' => $this->t('Rating'),
        '#attributes' => ['placeholder' => 'Rating', 'class' => ['form-control me-4', 'rating-field']],
        '#required' => TRUE,
       '#default_value' => $item['rating'] ?? '',
        '#prefix' => '<div class="col-md-2 mb-3"><h5 class="required mb-1">Rating</h5>',
        '#suffix' => '</div>',
      ];

      // Coupon field
      $form['platforms_wrapper']['platforms'][$delta]['coupon'] = [
        '#type' => 'textfield',
        //  '#title' => $this->t('Coupon'),
        '#attributes' => ['placeholder' => 'Coupon Code', 'class' => ['form-control me-4']],
        // '#required' => TRUE,
        '#default_value' => $item['coupon'] ?? '',
        '#prefix' => '<div class="col-md-2 mb-3"><h5 class="required mb-1">Coupon Code</h5>',
        '#suffix' => '</div>',
      ];


  // Date field
        $form['platforms_wrapper']['platforms'][$delta]['date'] = [
        '#type' => 'date',
        '#title' => $this->t('Coupon Expiry Date'),
        '#attributes' => ['class' => ['form-control']],
        '#default_value' => !empty($item['date']) ? date('Y-m-d', strtotime($item['date'])) : '',
         '#required' => TRUE,
        '#prefix' => '<div class="col-md-3 mb-3">',
        '#suffix' => '</div>',
        ];
// Check if affiliate value exists (TRUE if not empty)
$has_affiliate_value = !empty($item['affliate']);

// Get toggle value from form state (AJAX-safe)
$affiliate_toggle = $form_state->getValue(
  ['platforms_wrapper', 'platforms', $delta, 'affiliate_toggle'],
  $item['affiliate_toggle'] ?? FALSE
);

// Final decision: enable field if either is TRUE
$affiliate_enabled = $affiliate_toggle || $has_affiliate_value;

// Affiliate Link field
$form['platforms_wrapper']['platforms'][$delta]['affliate'] = [
  '#type' => 'textfield',
  '#attributes' => [
    'placeholder' => 'Affiliate Link', 
    'class' => ['form-control', 'me-4', 'affiliate-field'],
    'id' => 'paffliatelink-' . $delta,
    'disabled' =>!$affiliate_enabled,
  ],
  '#required' => $affiliate_toggle,
  '#default_value' => $item['affliate'] ?? '',
  '#prefix' => '<div class="col-md-5 mb-3" id="affiliate-field-wrapper-' . $delta . '"><h5 class="mb-1">Affiliate Link</h5>',
  '#suffix' => '</div>',
];




// Toggle switch for affiliate link - using custom markup
$form['platforms_wrapper']['platforms'][$delta]['affiliate_toggle'] = [
  '#type' => 'checkbox',
  // '#title' => $this->t('Enable Affiliate Link'),
 '#default_value' => $item['affiliate_toggle'] ?? FALSE,
  '#attributes' => [
    'class' => ['affiliateToggle'],
    'data-target' => 'paffliatelink-' . $delta,
    'data-delta' => $delta,
  ],
  '#prefix' => '<div class="col-md-1 mb-3 linktoggle-btn"><div class="toggle-wrapper mt-3"><label class="">',
  '#suffix' => '<span class="slider"></span></label></div></div>',
  '#ajax' => [
    'callback' => [$this, 'affiliateToggleCallback'],
    'wrapper' => 'affiliate-field-wrapper-' . $delta,
    'event' => 'change',
  ],
  // Hide the default label and use custom structure
  '#title_display' => 'invisible',
];

  // Remove button (only if more than one row)
  if (count($platform_items) > 1) {
    $form['platforms_wrapper']['platforms'][$delta]['remove'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove'),
      '#submit' => ['::removePlatformSubmit'],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'platforms-wrapper',
      ],
      '#name' => 'remove-' . $delta,
      '#limit_validation_errors' => [],
      '#attributes' => ['class' => ['button--danger', 'remove-platform-btn', 'btn danger remove-btn']],
      '#prefix' => '<div class="col-md-1">',
      '#suffix' => '</div>',
    ];
  } else {
    // Add empty column for alignment when no remove button
    $form['platforms_wrapper']['platforms'][$delta]['empty'] = [
      '#markup' => '<div class="col-md-1"></div>',
    ];
  }
}

// Add button
$form['platforms_wrapper']['add'] = [
  '#type' => 'submit',
  '#value' => $this->t('+ Add Another Link of Same Product from other Market Place'),
  '#submit' => ['::addPlatformSubmit'],
  '#ajax' => [
    'callback' => '::ajaxCallback',
    'wrapper' => 'platforms-wrapper',
  ],
  '#limit_validation_errors' => [],
  '#attributes' => ['class' => ['button--primary', 'add-platform-btn', 'btn addPlatformBtn mt-2']],
];

// Hint text
$form['platforms_wrapper']['hint'] = [
  '#markup' => '<div class="row"><div class="col-md-12 text-center"><span class="hint mt-2 mb-3">Add up to 20 Platform Links you operate under.</span></div></div>',
  '#prefix' => '<div class="row"><div class="col-md-12 text-center">',
  '#suffix' => '</div></div>',
];




// Submit button
    $form['actions'] = [
      '#type' => 'actions',
      '#prefix' => '<div class="col-md-12 text-center my-4">',
      '#suffix' => '</div>',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Product'),
      '#attributes' => ['class' => ['btn', 'btn-default']],
    ];

    // Attach library for styling
    $form['#attached']['library'][] = 'role_based_registration/product_form';
    // $form['#attached']['library'][] = 'role_based_registration/registration_form';
}
 }

 $form['#attached']['library'][] = 'role_based_registration/registration_form';
    return $form;
  }

  /**
 * AJAX callback for the affiliate toggle.
 */
public function affiliateToggleCallback(array &$form, FormStateInterface $form_state) {
  $triggering_element = $form_state->getTriggeringElement();
  $delta = $triggering_element['#attributes']['data-delta'];
  
  // Return the updated affiliate field
  return $form['platforms_wrapper']['platforms'][$delta]['affliate'];
}

  /**
   * Add row.
   */
  /*public function addPlatformSubmit(array &$form, FormStateInterface $form_state) {
    $platform_items = $form_state->get('platform_items');
    $new_delta = empty($platform_items) ? 0 : (max($platform_items) + 1);
    $platform_items[] = $new_delta;
    $form_state->set('platform_items', $platform_items);
    $form_state->setRebuild(TRUE);
  }*/

  public function addPlatformSubmit(array &$form, FormStateInterface $form_state) {
  $platform_items = $form_state->get('platform_items_data') ?? [[]];
  
  // Add a new empty row
  $platform_items[] = [];
  
  $form_state->set('platform_items_data', $platform_items);
  $form_state->setRebuild(TRUE);
}

  /**
   * Remove row.
   */
  /*public function removePlatformSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $name = $trigger['#name']; // e.g. remove-2
    $delta = str_replace('remove-', '', $name);
    $platform_items = $form_state->get('platform_items');
    
    // Remove the item and reindex the array
    if (($key = array_search((int) $delta, $platform_items)) !== FALSE) {
      unset($platform_items[$key]);
      $platform_items = array_values($platform_items); // Reindex
    }
    
    $form_state->set('platform_items', $platform_items);
    $form_state->setRebuild(TRUE);
  }*/



public function removePlatformSubmit(array &$form, FormStateInterface $form_state) {
  $trigger = $form_state->getTriggeringElement();
  $name = $trigger['#name'];
  $delta = (int) str_replace('remove-', '', $name);
  
  $platform_items = $form_state->get('platform_items_data') ?? [[]];
  
  if (isset($platform_items[$delta])) {
    unset($platform_items[$delta]);
    $platform_items = array_values($platform_items);
  }
  
  $form_state->set('platform_items_data', $platform_items);
  $form_state->setRebuild(TRUE);
}
  /**
   * Ajax callback.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['platforms_wrapper'];
  }

  /**
   * Submit form.
   */
public function addMoreImagesSubmit(array &$form, FormStateInterface $form_state) {
  $image_count = $form_state->get('image_count');
  if ($image_count < 6) {
    $form_state->set('image_count', $image_count + 1);
  }
  $form_state->setRebuild(TRUE);
}

public function addMoreImagesCallback(array &$form, FormStateInterface $form_state) {
  return $form['images_wrapper'];
}



public function submitForm(array &$form, FormStateInterface $form_state) {
  $user = \Drupal::currentUser();


// ✅ Read FIDs from raw POST
$all_post = \Drupal::request()->request->all();

// Because #tree = TRUE, images_wrapper is at root level in POST
$images_post = $all_post['images_wrapper']['image'] ?? [];

$fids = [];
foreach ($images_post as $index => $image_field) {
  if (!is_array($image_field)) {
    continue;
  }
  if (isset($image_field['fids']) && $image_field['fids'] !== '' && $image_field['fids'] !== '0') {
    $fid = (int) $image_field['fids'];
    if ($fid > 0) {
      $fids[] = $fid;
    }
  }
}

// ✅ Also try form_state values (works for manually uploaded images)
if (empty($fids)) {
  $images_raw = $form_state->getValue(['images_wrapper', 'image']) ?? [];
  foreach ($images_raw as $index => $image_field) {
    if (isset($image_field['fids'])) {
      $fid = (int)(is_array($image_field['fids']) ? $image_field['fids'][0] : $image_field['fids']);
      if ($fid > 0) {
        $fids[] = $fid;
      }
    }
    elseif (!empty($image_field[0])) {
      $fids[] = (int) $image_field[0];
    }
  }
}

// ✅ Fallback to hidden field
if (empty($fids)) {
  $hidden_val = $all_post['existing_fids_hidden'] ?? '';
  if (!empty($hidden_val)) {
    $fids = array_values(
      array_filter(array_map('intval', explode(',', $hidden_val)))
    );
  }
}

// ✅ Validate FIDs exist in DB
$fids = array_values(array_filter($fids, function($fid) {
  return $fid > 0 && File::load($fid);
}));

// ✅ Make permanent
foreach ($fids as $fid) {
  $file = File::load($fid);
  if ($file && !$file->isPermanent()) {
    $file->setPermanent();
    $file->save();
  }
}

// ✅ Build field array
$field_images = [];
foreach ($fids as $fid) {
  $field_images[] = [
    'target_id' => $fid,
    'alt'        => 'Product Image',
    'title'      => 'Product Image',
  ];
}






  // Get node id (assume you pass it as hidden field in form)
  $nid = $form_state->getValue('nid');
  $node = Node::load($nid);

  if ($node) {
    // Handle image
     

    // Update fields
    $node->setTitle($form_state->getValue('title'));
    $node->set('body', [
      'value' => $form_state->getValue('description'),
      'format' => 'plain_text',
    ]);
    $node->set('field_legal_business_name', $form_state->getValue('product_id'));
    $node->set('field_sold_bys', $form_state->getValue('sold_by'));
    $node->set('field_tests_done', $form_state->getValue('tests_done'));
   # $node->set('field_image', $fid ? ['target_id' => $fid] : NULL);
    $node->set('field_image', $field_images);
    $node->set('field_product_variation', $form_state->getValue('product_variation'));
    $node->set('field_testified_video', $form_state->getValue('testified_video'));
    $node->set('field_prices', [
      'value' => $form_state->getValue('keywords'),
      'format' => 'plain_text',
    ]);
    $node->set('field_product_url_new' , [
      'uri' => $form_state->getValue('product_url'),
    ]);

    // Replace old paragraphs (optional: clear before saving new ones)
    $node->get('field_platform_link')->setValue([]);


    $values = $form_state->getValue(['platforms_wrapper', 'platforms']);


    if (!empty($values)) {


      foreach ($values as $item) {
  if (!empty($item['link']) || !empty($item['price']) || !empty($item['coupon'])) {
    
    $link = trim($item['link'] ?? '');
    $title = 'View Product';

    // ✅ Dynamic platform detection — same as ProductForm
    $host = parse_url($link, PHP_URL_HOST);
    $host = strtolower($host ?? '');
    $host = str_replace('www.', '', $host);

    if (strpos($host, 'amazon.') !== FALSE) {
      $title = 'Amazon';
    }
    elseif (strpos($host, 'flipkart.') !== FALSE) {
      $title = 'Flipkart';
    }
    elseif (strpos($host, 'ebay.') !== FALSE) {
      $title = 'eBay';
    }
    elseif (strpos($host, 'meesho.') !== FALSE) {
      $title = 'Meesho';
    }
    elseif (strpos($host, 'myntra.') !== FALSE) {
      $title = 'Myntra';
    }
    elseif (!empty($host)) {
      $parts = explode('.', $host);
      $title = ucfirst($parts[0]);
    }

    $paragraph = Paragraph::create([
      'type' => 'platform_link',
      'field_online_selling_platforms' => [
        'uri' => $link,
        'title' => $title,
      ],
      'field_platform_price' => $item['price'] ?? '',
      'field_coupon_code' => $item['coupon'] ?? '',
      'field_rating' => $item['rating'] ?? '',
      'field_affliate_link' => !empty($item['affliate']) ? [
        'uri' => $item['affliate'],
      ] : NULL,
      'field_coupon_expiry_date' => $item['date'] ?? '',
    ]);
    $paragraph->save();
    $node->get('field_platform_link')->appendItem($paragraph);
  }
}



    }






    $node->save();
    $this->messenger()->addMessage($this->t('Product %title updated successfully.', ['%title' => $node->label()]));
  }
  else {
    $this->messenger()->addError($this->t('Node not found.'));
  }

  #$form_state->setRedirect('<current>');
  $url = \Drupal\Core\Url::fromUserInput('/merchant-dashboard/all-products');
$form_state->setRedirectUrl($url);


}


//   public function submitForm(array &$form, FormStateInterface $form_state) {
//     $user = \Drupal::currentUser();

//     // Handle image
//     $image = $form_state->getValue('image');
//     $fid = NULL;
//     if (!empty($image[0])) {
//       $file = File::load($image[0]);
//       $file->setPermanent();
//       $file->save();
//       $fid = $file->id();
//     }
//     // Collect values
//   $keywords = $form_state->getValue('keywords');
//   $platforms = $form_state->getValue('platforms');

//     // Create node
//     $node = Node::create([
//       'type' => 'marchant_products',
//       'title' => $form_state->getValue('title'),
//     'body' => [
//       'value' => $form_state->getValue('description'),
//       'format' => 'plain_text',
//     ],
//     'field_legal_business_name' => $form_state->getValue('product_id'),
//     'field_sold_bys' => $form_state->getValue('sold_by'),
//     'field_tests_done' => $form_state->getValue('tests_done'),
//     // 'field_price' => $form_state->getValue('price'),
//     'field_image' => $fid ? ['target_id' => $fid] : NULL,
//     // 'field_rating' => $form_state->getValue('rating'),
//     'field_product_variation' => $form_state->getValue('product_variation'),
//     'field_testified_video' => $form_state->getValue('testified_video'),
    
    
    
//     'field_prices' => [
//       'value' => $keywords,
//       'format' => 'plain_text',
//     ],
//     'uid' => $user->id(),
//     'status' => 1,
//     ]);

//     // Save platforms as paragraphs
//     $values = $form_state->getValue(['platforms_wrapper', 'platforms']);
//     if (!empty($values)) {
//       foreach ($values as $item) {
//         // Only create paragraphs for items with data
//         if (!empty($item['link']) || !empty($item['price']) || !empty($item['coupon'])) {
//           $paragraph = Paragraph::create([
//             'type' => 'platform_link',
//             'field_platform_link' => $item['link'] ?? '',
//             'field_platform_price' => $item['price'] ?? '',
//             'field_coupon_code' => $item['coupon'] ?? '',
//             'field_rating' => $item['rating'] ?? '',
//             'field_affliate_link' => $item['affliate'] ?? '',
//             'field_coupon_expiry_date' => $item['date'] ?? '',
            
            
            
//           ]);
//           $paragraph->save();
//           $node->get('field_platform_link')->appendItem($paragraph);
//         //   $entity->field_coupon_expiry_date->value = $platform['date'];
//         }
//       }
//     }

//     $node->save();
//     $this->messenger()->addMessage($this->t('Product %title created successfully.', ['%title' => $node->label()]));
//      $form_state->setRedirect('<current>');
//   }

 public function redirectToController(array &$form, FormStateInterface $form_state) {
  $url = $form_state->getValue('elink');   // ✅ must match the field name

  if (!empty($url)) {
    $form_state->setRedirect(
      'role_based_registration.extract_product',
      [],
      ['query' => ['url' => $url]]
    );
    \Drupal::logger('debug')->notice('<pre>@data</pre>', ['@data' => print_r($form_state->getValues(), TRUE)]);

  }
  else {
    $this->messenger()->addError($this->t('Please enter a product URL.'));
  }
}
 /**
   * Get user's primary role.
   */
  protected function getUserPrimaryRole(UserInterface $user) {
    $roles = $user->getRoles();
    $roles = array_diff($roles, ['authenticated']);
    return !empty($roles) ? reset($roles) : 'authenticated';
  }
  

}