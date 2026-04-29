<?php

namespace Drupal\merchant_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Render\Markup;

class CreateRebuttal extends FormBase {

  public function getFormId() {
    return 'merchant_dashboard_create_rebuttal_form';
  }

  // ══════════════════════════════════════════════════════════════════════════
  // BUILD FORM
  // ══════════════════════════════════════════════════════════════════════════

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'merchant_dashboard/tabs';
    $form['#attributes']['enctype'] = 'multipart/form-data';

    $reviews    = $form_state->get('reviews') ?? [];
    $edit_index = $form_state->get('edit_index');
    $is_edit    = ($edit_index !== NULL);
    $editing    = $is_edit ? ($reviews[$edit_index] ?? []) : [];

    $form['#prefix'] = '<div id="rebuttal-form-wrapper">';
    $form['#suffix'] = '</div>';

    // ═══════════════════════════════════════════════════════════════════════
    // SECTION 1 – REBUTTAL DETAILS
    // ═══════════════════════════════════════════════════════════════════════
    $form['rebuttal_details_open'] = [
      '#markup' => '<section class="card"><h2 class="card-header">Rebuttal Details</h2><div class="row">',
    ];
    $form['rebuttal_details_left_open'] = [
      '#markup' => '<div class="col-md-6"><div class="row">',
    ];
    $form['title'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Rebuttal Title'),
      '#placeholder' => $this->t('Example: Incorrect size claim on product listing'),
      '#required'    => TRUE,
      '#attributes'  => ['class' => ['form-control']],
      '#prefix'      => '<div class="col-md-12">',
      '#suffix'      => '<p class="help">Internal reference only. Not publicly visible.</p></div>',
    ];

    $form['select_product'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Select Product'),
      '#options'       => $this->getMerchantProductOptions(),
      '#empty_option'  => $this->t('— Select a Product —'),
      '#required'      => TRUE,
      '#attributes'    => ['class' => ['form-control']],
      '#default_value' => $form_state->getValue('select_product') ?? NULL,
      '#prefix'        => '<div class="col-md-12">',
      '#suffix'        => '<p class="help">Select the product this rebuttal is associated with.</p></div>',
    ];


