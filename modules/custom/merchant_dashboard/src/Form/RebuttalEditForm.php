<?php

namespace Drupal\merchant_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Rebuttal Form.
 */
class RebuttalEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'merchant_dashboard_rebuttal_and_gift_edit_form';
  }

  /**
   * Access callback for the form.
   */
  public function access(AccountInterface $account, $nid = NULL) {
    // Check if user has permission to access this form
    if (!$account->hasPermission('access merchant dashboard')) {
      return AccessResult::forbidden();
    }

    // If nid is provided, check if the node exists and is accessible
    if ($nid) {
      $node = Node::load($nid);
      if (!$node) {
        return AccessResult::forbidden();
      }
      
      // Add additional access checks here if needed
      // For example, check if the current user owns this node
      // or has permission to edit it
    }

    return AccessResult::allowed();
  }

  /**
   * Convert timestamp to DrupalDateTime for form default value.
   */
  protected function getDateTimeFromTimestamp($timestamp) {
    if (empty($timestamp)) {
      return NULL;
    }
    
    try {
      return \Drupal\Core\Datetime\DrupalDateTime::createFromTimestamp($timestamp);
    } catch (\Exception $e) {
      \Drupal::logger('merchant_dashboard')->error('Error converting timestamp to datetime: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid = NULL) {
    $node = NULL;
    
    if ($nid) {
      $node = Node::load($nid);
    }

    if (!$node) {
      $form['error'] = [
        '#markup' => '<p>Node not found.</p>',
      ];
      return $form;
    }

    // Remove complex nesting and use simpler structure
    $form['#attributes']['class'][] = 'col-md-12';

    $form['title'] = [
      '#markup' => '<h2 class="text-center mb-2">Rebuttal</h2>
                   <h6 class="text-center mb-5">(We encourage you to fill all possible info about the customer. It will help us track fast and respond to your concern.)</h6>',
    ];

    // Create a simpler container structure
    $form['screenshot'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Screenshot of User Comment'),
      '#upload_location' => 'public://merchant/rebuttal/screenshots/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif'],
        'file_validate_size' => [5 * 1024 * 1024],
      ],
      '#default_value' => $node && !$node->get('field_image')->isEmpty()
        ? [$node->get('field_image')->target_id]
        : NULL,
      '#required' => TRUE,
      '#prefix' => '<div class="row"><div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    // E-commerce Platform - FIXED
    $form['ecommerce_platform'] = [
      '#type' => 'select',
      '#title' => $this->t('E-commerce Platform'),
      '#options' => $this->getLanguageOptions(),
      '#required' => TRUE,
      '#default_value' => $node && !$node->get('field_e_commerce_platform')->isEmpty() 
        ? $node->get('field_e_commerce_platform')->target_id 
        : NULL,
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
      '#default_value' => $node && !$node->get('field_product_url')->isEmpty() 
        ? $node->get('field_product_url')->value 
        : '',
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
      '#default_value' => $node ? $node->label() : '',
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
      '#default_value' => $node ? $node->label() : '',
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
      '#default_value' => $node && !$node->get('field_external_id')->isEmpty() 
        ? $node->get('field_external_id')->value 
        : '',
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
      '#default_value' => $this->getAddressComponentFromBody($node, 'street'),
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
      '#default_value' => $this->getAddressComponentFromBody($node, 'apartment'),
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div></div>',
    ];

    // City, State, ZIP in one row
    $form['shipping_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#placeholder' => $this->t('City'),
      '#required' => TRUE,
      '#default_value' => $this->getAddressComponentFromBody($node, 'city'),
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="row"><div class="col-md-4 mb-3">',
      '#suffix' => '</div>',
    ];

    $form['shipping_state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State'),
      '#placeholder' => $this->t('State'),
      '#required' => TRUE,
      '#default_value' => $this->getAddressComponentFromBody($node, 'state'),
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-md-4 mb-3">',
      '#suffix' => '</div>',
    ];

    $form['shipping_zip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ZIP Code'),
      '#placeholder' => $this->t('ZIP Code'),
      '#required' => TRUE,
      '#default_value' => $this->getAddressComponentFromBody($node, 'zip'),
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
      '#default_value' => $node && !$node->get('field_email')->isEmpty() 
        ? $node->get('field_email')->value 
        : '',
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
      '#default_value' => $node && !$node->get('field_telephone')->isEmpty() 
        ? $node->get('field_telephone')->value 
        : '',
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div></div>',
    ];
     
    $form['order_datetime'] = [
      '#type' => 'datetime',
    //   '#title' => $this->t('Order Date & Time'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#default_value' => $node && !$node->get('field_order_datetime')->isEmpty() 
        ? $this->getDateTimeFromTimestamp($node->get('field_order_datetime')->value)
        : new \Drupal\Core\Datetime\DrupalDateTime(),
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_timezone' => date_default_timezone_get(),
      '#prefix' => '<div class="row"><div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    $form['coupon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Coupon/Affiliate Link'),
      '#attributes' => [
        'placeholder' => 'Enable toggle to enter coupon link', 
        'class' => ['form-control', 'coupon-field'],
        'id' => 'paffliatelink',
      ],
      '#default_value' => $node && !$node->get('field_comment_text')->isEmpty() 
        ? $node->get('field_comment_text')->value 
        : NULL,
      '#required' => FALSE,
      '#disabled' => TRUE,
      '#prefix' => '<div class="col-md-5 mb-3">',
      '#suffix' => '</div>',
    ];

    $form['coupon_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Coupon/Affiliate Link'),
      '#default_value' => $node && !$node->get('field_comment_text')->isEmpty() 
        ? !empty($node->get('field_comment_text')->value) 
        : FALSE,
      '#attributes' => [
        'class' => ['couponToggle'],
        'id' => 'couponToggle',
      ],
      '#prefix' => '<div class="col-md-1 mb-3 linktoggle-btn"><div class="toggle-wrapper mt-3"><label for="couponToggle" class="toggle-switch">',
      '#suffix' => '<span class="slider"></span></label></div></div>',
      '#title_display' => 'after',
    ];

    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $node ? $node->id() : NULL,
    ];
     
    // Submit button
    $form['actions'] = [
      '#type' => 'actions',
      '#prefix' => '<div class="row"><div class="col-md-12 text-center my-4">',
      '#suffix' => '</div></div>',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Details'),
      '#button_type' => 'primary',
      '#attributes' => ['class' => ['btn', 'btn-primary']],
    ];

    // Add JavaScript using proper Drupal method
    $form['#attached']['library'][] = 'merchant_dashboard/tabs';
    return $form;
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
  }

  /**
   * Extract address components from body field.
   */
  protected function getAddressComponentFromBody($node, $component) {
    if (!$node || $node->get('body')->isEmpty()) {
      return '';
    }
    
    $body_value = $node->get('body')->value;
    
    // Parse the address string format: "Street, Apartment, City, State - ZIP"
    $parts = explode(', ', $body_value);
    
    // If we don't have enough parts, return empty
    if (count($parts) < 3) {
      return '';
    }
    
    switch ($component) {
      case 'street':
        return $parts[0] ?? '';
        
      case 'apartment':
        // Apartment might be empty, so check if we have enough parts
        if (count($parts) >= 4) {
          return $parts[1] ?? '';
        }
        return '';
        
      case 'city':
        if (count($parts) >= 4) {
          return $parts[2] ?? '';
        } else {
          return $parts[1] ?? '';
        }
        
      case 'state':
        if (count($parts) >= 4) {
          $state_zip_part = $parts[3] ?? '';
        } else {
          $state_zip_part = $parts[2] ?? '';
        }
        // Extract state from "State - ZIP" format
        $state_zip_parts = explode(' - ', $state_zip_part);
        return $state_zip_parts[0] ?? '';
        
      case 'zip':
        if (count($parts) >= 4) {
          $state_zip_part = $parts[3] ?? '';
        } else {
          $state_zip_part = $parts[2] ?? '';
        }
        // Extract ZIP from "State - ZIP" format
        $state_zip_parts = explode(' - ', $state_zip_part);
        return $state_zip_parts[1] ?? '';
        
      default:
        return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $nid = $values['nid'];
    
    if (!$nid) {
      $this->messenger()->addError($this->t('Invalid node ID.'));
      return;
    }

    $node = Node::load($nid);
    if (!$node) {
      $this->messenger()->addError($this->t('Node not found.'));
      return;
    }

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

    // Handle datetime
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

    // Update node fields
    $node->set('title', $values['buyer_username']);
    $node->set('field_e_commerce_platform', $ecommerce_platform);
    $node->set('field_product_url', $values['product_link']);
    $node->set('field_buyer_username', $values['repeat_username']);
    $node->set('field_external_id', $values['order_id']);
    $node->set('field_email', $values['user_email']);
    $node->set('field_telephone', $values['user_phone']);
    $node->set('field_order_datetime', $timestamp_value);
    $node->set('field_comment_text', $values['coupon']);
    $node->set('body', [
      'value' => $shipping_address,
      'format' => 'basic_html',
    ]);

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
      $this->messenger()->addMessage($this->t('Your rebuttal has been updated successfully.'));
      $form_state->setRedirect('merchant_dashboard.claims_gifts');
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred while updating the rebuttal. Please try again.'));
      \Drupal::logger('merchant_dashboard')->error('Error updating rebuttal node: @error', ['@error' => $e->getMessage()]);
    }
  }
}