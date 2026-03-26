<?php

namespace Drupal\merchant_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Provides a Rebuttal Form.
 */
class GiftForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'merchant_dashboard_gift_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Remove complex nesting and use simpler structure
    $form['#attributes']['class'][] = 'col-md-12';

    $form['title'] = [
      '#markup' => '<h2 class="text-center mb-2">Gift</h2>',
    ];
$form['coupon_section'] = [
  '#type' => 'container',
  '#attributes' => ['class' => ['row']],
];
    // Create a simpler container structure
    $form['coupon_section']['screenshot'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Screenshot of User Comment'),
      '#upload_location' => 'public://merchant/gift/screenshots/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif'],
        'file_validate_size' => [5 * 1024 * 1024],
      ],
      '#required' => TRUE,
      '#prefix' => '<div class="row"><div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    // E-commerce Platform - FIXED
    $form['coupon_section']['ecommerce_platform'] = [
      '#type' => 'select',
      '#title' => $this->t('E-commerce Platform'),
      '#options' => $this->getLanguageOptions(),
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select -'),
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div></div>', // Close the row
    ];

    // Product Link
    $form['product_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product Link (Original)'),
      '#placeholder' => 'https://example.com/product',
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="row"><div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    // Buyer Username
    $form['buyer_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Buyer Username'),
      '#placeholder' => $this->t('Enter buyer username'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div></div>',
    ];

    // Repeat Username
    $form['repeat_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Repeat Buyer Username'),
      '#placeholder' => $this->t('Re-enter buyer username'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="row"><div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    // Order ID
    $form['order_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order ID'),
      '#placeholder' => $this->t('Enter order ID'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div></div>',
    ];

    // Shipping Street
    $form['shipping_street'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street Address'),
      '#placeholder' => $this->t('Street Address'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="row"><div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    // Shipping Apartment
    $form['shipping_apartment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Apartment, Suite, etc.'),
      '#placeholder' => $this->t('Apartment, Suite, etc.'),
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div></div>',
    ];

    // City, State, ZIP in one row
    $form['shipping_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#placeholder' => $this->t('City'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="row"><div class="col-md-4 mb-3">',
      '#suffix' => '</div>',
    ];

    $form['shipping_state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State'),
      '#placeholder' => $this->t('State'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-md-4 mb-3">',
      '#suffix' => '</div>',
    ];

    $form['shipping_zip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ZIP Code'),
      '#placeholder' => $this->t('ZIP Code'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-md-4 mb-3">',
      '#suffix' => '</div></div>',
    ];

    // User Email
    $form['user_email'] = [
      '#type' => 'email',
      '#title' => $this->t('User Email'),
      '#placeholder' => 'user@example.com',
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="row"><div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    // User Phone
    $form['user_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('User Phone'),
      '#placeholder' => '+91 9876543210',
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div></div>',
    ];
     
  $form['order_datetime'] = [
  '#type' => 'datetime',
//   '#title' => $this->t('Order Date & Time'),
  '#required' => TRUE,
  '#attributes' => ['class' => ['form-control']],
  '#default_value' => NULL,
  '#date_date_format' => 'Y-m-d',
  '#date_time_format' => 'H:i:s',
  '#date_date_element' => 'date',
  '#date_time_element' => 'time',
  '#date_timezone' => date_default_timezone_get(),
  '#prefix' => '<div class="row"><div class="col-md-6 mb-3">',
  '#suffix' => '</div>',
];

    // In your buildForm method, replace the coupon section with:

// $form['coupon_section'] = [
//   '#type' => 'container',
//   '#attributes' => ['class' => ['row']],
// ];

$form['coupon'] = [
  '#type' => 'textfield',
  '#title' => $this->t('Coupon/Affiliate Link'),
  '#attributes' => [
    'placeholder' => 'Enable toggle to enter coupon link', 
    'class' => ['form-control', 'coupon-field'],
    'id' => 'paffliatelink',
  ],
  '#required' => FALSE,
  '#disabled' => TRUE,
  '#prefix' => '<div class="col-md-5 mb-3">',
  '#suffix' => '</div>',
];

$form['coupon_toggle'] = [
  '#type' => 'checkbox',
  '#title' => $this->t('Enable Coupon/Affiliate Link'),
  '#default_value' => FALSE,
  '#attributes' => [
    'class' => ['couponToggle'],
    'id' => 'couponToggle',
  ],
  '#prefix' => '<div class="col-md-1 mb-3 linktoggle-btn"><div class="toggle-wrapper mt-3"><label for="couponToggle" class="toggle-switch">',
  '#suffix' => '<span class="slider"></span></label></div></div>',
  '#title_display' => 'after',
];

    // Submit button
    $form['actions'] = [
      '#type' => 'actions',
      '#prefix' => '<div class="row"><div class="col-md-12 text-center my-4">',
      '#suffix' => '</div></div>',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Details'),
      '#button_type' => 'primary',
      '#attributes' => ['class' => ['btn', 'btn-primary']],
    ];

    // Add JavaScript using proper Drupal method
    $form['#attached']['library'][] = 'merchant_dashboard/tabs';

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

  protected function getLanguageOptions() {
    $options = [];
    
    // Load taxonomy terms from the Languages vocabulary
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'e_commerce_platform']);
    
    foreach ($terms as $term) {
      $options[$term->id()] = $term->getName();
    }
    
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $ecommerce_platform = $form_state->getValue('ecommerce_platform');
    $buyer_username = $form_state->getValue('buyer_username');
    $repeat_username = $form_state->getValue('repeat_username');
    $coupon_toggle = $form_state->getValue('coupon_toggle');
    $coupon = $form_state->getValue('coupon');

    // Validate that a taxonomy term is selected
    if (empty($ecommerce_platform)) {
      $form_state->setErrorByName('ecommerce_platform', 
        $this->t('Please select an e-commerce platform.'));
    } else {
      // Validate that the selected term exists
      $term = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->load($ecommerce_platform);
      
      if (!$term) {
        $form_state->setErrorByName('ecommerce_platform', 
          $this->t('Invalid e-commerce platform selected.'));
      }
    }

    // Add username matching validation
    if ($buyer_username !== $repeat_username) {
      $form_state->setErrorByName('repeat_username', 
        $this->t('Buyer usernames do not match.'));
    }

    // Validate coupon field if toggle is enabled
    if ($coupon_toggle && empty($coupon)) {
      $form_state->setErrorByName('coupon', 
        $this->t('Coupon/Affiliate link is required when enabled.'));
    }
  }
public function submitForm(array &$form, FormStateInterface $form_state) {
  $values = $form_state->getValues();

  // Debug raw datetime object
  \Drupal::logger('merchant_dashboard')->notice('Raw datetime object: @datetime', [
    '@datetime' => print_r($values['order_datetime'], TRUE),
  ]);

  // Validate ecommerce platform
  $ecommerce_platform = $values['ecommerce_platform'];
  if (empty($ecommerce_platform)) {
    $this->messenger()->addError($this->t('E-commerce platform is required.'));
    return;
  }

  $term = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->load($ecommerce_platform);

  if (!$term) {
    $this->messenger()->addError($this->t('Invalid e-commerce platform selected.'));
    return;
  }

  // Handle datetime - CONVERT TO TIMESTAMP
  $order_datetime = $values['order_datetime'];
  $timestamp_value = NULL;

  if ($order_datetime instanceof \Drupal\Core\Datetime\DrupalDateTime) {
    // Convert to timestamp (Unix timestamp)
    $timestamp_value = $order_datetime->getTimestamp();
    
    // Debug the converted value
    \Drupal::logger('merchant_dashboard')->notice('Converted timestamp: @timestamp', [
      '@timestamp' => $timestamp_value,
    ]);
  }

  // Build shipping address
  $shipping_address = $values['shipping_street'];
  if (!empty($values['shipping_apartment'])) {
    $shipping_address .= ', ' . $values['shipping_apartment'];
  }
  $shipping_address .= ', ' . $values['shipping_city'];
  $shipping_address .= ', ' . $values['shipping_state'];
  $shipping_address .= ' - ' . $values['shipping_zip'];

  // Node data
  $node_data = [
    'type' => 'gift',
    'title' => $values['buyer_username'],
    'field_e_commerce_platform' => $ecommerce_platform,
    'field_product_url' => $values['product_link'],
    'field_buyer_username' => $values['repeat_username'],
    'field_external_id' => $values['order_id'],
    'field_email' => $values['user_email'],
    'field_telephone' => $values['user_phone'],
    'field_order_datetime' => $timestamp_value, // Use timestamp here
    'field_comment_text' => $values['coupon'],
    'body' => [
      'value' => $shipping_address,
      'format' => 'basic_html',
    ],
    'status' => 1,
  ];

  $node = Node::create($node_data);

  // Handle screenshot upload
  if (!empty($values['screenshot'][0])) {
    $fid = $values['screenshot'][0];
    if ($file = File::load($fid)) {
      $file->setPermanent();
      $file->save();
      $node->set('field_image', [$fid]);
    }
  }

  // Save node
  try {
    $node->save();
    $this->messenger()->addMessage($this->t('Your gift submission has been submitted successfully.'));
    $form_state->setRedirect('<current>');
  }
  catch (\Exception $e) {
    $this->messenger()->addError($this->t('An error occurred while saving the submission. Please try again.'));
    \Drupal::logger('merchant_dashboard')->error('Error saving gift node: @error', ['@error' => $e->getMessage()]);
  }
}
//   public function submitForm(array &$form, FormStateInterface $form_state) {
//     $values = $form_state->getValues();

//     // Debug raw datetime object
//     \Drupal::logger('merchant_dashboard')->notice('Raw datetime object: @datetime', [
//       '@datetime' => print_r($values['order_datetime'], TRUE),
//     ]);

//     // Validate ecommerce platform
//     $ecommerce_platform = $values['ecommerce_platform'];
//     if (empty($ecommerce_platform)) {
//       $this->messenger()->addError($this->t('E-commerce platform is required.'));
//       return;
//     }

//     $term = \Drupal::entityTypeManager()
//       ->getStorage('taxonomy_term')
//       ->load($ecommerce_platform);

//     if (!$term) {
//       $this->messenger()->addError($this->t('Invalid e-commerce platform selected.'));
//       return;
//     }

//     // Handle datetime
//     $order_datetime = $values['order_datetime'];
//     $datetime_value = NULL;

//     if ($order_datetime instanceof \Drupal\Core\Datetime\DrupalDateTime) {
//       $datetime_value = $order_datetime->format('Y-m-d\TH:i:s');
//     }

//     // Build shipping address
//     $shipping_address = $values['shipping_street'];
//     if (!empty($values['shipping_apartment'])) {
//       $shipping_address .= ', ' . $values['shipping_apartment'];
//     }
//     $shipping_address .= ', ' . $values['shipping_city'];
//     $shipping_address .= ', ' . $values['shipping_state'];
//     $shipping_address .= ' - ' . $values['shipping_zip'];

//     // Node data - FIXED: Use 'coupon' instead of 'affliate'
//     $node_data = [
//       'type' => 'gift',
//       'title' => $values['buyer_username'],
//       'field_e_commerce_platform' => $ecommerce_platform,
//       'field_product_url' => $values['product_link'],
//       'field_buyer_username' => $values['repeat_username'],
//       'field_external_id' => $values['order_id'],
//       'field_email' => $values['user_email'],
//       'field_telephone' => $values['user_phone'],
//       'field_order_datetime' => $datetime_value,
//       'field_comment_text' => $values['coupon'], // Fixed this line
//       'body' => [
//         'value' => $shipping_address,
//         'format' => 'basic_html',
//       ],
//       'status' => 1,
//     ];

//     $node = Node::create($node_data);

//     // Handle screenshot upload
//     if (!empty($values['screenshot'][0])) {
//       $fid = $values['screenshot'][0];
//       if ($file = File::load($fid)) {
//         $file->setPermanent();
//         $file->save();
//         $node->set('field_image', [$fid]);
//       }
//     }

//     // Save node
//     try {
//       $node->save();
//       $this->messenger()->addMessage($this->t('Your gift submission has been submitted successfully.'));
//       $form_state->setRedirect('<current>');
//     }
//     catch (\Exception $e) {
//       $this->messenger()->addError($this->t('An error occurred while saving the submission. Please try again.'));
//       \Drupal::logger('merchant_dashboard')->error('Error saving gift node: @error', ['@error' => $e->getMessage()]);
//     }
//   }
}