<?php

namespace Drupal\merchant_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\AppendCommand;

class DebateCommentForm extends FormBase {

  public function getFormId() {
    return 'merchant_dashboard_debate_comment_form';
  }

  // ══════════════════════════════════════════════════════════════════════════
  // BUILD FORM
  // ══════════════════════════════════════════════════════════════════════════

  public function buildForm(array $form, FormStateInterface $form_state, $rebuttal_id = NULL, $product_id = NULL) {

    $form['#attached']['library'][] = 'merchant_dashboard/debate';
    $current_user = \Drupal::currentUser();

    // ── Anonymous users: show login notice, no form ────────────────────────
    if ($current_user->isAnonymous()) {
      $login_url = \Drupal\Core\Url::fromRoute('user.login', [], [
        'query' => ['destination' => \Drupal::service('path.current')->getPath()],
      ])->toString();
      $form['login_notice'] = [
        '#markup' => \Drupal\Core\Render\Markup::create('
          <div class="debate-login-notice" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border:1px solid #e8e8e4;border-radius:18px;padding:20px;margin-top:16px;">
            <div style="font-size:14px;color:#6b6b63;">🔒 <strong>Want to participate?</strong> Login to post your statement.</div>
            <a href="' . $login_url . '" class="btn btn-dark">Login to Participate</a>
          </div>'),
      ];
      return $form;
    }

    // ── Load buyer usernames from rebuttal paragraphs ──────────────────────
    $rebuttal    = Node::load($rebuttal_id);
    $buyer_names = [];
    if ($rebuttal && $rebuttal->hasField('field_rebuttal_para')) {
      foreach ($rebuttal->get('field_rebuttal_para') as $item) {
        $para = $item->entity;
        if ($para && $para->hasField('field_buyer_username')) {
          $name = trim($para->get('field_buyer_username')->value ?? '');
          if ($name) { $buyer_names[] = $name; }
        }
      }
    }
    $buyer_names = array_unique($buyer_names);

    // ── Determine commenter type ───────────────────────────────────────────
    $roles        = $current_user->getRoles();
    $display_name = $current_user->getDisplayName();
    $uid          = (int) $current_user->id();

    if (in_array('merchant', $roles)) {
      $commenter_type = 'seller';
      $is_buyer_locked = FALSE;
    } elseif (in_array(strtolower($display_name), array_map('strtolower', $buyer_names))) {
      // Username matches a buyer in this rebuttal.
      // Check if this Drupal user has an approved buyer_claim for this rebuttal.
      $approved_claims = \Drupal::entityQuery('node')
        ->condition('type', 'buyer_claim')
        ->condition('field_claim_rebuttal', $rebuttal_id)
        ->condition('field_claim_user', $uid)
        ->condition('field_claim_status', 'approved')
        ->condition('status', 1)
        ->accessCheck(FALSE)
        ->execute();

      if (!empty($approved_claims)) {
        // Verified buyer — can post.
        $commenter_type  = 'buyer';
        $is_buyer_locked = FALSE;
      } else {
        // Previous buyer but NOT yet verified — lock the form.
        $commenter_type  = 'buyer';
        $is_buyer_locked = TRUE;
      }
    } else {
      $commenter_type  = 'visitor';
      $is_buyer_locked = FALSE;
    }

    // ── If previous buyer but not yet claimed — show locked notice ─────────
    if ($is_buyer_locked) {
      $form['locked_notice'] = [
        '#markup' => \Drupal\Core\Render\Markup::create('
          <div class="debate-buyer-locked card card-rose" id="debate-form-card-' . $rebuttal_id . '" style="border-radius:18px;padding:20px;margin-top:16px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
              <span style="font-size:28px;">🔒</span>
              <div>
                <div style="font-weight:700;font-size:16px;margin-bottom:2px;">Comment Section Locked</div>
                <div style="font-size:13px;color:#6b6b63;">You appear to be a <strong>previous buyer</strong> in this rebuttal.</div>
              </div>
            </div>
            <div style="background:#fff4f4;border:1px solid #ffc2c2;border-radius:14px;padding:14px 16px;font-size:13px;color:#a02020;margin-bottom:14px;">
              As a previous buyer, you can <strong>view all comments</strong> but cannot post until you verify your identity.
              Once verified, you will appear as a <strong>✅ Verified Buyer</strong> and your comment section will unlock.
            </div>
            <button type="button"
              class="btn btn-default w-100"
              data-bs-toggle="modal"
              data-bs-target="#claimBuyerModal-' . $rebuttal_id . '">
              🛡️ Claim &amp; Verify My Buyer Identity to Unlock Comments
            </button>
          </div>'),
      ];
      return $form;
    }

    $type_labels      = ['seller' => 'Product Owner', 'buyer' => 'Previous Buyer', 'visitor' => 'Visitor'];
    $type_badge_class = ['seller' => 'badge-solid-emerald', 'buyer' => 'badge-solid-rose', 'visitor' => 'badge-solid-amber'];

    $user_badge_html = '<div style="margin-bottom:12px;">'
      . '<span class="badge ' . $type_badge_class[$commenter_type] . '">' . htmlspecialchars($type_labels[$commenter_type]) . '</span>'
      . '<span style="font-size:13px;color:#6b6b63;margin-left:8px;">Posting as <strong>' . htmlspecialchars($display_name) . '</strong></span>'
      . '</div>';

    // ── Audience pills ─────────────────────────────────────────────────────
    $pills_html  = '<div class="audience-pills" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">';
    $pills_html .= '<button type="button" class="btn btn-pill btn-pill-emerald debate-audience-pill debate-pill-active" data-value="Product Owner">Product Owner</button>';
    foreach ($buyer_names as $bname) {
      $pills_html .= '<button type="button" class="btn btn-pill btn-pill-rose debate-audience-pill" data-value="' . htmlspecialchars($bname) . '">' . htmlspecialchars($bname) . '</button>';
    }
    $pills_html .= '</div>';

    // ══════════════════════════════════════════════════════════════════════
    // FORM ELEMENTS
    // Note: support_choice uses Drupal #type => 'radios' so the value goes
    // through the normal form API and is reliably read via getValue().
    // ══════════════════════════════════════════════════════════════════════

    // Messages div.
    $form['messages_wrapper'] = [
      '#markup' => \Drupal\Core\Render\Markup::create(
        '<div id="debate-msg-' . $rebuttal_id . '" class="debate-messages"></div>'
      ),
    ];

    // Card wrapper.
    $form['card_open'] = [
      '#markup' => \Drupal\Core\Render\Markup::create(
        '<div class="debate-input-card card" id="debate-form-card-' . $rebuttal_id . '" data-rebuttal="' . $rebuttal_id . '">'
        . '<div style="margin-bottom:12px;">'
        . '<div style="font-size:18px;font-weight:700;margin-bottom:4px;">Join the debate</div>'
        . '<div style="font-size:13px;color:#6b6b63;">Choose who you speak to, then write your statement.</div>'
        . '</div>'
        . $user_badge_html
      ),
    ];

    // Audience pills.
    $form['audience_pills'] = [
      '#markup' => \Drupal\Core\Render\Markup::create($pills_html),
    ];

    // ── Hidden: speaking_to (JS writes pill value here) ────────────────────
    $form['speaking_to'] = [
      '#type'          => 'hidden',
      '#default_value' => 'Product Owner',
      '#attributes'    => [
        'id'    => 'speaking-to-' . $rebuttal_id,
        'class' => ['debate-speaking-to'],
      ],
    ];

    // ── Hidden: parent_id (JS writes when reply button clicked) ───────────
    $form['parent_id'] = [
      '#type'          => 'hidden',
      '#default_value' => '0',
      '#attributes'    => [
        'id'    => 'parent-id-' . $rebuttal_id,
        'class' => ['debate-parent-id'],
      ],
    ];

    // ── Hidden: rebuttal_id, product_id, commenter_type ───────────────────
    $form['rebuttal_id']    = ['#type' => 'hidden', '#value' => (string) $rebuttal_id];
    $form['product_id']     = ['#type' => 'hidden', '#value' => (string) $product_id];
    $form['commenter_type'] = ['#type' => 'hidden', '#value' => $commenter_type];

    // ── Comment textarea ───────────────────────────────────────────────────
    $form['comment'] = [
      '#type'        => 'textarea',
      '#placeholder' => $this->t('Write your statement here...'),
      '#rows'        => 3,
      '#required'    => TRUE,
      '#attributes'  => [
        'class' => ['debate-comment-textarea'],
        'id'    => 'comment-textarea-' . $rebuttal_id,
        'style' => 'width:100%;border-radius:14px;border:1px solid #d1d1cb;padding:12px 16px;font-size:14px;font-family:inherit;resize:vertical;',
      ],
      '#prefix' => '<div style="margin:12px 0;">',
      '#suffix' => '</div>',
    ];

    // ── Media upload ───────────────────────────────────────────────────────
    $form['media_proof'] = [
      '#type'              => 'managed_file',
      '#title'             => $this->t('Add media proof (optional)'),
      '#upload_location'   => 'public://merchant/debate/media/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif pdf mp4 mov'],
        'file_validate_size'       => [20 * 1024 * 1024],
      ],
      '#multiple'   => TRUE,
      '#attributes' => ['class' => ['debate-media-upload']],
      '#prefix'     => '<div style="margin-bottom:12px;">',
      '#suffix'     => '</div>',
    ];

    // ── Support choice: proper Drupal radios (value reliably via getValue())
    // KEY: this is what gets saved. Values exactly: seller | buyer | neutral.
    $support_options = ['seller' => $this->t('I support Owner')];
    foreach ($buyer_names as $bname) {
      $support_options['buyer'] = $this->t('I support @name', ['@name' => $bname]);
    }
    $support_options['neutral'] = $this->t('Need more proof');

    $form['support_choice'] = [
      '#type'          => 'radios',
      #'#title'         => $this->t('Your position:'),
      '#options'       => $support_options,
      '#default_value' => 'seller',
      #'#required'      => TRUE,
      '#prefix'        => '<div class="support-row" style="display:flex;flex-wrap:wrap;gap:10px;margin:12px 0;">',
      '#suffix'        => '</div>',
      '#attributes'    => ['class' => ['debate-support-radios']],
    ];

    // ── Submit ─────────────────────────────────────────────────────────────
    /*$form['submit'] = [
      '#type'       => 'submit',
      '#value'      => $this->t('Post Statement'),
      '#attributes' => ['class' => ['btn', 'btn-dark', 'debate-submit-btn']],
      '#ajax'       => [
        'callback' => '::ajaxSubmit',
        'wrapper'  => 'debate-comments-list-' . $rebuttal_id,
        'effect'   => 'fade',
        'progress' => ['type' => 'throbber', 'message' => ''],
      ],
      '#prefix' => '<div style="margin-top:14px;">',
      '#suffix' => '</div>',
    ];*/

$form['submit'] = [
  '#type'       => 'submit',
  '#value'      => $this->t('Post Statement'),
  '#attributes' => ['class' => ['btn', 'btn-dark', 'debate-submit-btn']],
  '#ajax'       => [
    'callback' => '::ajaxSubmit',
    // NO wrapper here — we handle all DOM changes via AjaxResponse commands
    'effect'   => 'fade',
    'progress' => ['type' => 'throbber', 'message' => ''],
  ],
  '#prefix' => '<div style="margin-top:14px;">',
  '#suffix' => '</div>',
];

    $form['card_close'] = ['#markup' => '</div>'];

    return $form;
  }

