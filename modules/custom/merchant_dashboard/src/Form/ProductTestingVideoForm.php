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
class ProductTestingVideoForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'merchant_dashboard_product_testing_video_form';
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
      '#markup' => '<h2 class="text-center mb-4">Submit Your Product Testing Video</h2>',
    ];


    // Product Link
    $form['product_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product Link'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    // Product Name
    $form['product_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product Name'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    // Video Link
    $form['video_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attach Product Testing Video'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    // Benchmark vs Competitor Content
    $form['benchmark'] = [
      '#type' => 'radios',
      '#title' => $this->t('Benchmark vs Competitor Content'),
      '#options' => ['1' => $this->t('Yes'), '0' => $this->t('No')],
      '#default_value' => 'no',
      '#required' => TRUE,
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    // AI Generated Content
    $form['aicontent'] = [
      '#type' => 'radios',
      '#title' => $this->t('AI Generated Content'),
      '#options' => ['1' => $this->t('Yes'), '0' => $this->t('No')],
      '#default_value' => 'no',
      '#required' => TRUE,
      '#prefix' => '<div class="col-md-6 mb-3">',
      '#suffix' => '</div>',
    ];

    // Content Challenge
    $form['content_challenge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('My Content is ready for challenges always ready for audience poll.'),
      '#prefix' => '<div class="col-md-6 mb-3"><div class="form-check d-flex align-items-center contentChallenge">',
      '#suffix' => '</div></div>',
      '#required' => TRUE,
    ];

    // Terms & Conditions
$form['termscondition_section'] = [
  '#type' => 'container',
  '#attributes' => ['class' => ['termscondition mt-3 mb-3']],
];
     $form['termscondition_section']['title'] = [
      '#markup' => '<h6 class="mb-2">Terms &amp; Conditions:</h6>
                   <div class="terms-box">
                                 <p>By submitting this video, you confirm:</p>
                                 <ul>
                                    <li>The content is original or has required permissions.</li>
                                    <li>No misleading claims are included.</li>
                                    <li>Complies with marketplace guidelines.</li>
                                 </ul>
                              </div>',
    ];
    $form['termscondition_section']['agree_terms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I agree to the terms and conditions'),
      '#prefix' => '<div class="form-check mt-2">',
      '#suffix' => '</div>',
      '#required' => TRUE,
    ];

    // Submit
    $form['actions'] = [
      '#type' => 'actions',
      '#prefix' => '<div class="col-md-12 text-center my-4">',
      '#suffix' => '</div>',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Video'),
      '#attributes' => ['class' => ['btn', 'btn-default', 'submitBtn']],
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
      'type' => 'product_testing_video',
      'title' => $values['product_name'],
      'field_product_url' => $values['product_link'],
      'field_external_id' => $values['video_link'],
      'field_product_variation' => $values['benchmark'],
      'field_testified_video' => $values['aicontent'],
      'field_challenges' => $values['content_challenge'],
      'field_terms_and_conditions' => $values['agree_terms'],
    ]);

    try {
      $node->save();
      $this->messenger()->addMessage($this->t('Your product testing video has been submitted successfully.'));
      $form_state->setRedirect('<current>');
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error saving product testing video. Please try again.'));
    }
  }
}