    $form['body'] = [
      '#type'        => 'textarea',
      '#title'       => $this->t('Rebuttal Summary (Internal)'),
      '#placeholder' => $this->t('Short note about this rebuttal'),
      '#rows'        => 2,
      '#attributes'  => ['class' => ['form-control']],
      '#prefix'      => '<div class="col-md-12">',
      '#suffix'      => '<p class="help">Optional. Visible only to your team.</p></div>',
    ];
    $form['rebuttal_details_left_close'] = ['#markup' => '</div></div>'];
    $form['rebuttal_details_right'] = [
      '#markup' => Markup::create('
        <div class="col-md-6">
          <h5 class="text-decoration-underline mb-2">Instructions:</h5>
          <ul>
            <li>You can add rebuttal and evidence in this portal</li>
            <li>Please go through the video to know the steps.</li>
          </ul>
          <div>
            <iframe id="rebuttalvideo" frameborder="0"
              allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
              src="https://www.youtube.com/embed/UWFoxol1yV0">
            </iframe>
          </div>
        </div>
      '),
    ];
    $form['rebuttal_details_close'] = ['#markup' => '</div></section>'];

    // ═══════════════════════════════════════════════════════════════════════
    // SECTION 2 – ADD / EDIT REVIEW EVIDENCE
    // ═══════════════════════════════════════════════════════════════════════
    $review_count = count($reviews);
    $max_reviews  = 10;

    $section_label = $is_edit
      ? $this->t('Edit Review #@n', ['@n' => ($edit_index + 1)])
      : $this->t('Add Review Evidence');

    $form['review_evidence_open'] = [
      '#markup' => '<section class="card">
        <div class="card-header">
          <h2>' . $section_label . '</h2>
          <span class="badge-muted">' . $review_count . ' / ' . $max_reviews . ' Reviews Added</span>
        </div>',
    ];

    if ($is_edit || $review_count < $max_reviews) {

      // Determine default FID for the screenshot widget.
      $widget_default_fid = NULL;
      if ($is_edit && !empty($editing['screenshot'])) {
        $widget_default_fid = (int) $editing['screenshot'];
      }
      elseif (!$is_edit) {
        $widget_default_fid = $form_state->get('pending_screenshot_fid') ?: NULL;
      }

      // Ensure file is permanent so widget renders it.
      if ($widget_default_fid) {
        $check = File::load($widget_default_fid);
        if (!$check) {
          $widget_default_fid = NULL;
        }
        elseif ($check->isTemporary()) {
          $check->setPermanent();
          $check->save();
        }
      }

      $form['review_screenshot_open'] = [
        '#markup' => '<div class="grid grid-2"><div><div class="mb-3">
                        <p class="help">Upload a clear screenshot of the review.</p>',
      ];

      $form['review_screenshot'] = [
        '#type'              => 'managed_file',
        '#title'             => $this->t('Review Screenshot'),
        '#upload_location'   => 'public://merchant/rebuttal/screenshots/',
        '#upload_validators' => [
          'file_validate_extensions' => ['png jpg jpeg gif'],
          'file_validate_size'       => [5 * 1024 * 1024],
        ],
        '#default_value'    => $widget_default_fid ? [$widget_default_fid] : [],
        '#element_validate' => ['::screenshotElementValidate'],
        // KEY: dedicated AJAX upload callback stores FID immediately.
        '#upload_button_title' => $this->t('Upload'),
        '#ajax' => [
          'callback' => '::screenshotUploadAjax',
          'wrapper'  => 'screenshot-upload-wrapper',
          'event'    => 'change',
        ],
       '#required'      => TRUE,
        '#prefix' => '<div id="screenshot-upload-wrapper">',
        '#suffix' => '</div>',
      ];

      $form['review_screenshot_close'] = ['#markup' => '</div></div>'];

      $form['review_ecommerce_platform'] = [
        '#type'          => 'select',
        '#title'         => $this->t('E-commerce Platform'),
        '#options'       => $this->getEcommercePlatformOptions(),
        '#empty_option'  => $this->t('Select Platform'),
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['ecommerce_platform'] ?? NULL,
        '#prefix'        => '<div>',
        '#suffix'        => '</div></div>',
      ];

      $form['review_product_link'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Product Link'),
        '#placeholder'   => $this->t('Any marketplace but original link'),
        '#required'      => TRUE,
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['product_link'] ?? '',
        '#prefix'        => '<div class="grid grid-2 mt-4"><div>',
        '#suffix'        => '</div>',
      ];
      $form['review_buyer_username'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Buyer Username'),
        '#placeholder'   => $this->t('Public username'),
        '#required'      => TRUE,
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['buyer_username'] ?? '',
        '#prefix'        => '<div>',
        '#suffix'        => '</div>',
      ];
      $form['review_re_enter_buyer_username'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Re-Enter Buyer Username'),
        '#required'      => TRUE,
        '#placeholder'   => $this->t('Re-Enter Buyer Username'),
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['re_enter_buyer_username'] ?? ($editing['buyer_username'] ?? ''),
        '#prefix'        => '<div>',
        '#suffix'        => '</div>',
      ];
      $form['review_buyer_email'] = [
        '#type'          => 'email',
        '#title'         => $this->t('Buyer Email'),
        '#placeholder'   => $this->t('Public Email'),
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['buyer_email'] ?? '',
        '#prefix'        => '<div>',
        '#suffix'        => '</div>',
      ];
      $form['review_buyer_phone'] = [
        '#type'          => 'tel',
        '#title'         => $this->t('Buyer Phone'),
        '#placeholder'   => $this->t('Buyer Phone'),
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['buyer_phone'] ?? '',
        '#prefix'        => '<div>',
        '#suffix'        => '</div>',
      ];
      $form['review_order_id'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Order ID'),
        '#placeholder'   => $this->t('Enter Order ID or N/A'),
        '#required'      => TRUE,
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['order_id'] ?? '',
        '#prefix'        => '<div>',
        '#suffix'        => '</div>',
      ];
      $form['review_date'] = [
        '#type'          => 'date',
        '#title'         => $this->t('Review Date'),
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['review_date'] ?? '',
        '#prefix'        => '<div>',
        '#suffix'        => '</div>',
      ];
      $form['review_time'] = [
        '#type'          => 'time',
        '#title'         => $this->t('Review Time'),
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['review_time'] ?? '',
        '#prefix'        => '<div>',
        '#suffix'        => '</div></div>',
      ];

      $form['review_street_address'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Street Address'),
        '#placeholder'   => $this->t('Street Address'),
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['street_address'] ?? '',
        '#prefix'        => '<div class="grid grid-2 mt-4"><div>',
        '#suffix'        => '</div>',
      ];
      $form['review_apartment'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Apartment, Suite, etc.'),
        '#placeholder'   => $this->t('Apartment, Suite, etc.'),
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['apartment'] ?? '',
        '#prefix'        => '<div>',
        '#suffix'        => '</div></div>',
      ];
      $form['review_city'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('City'),
        '#placeholder'   => $this->t('City'),
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['city'] ?? '',
        '#prefix'        => '<div class="grid grid-2 mt-4"><div>',
        '#suffix'        => '</div>',
      ];
      $form['review_state'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('State / Province'),
        '#placeholder'   => $this->t('State / Province'),
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['state'] ?? '',
        '#prefix'        => '<div>',
        '#suffix'        => '</div>',
      ];
      $form['review_zip'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('ZIP / Postal Code'),
        '#placeholder'   => $this->t('ZIP / Postal Code'),
        '#attributes'    => ['class' => ['form-control']],
        '#default_value' => $editing['zip'] ?? '',
        '#prefix'        => '<div>',
        '#suffix'        => '</div></div>',
      ];

      $form['review_btns_open'] = ['#markup' => '<div class="btn-row-right">'];

      if ($is_edit) {
        $form['update_review'] = [
          '#type'                    => 'submit',
          '#value'                   => $this->t('✔ Update Review'),
          '#name'                    => 'update_review',
          '#submit'                  => ['::updateReviewSubmit'],
          '#validate'                => ['::addReviewValidate'],
          '#ajax'                    => [
            'callback' => '::ajaxRefreshForm',
            'wrapper'  => 'rebuttal-form-wrapper',
            'effect'   => 'fade',
          ],
          '#attributes'              => ['class' => ['btn', 'btn-warning']],
          '#limit_validation_errors' => [],
        ];
        $form['cancel_edit'] = [
          '#type'                    => 'submit',
          '#value'                   => $this->t('✖ Cancel'),
          '#name'                    => 'cancel_edit',
          '#submit'                  => ['::cancelEditSubmit'],
          '#ajax'                    => [
            'callback' => '::ajaxRefreshForm',
            'wrapper'  => 'rebuttal-form-wrapper',
            'effect'   => 'fade',
          ],
          '#attributes'              => ['class' => ['btn', 'btn-secondary']],
          '#limit_validation_errors' => [],
        ];
      }
      else {
        $form['add_review'] = [
          '#type'                    => 'submit',
          '#value'                   => $this->t('+ Add Review to Rebuttal'),
          '#name'                    => 'add_review',
          '#submit'                  => ['::addReviewSubmit'],
          '#validate'                => ['::addReviewValidate'],
          '#ajax'                    => [
            'callback' => '::ajaxRefreshForm',
            'wrapper'  => 'rebuttal-form-wrapper',
            'effect'   => 'fade',
          ],
          '#attributes'              => ['class' => ['btn', 'btn-primary']],
          '#limit_validation_errors' => [],
        ];
      }

      $form['review_btns_close'] = ['#markup' => '</div>'];
    }
    else {
      $form['max_reviews_notice'] = [
        '#markup' => '<p class="text-danger">Maximum of 10 reviews reached.</p>',
      ];
    }

    $form['review_evidence_close'] = [
      '#markup' => '<p class="footer-hint help">You can attach up to 10 reviews. Each review is saved individually.</p></section>',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // SECTION 3 – REVIEWS TABLE
    // ═══════════════════════════════════════════════════════════════════════
    $form['reviews_table_open'] = [
      '#markup' => '<section class="card"><h2>Reviews Added</h2>',
    ];

    foreach ($reviews as $i => $review) {
      $form['edit_trigger_' . $i] = [
        '#type'                    => 'submit',
        '#value'                   => $this->t('edit_@i', ['@i' => $i]),
        '#name'                    => 'edit_trigger_' . $i,
        '#submit'                  => ['::editReviewSubmit'],
        '#ajax'                    => [
          'callback' => '::ajaxRefreshForm',
          'wrapper'  => 'rebuttal-form-wrapper',
          'effect'   => 'fade',
        ],
        '#attributes'              => [
          'style'             => 'display:none !important;',
          'data-review-index' => $i,
          'id'                => 'edit-trigger-' . $i,
        ],
        '#limit_validation_errors' => [],
      ];
      $form['delete_trigger_' . $i] = [
        '#type'                    => 'submit',
        '#value'                   => $this->t('delete_@i', ['@i' => $i]),
        '#name'                    => 'delete_trigger_' . $i,
        '#submit'                  => ['::removeReviewSubmit'],
        '#ajax'                    => [
          'callback' => '::ajaxRefreshForm',
          'wrapper'  => 'rebuttal-form-wrapper',
          'effect'   => 'fade',
        ],
        '#attributes'              => [
          'style'             => 'display:none !important;',
          'data-review-index' => $i,
          'id'                => 'delete-trigger-' . $i,
        ],
        '#limit_validation_errors' => [],
      ];
    }

    $header = [
      'select'         => Markup::create('<input type="checkbox" id="select-all-reviews" title="Select / deselect all">'),
      'platform'       => $this->t('Platform'),
      'buyer_username' => $this->t('Buyer Username'),
      'order_id'       => $this->t('Order ID'),
      'buyer_email'    => $this->t('Buyer Email'),
      'phone'          => $this->t('Phone'),
      'address'        => $this->t('Address'),
      'review_date'    => $this->t('Review Date'),
      'status'         => $this->t('Status'),
      'actions'        => $this->t('Actions'),
    ];

    $platform_options = $this->getEcommercePlatformOptions();
    $rows = [];

    foreach ($reviews as $i => $review) {
      $address = implode(', ', array_filter([
        $review['street_address'] ?? '',
        $review['apartment']      ?? '',
        $review['city']           ?? '',
        $review['state']          ?? '',
        $review['zip']            ?? '',
      ]));

      $platform_label = $platform_options[$review['ecommerce_platform']] ?? ($review['ecommerce_platform'] ?? '');

      $checkbox_html = '<input type="checkbox"
        name="review_select[' . $i . ']"
        id="review-select-' . $i . '"
        value="' . $i . '"
        class="review-row-checkbox"
        checked="checked">';

      $actions_html =
        '<button type="button" class="btn btn-success btn-sm my-1 review-edit-btn"'
        . ' data-edit-id="edit-trigger-' . $i . '">&#9999; Edit</button> '
        . '<button type="button" class="btn btn-danger btn-sm my-1 review-delete-btn"'
        . ' data-delete-id="delete-trigger-' . $i . '">&#128465; Delete</button>';

      $row = [
        'select'         => ['data' => Markup::create($checkbox_html)],
        'platform'       => $platform_label,
        'buyer_username' => $review['buyer_username'] ?? '',
        'order_id'       => $review['order_id']       ?? '',
        'buyer_email'    => $review['buyer_email']     ?? '',
        'phone'          => $review['buyer_phone']     ?? '',
        'address'        => $address,
        'review_date'    => ($review['review_date'] ?? '') . ' ' . ($review['review_time'] ?? ''),
        'status'         => ['data' => Markup::create('<span class="status-pill">Included</span>')],
        'actions'        => ['data' => Markup::create($actions_html)],
      ];

      if ($is_edit && (int) $edit_index === $i) {
        $row = ['#attributes' => ['class' => ['color-warning']]] + $row;
      }

      $rows[] = $row;
    }

    if (!empty($rows)) {
      $form['reviews_table'] = [
        '#type'       => 'table',
        '#header'     => $header,
        '#rows'       => $rows,
        '#attributes' => ['id' => 'reviews-evidence-table', 'class' => ['reviews-evidence-table']],
      ];
    }
    else {
      $form['reviews_table_empty'] = [
        '#markup' => '<p class="help text-center">No reviews added yet.</p>',
      ];
    }

    $form['reviews_table_close'] = [
      '#markup' => '<p class="footer-hint help">Only checked reviews will appear in the public rebuttal.</p></section>',
    ];

    $form['review_table_js'] = [
      '#markup' => Markup::create('
        <script>
        (function () {
          "use strict";
          document.addEventListener("change", function (e) {
            if (e.target && e.target.id === "select-all-reviews") {
              var checked = e.target.checked;
              document.querySelectorAll(".review-row-checkbox").forEach(function (cb) {
                cb.checked = checked;
              });
            }
          });
          document.addEventListener("click", function (e) {
            var editBtn = e.target.closest(".review-edit-btn");
            if (editBtn) {
              e.preventDefault();
              var hidden = document.getElementById(editBtn.getAttribute("data-edit-id"));
              if (hidden) { hidden.click(); }
              return;
            }
            var delBtn = e.target.closest(".review-delete-btn");
            if (delBtn) {
              e.preventDefault();
              if (!window.confirm("Are you sure you want to delete this review?")) { return; }
              var hidden = document.getElementById(delBtn.getAttribute("data-delete-id"));
              if (hidden) { hidden.click(); }
            }
          });
        })();
        </script>
      '),
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // SECTION 4 – MERCHANT REBUTTAL STATEMENT
    // ═══════════════════════════════════════════════════════════════════════
    $form['rebuttal_statement_open'] = [
      '#markup' => '<section class="card"><h2>Merchant Rebuttal Statement</h2>',
    ];
    $form['field_rebuttal_statement'] = [
      '#type'        => 'textarea',
      '#title'       => $this->t('Rebuttal Statement'),
      '#placeholder' => $this->t('Explain your side clearly using factual language.'),
      '#rows'        => 5,
      '#attributes'  => ['class' => ['form-control']],
    ];
    $form['rebuttal_statement_help'] = [
      '#markup' => '<p class="help">Do not include private data, promotions, or abusive language.</p><div style="margin-top:12px;">',
    ];
    $form['field_upload_supporting_evidence'] = [
      '#type'              => 'managed_file',
      '#title'             => $this->t('Upload Supporting Evidence'),
      '#upload_location'   => 'public://merchant/rebuttal/evidence/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif pdf mp4 mov'],
        'file_validate_size'       => [20 * 1024 * 1024],
      ],
      '#multiple'         => TRUE,
      '#attributes'       => ['class' => ['form-control']],
      '#element_validate' => ['::evidenceElementValidate'],
    ];
    $form['supporting_evidence_help'] = [
      '#markup' => '<p class="help">Optional: product photos, invoices, test results, or videos.</p></div>',
    ];

    $form['actions'] = [
      '#type'   => 'actions',
      '#prefix' => '<div class="btn-group">',
      '#suffix' => '</div>',
    ];
    $form['actions']['draft'] = [
      '#type'                    => 'submit',
      '#value'                   => $this->t('Save as Draft'),
      '#name'                    => 'save_draft',
      '#submit'                  => ['::saveAsDraft'],
      '#attributes'              => ['class' => ['btn', 'btn-outline']],
      '#limit_validation_errors' => [],
    ];
    $form['actions']['preview'] = [
      '#type'                    => 'submit',
      '#value'                   => $this->t('Preview'),
      '#name'                    => 'preview_rebuttal',
      '#submit'                  => ['::previewRebuttal'],
      '#attributes'              => ['class' => ['btn', 'btn-outline']],
      '#limit_validation_errors' => [],
    ];
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Publish Rebuttal'),
      '#name'        => 'publish_rebuttal',
      '#button_type' => 'primary',
      '#attributes'  => ['class' => ['btn', 'btn-success']],
      '#submit' => ['::publishRebuttal'],
    ];
    $form['rebuttal_statement_close'] = ['#markup' => '</section>'];

    return $form;
  }

public function publishRebuttal(array &$form, FormStateInterface $form_state): void {
  $this->saveNode($form, $form_state, 1);
}
  // ══════════════════════════════════════════════════════════════════════════
  // AJAX CALLBACK – screenshot upload
  // Fires when managed_file upload button is clicked. At this point
  // $element['#value'] IS populated because it is the triggering element.
  // We store the FID immediately into form storage here.
  // ══════════════════════════════════════════════════════════════════════════

