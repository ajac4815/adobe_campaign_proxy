<?php

namespace Drupal\adobe_campaign_proxy;

/**
 * Email send integration.
 */
class Delivery {

  /**
   * The proxy API service.
   *
   * @var Proxy
   */
  protected $proxy;

  /**
   * Constructs the delivery service.
   *
   * @param Proxy $proxy
   *   The proxy API service.
   */
  public function __construct(Proxy $proxy) {
    $this->proxy = $proxy;
  }

  /**
   * Start the workflow.
   *
   * @param string $workflow_id
   *   The ID of the workflow to start.
   */
  public function startWorkflow(string $workflow_id) {
    $endpoint = "workflow/execution/{$workflow_id}/commands";
    $data = ['method' => 'start'];
    $this->proxy->post($endpoint, $data);
  }

}
