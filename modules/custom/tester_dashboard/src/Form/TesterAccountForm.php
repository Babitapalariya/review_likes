<?php

namespace Drupal\tester_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;



/**
 * Provides Discount Form.
 */
class TesterAccountForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tester_dashboard_tester_account_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Load taxonomy terms from vocabulary "tester_account".
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('tester_account');

    $options = ['' => $this->t('Select Type')];
    $term_map = [];

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
      $term_map[$term->name] = $term->tid;
    }

    // Store term_map in form state for use in submit
    $form['#term_map'] = $term_map;
    
    $form['title'] = [
      '#markup' => '<h2 class=" mb-4 text-center">Account</h2>',
    ];
    
    // Dropdown for selecting discount type.
    $form['tester_account'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Account Type'),
      '#options' => $options,
      '#required' => TRUE,
      '#attributes' => [
        'id' => 'edit-discount-type',
        'class' => ['form-select', 'required'],
      ],
      '#prefix' => '<div class="row mb-4">'
    ];

    // Deposit
    if (isset($term_map['Deposit'])) {
      $tid = $term_map['Deposit'];
      $form['deposit'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'account-container-' . $tid,
          'class' => ['account-container deposit row mt-5'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="tester_account"]' => ['value' => (string) $tid],
          ],
        ],
      ];
      $form['deposit']['bank_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Bank Name'),
        '#placeholder' => 'EnterBank Name',
        '#prefix' => '<div class="col-md-6">',
        '#suffix' => '</div>',
      ];
      $form['deposit']['account_number'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Account Number'),
        '#placeholder' => 'Enter Account Number',
        '#prefix' => '<div class="col-md-6">',
        '#suffix' => '</div>',
      ];
      $form['deposit']['enter_account_number'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Re Enter Account Number'),
        '#placeholder' => 'Enter Re Enter Account Number',
        '#prefix' => '<div class="col-md-6 mt-3">',
        '#suffix' => '</div>',
      ];

      $form['deposit']['ecommerce_platform'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Currency'),
      '#options' => $this->getLanguageOptions(),
    //   '#required' => TRUE,
      '#empty_option' => $this->t('- Select Currency -'),
      '#prefix' => '<div class="col-md-6 mt-3">',
      '#suffix' => '</div>', // Close the row
    ];
    $form['deposit']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Bank Deatils'),
      '#attributes' => ['class' => ['btn', 'btn-default', 'px-4']],
         '#prefix' => '<div class="my-3">',
        '#suffix' => '</div>',
    ];

    $form['deposit']['bank_table'] = [
      '#markup' => $this->getBankDetailsTable(),
      '#prefix' => '<div class="row"><div class="table-responsive bank-details mb-5">',
      '#suffix' => '</div></div> </div>',
    ];
   
    }

    // Tax
    if (isset($term_map['Tax'])) {
      $tid = $term_map['Tax'];
      $form['taxaccount'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'account-container-' . $tid,
          'class' => ['account-container taxaccount row mb-3'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="tester_account"]' => ['value' => (string) $tid],
          ],
        ],
      ];
      $form['taxaccount']['full_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Full Name'),
        '#placeholder' => 'Enter Full Name',
        '#prefix' => '<div class="col-md-6">',
        '#suffix' => '</div>',
      ];
      $form['taxaccount']['pan_number'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Tax ID / PAN Number'),
        '#placeholder' => 'Enter Tax ID / PAN Number',
        '#prefix' => '<div class="col-md-6">',
        '#suffix' => '</div>',
      ];
      $form['taxaccount']['country'] = [
        '#prefix' => '<div class="row mb-3"> <div class="col-md-6">',
        '#type' => 'select',
        '#title' => $this->t('Country'),
        '#options' => \Drupal::service('country.field.manager')->getList(),
        //   '#default_value' => $user->get('field_country')->value,
        '#required' => TRUE,
        '#suffix' => '</div>',
        '#attributes' => [
            'class' => ['form-select'],
            'id' => 'country',
        ],
        '#name' => 'country',
        ];
       $form['taxaccount']['screenshot'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Tax Document'),
      '#upload_location' => 'public://tester/tax/documents/',
      '#upload_validators' => [
        'file_validate_extensions' => ['gif pdf '],
        'file_validate_size' => [5 * 1024 * 1024],
      ],
      '#required' => TRUE,
      '#prefix' => '<div class="col-md-6">',
      '#suffix' => '</div>',
    ];
      $form['taxaccount']['coupon_multi_use'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Multi Use'),
        '#prefix' => '<div class="form-check mb-3">',
        '#suffix' => '</div>',
      ];
      $form['taxaccount']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Tax info'),
      '#attributes' => ['class' => ['btn', 'btn-default', 'my-5']],
       '#prefix' => '<div class="my-3">',
        '#suffix' => '</div>',
    
    ];

     $form['taxaccount']['tax_table'] = [
      '#markup' => $this->getTaxDetailsTable(),
      '#prefix' => '<div class="row"><div class="table-responsive bank-details mb-5">',
      '#suffix' => '</div></div>',
    ];
    }

    // Earning summry
    if (isset($term_map['Earning summry'])) {
      $tid = $term_map['Earning summry'];
      $form['earningaccount'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'account-container-' . $tid,
          'class' => ['account-container earningaccount row mt-5'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="tester_account"]' => ['value' => (string) $tid],
          ],
        ],
      ];
    }


    // Submit button
    // $form['submit'] = [
    //   '#type' => 'submit',
    //   '#value' => $this->t('Save Bank Deatils'),
    //   '#attributes' => ['class' => ['btn', 'btn-default', 'px-4']],
    //   '#suffix' => '</div>',
    // ];

    // Add JavaScript using proper Drupal method
    $form['#attached']['library'][] = 'tester_dashboard/tester_form';
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $selected_tid = $form_state->getValue('tester_account');
    $term_map = $form['#term_map'];
    $values = $form_state->getValues();

    // Validate based on selected discount type
    switch ($selected_tid) {
      case $term_map['Deposit'] ?? NULL:
        if (empty($values['bank_name'])) {
          $form_state->setErrorByName('bank_name', $this->t('Bank Name is required for Deposit.'));
        }
        if (empty($values['account_number'])) {
          $form_state->setErrorByName('account_number', $this->t('Account is required for Deposit.'));
        }
   // Validate ecommerce platform
  $ecommerce_platform = $values['ecommerce_platform'];
  if (empty($ecommerce_platform)) {
    $this->messenger()->addError($this->t('E-commerce platform is required.'));
    return;
  }
        if (empty($values['enter_account_number'])) {
          $form_state->setErrorByName('enter_account_number', $this->t('Re Enter is required for Deposit.'));
        }
         $account_number = $form_state->getValue('account_number');
    $enter_account_number = $form_state->getValue('enter_account_number');
    $ecommerce_platform = $form_state->getValue('ecommerce_platform');
         // Add username matching validation
    if ($account_number !== $enter_account_number) {
      $form_state->setErrorByName('repeat_username', 
        $this->t('Account Number do not match.'));
    }

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

     $term = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->load($ecommerce_platform);

  if (!$term) {
    $this->messenger()->addError($this->t('Invalid e-commerce platform selected.'));
    return;
  }
        // if (empty($values['flat_amount_saved'])) {
        //   $form_state->setErrorByName('flat_amount_saved', $this->t('Amount Saved is required for Deposit.'));
        // }
        break;

      case $term_map['Tax'] ?? NULL:
        if (empty($values['full_name'])) {
          $form_state->setErrorByName('full_name', $this->t('Tax Name is required for Tax.'));
        }
        if (empty($values['pan_number'])) {
          $form_state->setErrorByName('pan_number', $this->t('Pan Number is required for Tax.'));
        }  
         if (empty($values['country'])) {
          $form_state->setErrorByName('country', $this->t('Country is required for Tax.'));
        }  
      if (empty($values['screenshot'])) {
          $form_state->setErrorByName('screenshot', $this->t('Tax Documents is required for Tax.'));
        }  

        break;

      case $term_map['Earning summry'] ?? NULL:
        if (empty($values['bundle_product_link'])) {
          $form_state->setErrorByName('bundle_product_link', $this->t('Product Link is required for Earning summry.'));
        }
        
        break;

      case $term_map['Custom Discount'] ?? NULL:
        if (empty($values['custom_product_link'])) {
          $form_state->setErrorByName('custom_product_link', $this->t('Product Link is required for Custom Discount.'));
        }
        break;
    }
  }
