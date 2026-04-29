<?php

namespace Drupal\merchant_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

class ClaimBuyerForm extends FormBase {

  public function getFormId() {
    return 'merchant_dashboard_claim_buyer_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $product_id = NULL, $rebuttal_id = NULL) {

    $form['#attached']['library'][] = 'merchant_dashboard/debate';
    $form['product_id']  = ['#type' => 'hidden', '#value' => (string) $product_id];
    $form['rebuttal_id'] = ['#type' => 'hidden', '#value' => (string) $rebuttal_id];

    $form['messages'] = [
      '#markup' => '<div id="claim-form-messages"></div>',
    ];

    $form['intro'] = [
      '#markup' => '<p style="font-size:13px;color:#6b6b63;margin-bottom:16px;">Enter the username used in your review and your Order ID. We will verify and give you a <strong>Verified Buyer</strong> badge.</p>',
    ];

    $form['claim_username'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Your Buyer Username'),
      '#placeholder' => $this->t('Same username shown in the review screenshot'),
      '#required'    => TRUE,
      '#attributes'  => ['class' => ['form-control']],
      '#prefix'      => '<div class="mb-3">',
      '#suffix'      => '</div>',
    ];

    $form['claim_order_id'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Order ID'),
      '#placeholder' => $this->t('Your order ID from the purchase'),
      '#required'    => TRUE,
      '#attributes'  => ['class' => ['form-control']],
      '#prefix'      => '<div class="mb-3">',
      '#suffix'      => '</div>',
    ];

    $form['claim_statement'] = [
      '#type'        => 'textarea',
      '#title'       => $this->t('Your Statement (optional)'),
      '#placeholder' => $this->t('Briefly explain your experience...'),
      '#rows'        => 3,
      '#attributes'  => ['class' => ['form-control']],
      '#prefix'      => '<div class="mb-3">',
      '#suffix'      => '</div>',
    ];

    $form['submit'] = [
      '#type'       => 'submit',
      '#value'      => $this->t('Submit Claim'),
      '#attributes' => ['class' => ['btn', 'btn-default', 'w-100']],
      '#ajax'       => [
        'callback' => '::ajaxSubmit',
        'wrapper'  => 'claim-form-messages',
        'progress' => ['type' => 'throbber', 'message' => ''],
      ],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $username   = trim($form_state->getValue('claim_username') ?? '');
    $order_id   = trim($form_state->getValue('claim_order_id') ?? '');
    $product_id = (int) ($form_state->getValue('product_id') ?? 0);
    $rebuttal_id= (int) ($form_state->getValue('rebuttal_id') ?? 0);

    if (empty($username)) {
      $form_state->setErrorByName('claim_username', $this->t('Please enter your buyer username.'));
      return;
    }
    if (empty($order_id)) {
      $form_state->setErrorByName('claim_order_id', $this->t('Please enter your Order ID.'));
      return;
    }

    // ── Verify username matches a buyer in the rebuttal paragraphs ─────────
    $rebuttal    = Node::load($rebuttal_id);
    $buyer_names = [];
    if ($rebuttal && $rebuttal->hasField('field_rebuttal_para')) {
      foreach ($rebuttal->get('field_rebuttal_para') as $item) {
        $para = $item->entity;
        if ($para && $para->hasField('field_buyer_username')) {
          $name = trim($para->get('field_buyer_username')->value ?? '');
          if ($name) { $buyer_names[] = strtolower($name); }
        }
      }
    }

    if (!in_array(strtolower($username), $buyer_names)) {
      $form_state->setErrorByName('claim_username', $this->t('This username does not match any buyer in this rebuttal. Please use the exact username shown in the review.'));
      return;
    }

    // ── Verify Order ID matches field_order_id in any rebuttal paragraph ───
    $order_matched = FALSE;
    if ($rebuttal && $rebuttal->hasField('field_rebuttal_para')) {
      foreach ($rebuttal->get('field_rebuttal_para') as $item) {
        $para = $item->entity;
        if (!$para) { continue; }
        $para_username = strtolower(trim($para->get('field_buyer_username')->value ?? ''));
        $para_order    = strtolower(trim($para->get('field_order_id')->value ?? ''));
        if ($para_username === strtolower($username) && $para_order === strtolower($order_id)) {
          $order_matched = TRUE;
          break;
        }
      }
    }

    if (!$order_matched) {
      $form_state->setErrorByName('claim_order_id', $this->t('Order ID does not match our records for this username. Please double-check your order details.'));
      return;
    }

    // ── Check if already claimed by another user ───────────────────────────
    $existing = \Drupal::entityQuery('node')
      ->condition('type', 'buyer_claim')
      ->condition('field_claim_rebuttal', $rebuttal_id)
      ->condition('field_claim_username', $username)
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($existing)) {
      $form_state->setErrorByName('claim_username', $this->t('This buyer identity has already been claimed.'));
    }
  }

  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
  $response = new AjaxResponse();

