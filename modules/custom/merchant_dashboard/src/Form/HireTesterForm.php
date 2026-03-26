<?php

namespace Drupal\merchant_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldConfig;

/**
 * Provides a Hire a Tester form.
 */
class HireTesterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'merchant_dashboard_hire_tester_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#markup' => '<h2 class="text-center mb-4">Hire a Tester Form</h2>',
    ];
    // Section 1 - Product & Testing Details
    $form['section_1'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Section 1 — Product & Testing Details:'),
      '#attributes' => ['class' => ['section-fieldset']],
      
    ];

    // Product Category - Load from taxonomy - RADIO BUTTONS
//     $product_categories = $this->getTaxonomyTerms('product_category');
// $product_categories = $this->getTaxonomyTerms('product_category');
// foreach ($product_categories as $term_id => $term_name) {
//   \Drupal::logger('merchant_dashboard')->notice('Term ID: @id, Name: @name', [
//     '@id' => $term_id,
//     '@name' => $term_name,
//   ]);
// }
// Ensure taxonomy terms are loaded
$product_categories = $this->getTaxonomyTerms('product_category');

$form['section_1']['product_category'] = [
  '#type' => 'radios',
  '#title' => $this->t('Product Category'),
  '#required' => TRUE,
  '#options' => $product_categories,
  '#attributes' => ['class' => ['product-category-radios']],
  '#prefix' => '<div class="row mb-3">',
  '#suffix' => '</div>',
];

$form['section_1']['product_category_other'] = [
  '#type' => 'textfield',
  // '#title' => $this->t('Other Product Category'),
  // '#description' => $this->t('Required when "Other" is selected'),
  // '#attributes' => ['class' => ['product-category-other-field']],
  '#attributes' => [
    'class' => ['product-category-other-wrapper', 'js-form-wrapper', 'form-wrapper'],
  ],
];

// Add custom JavaScript
$form['#attached']['library'][] = 'core/jquery';
$form['#attached']['drupalSettings']['merchant_dashboard'] = [
  'other_term_id' => '23'
];

$form['#attached']['library'][] = 'merchant_dashboard/tabs';

// Attach Drupal states library
$form['#attached']['library'][] = 'core/drupal.states';

    // Testing Type - Load from taxonomy - RADIO BUTTONS
    $testing_types = $this->getTaxonomyTerms('testing_type');
    $form['section_1']['testing_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Testing Type'),
      '#required' => TRUE,
      '#options' => $testing_types,
      '#attributes' => ['class' => ['testing-type-radios']],
    ];

    // Add "Other" option for testing type
    $form['section_1']['testing_type_other'] = [
      '#type' => 'textfield',
      // '#title' => $this->t('Other Testing Type'),
       '#attributes' => [
    'class' => ['product-category-other-wrapper', 'js-form-wrapper', 'form-wrapper'],
  ],
    ];

    // Tester Type Needed - Load from taxonomy - RADIO BUTTONS
    $tester_type_needed = $this->getTaxonomyTerms('tester_type_needed');
    $form['section_1']['tester_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Tester Type Needed'),
      '#required' => TRUE,
      '#options' => $tester_type_needed,
      '#attributes' => ['class' => ['tester-type-radios']],
    ];

    // Specification field for skilled expert
    $form['section_1']['skilled_expert_specification'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Specification for Skilled Expert'),
      '#placeholder' => $this->t('e.g., mechanic, athlete, chef, etc.'),
      '#states' => [
        'visible' => [
          ':input[name="tester_type"]' => ['value' => 'skilled_expert'],
        ],
      ],
    ];

    // Other tester type
    $form['section_1']['tester_type_other'] = [
      '#type' => 'textfield',
         '#attributes' => [
    'class' => ['product-category-other-wrapper', 'js-form-wrapper', 'form-wrapper'],
  ],
      '#suffix' => '</div>',
    ];

    // Section 2 — Tester Demographics
    $form['section_2'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Section 2 — Tester Demographics:'),
      '#attributes' => ['class' => ['section-fieldset']],
    ];

    // Gender Preference - Load from field_gender_preference allowed values - RADIO BUTTONS
   $gender_preference_options = $this->getFieldAllowedValues('node', 'hire_tester', 'field_gender_preference');
