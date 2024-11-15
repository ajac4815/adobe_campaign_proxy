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

  /**
   * Send trigger signal to progress workflow.
   *
   * @param string $workflow_id
   *   The ID of the workflow.
   * @param array $parameters
   *   The data to pass to the workflow.
   */
  public function sendWorkflowSignal(string $workflow_id, array $parameters) {
    // Required for external signal activity.
    $data['source'] = 'API';
    $data['parameters'] = $parameters;
    // @todo Get endpoint here.
    $endpoint = '';
    $this->proxy->post($endpoint, $data);
  }

  /**
   * Complete workflow-based email delivery.
   *
   * @param string $workflow_id
   *   The ID of the workflow that will be used.
   * @param array $parameters
   *   Data to pass to the workflow.
   */
  public function send(string $workflow_id, array $parameters) {
    // @todo make both methods private.
    $this->startWorkflow($workflow_id);
    // @todo Replace with real sleep.
    sleep(5);
    $this->sendWorkflowSignal($workflow_id, $parameters);
  }

}
