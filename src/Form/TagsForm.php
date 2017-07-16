<?php

namespace Drupal\opencalais_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\opencalais_ui\CalaisService;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto tag content with the OpenCalais service.
 */
class TagsForm extends FormBase {

  /**
   * The OpenCalais service.
   *
   * @var \Drupal\opencalais_ui\CalaisService
   */
  protected $calaisService;

  /**
   * Wrapper object for simple configuration from diff.settings.yml.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\opencalais_ui\CalaisService $calais_service
   *   The OpenCalais service.
   */
  public function __construct(CalaisService $calais_service) {
    $this->config = $this->config('opencalais_ui.settings');
    $this->calaisService = $calais_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('opencalais_ui.calais_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opencalais_ui_tags';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $form['#tree'] = TRUE;
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $library_item_render_array = $view_builder->view($node);
    // This will remove all fields other then field_reusable_paragraph.
    $form['content'] = $view_builder->build($library_item_render_array);
    $form_state->set('entity', $node);

    if ($form_state->get('analyse')) {
      $text_types = [
        'text_with_summary',
        'text',
        'text_long',
        'list_string',
        'string',
      ];
      $text = '';
      $node = $form_state->get('entity');
      foreach ($node->getFieldDefinitions() as $field_name => $field_definition) {
        if (in_array($field_definition->getType(), $text_types)) {
          $text .= $node->get($field_name)->value;
        }
      };
      $result = $this->calaisService->analyze($text);

      $form['open_calais'] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];
      $social_tags_options = [];
      foreach ($result['social_tags'] as $key => $value) {
        $social_tags_options[$key] = $value;
      }
      $form['open_calais']['social_tags'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Social tags'),
        '#options' => $social_tags_options,
      ];
      $form['open_calais']['entities'] = [
        '#type' => 'item',
        '#title' => $this->t('Entities'),
      ];
      $entities_options = [];
      foreach ($result['entities'] as $key => $value) {
        foreach ($value as $entity_value) {
          $entities_options[$entity_value] = $entity_value;
        }
        $form['open_calais']['entities'][$key] = [
          '#type' => 'checkboxes',
          '#title' => $this->t($key),
          '#options' => $entities_options,
        ];
        $entities_options = [];
      }
    }
    // Add a submit button. Give it a class for easy JavaScript targeting.
    $form['suggested_tags'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'opencalais-suggested-tags',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 999,
    ];
    $form['actions']['suggest_tags'] = [
      '#type' => 'submit',
      '#value' => t('Suggest Tags'),
      '#attributes' => ['class' => ['opencalais_submit']],
      '#submit' => [[get_class($this), 'addMoreSubmit']],
      '#ajax' => [
        'callback' => '::suggestTagsAjax',
        'wrapper' => 'opencalais-suggested-tags',
        'effect' => 'fade',
      ],
    ];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Ajax callback for the "Suggest tags" button.
   */
  public function suggestTagsAjax(array $form, FormStateInterface $form_state) {
    return $form['open_calais'];
  }

  /**
   * Submission handler for the "Suggest tags" button.
   */
  public function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $form_state->set('analyse', TRUE);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('open_calais');
    $node = $form_state->get('entity');
    $type = NodeType::load($node->getType());
    $field = $type->getThirdPartySetting('opencalais_ui', 'field');
    foreach ($values['social_tags'] as $key => $value) {
      if ($value != FALSE) {
        $values = [
          'name' => $key,
          'vid' => 'tags',
        ];
        $term = Term::create($values);
        $term->save();
        $node->$field[] = $term->id();
      }
    }
    foreach ($values['entities'] as $key => $value) {
      foreach ($value as $entity_id => $entity_value) {
        if ($entity_value != FALSE) {
          $values = [
            'name' => $entity_id,
            'vid' => 'tags',
          ];
          $term = Term::create($values);
          $term->save();
          $node->$field[] = $term->id();
        }
      }
    }
    $node->save();
  }

}
