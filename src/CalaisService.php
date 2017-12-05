<?php

namespace Drupal\opencalais_ui;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

class CalaisService {

  /**
   * The Open Calais Json Processor.
   *
   * @var \Drupal\opencalais_ui\JsonProcessor
   */
  protected $jsonProcessor;

  /**
   * Wrapper object for simple configuration from opencalais_ui.settings.yml.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Open Calais service default parameters.
   *
   * @var array
   */
  protected $parameters = [
    'protocol' => 'https',
    'contentType' => 'TEXT/HTML',
    'outputFormat' => 'XML/RDF',
    'externalID' => '',
    'submitter' => 'Drupal',
    'calculateRelevanceScore' => 'true',
    'enableMetadataType' => 'person, SocialTags',
    'allowSearch' => 'false',
    'allowDistribution' => 'false',
    'caller' => 'Drupal',
  ];

  /**
   * Open Calais service default path.
   *
   * @var string
   */
  protected $path = '/permid/calais';

  /**
   * Constructs a CalaisService object.
   *
   * Valid parameters are specified in the options array as key/value pairs with the
   * parameter name being the key and the parameter setting being the value
   * e.g. array('allowSearch' => 'false')
   *
   * @param \Drupal\opencalais_ui\JsonProcessor
   *   The .
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   */
  public function __construct(JsonProcessor $json_processor, ConfigFactoryInterface $config_factory, Client $http_client) {
    $this->config = $config_factory->get('opencalais_ui.settings');
    $this->jsonProcessor = $json_processor;
    $this->httpClient = $http_client;
    $this->parameters['externalID'] = time();
    $this->parameters['host'] = $this->config->get('api_server');
  }

  /**
   * Analyze the provided content, passing it to Calais in HTML format.
   *
   * @param $content
   *   The HTML content to process
   * @return array
   *   The processed Calais results.
   */
  public function analyzeHTML($content) {
    $this->parameters['contentType'] = 'text/html';
    return $this->analyze($content);
  }

  /**
   * Analyze the content via Calais.
   *
   * @param $content
   *   The content to ship off to Calais for analysis
   * @return array
   *   The processed Calais results.
   */
  public function analyze($content) {
    $headers = [
      'Content-Type' => 'text/html',
      'x-ag-access-token' => $this->config->get('api_key'),
      'outputFormat' => 'application/json',
    ];
    $uri = $this->parameters['protocol'] . '://' . $this->parameters['host'] . $this->path;
    $req = [
      'headers' => $headers,
      'body' => $content,
    ];
    $response = $this->httpClient->post($uri, $req);
    $ret = (string) $response->getBody();

    $keywords = $this->jsonProcessor->parse_json($ret);
    return $keywords;
  }

  /**
   * Checks if the Calais api key is set.
   *
   * @return bool
   *   Whether the api key is set or not.
   */
  public function apiKeySet() {
    $config = \Drupal::config('opencalais_ui.settings');
    $api_key = $config->get('api_key');
    if ($api_key != '') {
      return TRUE;
    }
    return FALSE;
  }

}
