<?php

namespace Drupal\merchant_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Merchant Dashboard controller.
 */
class MerchantDashboardController extends ControllerBase {

  /**
   * Dashboard page.
   */
  public function dashboard() {
    $build = [];

    // Welcome section
    $build['welcome'] = [
      '#markup' => '<div class="merchant-dashboard-welcome"><h1>Merchant Dashboard</h1><p>Welcome to your merchant dashboard. Manage your claims, gifts, and customer interactions.</p></div>',
    ];

    // Quick stats
    $build['stats'] = $this->buildQuickStats();
    
    // Navigation cards
    $build['navigation'] = $this->buildNavigationCards();

    // Recent activity
    $build['recent_activity'] = $this->buildRecentActivity();

    $build['#attached']['library'][] = 'merchant_dashboard/dashboard_styles';

    return $build;
  }

  /**
   * Build quick stats section.
   */
  private function buildQuickStats() {
    $stats = [
      'total_claims' => 25,
      'pending_gifts' => 12,
      'completed' => 89,
      'revenue' => '$5,247',
    ];

    $items = [];
    foreach ($stats as $key => $value) {
      $label = str_replace(['_', 'total', 'pending'], ' ', ucfirst($key));
      $items[] = [
        '#markup' => "<div class='stat-card'><div class='stat-value'>{$value}</div><div class='stat-label'>{$label}</div></div>",
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#attributes' => ['class' => ['stats-grid']],
      '#prefix' => '<div class="dashboard-stats"><h2>Quick Stats</h2>',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Build navigation cards.
   */
  private function buildNavigationCards() {
    $cards = [
      [
        'title' => 'Claims & Gifts',
        'description' => 'Manage customer claims and gift requests',
        'route' => 'merchant_dashboard.claims_gifts',
        'icon' => '📋',
      ],
    //   [
    //     'title' => 'Orders',
    //     'description' => 'View and manage orders',
    //     'route' => 'view.orders.page_1',
    //     'icon' => '📦',
    //   ],
    //   [
    //     'title' => 'Products',
    //     'description' => 'Manage your product catalog',
    //     'route' => 'entity.node.collection',
    //     'icon' => '🏷️',
    //   ],
    //   [
    //     'title' => 'Analytics',
    //     'description' => 'View sales analytics',
    //     'route' => 'google_analytics_reports.settings',
    //     'icon' => '📊',
    //   ],
    ];

    $items = [];
    foreach ($cards as $card) {
      $url = Url::fromRoute($card['route']);
      $link = Link::fromTextAndUrl($card['title'], $url)->toString();
      
      $items[] = [
        '#markup' => "
          <div class='dashboard-card'>
            <div class='card-icon'>{$card['icon']}</div>
            <h3>{$link}</h3>
            <p>{$card['description']}</p>
          </div>
        ",
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#attributes' => ['class' => ['dashboard-cards']],
      '#prefix' => '<div class="dashboard-navigation"><h2>Quick Actions</h2>',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Build recent activity section.
   */
  private function buildRecentActivity() {
    $activities = [
      ['type' => 'Claim', 'action' => 'New claim submitted', 'time' => '2 hours ago'],
      ['type' => 'Gift', 'action' => 'Gift request approved', 'time' => '5 hours ago'],
      ['type' => 'Order', 'action' => 'New order received', 'time' => '1 day ago'],
      ['type' => 'Product', 'action' => 'Product updated', 'time' => '2 days ago'],
    ];

    $items = [];
    foreach ($activities as $activity) {
      $items[] = [
        '#markup' => "
          <div class='activity-item'>
            <span class='activity-type {$activity['type']}'>{$activity['type']}</span>
            <span class='activity-action'>{$activity['action']}</span>
            <span class='activity-time'>{$activity['time']}</span>
          </div>
        ",
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#attributes' => ['class' => ['recent-activities']],
      '#prefix' => '<div class="recent-activity"><h2>Recent Activity</h2>',
      '#suffix' => '</div>',
    ];
  }

}