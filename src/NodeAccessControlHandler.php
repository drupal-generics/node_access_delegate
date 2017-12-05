<?php

namespace Drupal\node_access_delegate;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeGrantDatabaseStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeAccessControlHandler as OriginalNodeAccessControlHandler;

/**
 * Replaces to core node access handler to delegate it to plugins.
 *
 * @package Drupal\node_access_delegate
 */
class NodeAccessControlHandler extends OriginalNodeAccessControlHandler {

  /**
   * The access delegates plugin manager.
   *
   * @var \Drupal\node_access_delegate\NodeAccessDelegateManager
   */
  protected $nodeAccessDelegate;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, NodeGrantDatabaseStorageInterface $grant_storage, NodeAccessDelegateManager $nodeAccessDelegateManager, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($entity_type, $grant_storage);
    $this->nodeAccessDelegate = $nodeAccessDelegateManager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('node.grant_storage'),
      $container->get('plugin.manager.node_access_delegate'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Entity\EntityAccessControlHandler::processAccessHookResults()
   *   Example how the access results are combined.
   * @see \Drupal\node\NodeAccessControlHandler::access()
   *   Default access bypass and denial checks.
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $entity */
    $account = $this->prepareUser($account);

    $result = AccessResult::neutral();

    // Delegate access operation and return the combined result of the various
    // access checks' results, but only when access not bypassed, or denied
    // explicitly.

    foreach ($this->getDelegates($account, $entity->bundle(), $operation, 1) as $accessDelegate) {
      $access = $accessDelegate->access($entity, $operation, $account, $this->isTranslationOperation())
        ->cachePerPermissions();

      $result = $result->orIf($access);

      if (!$result->isNeutral()) {
        return $return_as_object ? $result : $result->isAllowed();
      }
    }
    if (!$account->hasPermission('bypass node access') && $account->hasPermission('access content')) {
      foreach ($this->getDelegates($account, $entity->bundle(), $operation, NULL) as $accessDelegate) {
        $access = $accessDelegate->access($entity, $operation, $account, $this->isTranslationOperation())
          ->cachePerPermissions();

        $result = $result->orIf($access);

        if (!$result->isNeutral()) {
          return $return_as_object ? $result : $result->isAllowed();
        }
      }
    }

    $access = parent::access($entity, $operation, $account, $return_as_object);

    return $return_as_object ? $result->orIf($access) : $access;
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    // We are only interested in bundles.
    if (empty($entity_bundle)) {
      return parent::createAccess($entity_bundle, $account, $context, $return_as_object);
    }

    // If we are creating a translation then we can use the access method
    // so that delegates can get the node and use additional information to
    // determine access.
    if ($this->isTranslationOperation()) {
      $node = $this->currentRouteMatch->getParameter('node');
      return $this->access($node, 'create', $account, $return_as_object);
    }

    $account = $this->prepareUser($account);

    // Delegate access operation and return as soon as decisive result got.
    foreach ($this->getDelegates($account, $entity_bundle, 'create') as $accessDelegate) {
      $access = $accessDelegate->createAccess($account, $context)->cachePerPermissions();;
      if (!$access->isNeutral()) {
        return $return_as_object ? $access : $access->isAllowed();
      }
    }

    return parent::createAccess($entity_bundle, $account, $context, $return_as_object);
  }

  /**
   * Get the applying delegates per bundle, operation and account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param string $bundle
   *   The node bundle.
   * @param string $operation
   *   The operation.
   * @param int $superPriority
   *   The super priority.
   *
   * @return \Drupal\node_access_delegate\NodeAccessDelegatePluginInterface[]
   *   The access delegates
   */
  protected function getDelegates(AccountInterface $account, $bundle, $operation, $superPriority = NULL) {
    $delegates = $this->nodeAccessDelegate->getDelegates($bundle, $operation, $superPriority);

    // Filter out delegates that decide to not apply.
    foreach ($delegates as $id => $accessDelegate) {
      if (!$accessDelegate->appliesForAccount($account)) {
        unset($delegates[$id]);
      }
    }

    return $delegates;
  }

  /**
   * Determine whether current operation is for translation.
   *
   * @return bool
   *   Is translation operation.
   */
  protected function isTranslationOperation() {
    if (!($node = $this->currentRouteMatch->getParameter('node'))) {
      return FALSE;
    }

    $operation = $this->currentRouteMatch->getRouteObject()
      ->getRequirement('_access_content_translation_manage');

    return (bool) $operation;
  }

}
