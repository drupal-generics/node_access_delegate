<?php

namespace Drupal\node_access_delegate;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for the node access alter plugin.
 *
 * @package Drupal\node_access_delegate
 */
interface NodeAccessDelegatePluginInterface {

  /**
   * Checks access to an operation on a given node or node translation.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The entity for which to check access.
   * @param string $operation
   *   The operation access should be checked for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   * @param bool $isTranslation
   *   Whether the operation is for translation.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @see \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  public function access(NodeInterface $node, $operation, AccountInterface $account, $isTranslation);

  /**
   * Checks access to create a node.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   * @param array $context
   *   (optional) An array of key-value pairs to pass additional context when
   *   needed.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @see \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  public function createAccess(AccountInterface $account, array $context = []);

  /**
   * Determines whether this handler should apply.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   Apply or not.
   */
  public function appliesForAccount(AccountInterface $account);

}
