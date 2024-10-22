<?php

namespace Drupal\adobe_campaign_proxy;

use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Proxy class to connect to Adobe Campaign API.
 */
class Proxy {

  /**
   * The adobe token generation URL.
   */
  const GENERATE_TOKEN_URL = 'https://ims-na1.adobelogin.com/ims/token/v3';

  /**
   * The cache backend that should be used.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The proxy logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs proxy.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend service.
   * @param \GuzzleHttp\ClientInterface $client
   *   The http client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The proxy logger channel.
   */
  public function __construct(CacheBackendInterface $cache, ClientInterface $client, LoggerInterface $logger) {
    $this->cache = $cache;
    $this->client = $client;
    $this->logger = $logger;
  }

  /**
   * Generate access token.
   *
   * @return string|false
   *   Returns token or false value.
   */
  private function generateToken() {
    $time = time();
    // Check for existing auth token and return if not expired.
    // Otherwise, generate and cache a new one.
    $existing_token = $this->cache->get('adobe_campaign_token');
    if (!$existing_token || (int) $existing_token->expire <= time()) {
      if (!$_SERVER['ADOBE_API_KEY'] || !$_SERVER['ADOBE_CLIENT_SECRET'] || !$_SERVER['ADOBE_ORG']) {
        $message = 'A required env variable for the adobe campaign proxy is missing. Please review README.md.';
        $this->logger->error($message);
        return FALSE;
      }
      try {
        $token_request = $this->client->request(
        'POST',
        $this::GENERATE_TOKEN_URL,
        [
          'form_params' => [
            'grant_type' => 'client_credentials',
            'client_id' => $_SERVER['ADOBE_API_KEY'],
            'client_secret' => $_SERVER['ADOBE_CLIENT_SECRET'],
            'scope' => 'openid,AdobeID,campaign_sdk,additional_info.projectedProductContext,campaign_config_server_general,deliverability_service_general',
          ],
        ]
        );
        $token_body = $token_request->getBody();
        if ($token_body) {
          $token_json = json_decode($token_body);
          if ($token_json) {
            $token = $token_json->access_token;
            $token_expire = $time + $token_json->expires_in;
            $this->cache->set('adobe_campaign_token', base64_encode($token), $token_expire);
            return $token;
          }
        }
      }
      catch (GuzzleException $e) {
        return FALSE;
      }
      return FALSE;
    }
    return base64_decode($existing_token->data);
  }

  /**
   * Send API get request.
   *
   * @param string $endpoint
   *   The API endpoint.
   *
   * @return array|false
   *   Array of json response data or false.
   */
  public function get(string $endpoint) {
    $token = $this->generateToken();
    if ($token) {
      $org = $_SERVER['ADOBE_ORG'];
      if ($org) {
        try {
          $base_url = "https://mc.adobe.io/{$org}/campaign/";
          $full_url = $base_url . $endpoint;
          $request = $this->client->request(
          'GET',
          $full_url,
          [
            'headers' => [
              'Authorization' => "Bearer {$token}",
              'Cache-Control' => 'no-cache',
              'Content-Type' => 'application/json',
              'X-Api-Key' => $_SERVER['ADOBE_API_KEY'],
            ],
          ]
          );
          $body = $request->getBody();
          if ($body) {
            $json = json_decode($body);
            if ($json) {
              return $json;
            }
          }
          return FALSE;
        }
        catch (GuzzleException $e) {
          return FALSE;
        }
      }
    }
  }

  /**
   * Send API post request.
   */
  public function post() {
    $token = $this->generateToken();
    if ($token) {
      $org = $_SERVER['ADOBE_ORG'];
      if ($org) {
        try {
          $request = $this->client->request(
          'POST',
          'https://mc.adobe.io/ninds-mkt-stage1/campaign/profileAndServices/profile',
          [
            'headers' => [
              'Authorization' => "Bearer {$token}",
              'Cache-Control' => 'no-cache',
              'Content-Type' => 'application/json',
              'X-Api-Key' => $_SERVER['ADOBE_API_KEY'],
            ],
            'json' => [
              "email" => "adam.jacobs@nih.gov",
            ],
          ]
          );
        }
        catch (GuzzleException $e) {
          return FALSE;
        }
      }
    }
  }

}