  // ══════════════════════════════════════════════════════════════════════════
  // VALIDATE
  // ══════════════════════════════════════════════════════════════════════════

  /*public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (empty(trim((string) ($form_state->getValue('comment') ?? '')))) {
      $form_state->setErrorByName('comment', $this->t('Please write a statement before posting.'));
    }
    $support = $form_state->getValue('support_choice');
    if (empty($support)) {
      $form_state->setErrorByName('support_choice', $this->t('Please select your position.'));
    }
  }*/

  public function validateForm(array &$form, FormStateInterface $form_state): void {
  // Comment is NOT required — if empty, support label will be used as statement.
  $support = $form_state->getValue('support_choice');
  if (empty($support)) {
    $form_state->setErrorByName('support_choice', $this->t('Please select your position.'));
  }
}

  // ══════════════════════════════════════════════════════════════════════════
  // AJAX SUBMIT
  // ══════════════════════════════════════════════════════════════════════════

  /*public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response    = new AjaxResponse();
    $rebuttal_id = (int) ($form_state->getValue('rebuttal_id') ?? 0);
    $msg_sel     = '#debate-msg-' . $rebuttal_id;
    $list_sel    = '#debate-comments-list-' . $rebuttal_id;

    if ($form_state->hasAnyErrors()) {
      $html = '';
      foreach ($form_state->getErrors() as $err) {
        $html .= '<div class="debate-error" style="background:#fff4f4;border:1px solid #ffc2c2;border-radius:12px;padding:10px 16px;font-size:13px;color:#a02020;margin-bottom:8px;">' . $err . '</div>';
      }
      $response->addCommand(new HtmlCommand($msg_sel, $html));
      return $response;
    }

    $response->addCommand(new HtmlCommand($msg_sel, ''));
    $node = $this->saveDebateComment($form_state);

    if (!$node) {
      $response->addCommand(new HtmlCommand($msg_sel,
        '<div class="debate-error" style="background:#fff4f4;border:1px solid #ffc2c2;border-radius:12px;padding:10px 16px;font-size:13px;color:#a02020;">Failed to save. Please try again.</div>'
      ));
      return $response;
    }

    $raw       = $form_state->getUserInput();
    $parent_id = (int) ($raw['parent_id'] ?? 0);
    $card_html = $this->renderCommentCard($node, $parent_id > 0 ? 1 : 0);

    if ($parent_id > 0) {
      // ── Reply: inject into parent's replies container ────────────────────
      $reply_sel = '#replies-' . $parent_id;
      $response->addCommand(new RemoveCommand($reply_sel . ' .reply-empty'));
      $response->addCommand(new AppendCommand($reply_sel, $card_html));
      // Expand the replies container.
      $response->addCommand(new InvokeCommand($reply_sel, 'slideDown', [300]));
      // Update the toggle button text + count via JS custom event.
      $response->addCommand(new InvokeCommand(
        '[data-toggle-replies="' . $parent_id . '"]',
        'trigger',
        ['debate:recount']
      ));
    }
    else {
      // ── Top-level: prepend to main comments list ─────────────────────────
      $response->addCommand(new RemoveCommand($list_sel . ' .debate-no-comments'));
      $response->addCommand(new PrependCommand($list_sel, $card_html));
    }

    // Clear textarea.
    $response->addCommand(new InvokeCommand('#comment-textarea-' . $rebuttal_id, 'val', ['']));
    // Reset parent_id.
    $response->addCommand(new InvokeCommand('#parent-id-' . $rebuttal_id, 'val', ['0']));
    // Remove reply banner.
    $response->addCommand(new RemoveCommand('#debate-form-card-' . $rebuttal_id . ' .debate-reply-banner'));
    // Re-attach Drupal behaviors so new cards get JS handlers.
    $response->addCommand(new InvokeCommand('body', 'trigger', ['debate:reattach']));

    // Update trust snapshot bars.
    $product_id = (int) ($form_state->getValue('product_id') ?? 0);
    $counts     = $this->getTrustCounts($rebuttal_id, $product_id);
    $response->addCommand(new InvokeCommand(
      '#debate-section-' . $rebuttal_id,
      'trigger',
      ['updateTrust', [$counts]]
    ));

    // Success message.
    $response->addCommand(new HtmlCommand($msg_sel,
      '<div class="debate-success" style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:10px 16px;font-size:13px;color:#166534;margin-top:8px;">✓ Your statement has been posted.</div>'
    ));

    return $response;
  }*/



public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
  $response    = new AjaxResponse();
  $rebuttal_id = (int) ($form_state->getValue('rebuttal_id') ?? 0);
  $msg_sel     = '#debate-msg-' . $rebuttal_id;
  $list_sel    = '#debate-comments-list-' . $rebuttal_id;