  public function screenshotUploadAjax(array &$form, FormStateInterface $form_state) {
    // FID is already stored by screenshotElementValidate which runs before
    // any submit/ajax handler. Just return the wrapper element.
    return $form['review_screenshot'];
  }

  // ══════════════════════════════════════════════════════════════════════════
  // ELEMENT VALIDATE – review screenshot
  // ══════════════════════════════════════════════════════════════════════════

  public function screenshotElementValidate(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $fids = $element['#value'] ?? [];

    \Drupal::logger('merchant_dashboard')->debug('screenshotElementValidate — #value raw: @v', [
      '@v' => print_r($fids, TRUE),
    ]);

    $fid = NULL;

    // Format A: flat array [0 => 42]
    if (!empty($fids[0]) && is_numeric($fids[0])) {
      $fid = (int) $fids[0];
    }
    // Format B: ['fids' => '42'] string
    elseif (!empty($fids['fids']) && is_string($fids['fids']) && trim($fids['fids']) !== '') {
      $parts = array_filter(array_map('intval', explode(' ', trim($fids['fids']))));
      $fid   = !empty($parts) ? (int) reset($parts) : NULL;
    }

    // Format C: file_NNN key in raw input (draft path where widget
    // processing was bypassed by #limit_validation_errors).
    if (!$fid) {
      $raw_widget = $form_state->getUserInput()['review_screenshot'] ?? [];
      if (is_array($raw_widget)) {
        foreach ($raw_widget as $key => $val) {
          if (is_string($key) && strpos($key, 'file_') === 0) {
            $candidate = (int) substr($key, 5);
            if ($candidate > 0) {
              $fid = $candidate;
              break;
            }
          }
        }
      }
    }

    \Drupal::logger('merchant_dashboard')->debug('screenshotElementValidate — resolved FID: @f', [
      '@f' => $fid ?? 'NULL',
    ]);

    if ($fid) {
      $file = File::load($fid);
      if ($file) {
        if ($file->isTemporary()) {
          $file->setPermanent();
          $file->save();
        }
        $form_state->set('pending_screenshot_fid', $fid);
        \Drupal::logger('merchant_dashboard')->debug('screenshotElementValidate — stored FID @f in form storage', ['@f' => $fid]);
      }
      else {
        \Drupal::logger('merchant_dashboard')->warning('screenshotElementValidate — FID @f not found in DB', ['@f' => $fid]);
      }
    }
    // If no FID found at all, do NOT clear pending_screenshot_fid —
    // it may have been set by a previous upload in this session.
  }

