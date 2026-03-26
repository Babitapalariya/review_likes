<?php

namespace Drupal\merchant_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
// use Drupal\Core\Access\AccessResult;
// use Drupal\Core\Session\AccountInterface;




/**
 * Provides a Product Testing Video submission form.
 */
class ScamBuyersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'merchant_dashboard_scam_buyers_form';
  }

//  /**
//    * Access callback for the form.
//    */
//   public function access(AccountInterface $account) {
//     // Check if user has permission to access this form
//     if (!$account->hasPermission('access merchant dashboard')) {
//       return AccessResult::forbidden();
//     }
//      return AccessResult::allowed();
//   }
  

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'row';

 $form['title'] = [
      '#markup' => '<h2 class="text-center mb-4">Aware About Scam Buyers</h2>',
    ];


    // Product Link
    $form['customer_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer Name'),
      '#required' => TRUE,
      '#placeholder' => 'Customer Name',
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="row g-3"><div class="col-12 col-md-6">',
      '#suffix' => '</div>',
    ];

    // Product Name
    $form['product_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
      '#required' => TRUE,
      '#placeholder' => 'Address',
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-12 col-md-6">',
      '#suffix' => '</div>',
    ];

       // User Phone
    $form['user_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone'),
      '#placeholder' => '+91 9876543210',
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-12 col-md-6">',
      '#suffix' => '</div>',
    ];

     $form['order_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order ID'),
      '#required' => TRUE,
      '#placeholder' => 'Order ID',
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-12 col-md-6">',
      '#suffix' => '</div>',
    ];

    // Video Link
    $form['platform'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Platform'),
      '#required' => TRUE,
      '#placeholder' => 'Platform',
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-12 col-md-6">',
      '#suffix' => '</div>',
    ];

   

     $form['product_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product Link'),
      '#required' => TRUE,
      '#placeholder' => 'Product Link',
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-12 col-md-6">',
      '#suffix' => '</div>',
    ];
       // Order Date & Time - ensure it's configured correctly
      $form['order_datetime'] = [
      '#type' => 'datetime',
        // '#title' => $this->t('Order Date & Time'),
        '#required' => TRUE,
        '#attributes' => ['class' => ['form-control']],
        '#default_value' => NULL,
        '#date_date_format' => 'Y-m-d',
        '#date_time_format' => 'H:i:s',
        '#date_date_element' => 'date',
        '#date_time_element' => 'time',
        '#date_timezone' => date_default_timezone_get(),
        '#prefix' => '<div class="col-12 col-md-6">',
        '#suffix' => '</div>',
      ];
    $form['tracking'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tracking'),
      '#required' => TRUE,
      '#placeholder' => 'Tracking',
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-12 col-md-6">',
      '#suffix' => '</div>',
    ];

    
 $form['describe'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Describe Briefly'),
      '#required' => TRUE,
      '#placeholder' => 'Describe Briefly ...',
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-12">',
      '#suffix' => '</div>',
    ];
  



   
    // Submit
    $form['actions'] = [
      '#type' => 'actions',
      '#prefix' => '<div class="col-md-12 text-center mt-5">',
      '#suffix' => '</div>',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => ['class' => ['btn', 'btn-default', 'submitBtn']],
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get values
    $values = $form_state->getValues();

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

    // Create node of type product_testing_video
    $node = Node::create([
      'type' => 'scam_buyers',
      'title' => $values['customer_name'],
      'field_product_url' => $values['product_link'],
      'field_brand_name' => $values['platform'],
      'field_comment_text' => $values['product_name'],
      'field_external_id' => $values['order_id'],
      'field_sold_bys' => $values['tracking'],
      'body' => $values['describe'],
       'field_order_datetime' => $timestamp_value,
       'field_telephone' => $values['user_phone'],
    ]);

    try {
      $node->save();
      $this->messenger()->addMessage($this->t('Your Aware About Scam Buyers has been submitted successfully.'));
      $form_state->setRedirect('<current>');
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error saving product testing video. Please try again.'));
    }
  }
}