protected function getLanguageOptions() {
    $options = [];
    
    // Load taxonomy terms from the Languages vocabulary
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'currency']);
    
    foreach ($terms as $term) {
      $options[$term->id()] = $term->getName();
    }
    
    return $options;
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_tid = $form_state->getValue('tester_account');
    $values = $form_state->getValues();
    $term_map = $form['#term_map'];

    // Debug: Check what values we're getting
    // \Drupal::logger('tester_dashboard')->notice('Form values: @values', ['@values' => print_r($values, TRUE)]);

    // Prepare node data based on discount type
    $node_data = [
      'type' => 'tester_account',
      'field_filter' => $selected_tid,
    ];

    // Handle fields based on selected discount type
    switch ($selected_tid) {
      case $term_map['Deposit'] ?? NULL:
        $node_data['title'] = !empty($values['bank_name']) ? $values['bank_name'] : 'Deposit - ' . date('Y-m-d H:i:s');
        if (!empty($values['account_number'])) {
          $node_data['field_sold_bys'] = $values['account_number'];
        }
        // 'field_language' => $ecommerce_platform;
        if (!empty($values['enter_account_number'])) {
          $node_data['field_product_url'] = $values['enter_account_number'];
        }
        if (!empty($values['ecommerce_platform'])) {
          $node_data['field_language'] = $values['ecommerce_platform'];
        }
        // if (!empty($values['flat_multi_use'])) {
        //   $node_data['field_terms_and_conditions'] = $values['flat_multi_use'];
        // }
        break;

      case $term_map['Tax'] ?? NULL:
        $node_data['title'] = !empty($values['full_name']) ? $values['full_name'] : 'Tax - ' . date('Y-m-d H:i:s');
       
        if (!empty($values['coupon_multi_use'])) {
          $node_data['field_terms_and_conditions'] = $values['coupon_multi_use'];
        }
        if (!empty($values['pan_number'])) {
          $node_data['field_external_id'] = $values['pan_number'];
        }
         if (!empty($values['country'])) {
          $node_data['field_country'] = $values['country'];
         }
        
       // Handle file upload correctly
  if (!empty($values['screenshot'])) {
    $file_ids = [];

    foreach ($values['screenshot'] as $fid) {
      if ($file = \Drupal\file\Entity\File::load($fid)) {
        $file->setPermanent();
        $file->save();
        $file_ids[] = $fid;
      }
    }

    if (!empty($file_ids)) {
      $node_data['field_upload_document'] = $file_ids;
    }
  }
        break;

      case $term_map['Earning summry'] ?? NULL:
        $node_data['title'] = !empty($values['bundle_product_link']) ? $values['bundle_product_link'] : 'Earning summry - ' . date('Y-m-d H:i:s');
        if (!empty($values['bundle_amount_before'])) {
          $node_data['field_buyer_username'] = $values['bundle_amount_before'];
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
    // \Drupal::logger('tester_dashboard')->notice('Node data to save: @data', ['@data' => print_r($node_data, TRUE)]);

    try {
      // Create and save the node
      $node = Node::create($node_data);
      
// if (!empty($file_ids)) {
//         $node->set('field_upload_document', $file_ids);
//       }
      
      $node->save();

      $this->messenger()->addMessage($this->t('Account has been created successfully'));
      $form_state->setRedirect('<current>');
    }
    catch (\Exception $e) {
      \Drupal::logger('tester_dashboard')->error('Error creating Account: @error', ['@error' => $e->getMessage()]);
      $this->messenger()->addError($this->t('Error saving Account. Please try again.'));
    }
  }
   private function getBankDetailsTable() {
  $current_user = \Drupal::currentUser();

  // Load Deposit term ID
  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties([
      'vid' => 'tester_account',
      'name' => 'Deposit',
    ]);

  if (empty($terms)) {
    return 'Deposit term not found.';
  }

  /** @var \Drupal\taxonomy\Entity\Term $deposit_term */
  $deposit_term = reset($terms);
  $deposit_tid = $deposit_term->id();

  // Query nodes
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'tester_account')
    ->condition('field_filter', $deposit_tid) // ✅ FIX
    ->condition('uid', $current_user->id())
    ->condition('status', 1)
    ->sort('created', 'DESC')
    ->accessCheck(TRUE);

  $nids = $query->execute();

  if (empty($nids)) {
    return '<table class="table table-bordered text-center">
      <tbody><tr><td colspan="3">No bank details saved yet.</td></tr></tbody>
    </table>';
  }

  $nodes = Node::loadMultiple($nids);

  $rows = [];
  foreach ($nodes as $node) {
$actions = [
  '#type' => 'container',
  '#attributes' => ['class' => ['d-flex']],
];

// Edit button
$actions['edit'] = [
  '#type' => 'link',
  '#title' => $this->t('Edit'),
  '#url' => Url::fromRoute(
    'entity.node.edit_form',
    ['node' => $node->id()]
  ),
  '#attributes' => [
    'class' => ['btn', 'btn-sm', 'btn-primary', 'me-2'],
  ],
];

// Delete button (custom controller route)
$actions['delete'] = [
  '#type' => 'link',
  '#title' => $this->t('Delete'),
  '#url' => Url::fromRoute(
    'tester_dashboard.delete_account',
    ['node' => $node->id()]
  ),
  '#attributes' => [
    'class' => ['btn', 'btn-sm', 'btn-danger', 'delete-tester'],
  ],
];



  $rows[] = [
  $node->label(),
  $node->get('field_sold_bys')->value ?? '',
  [
    'data' => $actions, // ✅ render array only
  ],
 
    ];
  }

  $table = [
    '#type' => 'table',
    '#header' => ['Bank Name', 'Account Number', 'Actions'],
    '#rows' => $rows,
    '#attributes' => [
      'class' => ['table', 'table-bordered', 'text-center'],
    ],
  ];

  return \Drupal::service('renderer')->render($table);
}


  /**
   * Get tax details table HTML.
   */