  if ($form_state->hasAnyErrors()) {
    $html = '';
    foreach ($form_state->getErrors() as $err) {
      $html .= '<div style="background:#fff4f4;border:1px solid #ffc2c2;border-radius:10px;padding:10px 14px;font-size:13px;color:#a02020;margin-bottom:8px;">' . $err . '</div>';
    }
    $response->addCommand(new HtmlCommand('#claim-form-messages', $html));
    return $response;
  }

  $this->saveClaim($form_state);

  $rebuttal_id = (int) ($form_state->getValue('rebuttal_id') ?? 0);

  // 1. Show success message inside modal body.
  $response->addCommand(new HtmlCommand(
    '#claimBuyerModal-' . $rebuttal_id . ' .modal-body',
    '<div style="text-align:center;padding:30px 20px;">
      <div style="font-size:48px;margin-bottom:16px;">✅</div>
      <h5 style="font-weight:700;margin-bottom:8px;">Claim Submitted!</h5>
      <p style="font-size:14px;color:#6b6b63;margin-bottom:0;">Your identity has been submitted for review. You will receive a <strong>Verified Buyer</strong> badge once approved.</p>
    </div>'
  ));

  // 2. Auto-close modal after 2.5 seconds.
  $response->addCommand(new InvokeCommand(
    'body',
    'trigger',
    ['claim:autoclose', [$rebuttal_id]]
  ));

  // 3. Show persistent success notice below the claim button.
  $response->addCommand(new HtmlCommand(
    '#claim-success-notice-' . $rebuttal_id,
    '<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:12px 16px;font-size:13px;color:#166534;margin-top:10px;">✅ Claim submitted — pending verification.</div>'
  ));

  return $response;
}

  public function submitForm(array &$form, FormStateInterface $form_state): void {
   # $this->saveClaim($form_state);
  }

  protected function saveClaim(FormStateInterface $form_state): void {
    $username    = trim($form_state->getValue('claim_username') ?? '');
    $order_id    = trim($form_state->getValue('claim_order_id') ?? '');
    $statement   = trim($form_state->getValue('claim_statement') ?? '');
    $product_id  = (int) ($form_state->getValue('product_id')   ?? 0);
    $rebuttal_id = (int) ($form_state->getValue('rebuttal_id')  ?? 0);
    $current_uid = \Drupal::currentUser()->id();

    try {
      $node = Node::create([
        'type'                  => 'buyer_claim',
        'title'                 => 'Claim: ' . $username . ' — ' . date('Y-m-d H:i'),
        'status'                => 1,
        'field_claim_username'  => $username,
        'field_claim_order_id'  => $order_id,
        'field_claim_statement' => $statement,
        'field_claim_rebuttal'  => $rebuttal_id ? ['target_id' => $rebuttal_id] : NULL,
        'field_claim_product'   => $product_id  ? ['target_id' => $product_id]  : NULL,
        'field_claim_user'      => ['target_id' => $current_uid],
        'field_claim_status'    => 'pending',
      ]);
      $node->save();

      \Drupal::logger('merchant_dashboard')->info('Buyer claim submitted — user:@u  username:@n  order:@o  rebuttal:@r', [
        '@u' => $current_uid,
        '@n' => $username,
        '@o' => $order_id,
        '@r' => $rebuttal_id,
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('merchant_dashboard')->error('Claim save error: @e', ['@e' => $e->getMessage()]);
    }
  }
}