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
  private function startWorkflow(string $workflow_id) {
    $endpoint = "workflow/execution/{$workflow_id}/commands";
    $data = ['method' => 'start'];
    $this->proxy->post($endpoint, $data);
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
      $this->proxy->post($endpoint, $data);
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
    // @todo make both methods private.
    // @todo Handle bad response throughout.
    $this->startWorkflow($workflow_id);
    // @todo Replace with real sleep.
    sleep(10);
    // @todo Pass this signal ID via some sort of configuration...
    $this->sendWorkflowSignal($workflow_id, 'signal1', $parameters);
  }

}
