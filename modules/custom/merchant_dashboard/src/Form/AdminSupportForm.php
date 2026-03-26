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
class AdminSupportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'merchant_dashboard_admin_support_form';
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
      '#markup' => '<h2 class="text-center mb-4">Admin Support</h2>',
    ];


    // Product Link
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#required' => TRUE,
      '#placeholder' => 'Subject',
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="row g-3"><div class="col-12 col-md-6">',
      '#suffix' => '</div>',
    ];

     // Create a simpler container structure
    $form['attach_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Attach file'),
      '#upload_location' => 'public://merchant/admin/attach_file/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif'],
        'file_validate_size' => [5 * 1024 * 1024],
      ],
      '#required' => TRUE,
      '#prefix' => '<div class="col-12 col-md-6">',
      '#suffix' => '</div>',
    ];


    // Product Name
    $form['case_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Case ID'),
      '#required' => TRUE,
       '#placeholder' => 'Case-ID',
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-12 col-md-6">',
      '#suffix' => '</div>',
    ];

    // Video Link
    $form['describe'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Describe briefly'),
      '#required' => TRUE,
       '#placeholder' => 'Describe briefly ...',
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-12 col-md-12">',
      '#suffix' => '</div>',
    ];



    // Submit
    $form['actions'] = [
      '#type' => 'actions',
      '#prefix' => '<div class="col-12 text-center mt-5">',
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

    // Create node of type product_testing_video
    $node = Node::create([
      'type' => 'admin_support',
      'title' => $values['subject'],
      'field_image' => $values['attach_file'],
      'field_external_id' => $values['case_id'],
      'body' => $values['describe'],
    ]);

    try {
      $node->save();
      $this->messenger()->addMessage($this->t('Your Data has been submitted successfully.'));
      $form_state->setRedirect('<current>');
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error saving product testing video. Please try again.'));
    }
  }
}
