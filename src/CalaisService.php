<?php
namespace Drupal\opencalais_api;

// $Id: Calais.inc,v 1.1.2.16.2.2 2009/12/14 21:50:26 febbraro Exp $
/**
 * @file CalaisService.php
 * The main interface to the calais web service
 */

class CalaisService implements Calais {

  const PATH = '/permid/calais';

  private $defaults = array(
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
  );

  public $parameters;
  public $rdf;
  public $triples = array();
  public $flatTriples = array();
  public $keywords = array();

  /**
   * Constructs an instance of the Calais facade.
   *
   * Valid parameters are specified in the options array as key/value pairs with the
   * parameter name being the key and the parameter setting being the value
   * e.g. array('allowSearch' => 'false')
   *
   * @param options  An array of parameter options for the Calais Web Service.
   *                  These will override the defaults.
   *
   * @see http://opencalais.com/APIcalls#inputparameters
   */
  function __construct() {
    $this->defaults['externalID'] = time();
    $this->defaults['host'] = \Drupal::config('opencalais_api.settings')
      ->get('api_server');

    $this->parameters = array_merge($this->defaults);
  }

  /**
   * Analyze the provided content, passing it to Calais in XML format for more accurate data processing.
   *
   * @param $title  The title of the content to process
   * @param $body   The body ofd the content to process
   * @param $date   The date of the content, if left blank/null analysis will use "today"
   *
   * @return The processed Calais results. The raw RDF result is contained in the $this->rdf field.
   */
  public function analyzeXML($title, $body, $date) {
    $content = $this->build_xml_content($title, $body, $date);
    $this->parameters['contentType'] = 'TEXT/XML';
    return $this->analyze($content);
  }

  /**
   * Analyze the provided content, passing it to Calais in HTML format .
   *
   * @param $content
   *    The HTML content to process
   *
   * @return
   *    The processed Calais results. The raw RDF result is contained in the $this->rdf field.
   */
  public function analyzeHTML($content) {
    $this->parameters['contentType'] = 'TEXT/HTML';
    return $this->analyze($content);
  }

  /**
   * Analyze the content via Calais.
   *
   * @param $content
   *   The content to ship off to Calais for analysis
   *
   * @return array
   *   The processed Calais results.
   */
  public function analyze($content) {
    $headers = [
      'Content-Type' => 'text/html',
      'x-ag-access-token' => \Drupal::config('opencalais_api.settings')
        ->get('api_key'),
      'outputFormat' => 'application/json',
    ];
    //$data_enc = http_build_query(['content' => $content]);
    $uri = $this->parameters['protocol'] . '://' . $this->parameters['host'] . self::PATH;
    $req = array(
      'headers' => $headers,
      'body' => $content,
    );
    $response = \Drupal::httpClient()->post($uri, $req);
    $ret = (string) $response->getBody();

    $this->processor = new CalaisJsonProcessor();
    $this->keywords = $this->processor->parse_json($ret);
    $this->triples = $this->processor->triples;
    if (isset($this->processor->flatTriples)) {
      $this->flatTriples = $this->processor->flatTriples;
    }
    return $this->keywords;
  }

}
