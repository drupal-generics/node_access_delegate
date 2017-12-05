<?php

namespace Drupal\node_access_delegate;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for the node access alter plugins.
 *
 * @package Drupal\node_access_delegate
 */
class NodeAccessDelegateManager extends DefaultPluginManager {

  /**
   * Store for instantiated access delegates.
   *
   * @var array
   */
  protected $delegates = [];

  /**
   * Store per bundle+operation of delegates.
   *
   * @var array
   */
  protected $bundleOperationDelegates = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Access',
      $namespaces,
      $module_handler,
      'Drupal\node_access_delegate\NodeAccessDelegatePluginInterface',
      'Drupal\node_access_delegate\Annotation\NodeAccessDelegate'
    );

    $this->setCacheBackend($cache_backend, 'node_access_delegates');
  }

  /**
   * Get access alters for the provided bundle.
   *
   * @param string $bundle
   *   The content type of the node.
   * @param string $operation
   *   The operation to delegate for.
   * @param int $superPriority
   *   The priority to delegate for.
   *
   * @return \Drupal\node_access_delegate\NodeAccessDelegatePluginInterface[]
   *   Form alters for the given bundle.
   */
  public function getDelegates($bundle, $operation, $superPriority = NULL) {
    if (
      array_key_exists($bundle, $this->bundleOperationDelegates) &&
      array_key_exists($operation, $this->bundleOperationDelegates[$bundle]) &&
      array_key_exists($superPriority, $this->bundleOperationDelegates[$bundle][$operation])
    ) {
      return $this->bundleOperationDelegates[$bundle][$operation][$superPriority];
    }

    $delegates = [];
    // Get the alter definitions for the given bundle.
    foreach ($this->getDefinitions() as $id => $definition) {
      $definition['superPriority'] = isset($definition['superPriority']) ? $definition['superPriority'] : NULL;
      if (($definition['bundle'] == $bundle || (is_array($definition['bundle']) && in_array($bundle, $definition['bundle']))) &&
        (!$definition['operations'] || in_array($operation, $definition['operations'])) &&
        ($definition['superPriority'] == $superPriority)
      ) {
        $delegates[$id] = $definition;
      }
    }

    // Sort the definitions after priority.
    uasort($delegates, function ($a, $b) {
      return $a['priority'] <=> $b['priority'];
    });

    // Create the alter plugins.
    foreach ($delegates as $id => &$alter) {
      // Prevent multiple instances of same delegate.
      if (array_key_exists($id, $this->delegates)) {
        $alter = $this->delegates[$id];
      }
      else {
        $this->delegates[$id] = $alter = $this->createInstance($id);
      }
    }

    // Store the discovered delegates for the bundle per operation so we don't
    // have to calculate all this stuff every time, especially because the
    // access handlers are called many time.
    $this->bundleOperationDelegates[$bundle][$operation][$superPriority] = $delegates;
    return $delegates;
  }

}
