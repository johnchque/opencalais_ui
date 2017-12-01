<?php

namespace Drupal\opencalais_ui\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\opencalais_ui\CalaisService $calais_service
   *   The OpenCalais service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(CalaisService $calais_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $this->config('opencalais_ui.settings');
    $this->calaisService = $calais_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('opencalais_ui.calais_service'),
      $container->get('entity_type.manager')
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
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $library_item_render_array = $view_builder->view($node);
    $form['content'] = $view_builder->build($library_item_render_array);
    $form_state->set('entity', $node);

    if ($form_state->get('analyse')) {
      // Build the details boxes for aboutness tags and entities.
      $form['open_calais'] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];
      $form['open_calais']['entities'] = [
        '#type' => 'details',
        '#title' => $this->t('Entities'),
        '#open' => TRUE,
      ];
      $form['open_calais']['aboutness_tags'] = [
        '#type' => 'details',
        '#title' => $this->t('Aboutness tags'),
        '#open' => TRUE,
      ];

      // Append all the text fields in an unique string.
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

      $social_tags = [];
      foreach ($result['social_tags'] as $key => $value) {
        $social_tags[$key] = $value['name'] . ' (' . $value['importance'] . ')';
      }
      $form['open_calais']['aboutness_tags']['social_tags'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Social tags'),
        '#options' => $social_tags,
      ];

      $topic_tags = [];
      foreach ($result['topic_tags'] as $key => $value) {
        $topic_tags[$key] = $value['name'] . ' (' . $value['score'] . ')';
      }
      $form['open_calais']['aboutness_tags']['topic_tags'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Topic tags'),
        '#options' => $topic_tags,
      ];

      $industry_tags = [];
      foreach ($result['industry_tags'] as $key => $value) {
        $industry_tags[$key] = $value['name'] . ' (' . $value['relevance'] . ')';
      }
      $form['open_calais']['aboutness_tags']['industry_tags'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Industry tags'),
        '#options' => $industry_tags,
      ];

      $entities_options = [];
      foreach ($result['entities'] as $key => $value) {
        foreach ($value as $entity_value) {
          $entities_options[$entity_value['name']] = $entity_value['name'] . ' (' . $entity_value['confidence'] . ')';
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
    foreach ($values['aboutness_tags'] as $tags_type_id => $tags) {
      foreach ($tags as $key => $value) {
        if ($value != FALSE) {
          $values = [
            'name' => $key,
            'vid' => $tags_type_id,
          ];
          if (!$term = $this->entityTypeManager->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $value, 'vid' => $tags_type_id])) {
            $term = Term::create($values);
            $term->save();
            $node->$field[] = $term;
          }
        }
      }
    };

    foreach ($values['entities'] as $key => $value) {
      foreach ($value as $entity_id => $entity_value) {
        if ($entity_value != FALSE) {
          $key_id = $term = $this->entityTypeManager->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $key]);
          $values = [
            'name' => $entity_id,
            'vid' => 'markup_tags',
            'subclassof' => $key_id
          ];
          // If the term has been added already to the entity, skip it.
          if (!$term = $this->entityTypeManager->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $entity_value])) {
            $term = Term::create($values);
            $term->save();
            $node->$field[] = $term;
          }
        }
      }
    };
    $node->save();
  }

}
