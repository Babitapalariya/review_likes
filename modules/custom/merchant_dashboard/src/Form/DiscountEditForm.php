<?php

namespace Drupal\merchant_dashboard\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides Discount Edit Form.
 */
class DiscountEditForm extends DiscountForm {

  /**
   * The node being edited.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'merchant_dashboard_discount_edit_form';
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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    // Store the node for use in the form
    $this->node = $node;

    // Build the parent form (DiscountForm)
    $form = parent::buildForm($form, $form_state);

    // Change the title to indicate editing
    $form['title'] = [
      '#markup' => '<h2 class="text-center mb-4">Edit Discount</h2>',
    ];

    // Change submit button text
    $form['submit']['#value'] = $this->t('Update Discount');

    // Add a cancel button
    $form['actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-actions']],
    ];
    
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('merchant_dashboard.discount_form'),
      '#attributes' => ['class' => ['btn', 'btn-secondary']],
    ];

    // Load existing values into the form
    $this->populateFormWithExistingData($form, $form_state);

    return $form;
  }

  /**
   * Populate form with existing node data.
   */
  protected function populateFormWithExistingData(array &$form, FormStateInterface $form_state) {
    if (!$this->node) {
      return;
    }

    $node = $this->node;
    $term_map = $form['#term_map'];

    // Set the discount type
    if ($node->hasField('field_filter') && !$node->get('field_filter')->isEmpty()) {
      $discount_type_tid = $node->get('field_filter')->target_id;
      $form_state->setValue('discount_type', $discount_type_tid);
      $form['discount_type']['#default_value'] = $discount_type_tid;
      
      // Determine which discount type we're editing and populate fields
      foreach ($term_map as $type_name => $tid) {
        if ($discount_type_tid == $tid) {
          $this->populateDiscountTypeFields($type_name, $form, $form_state);
          break;
        }
      }
    }
  }

  /**
   * Populate fields for specific discount type.
   */
  protected function populateDiscountTypeFields($type_name, array &$form, FormStateInterface $form_state) {
    $node = $this->node;

    switch ($type_name) {
      case 'Flat Off Discount':
        $this->setFieldValue($form, $form_state, 'flat_product_link', $node->getTitle());
        $this->setFieldValue($form, $form_state, 'flat_amount_before', 'field_buyer_username');
        $this->setFieldValue($form, $form_state, 'flat_now', 'field_comment_text');
        $this->setFieldValue($form, $form_state, 'flat_amount_saved', 'field_legal_business_name');
        $this->setFieldValue($form, $form_state, 'flat_multi_use', 'field_terms_and_conditions');
        break;

      case 'Coupon Discount':
        $this->setFieldValue($form, $form_state, 'coupon_product_link', $node->getTitle());
        $this->setFieldValue($form, $form_state, 'coupon_code', 'field_external_id');
        $this->setFieldValue($form, $form_state, 'coupon_amount_before', 'field_buyer_username');
        $this->setFieldValue($form, $form_state, 'coupon_now', 'field_comment_text');
        $this->setFieldValue($form, $form_state, 'coupon_amount_saved', 'field_legal_business_name');
        $this->setFieldValue($form, $form_state, 'coupon_expiry_date', 'field_expiry_date');
        $this->setFieldValue($form, $form_state, 'coupon_multi_use', 'field_terms_and_conditions');
        break;

      case 'Bundle Discount':
        $this->setFieldValue($form, $form_state, 'bundle_product_link', $node->getTitle());
        $this->setFieldValue($form, $form_state, 'bundle_amount_before', 'field_buyer_username');
        $this->setFieldValue($form, $form_state, 'bundle_now', 'field_comment_text');
        $this->setFieldValue($form, $form_state, 'bundle_amount_saved', 'field_legal_business_name');
        $this->setFieldValue($form, $form_state, 'bundle_expiry_date', 'field_expiry_date');
        $this->setFieldValue($form, $form_state, 'bundle_multi_use', 'field_terms_and_conditions');
        break;

      case 'Custom Discount':
        $this->setFieldValue($form, $form_state, 'custom_product_link', $node->getTitle());
        break;
    }
  }

  /**
   * Helper method to set field values.
   */
  protected function setFieldValue(array &$form, FormStateInterface $form_state, $form_field, $node_field = NULL) {
    $node = $this->node;
    
    // If node_field is not provided, use form_field
    if ($node_field === NULL) {
      $node_field = $form_field;
    }

    $value = '';

    // Handle title field separately
    if ($node_field === 'title') {
      $value = $node->getTitle();
    }
    // Handle entity reference fields (like taxonomy)
    elseif ($node->hasField($node_field) && !$node->get($node_field)->isEmpty()) {
      $field = $node->get($node_field);
      
      if ($field->getFieldDefinition()->getType() === 'entity_reference') {
        $value = $field->target_id;
      }
      // Handle date fields
      elseif ($field->getFieldDefinition()->getType() === 'datetime') {
        $value = $field->date->format('Y-m-d');
      }
      // Handle boolean fields
      elseif ($field->getFieldDefinition()->getType() === 'boolean') {
        $value = (bool) $field->value;
      }
      // Handle text fields
      else {
        $value = $field->value;
      }
    }

    // Set the value in form state
    $form_state->setValue($form_field, $value);
    
    // Find and set the default value in the form
    $this->setFormFieldDefaultValue($form, $form_field, $value);
  }

