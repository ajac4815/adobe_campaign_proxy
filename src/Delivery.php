<?php

namespace Drupal\adobe_campaign_proxy;

use Drupal\Core\State\StateInterface;
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
   * The state service.
   *
   * @var Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the delivery service.
   *
   * @param Proxy $proxy
   *   The proxy API service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The proxy logger channel.
   * @param Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(Proxy $proxy, LoggerInterface $logger, StateInterface $state) {
    $this->logger = $logger;
    $this->proxy = $proxy;
    $this->state = $state;
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
   * @param string $signal_id
   *   The ID of the API signal activity.
   * @param array $parameters
   *   Data to pass to the workflow.
   */
  public function send(string $workflow_id, string $signal_id, array $parameters) {
    // Adobe has a default limit of 10 min. for workflow triggers.
    // Ensure 10 min. have passed since the last runtime.
    $state_name = "adobe_campaign_proxy.{$workflow_id}_last_runtime";
    $last_runtime = $this->state->get($state_name);
    if ($last_runtime && $last_runtime > (time() - 600)) {
      $this->logger->warning("Workflow {$workflow_id} ran less than 10 minutes ago.");
      return FALSE;
    }
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
      // Set last runtime.
      $this->state->set($state_name, time());
      return $this->sendWorkflowSignal($workflow_id, $signal_id, $parameters);
    }
    return FALSE;
  }

}