  if ($form_state->hasAnyErrors()) {
    $html = '';
    foreach ($form_state->getErrors() as $err) {
      $html .= '<div class="debate-error" style="background:#fff4f4;border:1px solid #ffc2c2;border-radius:12px;padding:10px 16px;font-size:13px;color:#a02020;margin-bottom:8px;">' . $err . '</div>';
    }
    $response->addCommand(new HtmlCommand($msg_sel, $html));
    return $response;
  }

  $response->addCommand(new HtmlCommand($msg_sel, ''));

  // Read parent_id from RAW input only — not form values.
  // $form_state->getValue('parent_id') is unreliable for hidden fields
  // because Drupal may reset it. getUserInput() is the ground truth.
  $raw       = $form_state->getUserInput();
  $parent_id = (int) trim((string) ($raw['parent_id'] ?? '0'));

  \Drupal::logger('merchant_dashboard')->debug(
    'ajaxSubmit — rebuttal_id:@r  parent_id:@p  raw_parent_id_key_exists:@e',
    [
      '@r' => $rebuttal_id,
      '@p' => $parent_id,
      '@e' => array_key_exists('parent_id', $raw) ? 'YES' : 'NO',
    ]
  );

  $node = $this->saveDebateComment($form_state);

  if (!$node) {
    $response->addCommand(new HtmlCommand($msg_sel,
      '<div class="debate-error" style="background:#fff4f4;border:1px solid #ffc2c2;border-radius:12px;padding:10px 16px;font-size:13px;color:#a02020;">Failed to save. Please try again.</div>'
    ));
    return $response;
  }

  // Render card at correct depth.
  $is_reply  = ($parent_id > 0);
  $card_html = $this->renderCommentCard($node, $is_reply ? 1 : 0);

