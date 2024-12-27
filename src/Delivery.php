<?php

namespace Drupal\adobe_campaign_proxy;

use Psr\Log\LoggerInterface;

/**
 * Email send integration.
 */
class Delivery {

  /**
   * The proxy logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * @param \Psr\Log\LoggerInterface $logger
   *   The proxy logger channel.
   */
  public function __construct(Proxy $proxy, LoggerInterface $logger) {
    $this->logger = $logger;
    $this->proxy = $proxy;
  }

  /**
   * Get workflow information.
   *
   * @param string $workflow_id
   *   The ID of the workflow.
   *
   * @return string
   *   The workflow state (if available).
   */
  private function getWorkflow(string $workflow_id) {
    $endpoint = "workflow/execution/{$workflow_id}";
    $response = $this->proxy->get($endpoint);
    return $response->state ?? '';
  }

  /**
   * Start the workflow.
   *
   * @param string $workflow_id
   *   The ID of the workflow to start.
   *
   * @return bool
   *   Success indicator of start attempt.
   */
  private function startWorkflow(string $workflow_id) {
    $endpoint = "workflow/execution/{$workflow_id}/commands";
    $data = ['method' => 'start'];
    $this->proxy->post($endpoint, $data);
    // Confirm start.
    $workflowResponse = $this->getWorkflow($workflow_id);
    if (!$workflowResponse) {
      return FALSE;
    }
    $start = time();
    $timeout = 60;
    while ($workflowResponse !== 'started') {
      // Timeout and reattempt later if more than a minute passes.
      if (time() > $start + $timeout) {
        return FALSE;
      }
      $workflowResponse = $this->getWorkflow($workflow_id);
      // Stop at any request failures.
      if (!$workflowResponse) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Send trigger signal to progress workflow.
   *
   * @param string $workflow_id
   *   The ID of the workflow.
   * @param string $signal_id
   *   The ID of the signal activity within the workflow.
   * @param array $parameters
   *   The data to pass to the workflow.
   *
   * @return bool
   *   Indicates whether send succeeded or failed.
   */
  private function sendWorkflowSignal(string $workflow_id, string $signal_id, array $parameters) {
    // Prepare data for signal.
    $data['source'] = 'API';
    $data['parameters'] = $parameters;
    // Get signal trigger URL and confirm it is present in response.
    $response = $this->proxy->get("workflow/execution/{$workflow_id}");
    if ($response && isset($response->activities->activity->$signal_id->trigger->href)) {
      $trigger_url = $response->activities->activity->$signal_id->trigger->href;
      $endpoint = str_replace(
        $this->proxy::urlBase(),
        '',
        $trigger_url
      );
      return $this->proxy->post($endpoint, $data) ? TRUE : FALSE;
    }
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
    // Confirm workflow is in expected state (stopped).
    $initialState = $this->getWorkflow($workflow_id);
    if (!$initialState) {
      return FALSE;
    }
    elseif ($initialState !== 'stopped') {
      $this->logger->warning("Workflow {$workflow_id} not stopped when attempting new send.");
      return FALSE;
    }
    // Attempt to start the workflow.
    // Must be in "started" state prior to triggering external signal.
    $success = $this->startWorkflow($workflow_id);
    if ($success) {
      // @todo Pass this signal ID via some sort of configuration...
      // @todo Check for confirmation, at least from send.
      return $this->sendWorkflowSignal($workflow_id, 'signal1', $parameters);
    }
    return FALSE;
  }

}
