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
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\HtmlCommand;

class ProductForm extends FormBase {

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
    return 'role_based_registration_product_form';
  }

  /**
   * Access check.
   */
  public static function access(AccountInterface $account) {
    $has_merchant_role = in_array('merchant', $account->getRoles());
    $has_permission = $account->hasPermission('create product content');
    return AccessResult::allowedIf($has_merchant_role && $has_permission)
      ->addCacheContexts(['user.roles', 'user.permissions']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {


$session = \Drupal::request()->getSession();
$extracted_data = $session->get('extracted_product_data');

// Optional: clear after reading
$session->remove('extracted_product_data');


/*$image_count = $form_state->get('image_count');
if ($image_count === NULL) {
  $image_count = 1;
  $form_state->set('image_count', 1);
}
*/



$image_count = $form_state->get('image_count');

if ($image_count === NULL) {
  $image_count = 1;
}

// ✅ FIX: update BEFORE loop
#if (!empty($extracted_data['image_urls'])) {
if ($form_state->get('stored_fids') === NULL && !empty($extracted_data['image_urls'])) {
  $image_count = min(count($extracted_data['image_urls']), 6);
  $form_state->set('image_count', $image_count);
}



$form['#prefix'] = '<div id="product-form-wrapper">';
$form['#suffix'] = '</div>';






 $form['extract_wrapper'] = [
  '#type' => 'container',
  '#attributes' => [
    'class' => ['row', 'justify-content-center', 'align-items-baseline', 'mb-3']
  ],
];

 $form['extract_wrapper']['title'] = [
      '#markup' => '<h3 class="mb-4 text-center">Add Product</h3>',
    ];

$form['extract_wrapper']['elink'] = [
  '#type' => 'textfield',
  '#attributes' => [
    'placeholder' => 'Provide marketplace URL where you want to extract product details',
    'class' => ['form-control', 'py-3'],
    'id' => 'elink',
  ],
  '#prefix' => '<div class="row justify-content-center align-items-baseline mb-3"><div class="col-md-8 mb-3">',
  '#suffix' => '<span class="g-title text-center"><i>Note: Only Amazon, Ebay link can be use to extract data</i></span></div>',
];

$form['extract_wrapper']['extract_button'] = [
  '#type' => 'submit',
  '#value' => $this->t('Extract Data'),
  '#submit' => ['::redirectToController'],
  '#limit_validation_errors' => [['elink']],
  '#attributes' => [
    'class' => ['btn', 'btn-default'],
    'id' => 'extractDataBtn',
  ],
  '#prefix' => '<div class="col-md-2">',
  '#suffix' => '</div></div>',
];





 

    // Start platform rows
    $form['productDetails'] = [
  '#type' => 'container',
  '#attributes' => [
    'id' => 'productDetailsSection',
    'style' => 'display:block;',
  ],
  '#prefix' => '<div id="productDetails"  >
                        <div class="row justify-content-center mt-3">',
  '#suffix' => '</div></div>',
];

 

    // === Product Fields ===




    $form['productDetails']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product Title'),
      '#default_value' => $extracted_data['title'] ?? '',
      '#title_display' => 'before',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-10 mb-3">',
      '#suffix' => '</div>',
      // '#prefix' => '<div class="col-md-5 mb-3">',
    ];

    $form['productDetails']['product_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product URL'),
      '#default_value' => $extracted_data['product_url'] ?? '',
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-10 mb-3">',
      '#suffix' => '</div>',
    ];

    $form['productDetails']['product_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product ID'),
      '#title_display' => 'before',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-5 mb-3">',
      '#suffix' => '</div>',
    ];

    

    /*$form['productDetails']['image'] = [      
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Image'),
      '#title_display' => 'before',
      '#upload_location' => 'public://products/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif'],
        'file_validate_size' => [2 * 1024 * 1024],
      ],
      '#required' => TRUE,
      '#suffix' => '</div>',
    ];
*/




// ===============================
// ✅ Image fields – single source of truth with logging
// ===============================
$form['productDetails']['images_wrapper'] = [
  '#type' => 'container',
  '#prefix' => '<div id="images-wrapper" class="col-md-5 mb-3">',
  '#suffix' => '</div>',
];

$max_images = 6;
$image_count = $form_state->get('image_count');
if ($image_count === NULL) {
  $image_count = 1;
}