  if ($is_reply) {
    // ── REPLY: inject ONLY into parent's replies container ──────────────
    $reply_sel = '#replies-' . $parent_id;

    \Drupal::logger('merchant_dashboard')->debug(
      'ajaxSubmit — injecting REPLY into @sel', ['@sel' => $reply_sel]
    );

    $response->addCommand(new RemoveCommand($reply_sel . ' .reply-empty'));
    $response->addCommand(new AppendCommand($reply_sel, $card_html));
    $response->addCommand(new InvokeCommand($reply_sel, 'slideDown', [300]));
    $response->addCommand(new InvokeCommand(
      '[data-toggle-replies="' . $parent_id . '"]',
      'trigger',
      ['debate:recount']
    ));
    // Do NOT touch the main list — return early after reply injection.
  }
  else {
    // ── TOP-LEVEL: prepend ONLY to main comments list ────────────────────
    \Drupal::logger('merchant_dashboard')->debug(
      'ajaxSubmit — injecting TOP-LEVEL into @sel', ['@sel' => $list_sel]
    );

    $response->addCommand(new RemoveCommand($list_sel . ' .debate-no-comments'));
    $response->addCommand(new PrependCommand($list_sel, $card_html));
  }

  // Clear textarea.
  $response->addCommand(new InvokeCommand(
    '#comment-textarea-' . $rebuttal_id, 'val', ['']
  ));

  // Reset parent_id hidden field back to 0.
  $response->addCommand(new InvokeCommand(
    '#parent-id-' . $rebuttal_id, 'val', ['0']
  ));

  // Remove reply banner from form card.
  $response->addCommand(new RemoveCommand(
    '#debate-form-card-' . $rebuttal_id . ' .debate-reply-banner'
  ));

  // Re-attach Drupal behaviors so new cards get JS handlers.
  $response->addCommand(new InvokeCommand('body', 'trigger', ['debate:reattach']));

  // Update trust snapshot.
  $product_id = (int) ($form_state->getValue('product_id') ?? 0);
  $counts     = $this->getTrustCounts($rebuttal_id, $product_id);
  $response->addCommand(new InvokeCommand(
    '#debate-section-' . $rebuttal_id,
    'trigger',
    ['updateTrust', [$counts]]
  ));

  // Success message.
  $response->addCommand(new HtmlCommand($msg_sel,
    '<div class="debate-success" style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:10px 16px;font-size:13px;color:#166534;margin-top:8px;">✓ Your statement has been posted.</div>'
  ));