$form['section_2']['gender_preference'] = [
  '#type' => 'radios',
  '#title' => $this->t('Gender Preference'),
  '#required' => FALSE,
  '#options' => $gender_preference_options,
  '#attributes' => [
    'class' => ['gender-preference-radios'],
  ],
];

// Other gender specification
$form['section_2']['gender_preference_other'] = [
  '#type' => 'textfield',
  // '#title' => $this->t('Specify Other Gender'),
  // '#description' => $this->t('Please specify your gender preference'),
  '#attributes' => [
    'class' => ['gender-preference-other-field gender-preference-other-wrapper'], // Changed class name
  ],
  // '#wrapper_attributes' => [ // Add wrapper attributes for better targeting
  //   'class' => ['gender-preference-other-wrapper'],
  // ],
];

    // Age Range - Load from field_age_range allowed values - RADIO BUTTONS
    $age_range_options = $this->getFieldAllowedValues('node', 'hire_tester', 'field_age_range');
    $form['section_2']['age_range'] = [
      '#type' => 'radios',
      '#title' => $this->t('Age Range'),
      '#required' => FALSE,
      '#options' => $age_range_options,
      '#attributes' => [
        'class' => ['age-range-radios'],
      ],
    ];

    // Body Metrics
    $form['section_2']['body_metrics'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Body Metrics'),
      '#attributes' => ['class' => ['body-metrics-fieldset']],
    ];

    $form['section_2']['body_metrics']['height_range'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height Range'),
      '#placeholder' => $this->t('e.g., 5\'6" - 6\'0" or 165cm - 180cm'),
      '#size' => 30,
    ];

    $form['section_2']['body_metrics']['weight_range'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Weight Range'),
      '#placeholder' => $this->t('e.g., 150-180 lbs or 68-82 kg'),
      '#size' => 30,
    ];

    $form['section_2']['body_metrics']['shoe_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shoe Size'),
      '#placeholder' => $this->t('e.g., 8-10 US or 41-43 EU'),
      '#size' => 20,
    ];

    // Languages Spoken On-Camera - Load from field_languages_spoken allowed values - CHECKBOXES (keep as checkboxes since multiple languages are possible)
    $languages_spoken_options = $this->getTaxonomyTerms('languages');
    $form['section_2']['languages_spoken'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Languages Spoken On-Camera'),
      '#options' => $languages_spoken_options,
      '#attributes' => [
        'class' => ['languages-spoken-checkboxes'],
      ],
    ];

    // Other language specification
    $form['section_2']['languages_spoken_other'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Specify Other Language'),
      '#states' => [
        'visible' => [
          ':input[name="languages_spoken[other]"]' => ['checked' => TRUE],
        ],
      ],
    ];