$stored_fids = $form_state->get('stored_fids') ?? [];

// Download extracted images only once (when fresh data is present and no FIDs stored)
if (!empty($extracted_data['image_urls']) && empty($stored_fids)) {
  $downloaded_fids = [];
  $i = 0;
  foreach ($extracted_data['image_urls'] as $image_url) {
    if ($i >= $max_images) break;
    // Skip empty URLs
    if (empty($image_url)) continue;
    
    $imageData = @file_get_contents($image_url);
    if ($imageData === FALSE) {
      \Drupal::logger('product_form')->warning('Failed to download image: @url', ['@url' => $image_url]);
      continue;
    }
    
    // Generate a unique filename to avoid collisions
    $filename = 'product_' . time() . '_' . $i . '_' . basename(parse_url($image_url, PHP_URL_PATH));
    $file = \Drupal::service('file.repository')->writeData(
      $imageData,
      'public://products/' . $filename,
      FileSystemInterface::EXISTS_REPLACE
    );
    if ($file) {
      $file->setPermanent();
      $file->save();
      $downloaded_fids[$i] = $file->id();
      \Drupal::logger('product_form')->notice('Downloaded image @i, FID: @fid', ['@i' => $i, '@fid' => $file->id()]);
      $i++;
    }
  }
  
  if (!empty($downloaded_fids)) {
    $stored_fids = $downloaded_fids;
    $form_state->set('stored_fids', $stored_fids);
    $image_count = count($stored_fids);
    $form_state->set('image_count', $image_count);
  }
  else {
    // Fallback: no images downloaded, keep default 1 empty field
    $image_count = 1;
    $form_state->set('image_count', $image_count);
  }
}

// Build the image fields using stored FIDs (extracted or from previous uploads)
for ($i = 0; $i < $image_count; $i++) {
  $default = [];
  if (isset($stored_fids[$i])) {
    $default = [$stored_fids[$i]];
  }
  $form['productDetails']['images_wrapper']['image'][$i] = [
    '#type' => 'managed_file',
    '#title' => $this->t('Upload Image ') . ($i + 1),
    '#upload_location' => 'public://products/',
    '#upload_validators' => [
      'file_validate_extensions' => ['png jpg jpeg gif svg'],
      'file_validate_size' => [5 * 1024 * 1024],
    ],
    '#default_value' => $default,
    '#parents' => ['productDetails', 'images_wrapper', 'image', $i],
  ];
}

$form['productDetails']['images_wrapper']['add_more'] = [
  '#type' => 'submit',
  '#value' => $this->t('Add another image'),
  '#ajax' => [
    'callback' => '::addMoreImagesCallback',
    'wrapper' => 'images-wrapper',
  ],
  '#submit' => ['::addMoreImagesSubmit'],
  '#limit_validation_errors' => [],
];
 