  /**
   * Recursively find and set form field default value.
   */
  protected function setFormFieldDefaultValue(array &$form, $field_name, $value) {
    // Direct check in form elements
    if (isset($form[$field_name])) {
      $form[$field_name]['#default_value'] = $value;
      return true;
    }

    // Check in discount type containers
    $discount_containers = ['flatdiscount', 'coupondiscount', 'bundlediscount', 'customdiscount'];
    
    foreach ($discount_containers as $container) {
      if (isset($form[$container][$field_name])) {
        $form[$container][$field_name]['#default_value'] = $value;
        return true;
      }
    }

    // Recursive search through all form elements
    foreach ($form as $key => &$element) {
      if (is_array($element) && $key !== '#parents' && !isset($element['#type'])) {
        if ($this->setFormFieldDefaultValue($element, $field_name, $value)) {
          return true;
        }
      }
    }
    
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_tid = $form_state->getValue('discount_type');
    $values = $form_state->getValues();
    $term_map = $form['#term_map'];

    // Update the existing node instead of creating a new one
    if ($this->node) {
      $node = $this->node;
      \Drupal::logger('merchant_dashboard')->notice('Current node title: @title', ['@title' => $node->getTitle()]);
      // Update node data based on discount type
      switch ($selected_tid) {
        case $term_map['Flat Off Discount'] ?? NULL:
          if (!empty($values['flat_product_link'])) {
            $node->setTitle($values['flat_product_link']);
          }
          if (!empty($values['flat_amount_before'])) {
            $node->set('field_buyer_username', $values['flat_amount_before']);
          }
          if (!empty($values['flat_now'])) {
            $node->set('field_comment_text', $values['flat_now']);
          }
          if (!empty($values['flat_amount_saved'])) {
            $node->set('field_legal_business_name', $values['flat_amount_saved']);
          }
          if (isset($values['flat_multi_use'])) {
            $node->set('field_terms_and_conditions', $values['flat_multi_use']);
          }
          break;

        case $term_map['Coupon Discount'] ?? NULL:
          if (!empty($values['coupon_product_link'])) {
          $node->setTitle($values['coupon_product_link']); // Set title from product link
        }
          if (!empty($values['coupon_amount_before'])) {
            $node->set('field_buyer_username', $values['coupon_amount_before']);
          }
          if (!empty($values['coupon_now'])) {
            $node->set('field_comment_text', $values['coupon_now']);
          }
          if (!empty($values['coupon_amount_saved'])) {
            $node->set('field_legal_business_name', $values['coupon_amount_saved']);
          }
          if (isset($values['coupon_multi_use'])) {
            $node->set('field_terms_and_conditions', $values['coupon_multi_use']);
          }
          if (!empty($values['coupon_code'])) {
            $node->set('field_external_id', $values['coupon_code']);
          }
          if (!empty($values['coupon_expiry_date'])) {
            $node->set('field_expiry_date', $values['coupon_expiry_date']);
          }
          break;

        case $term_map['Bundle Discount'] ?? NULL:
          if (!empty($values['bundle_product_link'])) {
            $node->setTitle( $values['bundle_product_link']);
          }
          if (!empty($values['bundle_amount_before'])) {
            $node->set('field_buyer_username', $values['bundle_amount_before']);
          }
          if (!empty($values['bundle_now'])) {
            $node->set('field_comment_text', $values['bundle_now']);
          }
          if (!empty($values['bundle_amount_saved'])) {
            $node->set('field_legal_business_name', $values['bundle_amount_saved']);
          }
          if (isset($values['bundle_multi_use'])) {
            $node->set('field_terms_and_conditions', $values['bundle_multi_use']);
          }
          if (!empty($values['bundle_expiry_date'])) {
            $node->set('field_expiry_date', $values['bundle_expiry_date']);
          }
          break;

        case $term_map['Custom Discount'] ?? NULL:
          if (!empty($values['custom_product_link'])) {
            $node->setTitle($values['custom_product_link']);
          }
          if (!empty($values['custom_product_link'])) {
            $node->set('field_buyer_username', $values['custom_product_link']);
          }
          break;
      }

      // Update the discount type if changed
      $node->set('field_filter', $selected_tid);

      try {
        $node->save();
        $this->messenger()->addMessage($this->t('Discount has been updated successfully'));
        $form_state->setRedirect('merchant_dashboard.discount_form');
      }
      catch (\Exception $e) {
        \Drupal::logger('merchant_dashboard')->error('Error updating discount: @error', ['@error' => $e->getMessage()]);
        $this->messenger()->addError($this->t('Error updating discount. Please try again.'));
      }
    }
  }
}