// Section 3 — Skills & Capabilities
    $form['section_3'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Section 3 — Skills & Capabilities:'),
      '#attributes' => ['class' => ['section-fieldset']],
    ];

    // Physical Abilities Required - RADIO BUTTONS
    $physical_abilities = $this->getFieldAllowedValues('node', 'hire_tester', 'field_physical_abilities');
    $form['section_3']['physical_abilities'] = [
      '#type' => 'radios',
      '#title' => $this->t('Physical Abilities Required'),
      '#required' => FALSE,
      '#options' => $physical_abilities,
      '#attributes' => [
        'class' => ['physical-abilities-radios'],
      ],
    ];

    // Sports specification
    
    $form['section_3']['sports_specification'] = [
      '#type' => 'textfield',
      // '#title' => $this->t('Specify Other Gender'),
  // '#description' => $this->t('Please specify your gender preference'),
  '#attributes' => [
    'class' => ['gender-preference-other-field gender-preference-other-wrapper'], // Changed class name
  ],
    ];

    // On-Camera Preferences - RADIO BUTTONS
    $on_camera = $this->getFieldAllowedValues('node', 'hire_tester', 'field_on_camera');
    $form['section_3']['on_camera_preferences'] = [
      '#type' => 'radios',
      '#title' => $this->t('On-Camera Preferences'),
      '#required' => FALSE,
      '#options' => $on_camera,
      '#attributes' => [
        'class' => ['on-camera-preferences-radios'],
      ],
    ];

    // Editing Skills Required - RADIO BUTTONS
    $editing_skills = $this->getFieldAllowedValues('node', 'hire_tester', 'field_editing_skills_required');
    $form['section_3']['editing_skills_required'] = [
      '#type' => 'radios',
      '#title' => $this->t('Editing Skills Required?'),
      '#required' => FALSE,
      '#options' => $editing_skills,
      '#attributes' => [
        'class' => ['editing-skills-radios'],
      ],
    ];

    // Software specification
    $form['section_3']['editing_software'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Software'),
      '#placeholder' => $this->t('e.g., Adobe Premiere, Final Cut Pro, etc.'),
      '#states' => [
        'visible' => [
          ':input[name="editing_skills_required"]' => ['value' => 'yes'],
        ],
      ],
    ];

    // Section 4 — Equipment & Production Quality
    $form['section_4'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Section 4 — Equipment & Production Quality:'),
      '#attributes' => ['class' => ['section-fieldset']],
    ];

    // Camera Requirements - RADIO BUTTONS
    $camera_requirements = $this->getFieldAllowedValues('node', 'hire_tester', 'field_camera_requirements');
    $form['section_4']['camera_requirements'] = [
      '#type' => 'radios',
      '#title' => $this->t('Camera Requirements'),
      '#required' => FALSE,
      '#options' =>$camera_requirements,
      '#attributes' => [
        'class' => ['camera-requirements-radios'],
      ],
    ];

    // Smartphone specs
    $form['section_4']['smartphone_specs'] = [
      '#type' => 'textfield',
      // '#title' => $this->t('Smartphone Specs'),
      '#attributes' => [
    'class' => ['gender-preference-other-field gender-preference-other-wrapper'], // Changed class name
  ],
    ];

    // Audio / Stabilization - RADIO BUTTONS
    $audio_stabilization = $this->getFieldAllowedValues('node', 'hire_tester', 'field_audio_stabilization');
    $form['section_4']['audio_stabilization'] = [
      '#type' => 'radios',
      '#title' => $this->t('Audio / Stabilization'),
      '#required' => FALSE,
      '#options' => $audio_stabilization,
      '#attributes' => [
        'class' => ['audio-stabilization-radios'],
      ],
    ];

    // Lighting Needs - RADIO BUTTONS
    $lighting_needs = $this->getFieldAllowedValues('node', 'hire_tester', 'field_lighting_needs');
    $form['section_4']['lighting_needs'] = [
      '#type' => 'radios',
      '#title' => $this->t('Lighting Needs'),
      '#required' => FALSE,
      '#options' =>$lighting_needs,
      '#attributes' => [
        'class' => ['lighting-needs-radios'],
      ],
    ];
    // Section 5 — Logistics & Safety
    $form['section_5'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Section 5 — Logistics & Safety:'),
      '#attributes' => ['class' => ['section-fieldset']],
    ];

    // Location Preferences - RADIO BUTTONS
     $location_preference = $this->getFieldAllowedValues('node', 'hire_tester', 'field_location_preferences');
    
    $form['section_5']['location_preferences'] = [
      '#type' => 'radios',
      '#title' => $this->t('Location Preferences'),
      '#required' => FALSE,
      '#options' => $location_preference,
      '#attributes' => [
        'class' => ['location-preferences-radios'],
      ],
    ];

    // Specific regions
    $form['section_5']['specific_regions'] = [
      '#type' => 'textfield',
        // '#title' => $this->t('Smartphone Specs'),
      '#attributes' => [
    'class' => ['gender-preference-other-field gender-preference-other-wrapper'], // Changed class name
  ],
    ];

    // Safety Gear Needed - RADIO BUTTONS
     $safety_gear_needed = $this->getFieldAllowedValues('node', 'hire_tester', 'field_safety_gear_needed');
    
    $form['section_5']['safety_gear_needed'] = [
      '#type' => 'radios',
      '#title' => $this->t('Safety Gear Needed'),
      '#required' => FALSE,
      '#options' => $safety_gear_needed,
      '#attributes' => [
        'class' => ['safety-gear-radios'],
      ],
    ];

    // Other safety gear specification
    $form['section_5']['safety_gear_other'] = [
      '#type' => 'textfield',
       '#attributes' => [
    'class' => ['gender-preference-other-field gender-preference-other-wrapper'], // Changed class name
  ],
    ];

    // Licenses/Certifications Required - RADIO BUTTONS
     $licenses_certification = $this->getFieldAllowedValues('node', 'hire_tester', 'field_licenses_certifications_re');
    $form['section_5']['licenses_certifications'] = [
      '#type' => 'radios',
      '#title' => $this->t('Licenses/Certifications Required'),
      '#required' => FALSE,
      '#options' =>$licenses_certification,
      '#attributes' => [
        'class' => ['licenses-certifications-radios'],
      ],
    ];

    // Other license specification
    $form['section_5']['licenses_other'] = [
      '#type' => 'textfield',
       '#attributes' => [
    'class' => ['gender-preference-other-field gender-preference-other-wrapper'], // Changed class name
  ],
    ];

    // Section 6 — Social Media & Audience
    $form['section_6'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Section 6 — Social Media & Audience:'),
      '#attributes' => ['class' => ['section-fieldset']],
    ];

    // Platform Preferences - RADIO BUTTONS
     $platform_preference = $this->getFieldAllowedValues('node', 'hire_tester', 'field_platform_preferences');
    $form['section_6']['platform_preferences'] = [
      '#type' => 'radios',
      '#title' => $this->t('Platform Preferences'),
      '#required' => FALSE,
      '#options' => $platform_preference,
      '#attributes' => [
        'class' => ['platform-preferences-radios'],
      ],
    ];

    // Other platform specification
    $form['section_6']['platform_other'] = [
      '#type' => 'textfield',
      '#attributes' => [
    'class' => ['gender-preference-other-field gender-preference-other-wrapper'], // Changed class name
  ],
    ];

    // Minimum Follower Count - RADIO BUTTONS
    $follower_count = $this->getFieldAllowedValues('node', 'hire_tester', 'field_minimum_follower_count');
    $form['section_6']['follower_count'] = [
      '#type' => 'radios',
      '#title' => $this->t('Minimum Follower Count'),
      '#required' => FALSE,
      '#options' => $follower_count,
      '#attributes' => [
        'class' => ['follower-count-radios'],
      ],
    ];

    // Content Style - RADIO BUTTONS
    $content_style = $this->getFieldAllowedValues('node', 'hire_tester', 'field_content_style');
    $form['section_6']['content_style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Content Style'),
      '#required' => FALSE,
      '#options' => $content_style,
      '#attributes' => [
        'class' => ['content-style-radios'],
      ],
    ];

    // Other content style specification
    $form['section_6']['content_style_other'] = [
      '#type' => 'textfield',
      '#attributes' => [
    'class' => ['gender-preference-other-field gender-preference-other-wrapper'], // Changed class name
  ],
    ];

    // Section 7 — Budget & Timeline
    $form['section_7'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Section 7 — Budget & Timeline:'),
      '#attributes' => ['class' => ['section-fieldset']],
    ];

    // Payment Structure - RADIO BUTTONS
    $form['section_7']['payment_structure'] = [
      '#type' => 'radios',
      '#title' => $this->t('Payment Structure'),
      '#required' => FALSE,
      '#options' => [
        'per_video' => $this->t('Per Video (Fixed rate) - 15 to 58 sec'),
        'other_structure' => $this->t('Other Payment Structure'),
      ],
      '#attributes' => [
        'class' => ['payment-structure-radios'],
      ],
    ];

    // Budget amounts for different tester levels
    $form['section_7']['budget_amounts'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Budget Amounts (Per Video)'),
      '#states' => [
        'visible' => [
          ':input[name="payment_structure"]' => ['value' => 'per_video'],
        ],
      ],
    ];

    $form['section_7']['budget_amounts']['beginner_tester_min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Beginner Tester - Min ($)'),
      '#size' => 10,
      '#prefix' => '<div class="budget-row">',
    ];

    $form['section_7']['budget_amounts']['beginner_tester_max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Beginner Tester - Max ($)'),
      '#size' => 10,
      '#suffix' => '</div>',
    ];

    $form['section_7']['budget_amounts']['pro_tester_min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pro Tester - Min ($)'),
      '#size' => 10,
      '#prefix' => '<div class="budget-row">',
    ];

    $form['section_7']['budget_amounts']['pro_tester_max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pro Tester - Max ($)'),
      '#size' => 10,
      '#suffix' => '</div>',
    ];

    $form['section_7']['budget_amounts']['influencer_min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Influencer (50K+) - Min ($)'),
      '#size' => 10,
      '#prefix' => '<div class="budget-row">',
    ];

    $form['section_7']['budget_amounts']['influencer_max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Influencer (50K+) - Max ($)'),
      '#size' => 10,
      '#suffix' => '</div>',
    ];

    // Budget Flexibility - RADIO BUTTONS 
    // $form['section_7']['budget_flexibility'] = [
    //   '#type' => 'radios',
    //   '#title' => $this->t('Budget Flexibility'),
    //   '#required' => FALSE,
    //   '#options' => [
    //     'strict_budget' => $this->t('Strict Budget'),
    //     'flexible' => $this->t('Flexible (Pay for quality)'),
    //   ],
    //   '#attributes' => [
    //     'class' => ['budget-flexibility-radios'],
    //   ],
    // ];
     $Flexibility = $this->getFieldAllowedValues('node', 'hire_tester', 'field_budget_flexibility');
    $form['section_7']['budget_flexibility'] = [
      '#type' => 'radios',
      '#title' => $this->t('Flexibility'),
      '#required' => FALSE,
      '#options' => $Flexibility,
      '#attributes' => [
        'class' => ['content-style-radios'],
      ],
    ];

    // Strict budget amount
    $form['section_7']['strict_budget_amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum Budget Amount ($)'),
      '#size' => 15,
      '#placeholder' => $this->t('e.g., 1000'),
      '#states' => [
        'visible' => [
          ':input[name="budget_flexibility"]' => ['value' => 'strict_budget'],
        ],
      ],
    ];

    // Urgency - RADIO BUTTONS
     $urgency = $this->getFieldAllowedValues('node', 'hire_tester', 'field_urgency');
    $form['section_7']['urgency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Urgency'),
      '#required' => FALSE,
      '#options' => $urgency,
      '#attributes' => [
        'class' => ['content-style-radios'],
      ],
    ];

    // Product Details
    $form['section_7']['product_details'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Product Details (Mandatory)'),
      '#attributes' => ['class' => ['product-details-fieldset']],
    ];

    $form['section_7']['product_details']['product_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product Name & Model'),
      '#required' => TRUE,
      '#size' => 40,
    ];

    $form['section_7']['product_details']['product_images'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Product Images/Files'),
      '#upload_location' => 'public://product_images/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif pdf doc docx'],
      ],
    ];

    $form['section_7']['product_details']['key_features'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Key Features'),
      '#rows' => 3,
      '#required' => TRUE,
    ];

    // Special Requests
    $form['section_7']['special_requests'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Special Requests'),
      '#rows' => 4,
      '#placeholder' => $this->t('Any additional requirements or special requests...'),
    ];
    // Submit button
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Request'),
      '#button_type' => 'primary',
    ];

    // Add CSS for styling
    $form['#attached']['library'][] = 'merchant_dashboard/tabs';
    $form['#attached']['library'][] = 'core/drupal.states';
    

    return $form;
  }

  /**
   * Get taxonomy terms for a vocabulary.
   *
   * @param string $vocabulary_name
   *   The vocabulary machine name.
   *
   * @return array
   *   Array of term names keyed by term ID.
   */
  protected function getTaxonomyTerms($vocabulary_name) {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vocabulary_name);

    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    // Add "Other" option for radio buttons
    // $options['other'] = $this->t('Other');

    return $options;
  }

  /**
   * Get allowed values from a list field.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle name.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   Array of allowed values.
   */
  protected function getFieldAllowedValues($entity_type, $bundle, $field_name) {
    $field_config = FieldConfig::loadByName($entity_type, $bundle, $field_name);
    
    if ($field_config) {
      $allowed_values = $field_config->getSetting('allowed_values');
      if (!empty($allowed_values)) {
        return $allowed_values;
      }
    }

    // Return default values if field doesn't exist or has no allowed values
    return $this->getDefaultFieldValues($field_name);
  }

  /**
   * Get default values for fields if they don't exist.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   Array of default values.
   */
  protected function getDefaultFieldValues($field_name) {
    $defaults = [
      'field_gender_preference' => [
        'no_preference' => 'No Preference',
        'male' => 'Male',
        'female' => 'Female',
        'non_binary' => 'Non-binary',
        'other' => 'Other',
      ],
      'field_age_range' => [
        '18-24' => '18-24',
        '25-34' => '25-34',
        '35-44' => '35-44',
        '45-54' => '45-54',
        '55+' => '55+',
        'no_preference' => 'No Preference',
      ],
      'field_languages_spoken' => [
        'english' => 'English',
        'spanish' => 'Spanish',
        'french' => 'French',
        'german' => 'German',
        'mandarin' => 'Mandarin',
        'other' => 'Other',
      ],
    ];

    return $defaults[$field_name] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Validate that if "Other" is selected for product category, the other field is filled
    if ($values['product_category'] == 'other' && empty($values['product_category_other'])) {
      $form_state->setErrorByName('product_category_other', $this->t('Please specify the other product category.'));
    }

    // Validate that if "Other" is selected for testing type, the other field is filled
    if ($values['testing_type'] == 'other' && empty($values['testing_type_other'])) {
      $form_state->setErrorByName('testing_type_other', $this->t('Please specify the other testing type.'));
    }

    // Validate skilled expert specification
    if ($values['tester_type'] == 'skilled_expert' && empty($values['skilled_expert_specification'])) {
      $form_state->setErrorByName('skilled_expert_specification', $this->t('Please specify the type of skilled expert needed.'));
    }

    // Validate other tester type
    if ($values['tester_type'] == 'other' && empty($values['tester_type_other'])) {
      $form_state->setErrorByName('tester_type_other', $this->t('Please specify the other tester type.'));
    }

    // Validate Section 2 fields
    if ($values['gender_preference'] == 'other' && empty($values['gender_preference_other'])) {
      $form_state->setErrorByName('gender_preference_other', $this->t('Please specify the other gender preference.'));
    }

    if (!empty($values['languages_spoken']['other']) && empty($values['languages_spoken_other'])) {
      $form_state->setErrorByName('languages_spoken_other', $this->t('Please specify the other language.'));
    }

        // Validate Section 3 fields
    if ($values['physical_abilities'] == 'sports' && empty($values['sports_specification'])) {
      $form_state->setErrorByName('sports_specification', $this->t('Please specify the sports.'));
    }

    if ($values['editing_skills_required'] == 'yes' && empty($values['editing_software'])) {
      $form_state->setErrorByName('editing_software', $this->t('Please specify the editing software.'));
    }
     // Validate Section 4 fields
    if ($values['camera_requirements'] == 'smartphone' && empty($values['smartphone_specs'])) {
      $form_state->setErrorByName('smartphone_specs', $this->t('Please specify the smartphone specifications.'));
    }

     // Validate Section 5 fields
    if ($values['safety_gear_needed'] == 'other' && empty($values['safety_gear_other'])) {
      $form_state->setErrorByName('safety_gear_other', $this->t('Please specify the other safety gear.'));
    }

    if ($values['licenses_certifications'] == 'other' && empty($values['licenses_other'])) {
      $form_state->setErrorByName('licenses_other', $this->t('Please specify the other license/certification.'));
    }

    // Validate Section 6 fields
    if ($values['platform_preferences'] == 'other' && empty($values['platform_other'])) {
      $form_state->setErrorByName('platform_other', $this->t('Please specify the other platform.'));
    }

    if ($values['content_style'] == 'other' && empty($values['content_style_other'])) {
      $form_state->setErrorByName('content_style_other', $this->t('Please specify the other content style.'));
    }

    // Validate Section 7 fields
    if ($values['payment_structure'] == 'per_video') {
      if (empty($values['beginner_tester_min']) || empty($values['beginner_tester_max'])) {
        $form_state->setErrorByName('beginner_tester_min', $this->t('Please specify budget range for beginner testers.'));
      }
      if (empty($values['pro_tester_min']) || empty($values['pro_tester_max'])) {
        $form_state->setErrorByName('pro_tester_min', $this->t('Please specify budget range for pro testers.'));
      }
      if (empty($values['influencer_min']) || empty($values['influencer_max'])) {
        $form_state->setErrorByName('influencer_min', $this->t('Please specify budget range for influencers.'));
      }
    }

    if ($values['budget_flexibility'] == 'strict_budget' && empty($values['strict_budget_amount'])) {
      $form_state->setErrorByName('strict_budget_amount', $this->t('Please specify the maximum budget amount.'));
    }

    // Validate mandatory product details
    if (empty($values['product_name'])) {
      $form_state->setErrorByName('product_name', $this->t('Product name is required.'));
    }

    if (empty($values['key_features'])) {
      $form_state->setErrorByName('key_features', $this->t('Key features are required.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $current_user = \Drupal::currentUser();

    // Process radio button values (single selection)
    $product_category = $values['product_category'];
    $testing_type = $values['testing_type'];
    $tester_type = $values['tester_type'];
    $gender_preference = $values['gender_preference'];
    $age_range = $values['age_range'];
     $physical_abilities = $values['physical_abilities'];
    $on_camera_preferences = $values['on_camera_preferences'];
    $editing_skills_required = $values['editing_skills_required'];
    $camera_requirements = $values['camera_requirements'];
    $audio_stabilization = $values['audio_stabilization'];
    $lighting_needs = $values['lighting_needs'];
    $location_preferences = $values['location_preferences'];
    $safety_gear_needed = $values['safety_gear_needed'];
    $licenses_certifications = $values['licenses_certifications'];
    $platform_preferences = $values['platform_preferences'];
    $follower_count = $values['follower_count'];
    $content_style = $values['content_style'];
    $payment_structure = $values['payment_structure'];
    $budget_flexibility = $values['budget_flexibility'];
    $urgency = $values['urgency'];

    // Process checkbox fields - get only selected values (for languages)
    $languages_spoken = $values['languages_spoken'];

    // Prepare node data
    $node_data = [
      'type' => 'hire_tester',
      'title' => 'Tester Request - ' . $values['product_name'],
      'uid' => $current_user->id(),
      'field_legal_business_name' =>  $values['weight_range'],
    'field_buyer_username' =>  $values['shoe_size'],
    'field_sold_bys' =>  $values['height_range'],
    'field_image' =>  $values['product_images'],
    'field_external_id' =>  $values['key_features'],
    ];

    // Section 1 fields - Radio buttons (single values)
    if (!empty($product_category)) {
      $node_data['field_e_commerce_platform'] = [$product_category];
    }
    if (!empty($testing_type) && $testing_type != 'other') {
      $node_data['field_filter'] = [$testing_type];
    }
    if (!empty($tester_type)) {
      $node_data['field_tester_type_needed'] = [$tester_type];
    }
    if (!empty($values['product_category_other'])) {
      $node_data['field_product_category_other'] = $values['product_category_other'];
    }
    if (!empty($values['testing_type_other'])) {
      $node_data['field_testing_type_other'] = $values['testing_type_other'];
    }
    if (!empty($values['skilled_expert_specification'])) {
      $node_data['field_skilled_expert_specification'] = $values['skilled_expert_specification'];
    }
    // if (!empty($values['tester_type_other'])) {
    //   $node_data['field_tester_type_other'] = $values['tester_type_other'];
    // }

    // Section 2 fields
    if (!empty($gender_preference)) {
      $node_data['field_gender_preference'] = [$gender_preference];
    }
    if (!empty($values['gender_preference_other'])) {
      $node_data['field_gender_preference_other'] = $values['gender_preference_other'];
    }
    if (!empty($age_range)) {
      $node_data['field_age_range'] = [$age_range];
    }
    if (!empty($values['height_range'])) {
      $node_data['field_height_range'] = $values['height_range'];
    }
    if (!empty($values['weight_range'])) {
      $node_data['field_weight_range'] = $values['weight_range'];
    }
    if (!empty($values['shoe_size'])) {
      $node_data['field_shoe_size'] = $values['shoe_size'];
    }
    if (!empty($languages_spoken)) {
      $node_data['field_language'] = $languages_spoken;
    }
    if (!empty($values['languages_spoken_other'])) {
      $node_data['field_languages_spoken_other'] = $values['languages_spoken_other'];
    }
     // Section 3 fields
    if (!empty($physical_abilities)) {
      $node_data['field_physical_abilities'] = [$physical_abilities];
    }
    if (!empty($values['sports_specification'])) {
      $node_data['field_sports_specification'] = $values['sports_specification'];
    }
    if (!empty($on_camera_preferences)) {
      $node_data['field_on_camera'] = [$on_camera_preferences];
    }
    if (!empty($editing_skills_required)) {
      $node_data['field_editing_skills_required'] = [$editing_skills_required];
    }
    if (!empty($values['editing_software'])) {
      $node_data['field_editing_software'] = $values['editing_software'];
    }

    // Section 4 fields
    if (!empty($camera_requirements)) {
      $node_data['field_camera_requirements'] = [$camera_requirements];
    }
    if (!empty($values['smartphone_specs'])) {
      $node_data['field_smartphone_specs'] = $values['smartphone_specs'];
    }
    if (!empty($audio_stabilization)) {
      $node_data['field_audio_stabilization'] = [$audio_stabilization];
    }
    if (!empty($lighting_needs)) {
      $node_data['field_lighting_needs'] = [$lighting_needs];
    }
     // Section 5 fields
    if (!empty($location_preferences)) {
      $node_data['field_location_preferences'] = [$location_preferences];
    }
    if (!empty($values['specific_regions'])) {
      $node_data['field_specific_regions'] = $values['specific_regions'];
    }
    if (!empty($safety_gear_needed)) {
      $node_data['field_safety_gear_needed'] = [$safety_gear_needed];
    }
    if (!empty($values['safety_gear_other'])) {
      $node_data['field_safety_gear_other'] = $values['safety_gear_other'];
    }
    if (!empty($licenses_certifications)) {
      $node_data['field_licenses_certifications_re'] = [$licenses_certifications];
    }
    if (!empty($values['licenses_other'])) {
      $node_data['field_licenses_other'] = $values['licenses_other'];
    }

    // Section 6 fields
    if (!empty($platform_preferences)) {
      $node_data['field_platform_preferences'] = [$platform_preferences];
    }
    if (!empty($values['platform_other'])) {
      $node_data['field_platform_other'] = $values['platform_other'];
    }
    if (!empty($follower_count)) {
      $node_data['field_minimum_follower_count'] = [$follower_count];
    }
    if (!empty($content_style)) {
      $node_data['field_content_style'] = [$content_style];
    }
    if (!empty($values['content_style_other'])) {
      $node_data['field_content_style_other'] = $values['content_style_other'];
    }

    // Section 7 fields
    if (!empty($payment_structure)) {
      $node_data['field_payment_structure'] = [$payment_structure];
    }
    if (!empty($values['beginner_tester_min'])) {
      $node_data['field_beginner_tester_min'] = $values['beginner_tester_min'];
    }
    if (!empty($values['beginner_tester_max'])) {
      $node_data['field_beginner_tester_max'] = $values['beginner_tester_max'];
    }
    if (!empty($values['pro_tester_min'])) {
      $node_data['field_pro_tester_min'] = $values['pro_tester_min'];
    }
    if (!empty($values['pro_tester_max'])) {
      $node_data['field_pro_tester_max'] = $values['pro_tester_max'];
    }
    if (!empty($values['influencer_min'])) {
      $node_data['field_influencer_min'] = $values['influencer_min'];
    }
    if (!empty($values['influencer_max'])) {
      $node_data['field_influencer_max'] = $values['influencer_max'];
    }
    if (!empty($budget_flexibility)) {
      $node_data['field_budget_flexibility'] = [$budget_flexibility];
    }
    if (!empty($values['strict_budget_amount'])) {
      $node_data['field_strict_budget_amount'] = $values['strict_budget_amount'];
    }
    if (!empty($urgency)) {
      $node_data['field_urgency'] = [$urgency];
    }
    if (!empty($values['product_name'])) {
      $node_data['field_product_name'] = $values['product_name'];
    }
    if (!empty($values['product_images'])) {
      $node_data['field_product_images'] = $values['product_images'];
    }
    if (!empty($values['key_features'])) {
      $node_data['field_key_features'] = $values['key_features'];
    }
    if (!empty($values['special_requests'])) {
      $node_data['field_special_requests'] = $values['special_requests'];
    }

    // Create the node
    $node = Node::create($node_data);

    try {
      $node->save();
      
      \Drupal::logger('merchant_dashboard')->notice('Tester Request node created with ID: @nid', [
        '@nid' => $node->id(),
      ]);

      \Drupal::messenger()->addStatus($this->t('Your tester request has been submitted successfully! Request ID: @nid', [
        '@nid' => $node->id(),
      ]));

      // Optionally redirect to the created node
      $form_state->setRedirect('<current>');

    } catch (\Exception $e) {
      \Drupal::logger('merchant_dashboard')->error('Error creating tester request node: @error', [
        '@error' => $e->getMessage(),
      ]);

      \Drupal::messenger()->addError($this->t('There was an error submitting your request. Please try again.'));
    }
  }

}