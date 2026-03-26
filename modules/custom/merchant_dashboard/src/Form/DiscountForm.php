<?php

namespace Drupal\merchant_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Provides Discount Form.
 */
class DiscountForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'merchant_dashboard_discount_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Load taxonomy terms from vocabulary "discount_type".
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('discount_type');

    $options = ['' => $this->t('Select Type')];
    $term_map = [];

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
      $term_map[$term->name] = $term->tid;
    }

    // Store term_map in form state for use in submit
    $form['#term_map'] = $term_map;
    
    $form['title'] = [
      '#markup' => '<h2 class="text-center mb-4">Create Discount</h2>',
    ];
    
    // Dropdown for selecting discount type.
    $form['discount_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Discount Type'),
      '#options' => $options,
      '#required' => TRUE,
      '#attributes' => [
        'id' => 'edit-discount-type',
        'class' => ['form-select', 'required'],
      ],
      '#prefix' => '<div class="row g-3">'
    ];

    // Flat Off Discount
    if (isset($term_map['Flat Off Discount'])) {
      $tid = $term_map['Flat Off Discount'];
      $form['flatdiscount'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'discount-container-' . $tid,
          'class' => ['discount-container flatdiscount row mt-5'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="discount_type"]' => ['value' => (string) $tid],
          ],
        ],
      ];
      $form['flatdiscount']['flat_product_link'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Product Link'),
        '#placeholder' => 'Enter Product Link',
        '#prefix' => '<div class="col-12 col-md-4">',
        '#suffix' => '</div>',
      ];
      $form['flatdiscount']['flat_amount_before'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Before'),
        '#placeholder' => 'Enter Before',
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['flatdiscount']['flat_now'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Now'),
        '#placeholder' => 'Enter Now',
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['flatdiscount']['flat_amount_saved'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Amount Saved'),
        '#placeholder' => 'Enter Amount Saved',
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['flatdiscount']['flat_multi_use'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Multi Use'),
        '#prefix' => '<div class="col-12 col-md-2 text-center">',
        '#suffix' => '</div>',
      ];
    }

    // Coupon Discount
    if (isset($term_map['Coupon Discount'])) {
      $tid = $term_map['Coupon Discount'];
      $form['coupondiscount'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'discount-container-' . $tid,
          'class' => ['discount-container coupondiscount row mt-5'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="discount_type"]' => ['value' => (string) $tid],
          ],
        ],
      ];
      $form['coupondiscount']['coupon_product_link'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Product Link'),
        '#placeholder' => 'Enter Product Link',
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['coupondiscount']['coupon_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Coupon Code'),
        '#placeholder' => 'Enter Coupon Code',
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['coupondiscount']['coupon_amount_before'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Before'),
        '#placeholder' => 'Enter Before',
        '#prefix' => '<div class="col-12 col-md-1">',
        '#suffix' => '</div>',
      ];
      $form['coupondiscount']['coupon_now'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Now'),
        '#placeholder' => 'Enter Now',
        '#prefix' => '<div class="col-12 col-md-1">',
        '#suffix' => '</div>',
      ];
      $form['coupondiscount']['coupon_amount_saved'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Amount Saved'),
        '#placeholder' => 'Enter Amount Saved',
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['coupondiscount']['coupon_expiry_date'] = [
        '#type' => 'date',
        '#title' => $this->t('Expiry Date'),
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['coupondiscount']['coupon_multi_use'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Multi Use'),
        '#prefix' => '<div class="col-12 col-md-2 text-center">',
        '#suffix' => '</div>',
      ];
    }

    // Bundle Discount
    if (isset($term_map['Bundle Discount'])) {
      $tid = $term_map['Bundle Discount'];
      $form['bundlediscount'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'discount-container-' . $tid,
          'class' => ['discount-container bundlediscount row mt-5'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="discount_type"]' => ['value' => (string) $tid],
          ],
        ],
      ];
      $form['bundlediscount']['bundle_product_link'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Product Link'),
        '#placeholder' => 'Enter Product Link',
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['bundlediscount']['bundle_amount_before'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Before'),
        '#placeholder' => 'Enter Before',
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['bundlediscount']['bundle_now'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Now'),
        '#placeholder' => 'Enter Now',
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['bundlediscount']['bundle_amount_saved'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Amount Saved'),
        '#placeholder' => 'Enter Amount Saved',
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['bundlediscount']['bundle_expiry_date'] = [
        '#type' => 'date',
        '#title' => $this->t('Expiry Date'),
        '#prefix' => '<div class="col-12 col-md-2">',
        '#suffix' => '</div>',
      ];
      $form['bundlediscount']['bundle_multi_use'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Multi Use'),
        '#prefix' => '<div class="col-12 col-md-2 text-center">',
        '#suffix' => '</div>',
      ];
    }

    // Custom Discount
    if (isset($term_map['Custom Discount'])) {
      $tid = $term_map['Custom Discount'];
      $form['customdiscount'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'discount-container-' . $tid,
          'class' => ['discount-container customdiscount row mt-5'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="discount_type"]' => ['value' => (string) $tid],
          ],
        ],
      ];
      $form['customdiscount']['custom_product_link'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Product Link'),
        '#placeholder' => 'Enter Product Link',
        '#prefix' => '<div class="col-12 col-md-6">',
        '#suffix' => '</div>',
      ];
    }

    // Submit button
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Discount'),
      '#attributes' => ['class' => ['btn', 'btn-default', 'px-4']],
      '#suffix' => '</div>',
    ];

    // Add JavaScript using proper Drupal method
    $form['#attached']['library'][] = 'merchant_dashboard/tabs';
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $selected_tid = $form_state->getValue('discount_type');
    $term_map = $form['#term_map'];
    $values = $form_state->getValues();

    // Validate based on selected discount type
    switch ($selected_tid) {
      case $term_map['Flat Off Discount'] ?? NULL:
        if (empty($values['flat_product_link'])) {
          $form_state->setErrorByName('flat_product_link', $this->t('Product Link is required for Flat Off Discount.'));
        }
        if (empty($values['flat_amount_before'])) {
          $form_state->setErrorByName('flat_amount_before', $this->t('Before is required for Flat Off Discount.'));
        }
        if (empty($values['flat_now'])) {
          $form_state->setErrorByName('flat_now', $this->t('Now is required for Flat Off Discount.'));
        }
        if (empty($values['flat_amount_saved'])) {
          $form_state->setErrorByName('flat_amount_saved', $this->t('Amount Saved is required for Flat Off Discount.'));
        }
        break;

      case $term_map['Coupon Discount'] ?? NULL:
        if (empty($values['coupon_product_link'])) {
          $form_state->setErrorByName('coupon_product_link', $this->t('Product Link is required for Coupon Discount.'));
        }
        if (empty($values['coupon_code'])) {
          $form_state->setErrorByName('coupon_code', $this->t('Coupon Code is required for Coupon Discount.'));
        }
        if (empty($values['coupon_amount_before'])) {
          $form_state->setErrorByName('coupon_amount_before', $this->t('Before is required for Coupon Discount.'));
        }
        if (empty($values['coupon_now'])) {
          $form_state->setErrorByName('coupon_now', $this->t('Now is required for Coupon Discount.'));
        }
        if (empty($values['coupon_amount_saved'])) {
          $form_state->setErrorByName('coupon_amount_saved', $this->t('Amount Saved is required for Coupon Discount.'));
        }
        if (empty($values['coupon_expiry_date'])) {
          $form_state->setErrorByName('coupon_expiry_date', $this->t('Expiry Date is required for Coupon Discount.'));
        }
        break;

      case $term_map['Bundle Discount'] ?? NULL:
        if (empty($values['bundle_product_link'])) {
          $form_state->setErrorByName('bundle_product_link', $this->t('Product Link is required for Bundle Discount.'));
        }
        if (empty($values['bundle_amount_before'])) {
          $form_state->setErrorByName('bundle_amount_before', $this->t('Before is required for Bundle Discount.'));
        }
        if (empty($values['bundle_now'])) {
          $form_state->setErrorByName('bundle_now', $this->t('Now is required for Bundle Discount.'));
        }
        if (empty($values['bundle_amount_saved'])) {
          $form_state->setErrorByName('bundle_amount_saved', $this->t('Amount Saved is required for Bundle Discount.'));
        }
        if (empty($values['bundle_expiry_date'])) {
          $form_state->setErrorByName('bundle_expiry_date', $this->t('Expiry Date is required for Bundle Discount.'));
        }
        break;

      case $term_map['Custom Discount'] ?? NULL:
        if (empty($values['custom_product_link'])) {
          $form_state->setErrorByName('custom_product_link', $this->t('Product Link is required for Custom Discount.'));
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_tid = $form_state->getValue('discount_type');
    $values = $form_state->getValues();
    $term_map = $form['#term_map'];

    // Debug: Check what values we're getting
    // \Drupal::logger('merchant_dashboard')->notice('Form values: @values', ['@values' => print_r($values, TRUE)]);

    // Prepare node data based on discount type
    $node_data = [
      'type' => 'discount',
      'field_filter' => $selected_tid,
    ];

    // Handle fields based on selected discount type
    switch ($selected_tid) {
      case $term_map['Flat Off Discount'] ?? NULL:
        $node_data['title'] = !empty($values['flat_product_link']) ? $values['flat_product_link'] : 'Flat Off Discount - ' . date('Y-m-d H:i:s');
        if (!empty($values['flat_amount_before'])) {
          $node_data['field_buyer_username'] = $values['flat_amount_before'];
        }
        if (!empty($values['flat_now'])) {
          $node_data['field_comment_text'] = $values['flat_now'];
        }
        if (!empty($values['flat_amount_saved'])) {
          $node_data['field_legal_business_name'] = $values['flat_amount_saved'];
        }
        if (!empty($values['flat_multi_use'])) {
          $node_data['field_terms_and_conditions'] = $values['flat_multi_use'];
        }
        break;

      case $term_map['Coupon Discount'] ?? NULL:
        $node_data['title'] = !empty($values['coupon_product_link']) ? $values['coupon_product_link'] : 'Coupon Discount - ' . date('Y-m-d H:i:s');
        if (!empty($values['coupon_amount_before'])) {
          $node_data['field_buyer_username'] = $values['coupon_amount_before'];
        }
        if (!empty($values['coupon_now'])) {
          $node_data['field_comment_text'] = $values['coupon_now'];
        }
        if (!empty($values['coupon_amount_saved'])) {
          $node_data['field_legal_business_name'] = $values['coupon_amount_saved'];
        }
        if (!empty($values['coupon_multi_use'])) {
          $node_data['field_terms_and_conditions'] = $values['coupon_multi_use'];
        }
        if (!empty($values['coupon_code'])) {
          $node_data['field_external_id'] = $values['coupon_code'];
        }
        if (!empty($values['coupon_expiry_date'])) {
          $node_data['field_expiry_date'] = $values['coupon_expiry_date'];
        }
        break;

      case $term_map['Bundle Discount'] ?? NULL:
        $node_data['title'] = !empty($values['bundle_product_link']) ? $values['bundle_product_link'] : 'Bundle Discount - ' . date('Y-m-d H:i:s');
        if (!empty($values['bundle_amount_before'])) {
          $node_data['field_buyer_username'] = $values['bundle_amount_before'];
        }
        if (!empty($values['bundle_now'])) {
          $node_data['field_comment_text'] = $values['bundle_now'];
        }
        if (!empty($values['bundle_amount_saved'])) {
          $node_data['field_legal_business_name'] = $values['bundle_amount_saved'];
        }
        if (!empty($values['bundle_multi_use'])) {
          $node_data['field_terms_and_conditions'] = $values['bundle_multi_use'];
        }
        if (!empty($values['bundle_expiry_date'])) {
          $node_data['field_expiry_date'] = $values['bundle_expiry_date'];
        }
        break;

      case $term_map['Custom Discount'] ?? NULL:
        $node_data['title'] = !empty($values['custom_product_link']) ? $values['custom_product_link'] : 'Custom Discount - ' . date('Y-m-d H:i:s');
        if (!empty($values['custom_product_link'])) {
          $node_data['field_buyer_username'] = $values['custom_product_link'];
        }
        break;
    }

    // Debug node data before saving
    // \Drupal::logger('merchant_dashboard')->notice('Node data to save: @data', ['@data' => print_r($node_data, TRUE)]);

    try {
      // Create and save the node
      $node = Node::create($node_data);
      $node->save();

      $this->messenger()->addMessage($this->t('Discount has been created successfully'));
      $form_state->setRedirect('<current>');
    }
    catch (\Exception $e) {
      \Drupal::logger('merchant_dashboard')->error('Error creating discount: @error', ['@error' => $e->getMessage()]);
      $this->messenger()->addError($this->t('Error saving discount. Please try again.'));
    }
  }

}