<?php

namespace Drupal\merchant_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ClaimsGiftsController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new ClaimsGiftsController.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Display both forms with tab functionality.
   */
  public function combinedForms() {
    $build = [];
    
    // Get current active tab from query parameter
    $current_tab = \Drupal::request()->query->get('tab', 'rebuttal');

    // Create tabs navigation
    $build['tabs'] = [
      '#theme' => 'item_list',
      '#items' => [
        [
          '#type' => 'link',
          '#title' => $this->t('Rebuttal'),
          '#url' => Url::fromRoute('merchant_dashboard.claims_gifts', [], ['query' => ['tab' => 'rebuttal']]),
          '#attributes' => [
            'class' => [$current_tab == 'rebuttal' ? 'nav-link active' : ''],
          ],
        ],
        [
          '#type' => 'link',
          '#title' => $this->t('Gifts'),
          '#url' => Url::fromRoute('merchant_dashboard.claims_gifts', [], ['query' => ['tab' => 'gift']]),
          '#attributes' => [
            'class' => [$current_tab == 'gift' ? 'nav-link active' : ''],
          ],
        ],
      ],
      '#attributes' => ['class' => ['tabs', ' nav nav-tabs mb-4 tabs--primary']],
    ];

    // Add CSS for tabs styling
    $build['#attached']['library'][] = 'core/drupal.tabs';

    // Display appropriate form based on active tab
    if ($current_tab == 'rebuttal') {
      $build['rebuttal_form'] = $this->formBuilder->getForm('\Drupal\merchant_dashboard\Form\RebuttalForm');
      $build['rebuttal_form']['#prefix'] = '<div id="rebuttal-tab">';
      $build['rebuttal_form']['#suffix'] = '</div>';
    }
    else {
      $build['gift_form'] = $this->formBuilder->getForm('\Drupal\merchant_dashboard\Form\GiftForm');
      $build['gift_form']['#prefix'] = '<div id="gift-tab">';
      $build['gift_form']['#suffix'] = '</div>';
    }

    return $build;
  }

}