  return $response;
}



  public function submitForm(array &$form, FormStateInterface $form_state): void {
    #$this->saveDebateComment($form_state);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // SAVE DEBATE COMMENT NODE
  // ══════════════════════════════════════════════════════════════════════════

  /*protected function saveDebateComment(FormStateInterface $form_state): ?Node {
    $values = $form_state->getValues();
    $raw    = $form_state->getUserInput();

    $rebuttal_id    = (int)    ($values['rebuttal_id']    ?? 0);
    $product_id     = (int)    ($values['product_id']     ?? 0);
    $parent_id      = (int)    ($raw['parent_id']         ?? 0);
    $comment        = trim((string) ($values['comment']   ?? ''));
    $commenter_type = (string) ($values['commenter_type'] ?? 'visitor');

    // support_choice comes through Drupal form API getValue() reliably.
    $support_raw = (string) ($values['support_choice'] ?? '');
    switch ($support_raw) {
      case 'seller':  $support_side = 'seller';  break;
      case 'buyer':   $support_side = 'buyer';   break;
      case 'neutral': $support_side = 'neutral'; break;
      default:
        $support_side = ($commenter_type === 'seller') ? 'seller'
          : (($commenter_type === 'buyer') ? 'buyer' : 'neutral');
    }

    // speaking_to from hidden field written by JS pill click.
    $speaking_to = strip_tags(trim((string) ($raw['speaking_to'] ?? 'Product Owner')));
    $speaking_to = mb_substr($speaking_to ?: 'Product Owner', 0, 255);

    \Drupal::logger('merchant_dashboard')->debug(
      'saveDebateComment — type:@ct | support_choice:"@sc" | saved:@s | speaking:@t | parent:@p',
      ['@ct' => $commenter_type, '@sc' => $support_raw, '@s' => $support_side, '@t' => $speaking_to, '@p' => $parent_id]
    );

    if (empty($comment)) { return NULL; }

    // Media files.
    $media_refs = [];
    $fids = $values['media_proof'] ?? [];
    if (is_array($fids)) {
      foreach (array_filter(array_map('intval', $fids)) as $fid) {
        $file = File::load($fid);
        if ($file) {
          $file->setPermanent();
          $file->save();
          $media_refs[] = ['target_id' => $fid];
        }
      }
    }

    $node_data = [
      'type'                        => 'rebuttal_debate',
      'title'                       => mb_substr($comment, 0, 60) . (mb_strlen($comment) > 60 ? '...' : ''),
      'status'                      => 1,
      'field_debate_rebuttal'       => $rebuttal_id ? ['target_id' => $rebuttal_id] : NULL,
      'field_debate_product'        => $product_id  ? ['target_id' => $product_id]  : NULL,
      'field_debate_comment'        => $comment,
      'field_debate_support'        => $support_side,    // Exactly: seller | buyer | neutral
      'field_debate_speaking_to'    => $speaking_to,
      'field_debate_commenter_type' => $commenter_type,
    ];

    if ($parent_id > 0) {
      $node_data['field_debate_parent'] = ['target_id' => $parent_id];
    }
    if (!empty($media_refs)) {
      $node_data['field_debate_media'] = $media_refs;
    }

    try {
      $node = Node::create($node_data);
      $node->save();
      foreach ($media_refs as $ref) {
        $file = File::load($ref['target_id']);
        if ($file) {
          \Drupal::service('file.usage')->add($file, 'merchant_dashboard', 'node', $node->id());
        }
      }
      return $node;
    }
    catch (\Exception $e) {
      \Drupal::logger('merchant_dashboard')->error('Debate save error: @e', ['@e' => $e->getMessage()]);
      return NULL;
    }
  }*/

  protected function saveDebateComment(FormStateInterface $form_state): ?Node {
  $values = $form_state->getValues();
  $raw    = $form_state->getUserInput();

  $rebuttal_id    = (int)    ($values['rebuttal_id']    ?? 0);
  $product_id     = (int)    ($values['product_id']     ?? 0);
  $parent_id      = (int)    ($raw['parent_id']         ?? 0);
  $commenter_type = (string) ($values['commenter_type'] ?? 'visitor');

  // Support choice — determines fallback message too.
  $support_raw = (string) ($values['support_choice'] ?? '');
  switch ($support_raw) {
    case 'seller':  $support_side = 'seller';  break;
    case 'buyer':   $support_side = 'buyer';   break;
    case 'neutral': $support_side = 'neutral'; break;
    default:
      $support_side = ($commenter_type === 'seller') ? 'seller'
        : (($commenter_type === 'buyer') ? 'buyer' : 'neutral');
  }

  // Support label messages — used as fallback if comment is empty.
  $support_messages = [
    'seller'  => 'I support the Owner',
    'buyer'   => 'I support the Buyer',
    'neutral' => 'Need more proof',
  ];

  // Comment — if empty use the support label as the statement.
  $comment = trim((string) ($values['comment'] ?? ''));
  if (empty($comment)) {
    $comment = $support_messages[$support_side] ?? 'I support the Owner';
  }

  // speaking_to from hidden field written by JS pill click.
  $speaking_to = strip_tags(trim((string) ($raw['speaking_to'] ?? 'Product Owner')));
  $speaking_to = mb_substr($speaking_to ?: 'Product Owner', 0, 255);

  // Tagged users — extract @mentions from the raw comment text.
  $original_comment = trim((string) ($values['comment'] ?? ''));
  $tagged_users = [];
  if (!empty($original_comment) && preg_match_all('/@([\w\.\-]+)/', $original_comment, $matches)) {
    $tagged_users = array_unique($matches[1]);
  }

  // Media files.
  $media_refs = [];
  $fids = $values['media_proof'] ?? [];
  if (is_array($fids)) {
    foreach (array_filter(array_map('intval', $fids)) as $fid) {
      $file = File::load($fid);
      if ($file) {
        $file->setPermanent();
        $file->save();
        $media_refs[] = ['target_id' => $fid];
      }
    }
  }

  $node_data = [
    'type'                        => 'rebuttal_debate',
    'title'                       => mb_substr($comment, 0, 60) . (mb_strlen($comment) > 60 ? '...' : ''),
    'status'                      => 1,
    'field_debate_rebuttal'       => $rebuttal_id ? ['target_id' => $rebuttal_id] : NULL,
    'field_debate_product'        => $product_id  ? ['target_id' => $product_id]  : NULL,
    'field_debate_comment'        => $comment,
    'field_debate_support'        => $support_side,
    'field_debate_speaking_to'    => $speaking_to,
    'field_debate_commenter_type' => $commenter_type,
  ];

  if ($parent_id > 0) {
    $node_data['field_debate_parent'] = ['target_id' => $parent_id];
  }
  if (!empty($media_refs)) {
    $node_data['field_debate_media'] = $media_refs;
  }

  try {
    $node = Node::create($node_data);
    $node->save();

    foreach ($media_refs as $ref) {
      $file = File::load($ref['target_id']);
      if ($file) {
        \Drupal::service('file.usage')->add($file, 'merchant_dashboard', 'node', $node->id());
      }
    }

    // Send notifications to tagged users.
    if (!empty($tagged_users)) {
      $this->notifyTaggedUsers($tagged_users, $node, $comment);
    }

    return $node;
  }
  catch (\Exception $e) {
    \Drupal::logger('merchant_dashboard')->error('Debate save error: @e', ['@e' => $e->getMessage()]);
    return NULL;
  }
}

protected function notifyTaggedUsers(array $usernames, Node $node, string $comment): void {
  foreach ($usernames as $username) {
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['name' => $username]);

    if (empty($users)) { continue; }

    $tagged_user = reset($users);
    $product_url = '';
    $product_ref = $node->get('field_debate_product');
    if (!$product_ref->isEmpty()) {
      $product_url = \Drupal\Core\Url::fromRoute('entity.node.canonical', [
        'node' => $product_ref->target_id,
      ])->setAbsolute()->toString();
    }

    $tagger  = \Drupal::currentUser()->getDisplayName();
    $excerpt = mb_substr($comment, 0, 100);
    $subject = 'You were mentioned in a debate';
    $body    = $tagger . ' mentioned you in a debate: "' . $excerpt . '" — View: ' . $product_url;

    $params = [
      'subject' => $subject,
      'body'    => $body,
    ];

    \Drupal::service('plugin.manager.mail')->mail(
      'merchant_dashboard',
      'debate_mention',
      $tagged_user->getEmail(),
      $tagged_user->getPreferredLangcode(),
      $params
    );

    \Drupal::logger('merchant_dashboard')->info(
      'Tagged notification sent to @u for comment @c',
      ['@u' => $username, '@c' => $node->id()]
    );
  }
}

 /* // ══════════════════════════════════════════════════════════════════════════
  // RENDER COMMENT CARD
  // $depth 0 = top-level comment (shows Reply button + replies container)
  // $depth 1 = reply (no reply button, no nested replies — like Instagram)
  // ══════════════════════════════════════════════════════════════════════════

  public function renderCommentCard(Node $node, int $depth = 0): string {
    $commenter_type = $node->get('field_debate_commenter_type')->value ?? 'visitor';
    $support_side   = $node->get('field_debate_support')->value        ?? 'neutral';
    $speaking_to    = $node->get('field_debate_speaking_to')->value    ?? 'Product Owner';
    $comment        = $node->get('field_debate_comment')->value        ?? '';
    $user           = $node->getOwner();
    $username       = $user ? $user->getDisplayName() : 'Anonymous';
    $date           = \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'medium');
    $node_id        = (int) $node->id();

    // Get rebuttal ID safely.
    $rebuttal_ref = $node->get('field_debate_rebuttal');
    $rebuttal_id  = (!$rebuttal_ref->isEmpty()) ? (int) $rebuttal_ref->target_id : 0;

    // Color scheme.
    $type_map = [
      'seller'  => ['card' => 'card-emerald', 'solid' => 'badge-solid-emerald', 'light' => 'badge-emerald', 'label' => 'Product Owner'],
      'buyer'   => ['card' => 'card-rose',    'solid' => 'badge-solid-rose',    'light' => 'badge-rose',    'label' => 'Previous Buyer'],
      'visitor' => ['card' => 'card-amber',   'solid' => 'badge-solid-amber',   'light' => 'badge-amber',   'label' => 'Visitor'],
    ];
    $t = $type_map[$commenter_type] ?? $type_map['visitor'];

    $support_labels = [
      'seller'  => 'I support the Owner',
      'buyer'   => 'I support the Buyer',
      'neutral' => 'Need more proof',
    ];
    $support_label = $support_labels[$support_side] ?? 'Need more proof';

    // Media files.
    $media_html = '';
    if ($node->hasField('field_debate_media') && !$node->get('field_debate_media')->isEmpty()) {
      $media_html = '<div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:8px;">';
      foreach ($node->get('field_debate_media') as $fi) {
        $file = $fi->entity;
        if (!$file) { continue; }
        $url  = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        $mime = $file->getMimeType();
        if (strpos($mime, 'image/') === 0) {
          $media_html .= '<a href="' . $url . '" target="_blank">'
            . '<img src="' . $url . '" style="max-height:120px;max-width:100%;border-radius:10px;object-fit:cover;" alt="proof">'
            . '</a>';
        } elseif (strpos($mime, 'video/') === 0) {
          $media_html .= '<video controls style="max-height:160px;border-radius:10px;"><source src="' . $url . '" type="' . $mime . '"></video>';
        } else {
          $media_html .= '<a href="' . $url . '" target="_blank" style="font-size:13px;">📄 ' . htmlspecialchars($file->getFilename()) . '</a>';
        }
      }
      $media_html .= '</div>';
    }

    // ── Replies section (only for top-level comments) ──────────────────────
    $replies_section = '';
    $reply_button    = '';

    if ($depth === 0) {
      // Load existing replies.
      $reply_nids = \Drupal::entityQuery('node')
        ->condition('type', 'rebuttal_debate')
        ->condition('status', 1)
        ->condition('field_debate_parent', $node_id)
        ->sort('created', 'ASC')
        ->accessCheck(FALSE)
        ->execute();

      $reply_count   = count($reply_nids);
      $replies_inner = '';
      foreach (Node::loadMultiple($reply_nids) as $rn) {
        $replies_inner .= $this->renderCommentCard($rn, 1);
      }

      // Toggle button — shows count, toggles visibility.
      $toggle_btn = $reply_count > 0
        ? '<button type="button"
            class="debate-toggle-replies"
            data-toggle-replies="' . $node_id . '"
            style="background:none;border:none;color:#6b6b63;font-size:12px;font-weight:600;cursor:pointer;padding:4px 0;margin-top:6px;display:block;text-align:left;"
            >▶ View ' . $reply_count . ' ' . ($reply_count === 1 ? 'reply' : 'replies') . '</button>'
        : '<button type="button"
            class="debate-toggle-replies"
            data-toggle-replies="' . $node_id . '"
            style="display:none;background:none;border:none;color:#6b6b63;font-size:12px;font-weight:600;cursor:pointer;padding:4px 0;margin-top:6px;text-align:left;"
            >▶ View 0 replies</button>';

      $replies_section = $toggle_btn . '
        <div class="debate-replies-container" id="replies-' . $node_id . '"
          style="display:none;margin-top:10px;padding-left:20px;border-left:3px solid rgba(0,0,0,.1);">
          ' . ($replies_inner ?: '<span class="reply-empty"></span>') . '
        </div>';

      // Reply button.
      $reply_button = '<button type="button"
        class="debate-reply-btn"
        data-id="' . $node_id . '"
        data-name="' . htmlspecialchars($t['label']) . '"
        data-username="' . htmlspecialchars($username) . '"
        data-rebuttal="' . $rebuttal_id . '"
        style="background:none;border:none;font-size:12px;font-weight:700;cursor:pointer;color:#1a1a17;padding:0;text-decoration:underline;">
        Reply
      </button>';
    }

    $card_margin = $depth === 1 ? 'margin-bottom:10px;' : 'margin-bottom:16px;';

return '
  <div class="debate-comment-card card ' . $t['card'] . '"
    id="debate-comment-' . $node_id . '"
    data-node-id="' . $node_id . '"
    data-rebuttal="' . $rebuttal_id . '"
    style="' . $card_margin . 'border-radius:18px;">

    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
      <div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:4px;">
          <span class="badge ' . $t['solid'] . '">' . htmlspecialchars($t['label']) . '</span>
          <span class="badge ' . $t['light'] . '">' . htmlspecialchars($username) . '</span>
        </div>
        <div style="font-size:12px;color:#6b6b63;margin-top:2px;">→ <strong>@' . htmlspecialchars($speaking_to) . '</strong></div>
      </div>
      <span style="font-size:11px;color:#8a8a82;white-space:nowrap;">' . $date . '</span>
    </div>

    <p style="margin:0 0 10px;font-size:14px;line-height:1.6;">' . $this->formatCommentText($comment) . '</p>

    ' . $media_html . '

    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:10px;">
      <span style="font-size:12px;background:rgba(0,0,0,.06);border-radius:999px;padding:4px 12px;">' . htmlspecialchars($support_label) . '</span>
      ' . $reply_button . '
    </div>

    ' . $replies_section . '

  </div>';
  }*/




  // ══════════════════════════════════════════════════════════════════════════
  // RENDER COMMENT CARD — matches HTML design exactly
  // $depth 0 = top-level (grid-reply layout + Reply button + replies container)
  // $depth 1 = reply (compact, no nested replies, no reply button)
  // ══════════════════════════════════════════════════════════════════════════

  public function renderCommentCard(Node $node, int $depth = 0): string {
    $commenter_type = $node->get('field_debate_commenter_type')->value ?? 'visitor';
    $support_side   = $node->get('field_debate_support')->value        ?? 'neutral';
    $speaking_to    = $node->get('field_debate_speaking_to')->value    ?? 'Product Owner';
    $comment        = $node->get('field_debate_comment')->value        ?? '';
    $user           = $node->getOwner();
    $username       = $user ? $user->getDisplayName() : 'Anonymous';
    $date           = \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'medium');
    $node_id        = (int) $node->id();

    // Get rebuttal ID safely.
    $rebuttal_ref = $node->get('field_debate_rebuttal');
    $rebuttal_id  = (!$rebuttal_ref->isEmpty()) ? (int) $rebuttal_ref->target_id : 0;

    // ── Color scheme per commenter type ───────────────────────────────────
    $type_map = [
      'seller'  => [
        'card'      => 'card-emerald',
        'solid'     => 'badge-solid-emerald',
        'light'     => 'badge-emerald',
        'direction' => 'direction-emerald',
        'label'     => 'Product Owner',
      ],
      'buyer'   => [
        'card'      => 'card-rose',
        'solid'     => 'badge-solid-rose',
        'light'     => 'badge-rose',
        'direction' => 'direction-rose',
        'label'     => 'Previous Buyer',
      ],
      'visitor' => [
        'card'      => 'card-amber',
        'solid'     => 'badge-solid-amber',
        'light'     => 'badge-amber',
        'direction' => 'direction-amber',
        'label'     => 'Visitor',
      ],
    ];
    $t = $type_map[$commenter_type] ?? $type_map['visitor'];

    // ── Verified Buyer badge — shown for buyer comments ────────────────────
    // Check if this comment's author has an approved buyer_claim for this rebuttal.
    $verified_badge_html = '';
    if ($commenter_type === 'buyer') {
      $comment_uid = $node->getOwnerId();
      $verified_claims = \Drupal::entityQuery('node')
        ->condition('type', 'buyer_claim')
        ->condition('field_claim_rebuttal', $rebuttal_id)
        ->condition('field_claim_user', $comment_uid)
        ->condition('field_claim_status', 'approved')
        ->condition('status', 1)
        ->accessCheck(FALSE)
        ->execute();
      if (!empty($verified_claims)) {
        $verified_badge_html = '<span style="background:#f0fdf4;color:#166534;border:1px solid #86efac;border-radius:999px;padding:2px 10px;font-size:11px;font-weight:700;margin-left:4px;">✅ Verified Buyer</span>';
      }
    }

    // ── Support label ──────────────────────────────────────────────────────
    $support_labels = [
      'seller'  => 'I support the Owner',
      'buyer'   => 'I support the Buyer',
      'neutral' => 'Need more proof',
    ];
    $support_label = $support_labels[$support_side] ?? 'Need more proof';

    // ── Direction string: "Visitor → Owner", "Previous Buyer → Owner" etc.
    // Hide direction if seller speaks to Product Owner (redundant).
    $direction_html = '';
    if (!($commenter_type === 'seller' && $speaking_to === 'Product Owner')) {
      $direction_html = '<span class="comment-direction ' . $t['direction'] . '">'
        . htmlspecialchars($t['label']) . ' → ' . htmlspecialchars($speaking_to)
        . '</span>';
    }

    // ── Media files — rendered in right panel ──────────────────────────────
    $media_panel_inner = '<div class="media-placeholder" style="height:120px;">No media attached</div>';

    if ($node->hasField('field_debate_media') && !$node->get('field_debate_media')->isEmpty()) {
      $media_panel_inner = '<div style="display:flex;flex-direction:column;gap:8px;">';
      foreach ($node->get('field_debate_media') as $fi) {
        $file = $fi->entity;
        if (!$file) { continue; }
        $url  = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        $mime = $file->getMimeType();
        if (strpos($mime, 'image/') === 0) {
          $media_panel_inner .= '<a href="' . $url . '" target="_blank">'
            . '<img src="' . $url . '" style="width:100%;max-height:140px;border-radius:10px;object-fit:cover;" alt="proof">'
            . '</a>';
        } elseif (strpos($mime, 'video/') === 0) {
          $media_panel_inner .= '<video controls style="width:100%;border-radius:10px;"><source src="' . $url . '" type="' . $mime . '"></video>';
        } else {
          $media_panel_inner .= '<a href="' . $url . '" target="_blank" style="font-size:13px;color:#1a1a17;">📄 ' . htmlspecialchars($file->getFilename()) . '</a>';
        }
      }
      $media_panel_inner .= '</div>';
    }

    // ── Right panel: media proof box (matches HTML design) ─────────────────
    $media_panel_label = htmlspecialchars($t['label']) . ' → ' . htmlspecialchars($speaking_to) . ' media';
    if ($commenter_type === 'seller' && $speaking_to === 'Product Owner') {
      $media_panel_label = 'Owner media proof';
    }

    $right_panel = '<div style="background:var(--white,#fff);border:1px solid var(--neutral-200,#e8e8e4);border-radius:var(--radius-lg,14px);padding:16px;box-shadow:var(--shadow,0 1px 4px rgba(0,0,0,.08));">'
      . '<div style="font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--neutral-500,#8a8a82);margin-bottom:10px;">'
      . $media_panel_label
      . '</div>'
      . $media_panel_inner
      . '</div>';

    // ── Replies section (depth 0 only) ─────────────────────────────────────
    $replies_section = '';
    $reply_button    = '';

    if ($depth === 0) {
      $reply_nids = \Drupal::entityQuery('node')
        ->condition('type', 'rebuttal_debate')
        ->condition('status', 1)
        ->condition('field_debate_parent', $node_id)
        ->sort('created', 'ASC')
        ->accessCheck(FALSE)
        ->execute();

      $reply_count   = count($reply_nids);
      $replies_inner = '';
      foreach (Node::loadMultiple($reply_nids) as $rn) {
        $replies_inner .= $this->renderCommentCard($rn, 1);
      }

      $toggle_display = $reply_count > 0 ? 'block' : 'none';
      $toggle_label   = $reply_count > 0
        ? '▶ View ' . $reply_count . ' ' . ($reply_count === 1 ? 'reply' : 'replies')
        : '▶ View 0 replies';

      $toggle_btn = '<button type="button"
        class="debate-toggle-replies"
        data-toggle-replies="' . $node_id . '"
        style="display:' . $toggle_display . ';background:none;border:none;color:#6b6b63;font-size:12px;font-weight:600;cursor:pointer;padding:4px 0;margin-top:6px;text-align:left;">'
        . $toggle_label . '</button>';

      $replies_section = $toggle_btn . '
        <div class="debate-replies-container" id="replies-' . $node_id . '"
          style="display:none;margin-top:10px;padding-left:20px;border-left:3px solid rgba(0,0,0,.1);">
          ' . ($replies_inner ?: '<span class="reply-empty"></span>') . '
        </div>';

      $reply_button = '<button type="button"
        class="btn btn-outline debate-reply-btn"
        data-id="' . $node_id . '"
        data-name="' . htmlspecialchars($t['label']) . '"
        data-username="' . htmlspecialchars($username) . '"
        data-rebuttal="' . $rebuttal_id . '"
        style="font-size:12px;">
        Reply to this
      </button>';
    }

    // ── Card style ─────────────────────────────────────────────────────────
    $card_style    = $depth === 1 ? 'margin-bottom:10px;' : 'margin-bottom:16px;';
    $border_radius = 'border-radius:var(--radius-xl,18px);';

    // ── Reply card: compact, no grid, left border only ─────────────────────
    if ($depth === 1) {
      return '
        <div class="debate-comment-card card ' . $t['card'] . '"
          id="debate-comment-' . $node_id . '"
          data-node-id="' . $node_id . '"
          data-rebuttal="' . $rebuttal_id . '"
          style="' . $card_style . $border_radius . '">

          <div class="comment-header" style="margin-bottom:8px;">
            <div>
              <div class="meta-badges">
                <span class="badge ' . $t['solid'] . '">' . htmlspecialchars($t['label']) . '</span>
                <span class="badge ' . $t['light'] . '">' . htmlspecialchars($username) . '</span>
                ' . $verified_badge_html . '
              </div>
              <div class="comment-target" style="font-size:12px;color:#6b6b63;margin-top:3px;">@' . htmlspecialchars($speaking_to) . '</div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
              ' . $direction_html . '
              <span style="font-size:11px;color:var(--neutral-500,#8a8a82);">' . $date . '</span>
            </div>
          </div>

          <div class="comment-body">
            <div class="comment-body-label">Statement</div>
            <p>' . $this->formatCommentText($comment) . '</p>
          </div>

          <div class="comment-actions">
            <span class="support-chip">' . htmlspecialchars($support_label) . '</span>
          </div>

        </div>';
    }

    // ── Top-level card: full grid-reply layout matching the HTML design ────
    return '
      <div class="debate-comment-card card ' . $t['card'] . '"
        id="debate-comment-' . $node_id . '"
        data-node-id="' . $node_id . '"
        data-rebuttal="' . $rebuttal_id . '"
        style="' . $card_style . $border_radius . '">

        <div class="grid-reply">

          <div>
            <div class="comment-header">
              <div>
                <div class="meta-badges">
                  <span class="badge ' . $t['solid'] . '">' . htmlspecialchars($t['label']) . '</span>
                  <span class="badge ' . $t['light'] . '">' . htmlspecialchars($username) . '</span>
                  ' . $verified_badge_html . '
                </div>
                <div class="comment-target">@' . htmlspecialchars($speaking_to) . '</div>
              </div>
              <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                ' . $direction_html . '
                <span style="font-size:11px;color:var(--neutral-500,#8a8a82);">' . $date . '</span>
              </div>
            </div>

            <div class="comment-body">
              <div class="comment-body-label">Statement</div>
              <p>' . $this->formatCommentText($comment) . '</p>
            </div>

            <div class="comment-actions">
              <span class="support-chip">' . htmlspecialchars($support_label) . '</span>
              ' . $reply_button . '
            </div>

            ' . $replies_section . '
          </div>

          ' . $right_panel . '

        </div>
      </div>';
  }

  // ══════════════════════════════════════════════════════════════════════════
  // TRUST COUNTS
  // ══════════════════════════════════════════════════════════════════════════

  public function getTrustCounts(int $rebuttal_id, int $product_id): array {
    $base = \Drupal::entityQuery('node')
      ->condition('type', 'rebuttal_debate')
      ->condition('status', 1)
      ->condition('field_debate_rebuttal', $rebuttal_id)
      ->accessCheck(FALSE);

    $total   = (clone $base)->count()->execute();
    $seller  = (clone $base)->condition('field_debate_support', 'seller')->count()->execute();
    $buyer   = (clone $base)->condition('field_debate_support', 'buyer')->count()->execute();
    $neutral = (clone $base)->condition('field_debate_support', 'neutral')->count()->execute();

    $sp = $total > 0 ? round(($seller  / $total) * 100) : 0;
    $bp = $total > 0 ? round(($buyer   / $total) * 100) : 0;
    $np = $total > 0 ? round(($neutral / $total) * 100) : 0;

    return [
      'total'       => (int) $total,
      'seller'      => (int) $seller,
      'buyer'       => (int) $buyer,
      'neutral'     => (int) $neutral,
      'seller_pct'  => $sp,
      'buyer_pct'   => $bp,
      'neutral_pct' => $np,
    ];
  }


protected function formatCommentText(string $comment): string {
  $escaped = nl2br(htmlspecialchars($comment));

  $formatted = preg_replace_callback(
    '/@([\w\.\-]+)/',
    function ($matches) {
      $username = $matches[1];
      $users = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->loadByProperties(['name' => $username]);

      if (!empty($users)) {
        $user = reset($users);
        $url  = \Drupal\Core\Url::fromRoute('entity.user.canonical', ['user' => $user->id()])->toString();
        $style = 'color:#1a1a17;font-weight:700;background:rgba(0,0,0,.06);border-radius:999px;padding:1px 8px;text-decoration:none;font-size:13px;';
        return '<a href="' . $url . '" class="debate-mention" style="' . $style . '">@' . htmlspecialchars($username) . '</a>';
      }

      $style = 'color:#8a8a82;font-weight:600;font-size:13px;';
      return '<span class="debate-mention-unknown" style="' . $style . '">@' . htmlspecialchars($username) . '</span>';
    },
    $escaped
  );

  return $formatted;
}







}