<?php

namespace Drupal\adobe_campaign_proxy\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\adobe_campaign_proxy\Delivery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Workflow send queue worker.
 *
 * @QueueWorker(
 *   id = "adobe_campaign_proxy_send",
 *   title = @Translation("Adobe Campaign Proxy send worker"),
 *   cron = {"time" = 60}
 * )
 */
class SendQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Email delivery service.
   *
   * @var \Drupal\adobe_campaign_proxy\Delivery
   */
  protected $delivery;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\adobe_campaign_proxy\Delivery $delivery
   *   The delivery service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Delivery $delivery, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->delivery = $delivery;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('adobe_campaign_proxy.delivery'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data) {
    // Pull data from node to pass to send.
    $node = $this->entityTypeManager->getStorage('node')->load($data);
    $parameters = [
      'title' => $node->label(),
      'link' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
      'summary' => $node->body->summary ?? '',
    ];
    // Get workflow and API signal IDs.
    $content_types = \Drupal::config('adobe_campaign_proxy.settings')->get('content_types');
    $bundle = $node->bundle();
    if (in_array($bundle, array_keys($content_types))) {
      $workflow_id = $content_types[$bundle]['workflow_id'] ?: FALSE;
      $signal_id = $content_types[$bundle]['signal_id'] ?: FALSE;
      if (!$workflow_id) {
        throw new \Exception("Error during Adobe delivery for node {$node->id()}. Missing workflow ID, please update in configuration.");
      }
      elseif (!$signal_id) {
        throw new \Exception("Error during Adobe delivery for node {$node->id()}. Missing signal ID, please update in configuration.");
      }
      $result = $this->delivery->send($workflow_id, $signal_id, $parameters);
      // Leave item in queue to process again later.
      if (!$result) {
        throw new \Exception("Error during Adobe delivery for node {$node->id()}. Check logs for more information.");
      }
    }
    else {
      // @todo evaluate tossing out of queue.
      throw new \Exception("Error during Adobe delivery for node {$node->id()}. Content type {$bundle} is not enabled to send.");
    }
  }

}
