<?php

namespace Drupal\node_access_delegate;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Base class for the node access alter plugin.
 *
 * @package Drupal\node_access_delegate
 */
class NodeAccessDelegatePluginBase extends PluginBase implements NodeAccessDelegatePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function access(NodeInterface $node, $operation, AccountInterface $account, $isTranslation) {
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(AccountInterface $account, array $context = []) {
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForAccount(AccountInterface $account) {
    if (!isset($this->pluginDefinition['bypassRoles']) || !($bypassRoles = $this->pluginDefinition['bypassRoles'])) {
      return TRUE;
    }

    return !array_intersect($bypassRoles, $account->getRoles());
  }

}