  // ══════════════════════════════════════════════════════════════════════════
  // ELEMENT VALIDATE – supporting evidence (#multiple = TRUE)
  // ══════════════════════════════════════════════════════════════════════════

  public function evidenceElementValidate(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $raw_value = $element['#value'] ?? [];
    $clean     = [];

    // Format A: flat array of numeric FIDs [0 => 42, 1 => 43]
    if (is_array($raw_value)) {
      foreach ($raw_value as $key => $val) {
        if (is_numeric($val) && (int) $val > 0) {
          $clean[] = (int) $val;
        }
      }
    }

    // Format B: file_NNN keys in raw input (draft bypass path)
    if (empty($clean)) {
      $raw_field = $form_state->getUserInput()['field_upload_supporting_evidence'] ?? [];
      if (is_array($raw_field)) {
        foreach ($raw_field as $key => $val) {
          if (is_string($key) && strpos($key, 'file_') === 0) {
            $fid = (int) substr($key, 5);
            if ($fid > 0) {
              $clean[] = $fid;
            }
          }
        }
      }
    }

    $confirmed = [];
    foreach (array_unique($clean) as $fid) {
      $file = File::load((int) $fid);
      if ($file) {
        if ($file->isTemporary()) {
          $file->setPermanent();
          $file->save();
        }
        $confirmed[] = (int) $fid;
      }
    }

    $form_state->set('pending_evidence_fids', $confirmed);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // AJAX CALLBACK
  // ══════════════════════════════════════════════════════════════════════════

  public function ajaxRefreshForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  // ══════════════════════════════════════════════════════════════════════════
  // VALIDATE – review sub-form
  // ══════════════════════════════════════════════════════════════════════════

  public function addReviewValidate(array &$form, FormStateInterface $form_state): void {
    $input    = $form_state->getUserInput();
    $buyer    = trim($input['review_buyer_username']          ?? '');
    $re_buyer = trim($input['review_re_enter_buyer_username'] ?? '');

    if (empty($input['review_ecommerce_platform'])) {
      $form_state->setErrorByName('review_ecommerce_platform', $this->t('Please select an e-commerce platform.'));
    }
    if (empty(trim($input['review_product_link'] ?? ''))) {
      $form_state->setErrorByName('review_product_link', $this->t('Product Link is required.'));
    }
    if (empty($buyer)) {
      $form_state->setErrorByName('review_buyer_username', $this->t('Buyer Username is required.'));
    }
    if (empty($re_buyer)) {
      $form_state->setErrorByName('review_re_enter_buyer_username', $this->t('Re-Enter Buyer Username is required.'));
    }
    if (!empty($buyer) && !empty($re_buyer) && $buyer !== $re_buyer) {
      $form_state->setErrorByName('review_re_enter_buyer_username', $this->t('Buyer usernames do not match.'));
    }
    if (empty(trim($input['review_order_id'] ?? ''))) {
      $form_state->setErrorByName('review_order_id', $this->t('Order ID is required.'));
    }
    if (empty(trim($input['review_date'] ?? ''))) {
      $form_state->setErrorByName('review_date', $this->t('Review Date is required.'));
    }
  }

  // ══════════════════════════════════════════════════════════════════════════
  // SUBMIT – Add review
  // ══════════════════════════════════════════════════════════════════════════

  public function addReviewSubmit(array &$form, FormStateInterface $form_state): void {
    $reviews = $form_state->get('reviews') ?? [];

    if (count($reviews) >= 10) {
      $this->messenger()->addWarning($this->t('Maximum of 10 reviews allowed.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    $input = $form_state->getUserInput();

    // Get FID — screenshotElementValidate already ran and stored it.
    // Also try raw input file_NNN as final fallback.
    $screenshot_fid = $form_state->get('pending_screenshot_fid');

    if (!$screenshot_fid) {
      $raw_widget = $input['review_screenshot'] ?? [];
      if (is_array($raw_widget)) {
        foreach ($raw_widget as $key => $val) {
          if (is_string($key) && strpos($key, 'file_') === 0) {
            $candidate = (int) substr($key, 5);
            if ($candidate > 0) {
              $screenshot_fid = $candidate;
              // Make permanent immediately.
              $file = File::load($screenshot_fid);
              if ($file && $file->isTemporary()) {
                $file->setPermanent();
                $file->save();
              }
              break;
            }
          }
        }
      }
    }

    \Drupal::logger('merchant_dashboard')->debug('addReviewSubmit — final screenshot FID: @f', [
      '@f' => $screenshot_fid ?? 'NULL',
    ]);

    $reviews[] = [
      'screenshot'              => $screenshot_fid ?: NULL,
      'ecommerce_platform'      => $input['review_ecommerce_platform']      ?? '',
      'product_link'            => $input['review_product_link']            ?? '',
      'buyer_username'          => $input['review_buyer_username']          ?? '',
      're_enter_buyer_username' => $input['review_re_enter_buyer_username'] ?? '',
      'buyer_email'             => $input['review_buyer_email']             ?? '',
      'buyer_phone'             => $input['review_buyer_phone']             ?? '',
      'order_id'                => $input['review_order_id']                ?? '',
      'review_date'             => $input['review_date']                    ?? '',
      'review_time'             => $input['review_time']                    ?? '',
      'street_address'          => $input['review_street_address']          ?? '',
      'apartment'               => $input['review_apartment']               ?? '',
      'city'                    => $input['review_city']                    ?? '',
      'state'                   => $input['review_state']                   ?? '',
      'zip'                     => $input['review_zip']                     ?? '',
    ];

    $form_state->set('reviews', $reviews);
    $form_state->set('pending_screenshot_fid', NULL);
    $this->clearReviewFields($form_state);
    $form_state->setRebuild(TRUE);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // SUBMIT – Load review into edit form
  // ══════════════════════════════════════════════════════════════════════════

  public function editReviewSubmit(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $index   = $trigger['#attributes']['data-review-index'] ?? NULL;

    if ($index !== NULL) {
      $form_state->set('edit_index', (int) $index);
      $reviews      = $form_state->get('reviews') ?? [];
      $existing_fid = $reviews[(int) $index]['screenshot'] ?? NULL;
      $form_state->set('pending_screenshot_fid', $existing_fid ?: NULL);
    }

    $form_state->setRebuild(TRUE);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // SUBMIT – Save updated review
  // ══════════════════════════════════════════════════════════════════════════

  public function updateReviewSubmit(array &$form, FormStateInterface $form_state): void {
    $edit_index = $form_state->get('edit_index');

    if ($edit_index === NULL) {
      $form_state->setRebuild(TRUE);
      return;
    }

    $reviews        = $form_state->get('reviews') ?? [];
    $input          = $form_state->getUserInput();
    $screenshot_fid = $form_state->get('pending_screenshot_fid')
      ?: ($reviews[$edit_index]['screenshot'] ?? NULL);

    $reviews[$edit_index] = [
      'screenshot'              => $screenshot_fid,
      'ecommerce_platform'      => $input['review_ecommerce_platform']      ?? '',
      'product_link'            => $input['review_product_link']            ?? '',
      'buyer_username'          => $input['review_buyer_username']          ?? '',
      're_enter_buyer_username' => $input['review_re_enter_buyer_username'] ?? '',
      'buyer_email'             => $input['review_buyer_email']             ?? '',
      'buyer_phone'             => $input['review_buyer_phone']             ?? '',
      'order_id'                => $input['review_order_id']                ?? '',
      'review_date'             => $input['review_date']                    ?? '',
      'review_time'             => $input['review_time']                    ?? '',
      'street_address'          => $input['review_street_address']          ?? '',
      'apartment'               => $input['review_apartment']               ?? '',
      'city'                    => $input['review_city']                    ?? '',
      'state'                   => $input['review_state']                   ?? '',
      'zip'                     => $input['review_zip']                     ?? '',
    ];

    $form_state->set('reviews', $reviews);
    $form_state->set('edit_index', NULL);
    $form_state->set('pending_screenshot_fid', NULL);
    $this->clearReviewFields($form_state);
    $this->messenger()->addStatus($this->t('Review #@n updated.', ['@n' => ($edit_index + 1)]));
    $form_state->setRebuild(TRUE);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // SUBMIT – Cancel edit
  // ══════════════════════════════════════════════════════════════════════════

  public function cancelEditSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->set('edit_index', NULL);
    $form_state->set('pending_screenshot_fid', NULL);
    $this->clearReviewFields($form_state);
    $form_state->setRebuild(TRUE);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // SUBMIT – Delete review
  // ══════════════════════════════════════════════════════════════════════════

  public function removeReviewSubmit(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $index   = $trigger['#attributes']['data-review-index'] ?? NULL;

    if ($index !== NULL) {
      $reviews = $form_state->get('reviews') ?? [];
      array_splice($reviews, (int) $index, 1);
      $form_state->set('reviews', array_values($reviews));

      if ($form_state->get('edit_index') === (int) $index) {
        $form_state->set('edit_index', NULL);
        $form_state->set('pending_screenshot_fid', NULL);
        $this->clearReviewFields($form_state);
      }

      $this->messenger()->addStatus($this->t('Review deleted.'));
    }

    $form_state->setRebuild(TRUE);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // DRAFT / PREVIEW / PUBLISH
  // ══════════════════════════════════════════════════════════════════════════

  public function saveAsDraft(array &$form, FormStateInterface $form_state): void {
    $this->saveNode($form, $form_state, 0);
  }

  public function previewRebuttal(array &$form, FormStateInterface $form_state): void {
    $node = $this->saveNode($form, $form_state, 0);
    if ($node) {
      $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
    }
  }

  // ── validateForm only runs for the Publish button (full validation path).
  // Draft/Preview use #limit_validation_errors so this is skipped for them.
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    // Only enforce title on the publish button.
    $trigger = $form_state->getTriggeringElement();
    $name    = $trigger['#name'] ?? '';
    if ($name === 'publish_rebuttal') {
      if (empty(trim((string) $form_state->getValue('title')))) {
        $form_state->setErrorByName('title', $this->t('Rebuttal Title is required.'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->saveNode($form, $form_state, 1);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // SAVE NODE
  // ══════════════════════════════════════════════════════════════════════════

  protected function saveNode(array &$form, FormStateInterface $form_state, int $status): ?Node {
    $values    = $form_state->getValues();
    $reviews   = $form_state->get('reviews') ?? [];
    $raw_input = $form_state->getUserInput();

    // ── Title ──────────────────────────────────────────────────────────────
    $title = trim((string) ($values['title'] ?? $raw_input['title'] ?? ''));
    if (empty($title)) {
      $title = 'Draft - ' . date('Y-m-d H:i');
    }

    $select_product = (int) ($values['select_product'] ?? $raw_input['select_product'] ?? 0);


    // ── Body / Summary ─────────────────────────────────────────────────────
    $body = trim((string) ($values['body'] ?? $raw_input['body'] ?? ''));

    // ── Rebuttal Statement ─────────────────────────────────────────────────
    $rebuttal_statement = trim((string) ($values['field_rebuttal_statement'] ?? $raw_input['field_rebuttal_statement'] ?? ''));

    // ── Selected review indices ────────────────────────────────────────────
    $selected_indices = (!empty($raw_input['review_select']) && is_array($raw_input['review_select']))
      ? array_keys($raw_input['review_select'])
      : array_keys($reviews);

    // ── Supporting evidence ────────────────────────────────────────────────
    // Priority 1: element_validate stored permanent FIDs here.
    $evidence_fids = $form_state->get('pending_evidence_fids') ?? [];

    // Priority 2: full validation path (Publish).
    if (empty($evidence_fids)) {
      $val = $form_state->getValue('field_upload_supporting_evidence') ?? [];
      if (!empty($val) && is_array($val)) {
        $evidence_fids = array_values(array_filter(array_map('intval', $val)));
      }
    }

    // Priority 3: file_NNN keys in raw input.
    if (empty($evidence_fids)) {
      $raw_field = $raw_input['field_upload_supporting_evidence'] ?? [];
      if (is_array($raw_field)) {
        foreach ($raw_field as $key => $val) {
          if (is_string($key) && strpos($key, 'file_') === 0) {
            $fid = (int) substr($key, 5);
            if ($fid > 0) {
              $evidence_fids[] = $fid;
            }
          }
        }
      }
    }

    $evidence_refs = [];
    foreach (array_unique($evidence_fids) as $fid) {
      $fid  = (int) $fid;
      if ($fid <= 0) { continue; }
      $file = File::load($fid);
      if ($file) {
        if ($file->isTemporary()) {
          $file->setPermanent();
          $file->save();
        }
        $evidence_refs[] = ['target_id' => $fid];
      }
    }

    // ── Build paragraphs ───────────────────────────────────────────────────
    $paragraph_refs = [];

    foreach ($selected_indices as $idx) {
      if (!isset($reviews[$idx])) { continue; }

      $review       = $reviews[$idx];
      $product_link = trim($review['product_link'] ?? '');
      if (!empty($product_link) && !preg_match('#^https?://#i', $product_link)) {
        $product_link = 'https://' . $product_link;
      }

      $paragraph = Paragraph::create(['type' => 'rebuttal']);
      #$paragraph->set('field_e_commerce_platform',     $review['ecommerce_platform']     ?? NULL);
      $platform = $review['ecommerce_platform'] ?? NULL;

if ($platform instanceof \Drupal\Core\StringTranslation\TranslatableMarkup) {
  $platform = (string) $platform;
}

if (!empty($platform) && is_numeric($platform)) {
  $paragraph->set('field_e_commerce_platform', [
    'target_id' => (int) $platform,
  ]);
}
      $paragraph->set('field_product_link',            ['uri' => $product_link, 'title' => '']);
      $paragraph->set('field_buyer_username',          $review['buyer_username']          ?? NULL);
      $paragraph->set('field_re_enter_buyer_username', $review['re_enter_buyer_username'] ?? NULL);
      $paragraph->set('field_buyer_email',             $review['buyer_email']             ?? NULL);
      $paragraph->set('field_buyer_phone',             $review['buyer_phone']             ?? NULL);
      $paragraph->set('field_order_id',                $review['order_id']                ?? NULL);
      $paragraph->set('field_street_address',          $review['street_address']          ?? NULL);
      $paragraph->set('field_apartment_suite_etc',     $review['apartment']               ?? NULL);
      $paragraph->set('field_city',                    $review['city']                    ?? NULL);
      $paragraph->set('field_state_province',          $review['state']                   ?? NULL);
      $paragraph->set('field_zip_postal_code',         $review['zip']                     ?? NULL);

      if (!empty($review['review_date'])) {
        $time = !empty($review['review_time']) ? $review['review_time'] : '00:00:00';
        if (strlen($time) === 5) { $time .= ':00'; }
        $paragraph->set('field_review_date', $review['review_date'] . 'T' . $time);
      }

      $screenshot_fid = !empty($review['screenshot']) ? (int) $review['screenshot'] : NULL;

      \Drupal::logger('merchant_dashboard')->debug('saveNode paragraph[@idx] — screenshot FID: @f', [
        '@idx' => $idx,
        '@f'   => $screenshot_fid ?? 'NULL',
      ]);

      if ($screenshot_fid) {
        $file = File::load($screenshot_fid);
        if ($file) {
          if ($file->isTemporary()) {
            $file->setPermanent();
            $file->save();
          }
          $paragraph->set('field_review_screenshot', [
            ['target_id' => $screenshot_fid, 'alt' => 'Review screenshot'],
          ]);
          \Drupal::logger('merchant_dashboard')->debug('saveNode — field_review_screenshot set with FID @f', ['@f' => $screenshot_fid]);
        }
        else {
          \Drupal::logger('merchant_dashboard')->warning('saveNode — screenshot FID @f not found in DB', ['@f' => $screenshot_fid]);
        }
      }

      try {
        $paragraph->save();

        if ($screenshot_fid) {
          $file = File::load($screenshot_fid);
          if ($file) {
            \Drupal::service('file.usage')->add($file, 'merchant_dashboard', 'paragraph', $paragraph->id());
          }
        }

        $paragraph_refs[] = [
          'target_id'          => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
      catch (\Exception $e) {
        \Drupal::logger('merchant_dashboard')->error('Paragraph save error: @msg', ['@msg' => $e->getMessage()]);
      }
    }

    // ── Create node ────────────────────────────────────────────────────────
    $node = Node::create([
      'type'                             => 'seller_rebuttal',
      'title'                            => $title,
      'status'                           => $status,
      'field_rebuttal_summary'           => ['value' => $body,               'format' => 'full_html'],
      'field_rebuttal_statement'         => ['value' => $rebuttal_statement, 'format' => 'full_html'],
      'field_rebuttal_para'              => $paragraph_refs,
      'field_upload_supporting_evidence' => $evidence_refs,
      'field_select_product'             => $select_product > 0 ? ['target_id' => $select_product] : NULL, // ← ADD THIS
 
    ]);

    if ($node->hasField('moderation_state')) {
  #$node->set('moderation_state', $status ? 'published' : 'draft');

      if ($node->hasField('moderation_state')) {

  // Get all allowed states dynamically
  $workflow = \Drupal::service('content_moderation.moderation_information')
    ->getWorkflowForEntity($node);

  if ($workflow) {
    $states = $workflow->getTypePlugin()->getStates();

    // Find a "published-like" state
    $publish_state = NULL;

    foreach ($states as $state_id => $state) {
      if ($state->isPublishedState()) {
        $publish_state = $state_id;
        break;
      }
    }

    // Fallback
    $publish_state = $publish_state ?: 'published';

    $node->set('moderation_state', $status ? $publish_state : 'draft');
  }
}
}

    $node->save();

    foreach ($evidence_refs as $ref) {
      $file = File::load($ref['target_id']);
      if ($file) {
        \Drupal::service('file.usage')->add($file, 'merchant_dashboard', 'node', $node->id());
      }
    }

    if ($status === 1) {
      $this->messenger()->addStatus($this->t('Rebuttal %title published.', ['%title' => $title]));
      $form_state->setRedirect('<current>');
    }
    else {
      $this->messenger()->addStatus($this->t('Rebuttal %title saved as draft.', ['%title' => $title]));
    }

    return $node;
  }

  // ══════════════════════════════════════════════════════════════════════════
  // HELPERS
  // ══════════════════════════════════════════════════════════════════════════

  private function clearReviewFields(FormStateInterface $form_state): void {
    $fields = [
      'review_ecommerce_platform',
      'review_product_link',
      'review_buyer_username',
      'review_re_enter_buyer_username',
      'review_buyer_email',
      'review_buyer_phone',
      'review_order_id',
      'review_date',
      'review_time',
      'review_street_address',
      'review_apartment',
      'review_city',
      'review_state',
      'review_zip',
    ];

    foreach ($fields as $f) {
      $form_state->setValue($f, NULL);
    }

    // Clear review_screenshot VALUE only — NOT raw input.
    // Raw input must survive so screenshotElementValidate can
    // read the file_NNN keys on the next form rebuild.
    $form_state->setValue('review_screenshot', NULL);

    $input = $form_state->getUserInput();
    foreach ($fields as $f) {
      unset($input[$f], $input[$f . '_fids']);
    }
    // Do NOT touch $input['review_screenshot'] or $input['files'].
    $form_state->setUserInput($input);
  }

  protected function getEcommercePlatformOptions(): array {
    $options = [];
    $terms   = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'e_commerce_platform']);
    foreach ($terms as $term) {
      $options[$term->id()] = $term->getName();
    }
    return $options;
  }


protected function getMerchantProductOptions(): array {
  $options = [];

  // Step 1: find users with 'merchant' role
  $merchant_uids = \Drupal::entityQuery('user')
    ->condition('roles', 'merchant')
    ->accessCheck(FALSE)
    ->execute();

  \Drupal::logger('merchant_dashboard')->debug('Merchant UIDs: @u', [
    '@u' => implode(', ', $merchant_uids ?: ['NONE']),
  ]);

  if (empty($merchant_uids)) {
    return $options;
  }

  // Step 2: NOTE — 'marchant_products' (correct spelling from your system)
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'marchant_products')
    ->condition('uid', array_values($merchant_uids), 'IN')
    ->accessCheck(FALSE)
    ->sort('title', 'ASC')
    ->execute();

  \Drupal::logger('merchant_dashboard')->debug('Product NIDs: @n', [
    '@n' => implode(', ', $nids ?: ['NONE']),
  ]);

  if (empty($nids)) {
    return $options;
  }

  $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
  foreach ($nodes as $node) {
    $options[$node->id()] = $node->getTitle();
  }

  return $options;
}

}