private function getTaxDetailsTable() {
  $current_user = \Drupal::currentUser();

  // Load Tax term ID
  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties([
      'vid' => 'tester_account',
      'name' => 'Tax',
    ]);

  if (empty($terms)) {
    return 'Tax term not found.';
  }

  /** @var \Drupal\taxonomy\Entity\Term $tax_term */
  $tax_term = reset($terms);
  $tax_tid = $tax_term->id();

  // Query tax nodes
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'tester_account')
    ->condition('field_filter', $tax_tid) // ✅ FIX
    ->condition('uid', $current_user->id())
    ->condition('status', 1)
    ->sort('created', 'DESC')
    ->accessCheck(TRUE);

  $nids = $query->execute();

  if (empty($nids)) {
    return '<table class="table table-bordered text-center">
      <tbody><tr><td colspan="5">No tax details saved yet.</td></tr></tbody>
    </table>';
  }

  $nodes = Node::loadMultiple($nids);

  $rows = [];
  foreach ($nodes as $node) {

    // Document link
    $document_link = '';
    if ($node->hasField('field_upload_document') && !$node->get('field_upload_document')->isEmpty()) {
      $file = File::load($node->get('field_upload_document')->target_id);
      if ($file) {
  $file_url = \Drupal::service('file_url_generator')
    ->generateAbsoluteString($file->getFileUri());

  $document_link = Link::fromTextAndUrl(
    'View Tax Document',
    Url::fromUri($file_url)
  )->toString();
}

    }

  



$actions = [
  '#type' => 'container',
  '#attributes' => ['class' => ['d-flex']],
];

// Edit button
$actions['edit'] = [
  '#type' => 'link',
  '#title' => $this->t('Edit'),
  '#url' => Url::fromRoute(
    'entity.node.edit_form',
    ['node' => $node->id()]
  ),
  '#attributes' => [
    'class' => ['btn', 'btn-sm', 'btn-primary', 'me-2'],
  ],
];

// Delete button (custom controller route)
$actions['delete'] = [
  '#type' => 'link',
  '#title' => $this->t('Delete'),
  '#url' => Url::fromRoute(
    'tester_dashboard.delete_account',
    ['node' => $node->id()]
  ),
  '#attributes' => [
    'class' => ['btn', 'btn-sm', 'btn-danger', 'delete-tester'],
  ],
];




  $rows[] = [
  $node->label(),
  $node->get('field_external_id')->value ?? '',
  $node->get('field_country')->value ?? '',
  [
    'data' => $document_link, // can be string or render array
  ],
  [
    'data' => $actions, // ✅ render array only
  ],
];

  }

  $table = [
    '#type' => 'table',
    '#header' => [
      'Full Name',
      'Tax ID / PAN Number',
      'Country of Tax Residence',
      'Uploaded Tax Document',
      'Actions',
    ],
    '#rows' => $rows,
    '#attributes' => [
      'class' => ['table', 'table-bordered', 'text-center'],
    ],
  ];

  return \Drupal::service('renderer')->render($table);
}


}
