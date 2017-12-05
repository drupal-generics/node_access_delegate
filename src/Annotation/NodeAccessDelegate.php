<?php

namespace Drupal\node_access_delegate\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for NodeAccessAlter plugins.
 *
 * @Annotation
 */
class NodeAccessDelegate extends Plugin {

  /**
   * The content type of the node which to alter.
   *
   * @var array|string
   */
  public $bundle;

  /**
   * The type of operations to apply for.
   *
   * Defaults to null which means all operations.
   *
   * @var array|null
   */
  public $operations = NULL;

  /**
   * Use roles for which this access handler should not apply.
   *
   * @var array|null
   */
  public $bypassRoles = NULL;

  /**
   * The priority of this alter.
   *
   * @var int
   */
  public $priority = 1;

  /**
   * The super priority of this alter. Plugins with super priority precede node bypass access.
   *
   * @var int
   */
  public $superPriority = NULL;

}
