<?php
namespace Drupal\opencalais_api;

// $Id: CalaisMetadata.inc,v 1.1.2.7 2009/02/06 22:32:53 febbraro Exp $
/**
 * @file CalaisMetadata.inc
 */
 
/**
 * This class represents results from the Calais Web Service.  These results are for 
 * Entities/Facts/Events.
 */
class CalaisMetadata {
  
  public $guid;
  public $type;
  public $terms = array();
  
  function __construct($guid, $type) {
    $this->guid = $guid;
    $this->type = $type;
  }
  
  /**
   * Does a term with this GUID already exist.
   *
   * @param $guid
   *    The global identifier of a term
   */
  function has_guid($guid) {
    return array_key_exists($guid, $this->terms);
  }
  
  /**
   * Add a relevance score to a term.
   *
   * @param $guid
   *    The global identifier of a term
   * @param $subject
   *    The identified value/term name
   * @param $relevance
   *    The relevance score for this term
   */
  function set_term_data($guid, $subject, $relevance = 0.0) {
    $term = &$this->_get_term($guid);
    $term->name = $subject;      
    $term->relevance = $relevance;      
  }

  /**
   * Add a relevance score to a term.
   *
   * @param $guid
   *    The global identifier of a term
   * @param $relevance
   *    The relevance score for this term
   */
  function set_term_relevance($guid, $relevance) {
    $term = &$this->_get_term($guid);
    $term->relevance = $relevance;      
  }
  
  /**
   * Set the resolved (normalized) name for the guid.
   *
   * @param $guid
   *    The global identifier of a term
   * @param $resolvedName
   *    The normalized value of a term
   * @param $resolvedGuid
   *    The resolved GUID of this entity (for Linked Data purposes)
   * @param $extra
   *    The extra data associated with a resolved entity (e.g. lat/lon, ticker, etc.)
   * @param $uris
   *    Any Linked Open Data URIs associated with the resolved entity
   */
  function set_term_resolved_data($guid, $resolvedName, $resolvedGuid, $resolvedType = NULL, $extra = array(), $uris = array()) {
    $term = &$this->_get_term($guid);
    $term->resolved_name = $resolvedName;      
    $term->resolved_guid = $resolvedGuid;      
    $term->resolved_type = $resolvedType;      
    $term->extra = array_merge($term->extra, $extra);   
    $term->uris = array_merge($term->uris, $uris);   
  }
  
  /**
   * Get a term by value.
   *
   * @param $value
   *    The value of a term
   * @return object 
   *    The CalaisTerm that has this value, or FALSE is it does not exist.
   */
  function get_term_by_value($value) {
    foreach ($this->terms as $term) {
      if ($term->name == $value) {
        return $term;
      }
    }
    return FALSE;
  }
  
  /**
   * Does a term with this value exist in this Entity bucket.
   *
   * @param $value
   *    The value of a term
   * @return boolean 
   *    TRUE if a CalaisTerm has this value, or FALSE is it does not exist.
   */
  function has_term_value($value) {
    return $this->get_term_by_value($value) !== FALSE;
  }
  
  /**
   * Remove the term with the provided value from this bucket
   *
   * @param $value
   *    The value of a term
   */
  function remove_term_by_name($name) {
    $filter = new TermFilter($name);
    $this->terms = array_filter($this->terms, array($filter, 'filter'));
  }
  
  /**
   * Takes the CamelCase type and adds spaces (to make it Camel Case)
   *
   * @return string
   */
  function readable_type() {
    // return calais_api_make_readable($this->type);
    return NULL;
  }
  
  // Get a term if it exists, or create it if it doesn't
  protected function &_get_term($guid) {
    if (!$this->has_guid($guid)) {
      $this->terms[$guid] = new CalaisTerm($guid);
    }
    return $this->terms[$guid];
  }
}

class TermFilter {
  public $filter;

  function __construct($filter) {
    $this->filter = $filter;
  }
  
  function filter($term) {
    return $term->name != $this->filter;
  }
}

/**
 * This class represents a specific term result from the Calais Web Service.
 */
class CalaisTerm {
  public $guid;
  public $name;
  public $relevance;
  
  // Term Data ID
  public $tdid = NULL;
  
  // Extra data for resolved entities
  public $resolved_name = NULL;
  public $resolved_guid = NULL;
  public $resolved_type = NULL;
  
  function __construct($guid, $name = '', $relevance = 0.0) {
    $this->guid = $guid;
    $this->name = $name;
    $this->relevance = $relevance;
    $this->uris = array();
    $this->extra = array();
  }
}