// ✅ Persist stored_fids across submit via hidden field
// Change this in buildForm():
$form['productDetails']['images_wrapper']['stored_fids_hidden'] = [
  '#type' => 'hidden',
  '#value' => implode(',', array_values($stored_fids)),
  // ✅ Force it to render at root level with a predictable name
  '#name' => 'stored_fids_hidden',
];











    $form['productDetails']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Product Description'),
      '#default_value' => $extracted_data['description'] ?? '',
      '#title_display' => 'before',
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control'], 'rows' => 3],
      '#prefix' => '<div class="col-md-5 mb-3">',
      '#suffix' => '</div>',
    ];

     // Keywords
    $form['productDetails']['keywords'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Keywords'),
      '#title_display' => 'before',
      '#description' => $this->t('Separate keywords with commas'),
      #'#required' => TRUE,
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
    $form['productDetails']['sold_by'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sold By'),
      '#title_display' => 'before',
      '#default_value' => $extracted_data['sold_by'] ?? '',
    //   '#target_type' => 'user',
    //   '#default_value' => $user_entity,
      '#required' => TRUE,
    //   '#selection_settings' => ['include_anonymous' => FALSE],
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-5 mb-3">',
      '#suffix' => '</div>',
    ];


    // Number of tests done
    $form['productDetails']['tests_done'] = [
      '#type' => 'number',
      '#title' => $this->t('No.of tests done'),
      '#title_display' => 'before',
      '#min' => 0,
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control', 'mb-3']],
      '#prefix' => '<div class="col-md-5 mb-3">',
      '#suffix' => '</div> ',
    ];
        // Add this to your form building function
        $form['productDetails']['testified_video'] = [
        '#type' => 'radios',
        '#title' => $this->t('Do Testified video of your product?'),
        '#title_display' => 'before',
        '#options' => [
            '1' => $this->t('Yes'),
            '0' => $this->t('No'),
        ],
        // '#default_value' => $form_state->getValue('testified_video', 'no'),
       '#required' => TRUE,
        '#prefix' => '<div class="col-md-5 mb-3">',
        '#suffix' => '<div class="my-2 text-danger">' . $this->t('If yes, you may have to add your video in Add own product video') . '</div></div>',
        // '#attributes' => [
        //     'class' => ['d-flex', 'align-items-center', 'gap-3'],
        // ],
        ];


        // Add this to your form building function
        $form['productDetails']['product_variation'] = [
        '#type' => 'radios',
        '#title' => $this->t('Is this product has Variation?'),
        '#options' => [
            '1' => $this->t('Yes'),
            '0' => $this->t('No'),
        ],
        // '#default_value' => $form_state->getValue('product_variation', 'no'),
        '#required' => TRUE,
        '#prefix' => '<div class="col-md-5 mb-3">',
        '#suffix' => '</div> </div>',
        // '#attributes' => [
        //     'class' => ['d-flex', 'align-items-center', 'gap-3'],
        // ],
        ];
   


    // === Online Selling Platforms Section ===
$form['productDetails']['platforms_wrapper'] = [
  '#type' => 'container',
  '#attributes' => ['id' => 'platforms-wrapper'],
 
  #'#prefix' => '<div id="productDetailsSection" style="display: block;"><div class="row justify-content-center mt-3"><div class="row justify-content-center"><div id="platforms-wrapper"> <div class="row justify-content-center"> <div class="col-md-10"><div class="mb-3 adminplatform"> <div class="platform-wrapper">',
  '#prefix' => '<div class="row justify-content-center"><div id="platforms-wrapper"> <div class="row justify-content-center"> <div class="col-md-10"><div class="mb-3 adminplatform"> <div class="platform-wrapper">',
  '#suffix' => '</div> </div> </div> </div></div>',
];

$platform_items = $form_state->get('platform_items');

if (empty($platform_items)) {
  $platform_items = [0]; // default first row
  $form_state->set('platform_items', $platform_items);
}

foreach ($platform_items as $delta) {
  // Create a row container for each platform
  $form['productDetails']['platforms_wrapper']['platforms'][$delta] = [
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
    $form['productDetails']['platforms_wrapper']['platforms'][$delta]['link'] = [
      '#type' => 'textfield',
      // '#title' => $this->t('Online Selling Platforms (links)'),
      '#required' => TRUE,
      '#attributes' => ['placeholder' => 'add more link same product', 'class' => ['form-control me-4']],
      '#default_value' => $form_state->getValue(['productDetails','platforms_wrapper','platforms',$delta,'link']),
      '#prefix' => '<div class="col-md-5 mb-3"><h5 class="required mb-1">Online Selling Platforms (links)</h5>',
      '#suffix' => '</div>',
      '#parents' => ['productDetails','platforms_wrapper','platforms',$delta,'link'],
    ];

  // Price field
    $form['productDetails']['platforms_wrapper']['platforms'][$delta]['price'] = [
      '#type' => 'textfield',
      //  '#title' => $this->t('Price'),
      '#attributes' => ['placeholder' => 'Price', 'class' => ['form-control me-4', 'rating-field']],
      '#required' => TRUE,
      '#default_value' => $form_state->getValue(['productDetails','platforms_wrapper','platforms',$delta,'price']),
      '#prefix' => '<div class="col-md-2 mb-3"><h5 class="required mb-1">Price</h5>',
      '#suffix' => '</div>',
      '#parents' => ['productDetails','platforms_wrapper','platforms',$delta,'price'],
    ];

   // Rating field
      $form['productDetails']['platforms_wrapper']['platforms'][$delta]['rating'] = [
        '#type' => 'textfield',
        // '#title' => $this->t('Rating'),
        '#attributes' => ['placeholder' => 'Rating', 'class' => ['form-control me-4', 'rating-field']],
        '#required' => TRUE,
        '#default_value' =>$form_state->getValue(['productDetails','platforms_wrapper','platforms',$delta,'rating']),
        '#prefix' => '<div class="col-md-2 mb-3"><h5 class="required mb-1">Rating</h5>',
        '#suffix' => '</div>',
        '#parents' => ['productDetails','platforms_wrapper','platforms',$delta,'rating'],
      ];


      // Coupon field
      $form['productDetails']['platforms_wrapper']['platforms'][$delta]['coupon'] = [
        '#type' => 'textfield',
        //  '#title' => $this->t('Coupon'),
        '#attributes' => ['placeholder' => 'Coupon Code', 'class' => ['form-control me-4']],
        '#required' => TRUE,
        '#default_value' => $form_state->getValue(['productDetails','platforms_wrapper','platforms',$delta,'coupon']),
        '#prefix' => '<div class="col-md-2 mb-3"><h5 class="required mb-1">Coupon Code</h5>',
        '#suffix' => '</div>',
        '#parents' => ['productDetails','platforms_wrapper','platforms',$delta,'coupon'],
      ];


  // Date field
        $form['productDetails']['platforms_wrapper']['platforms'][$delta]['date'] = [
        '#type' => 'date',
        #'#title' => $this->t('Date'),
        '#attributes' => ['class' => ['form-control me-4']],
        // '#default_value' => $form_state->getValue(
        //     ['platforms_wrapper', 'platforms', $delta, 'date'], 
        //     date('Y-m-d') // Default to current date
        // ),
        '#prefix' => '<div class="col-md-2 mb-3"><h5 class="required mb-1">Date</h5>',
        '#suffix' => '</div>',
        '#parents' => ['productDetails','platforms_wrapper','platforms',$delta,'date'],
        ];

//Affiliate Link field
/*$form['productDetails']['platforms_wrapper']['platforms'][$delta]['affliate'] = [
  '#type' => 'textfield',
  '#attributes' => [
    'placeholder' => 'Affiliate Link', 
    'class' => ['form-control', 'me-4', 'affiliate-field'],
    'id' => 'paffliatelink-' . $delta,
    'disabled' => !$form_state->getValue([
      'platforms_wrapper', 'platforms', $delta, 'affiliate_toggle'
    ], FALSE),
  ],
  '#required' => $form_state->getValue([
    'platforms_wrapper', 'platforms', $delta, 'affiliate_toggle'
  ], FALSE),
  '#default_value' => $form_state->getValue(['platforms_wrapper', 'platforms', $delta, 'affliate']),
  '#prefix' => '<div class="col-md-5 mb-3" id="affiliate-field-wrapper-' . $delta . '"><h5 class="mb-1">Affiliate Link</h5>',
  '#suffix' => '</div>',
];*/


$form['productDetails']['platforms_wrapper']['platforms'][$delta]['affiliate_wrapper'] = [
  '#type' => 'container',
  '#attributes' => [
    'id' => 'affiliate-field-wrapper-' . $delta,
    'class' => ['col-md-5', 'mb-3'],
  ],
  '#prefix' => '<div class="col-md-5 mb-3" ><h5 class="mb-1">Affiliate Link</h5>',
  '#suffix' => '</div>',
];

/*$form['productDetails']['platforms_wrapper']['platforms'][$delta]['affiliate_wrapper']['affliate'] = [
  '#type' => 'textfield',
  '#attributes' => [
    'placeholder' => 'Affiliate Link',
    'class' => ['form-control', 'me-4'],
  ],
  '#default_value' => $form_state->getValue(['platforms_wrapper', 'platforms', $delta, 'affliate']),
  '#disabled' => !$form_state->getValue([
    'platforms_wrapper', 'platforms', $delta, 'affiliate_toggle'
  ], FALSE),

];*/

 
 




/*$form['productDetails']['platforms_wrapper']['platforms'][$delta]['affiliate_toggle'] = [ 
  '#type' => 'checkbox', 
  '#title' => $this->t('Enable Affiliate Link'), 
  '#default_value' => $form_state->getValue([ 
    'platforms_wrapper', 'platforms', $delta, 'affiliate_toggle' 
  ], FALSE), 
  '#default_value' => $form_state->getValue([
  'productDetails', 'platforms_wrapper', 'platforms', $delta, 'affiliate_toggle'
], FALSE),
  '#attributes' => [ 
    'class' => ['affiliateToggle'], 
    'data-target' => 'paffliatelink-' . $delta, 
    'data-delta' => $delta, 
  ], 
  // 👇 wrap checkbox + custom slider in one <label>
  '#prefix' => '<div class="col-md-1 mb-3 linktoggle-btn"><div class="toggle-wrapper mt-3"><label class="toggle-switch">',
  '#suffix' => '<span class="slider"></span></label></div></div>', 
  '#ajax' => [ 
    'callback' => [$this, 'affiliateToggleCallback'], 
    'wrapper' => 'affiliate-field-wrapper-' . $delta, 
    'event' => 'change', 
  ], 
  '#title_display' => 'invisible',
];*/



// $form['productDetails']['platforms_wrapper']['platforms'][$delta]['affiliate_wrapper'] = [
//   '#type' => 'container',
//   '#attributes' => [
//     'id' => 'affiliate-field-wrapper-' . $delta,
//     'class' => ['col-md-11', 'mb-3'],
//   ],
// ];

$form['productDetails']['platforms_wrapper']['platforms'][$delta]['affiliate_wrapper']['affliate'] = [
  '#type' => 'textfield',
 # '#required' => TRUE,
 # '#title' => 'Affliate Link',
  '#attributes' => [
    'placeholder' => 'Affiliate Link',
    'class' => ['form-control', 'me-4', 'affiliate-input'],
  ],
  '#default_value' => $form_state->getValue([
    'productDetails', 'platforms_wrapper', 'platforms', $delta, 'affliate'
  ]),

  // ✅ IMPORTANT (for correct structure)
  '#parents' => [
    'productDetails',
    'platforms_wrapper',
    'platforms',
    $delta,
    'affliate'
  ],
 # '#prefix' => '<div class="col-md-5 mb-3">',
 # '#suffix' => '<span class="slider"></span> </div>', 
];


$form['productDetails']['platforms_wrapper']['platforms'][$delta]['affiliate_toggle'] = [
  '#type' => 'checkbox',

  '#default_value' => $form_state->getValue([
    'productDetails', 'platforms_wrapper', 'platforms', $delta, 'affiliate_toggle'
  ], FALSE),

  '#parents' => [
    'productDetails',
    'platforms_wrapper',
    'platforms',
    $delta,
    'affiliate_toggle'
  ],

  '#attributes' => [
    'class' => ['affiliate-toggle'],
    'data-delta' => (string) $delta,
  ],

  // ✅ YOUR DESIGN (UNCHANGED)
  '#prefix' => '<div class="col-md-1 mb-3 linktoggle-btn">
                  <div class="toggle-wrapper mt-3">
                    <label class="toggle-switch">',
  '#suffix' => '<span class="slider"></span></label></div></div>',

  '#title_display' => 'invisible',
];




  // Remove button (only if more than one row)
  #if (count($platform_items) > 1) {
#if ($delta > 0){
if (count($platform_items) > 1 && $delta != min($platform_items)) {
    $form['productDetails']['platforms_wrapper']['platforms'][$delta]['remove'] = [
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
    $form['productDetails']['platforms_wrapper']['platforms'][$delta]['empty'] = [
      '#markup' => '<div class="col-md-1"></div>',
    ];
  }
}

// Add button
$form['productDetails']['platforms_wrapper']['add'] = [
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
$form['productDetails']['platforms_wrapper']['hint'] = [
  '#markup' => '<div class="row"><div class="col-md-12 text-center"><span class="hint mt-2 mb-3">Add up to 20 Platform Links you operate under.</span></div></div>',
  '#prefix' => '<div class="row"><div class="col-md-12 text-center">',
  '#suffix' => '</div></div>',
];




// Actions container wrapping all buttons
$form['actions'] = [
  '#type' => 'actions',
  '#prefix' => '<div class="col-md-12 text-center my-4">',
  '#suffix' => '</div>',
];

// Add Product button
/*$form['actions']['submit'] = [
  '#type' => 'submit',
  '#value' => $this->t('Save Product'),
  '#attributes' => ['class' => ['btn', 'btn-default', 'mx-2']],
];*/

$form['actions']['submit'] = [
  '#type' => 'submit',
  '#value' => $this->t('Save Product'),
  '#attributes' => ['class' => ['btn', 'btn-default', 'mx-2']],
  '#ajax' => [
    'callback' => '::ajaxSubmitCallback',
    'wrapper'  => 'product-form-wrapper',
    'effect'   => 'fade',
  ],
];




    // Attach library for styling
    $form['#attached']['library'][] = 'role_based_registration/registration_form';

    return $form;
  }


public function addMoreImagesSubmit(array &$form, FormStateInterface $form_state) {
  $image_count = $form_state->get('image_count');

  if ($image_count < 6) {
    $form_state->set('image_count', $image_count + 1);
  }

  $form_state->setRebuild(TRUE);
}


public function addMoreImagesCallback(array &$form, FormStateInterface $form_state) {
  return $form['productDetails']['images_wrapper'];
}

  /**
 * AJAX callback for the affiliate toggle.
 */
public function affiliateToggleCallback(array &$form, FormStateInterface $form_state) {
  $triggering_element = $form_state->getTriggeringElement();
  $delta = $triggering_element['#attributes']['data-delta'];
  $form_state->setRebuild(TRUE);
  
  // Return the updated affiliate field
  return $form['productDetails']['platforms_wrapper']['platforms'][$delta]['affiliate_wrapper'];
}

  /**
   * Add row.
   */
  public function addPlatformSubmit(array &$form, FormStateInterface $form_state) {
    $platform_items = $form_state->get('platform_items');
    $new_delta = empty($platform_items) ? 0 : (max($platform_items) + 1);
    $platform_items[] = $new_delta;
    $form_state->set('platform_items', $platform_items);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Remove row.
   */
  public function removePlatformSubmit(array &$form, FormStateInterface $form_state) {
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
  }

  /**
   * Ajax callback.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['productDetails']['platforms_wrapper'];
  }

  /**
   * Submit form.
   */
  

public function submitForm(array &$form, FormStateInterface $form_state) {

  $user = \Drupal::currentUser();

  // ===============================
  // ✅ Handle Image Upload
  // ===============================
  /*$image = $form_state->getValue('image');
  $fid = NULL;

  if (!empty($image[0])) {
    $file = File::load($image[0]);
    if ($file) {
      $file->setPermanent();
      $file->save();
      $fid = $file->id();
    }
  }*/

 /*$fids = $form_state->get('stored_fids') ?? [];

// 🔥 Also merge manually uploaded ones (important)
$images = $form_state->getValue([
  'productDetails',
  'images_wrapper',
  'image'
]);*/

/*if (!empty($images)) {
  foreach ($images as $index => $image_field) {
    if (!empty($image_field[0])) {
      $fids[$index] = $image_field[0]; // overwrite or add
    }
  }
}*/



// ✅ Start with stored (auto extracted)
/*$fids = $form_state->get('stored_fids') ?? [];

// ✅ Merge manually uploaded images
$images = $form_state->getValue([
  'productDetails',
  'images_wrapper',
  'image'
]);

if (!empty($images)) {
  foreach ($images as $index => $image_field) {
    if (!empty($image_field[0])) {
      $fids[$index] = $image_field[0];
    }
  }
}

// Clean
$fids = array_filter($fids);
$fids = array_values($fids);
*/ 
// ✅ Always read from raw POST — most reliable for both manual and extracted images
$all_post = \Drupal::request()->request->all();
$images_post = $all_post['productDetails']['images_wrapper']['image'] ?? [];

$fids = [];
foreach ($images_post as $index => $image_field) {
  if (!is_array($image_field)) {
    continue;
  }
  
  // fids comes as plain string "208" from POST
  if (isset($image_field['fids']) && $image_field['fids'] !== '' && $image_field['fids'] !== '0') {
    $fid = (int) $image_field['fids'];
    if ($fid > 0) {
      $fids[] = $fid;
    }
  }
}

// ✅ If POST gave nothing, fall back to stored_fids hidden field
if (empty($fids)) {
  $hidden_val = $all_post['stored_fids_hidden'] ?? '';
  if (!empty($hidden_val)) {
    $fids = array_values(
      array_filter(array_map('intval', explode(',', $hidden_val)))
    );
  }
}

// ✅ Validate — only keep FIDs that exist in DB
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

// ✅ Now safe to create node
$keywords = $form_state->getValue('keywords');

$node = Node::create([
  'type'                     => 'marchant_products',
  'title'                    => $form_state->getValue('title'),
  'body'                     => [
    'value'  => $form_state->getValue('description'),
    'format' => 'plain_text',
  ],
  'field_product_url_new'    => ['uri' => $form_state->getValue('product_url')],
  'field_legal_business_name'=> $form_state->getValue('product_id'),
  'field_sold_bys'           => $form_state->getValue('sold_by'),
  'field_tests_done'         => $form_state->getValue('tests_done'),
  'field_image'              => $field_images,
  'field_rating_new'         => $form_state->getValue('rating'),
  'field_product_variation'  => $form_state->getValue('product_variation'),
  'field_testified_video'    => $form_state->getValue('testified_video'),
  'field_prices'             => [
    'value'  => $keywords,
    'format' => 'plain_text',
  ],
  'uid'                      => $user->id(),
  'status'                   => 1,
]);


\Drupal::logger('product_form')->notice('stored_fids: @s | hidden: @h | fids final: @f', [
  '@s' => print_r($form_state->get('stored_fids'), TRUE),
  '@h' => $form_state->getValue(['productDetails', 'images_wrapper', 'stored_fids_hidden']),
  '@f' => print_r($fids, TRUE),
]);
  /* $node->set('field_image', []);
foreach ($field_images as $image) {
  $node->get('field_image')->appendItem($image);
}*/


  // ===============================
  // ✅ Get Platform Values
  // ===============================
  $values = $form_state->getValue([
    'productDetails',
    'platforms_wrapper',
    'platforms'
  ]);

  if (!empty($values) && is_array($values)) {

    foreach ($values as $delta => $item) {

      // ✅ Skip empty rows (ONLY check link)
      if (empty($item['link'])) {
        continue;
      }

      $link = trim($item['link']);
      $title = 'View Product';

      // ===============================
      // ✅ Dynamic Platform Detection
      // ===============================
      #$link_lower = strtolower($link);

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
      else {
  // fallback
  if (!empty($host)) {
    $parts = explode('.', $host);
    $title = ucfirst($parts[0]);
  }
}

      // ===============================
      // ✅ Prepare Field Values
      // ===============================
      $price     = $item['price'] ?? '';
      $coupon    = $item['coupon'] ?? '';
      $rating    = $item['rating'] ?? '';
      $date      = $item['date'] ?? '';
      $affiliate = $item['affliate'] ?? '';

      // OPTIONAL: use toggle (if exists)
      $affiliate_toggle = $item['affiliate_toggle'] ?? 0;

      $affiliate_field = NULL;
      if (!empty($affiliate_toggle) && !empty($affiliate)) {
        $affiliate_field = [
          'uri' => $affiliate,
        ];
      }

      // ===============================
      // ✅ Create Paragraph
      // ===============================
      $paragraph = Paragraph::create([
        'type' => 'platform_link',

        'field_online_selling_platforms' => [
          'uri' => $link,
          'title' => $title,
        ],

        'field_platform_price' => $price,
        'field_coupon_code' => $coupon,
        'field_rating' => $rating,
        'field_affliate_link' => $affiliate_field,
        'field_coupon_expiry_date' => $date,
      ]);

      $paragraph->save();

      // Attach paragraph to node
      $node->get('field_platform_link')->appendItem($paragraph);
    }
  }

  // ===============================
  // ✅ Save Node (IMPORTANT)
  // ===============================
  $node->save();

  // ===============================
  // ✅ Success Message
  // ===============================
  \Drupal::messenger()->addStatus(
    $this->t('Product %title created successfully.', [
      '%title' => $node->label()
    ])
  );

  // ===============================
  // ✅ Redirect
  // ===============================

  //$form_state->setRedirect('<current>');
}



public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
  $response = new AjaxResponse();

  if ($form_state->getErrors()) {
    // Re-render form with validation errors — just replace with current $form
    $response->addCommand(new ReplaceCommand(
      '#product-form-wrapper',
      $form
    ));
    return $response;
  }

  // ✅ Show the success modal
  $response->addCommand(new InvokeCommand(
    '#productSuccessModal',
    'modal',
    ['show']
  ));

  // ✅ Reset form fields via JS instead of rebuilding PHP form
  $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand(
    '#product-form-wrapper form',
    'trigger',
    ['reset']
  ));

  // ✅ Clear any Drupal status messages that may have been set
  $response->addCommand(new \Drupal\Core\Ajax\HtmlCommand(
    '.messages-list',
    ''
  ));

  return $response;
}








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