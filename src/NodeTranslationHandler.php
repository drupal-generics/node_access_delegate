<?php

namespace Drupal\node_access_delegate;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeTranslationHandler as OriginalTranslationHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Node translation handler replacement.
 *
 * @package Drupal\node_access_delegate
 */
class NodeTranslationHandler extends OriginalTranslationHandler {

  /**
   * The node access handler.
   *
   * @var \Drupal\node\NodeAccessControlHandler
   */
  protected $nodeAccessHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    LanguageManagerInterface $language_manager,
    ContentTranslationManagerInterface $manager,
    EntityManagerInterface $entity_manager,
    AccountInterface $current_user,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($entity_type, $language_manager, $manager, $entity_manager, $current_user);
    $this->nodeAccessHandler = $entityTypeManager->getHandler('node', 'access');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('language_manager'),
      $container->get('content_translation.manager'),
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationAccess(EntityInterface $entity, $op) {
    /** @var \Drupal\Core\Access\AccessResult $parentResult */
    $parentResult = parent::getTranslationAccess($entity, $op);
    /** @var \Drupal\Core\Access\AccessResult $nodeAccessResult */
    $nodeAccessResult = $this->nodeAccessHandler
      ->access($entity, $op, NULL, TRUE);

    return $parentResult->andIf($nodeAccessResult);
  }

}
