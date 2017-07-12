<?php

namespace Drupal\opencalais_api;

class JsonProcessor {

  /**
   * The decoded OpenCalais response.
   *
   * @var array
   */
  protected $decoded_response;

  /**
   * The keywords found for the analyzed text.
   *
   * @var array
   */
  protected $keywords = [];

  /**
   * Parse the Json response. It is processed in two stages. The first stage
   * identifies all entities, events, and facts. The second stage adds relevance
   * and geo info to those previously identified terms. The 2nd pass is required
   * because sometimes the relevance/geo data appears in the document before the
   * term has been identified.
   *
   * @param $json
   *    The json to parse
   *
   * @return array
   *    An array of CalaisMetadata objects.
   */
  public function parse_json($json) {
    $this->decoded_response = json_decode($json, TRUE);
    $this->build_entities();
    return $this->keywords;
  }

  /**
   * Build the set of entities from this RDF triples returned from Calais.
   */
  protected function build_entities() {
    foreach ($this->decoded_response as $guid => $data) {
      if (isset($data['_typeGroup']) && $data['_typeGroup'] == 'entities') {
        $this->extractEntities($guid, $data);
      }
      elseif (isset($data['_typeGroup']) && $data['_typeGroup'] == 'socialTag') {
        $this->extractTags($guid, $data);
      }
    }
    return $this->keywords;
  }

  /**
   * Extracts the entities from the returned data.
   *
   * @param $guid
   *   The guid for the current Calais Term
   * @param $data
   *   The indexed triple for the current Calais Term/GUID
   */
  protected function extractEntities($guid, $data) {
    $entity_type = $data['_type'];
    $entity_value = $data['name'];
    $this->keywords['entities'][$entity_type][] = $entity_value;
  }

  /**
   * Extracts the Social Tags from the returned data.
   *
   * @param $guid
   *   The guid for the current Calais Term
   * @param $data
   *   The indexed triple for the current Calais Term/GUID
   */
  protected function extractTags($guid, $data) {
    $tag_val = $data['name'];
    $this->keywords['social_tags'][$tag_val] = $tag_val;
  }

}
