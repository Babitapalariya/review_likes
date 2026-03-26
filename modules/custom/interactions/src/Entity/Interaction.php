<?php

namespace Drupal\interactions\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Interaction entity.
 *
 * @ContentEntityType(
 *   id = "interaction",
 *   label = @Translation("Interaction"),
 *   base_table = "interaction",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *   },
 *   admin_permission = "administer site configuration",
 *   entity_form_views = {},
 * )
 */
class Interaction extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = [];

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['node_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Node'))
      ->setSetting('target_type', 'node')
      ->setRequired(TRUE);

    $fields['action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Action'))
      ->setSettings([
        'max_length' => 16,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }
}
