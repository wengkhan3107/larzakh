<?php

namespace Drupal\xmlfeedviews\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Renders an XML FEED VIEWS item based on fields.
 *
 * @ViewsRow(
 *   id = "xmlfeedviews_fields",
 *   title = @Translation("XML Feed Views fields"),
 *   help = @Translation("Display fields as XML items."),
 *   theme = "xmlfeedviews_row",
 *   display_types = {"feed"}
 * )
 */
class XmlFeedViewsFields extends RowPluginBase {

  /**
   * Does the row plugin support to add fields to its output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Returns the request_stack service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new XmlFeedViewsFields object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request represents an HTTP request.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
    );
  }

  /**
   * Define options.
   *
   * @return array
   *   Returns array.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['xmlfeedviews_body_before'] = ['default' => ''];
    $options['xmlfeedviews_body'] = ['default' => ''];
    $options['xmlfeedviews_body_after'] = ['default' => ''];
    return $options;
  }

  /**
   * Build options for form.
   *
   * @param array &$form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Form with new fields.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['xmlfeedviews_body_before'] = [
      '#type' => 'textfield',
      '#title' => $this->t('XML Items Wrapper'),
      '#default_value' => $this->options['xmlfeedviews_body_before'],
      '#description' => $this->t('This is your XML items wrapper opening tag. For ex: &lt;url&gt;'),
    ];
    $form['xmlfeedviews_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('XML Body'),
      '#default_value' => $this->options['xmlfeedviews_body'],
      '#description' => $this->t('This is your XML body. Add items on each line. For ex: &lt;google:title&gt;{{ title }}&lt;/google:title&gt;. Make sure you add the "title" in the Fields sections.'),
    ];
    $form['xmlfeedviews_body_after'] = [
      '#type' => 'textfield',
      '#title' => $this->t('XML Items Wrapper closing'),
      '#default_value' => $this->options['xmlfeedviews_body_after'],
      '#description' => $this->t('This is your XML items wrapper closing tag. For ex: &lt;/url&gt;'),
    ];

    return $form;
  }

  /**
   * Validate the form.
   *
   * @return array
   *   Returns Array.
   */
  public function validate() {
    $errors = parent::validate();
    $required_options = [
      'xmlfeedviews_body',
    ];
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Row style plugin requires specifying which views fields to use for XML FEED VIEWS items.');
        break;
      }
    }
    return $errors;
  }

  /**
   * Render.
   *
   * @param object $row
   *   Row object.
   *
   * @return array|string
   *   Returns array or string.
   */
  public function render($row) {
    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }

    $item = new \stdClass();
    $item->body_before = $this->options['xmlfeedviews_body_before'] ?? NULL;
    $item->body = $this->getBodyField($row_index, $this->options['xmlfeedviews_body']);
    $item->body_after = $this->options['xmlfeedviews_body_after'] ?? NULL;

    $row_index++;

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => $this->field_alias ?? '',
    ];

    return $build;
  }

  /**
   * Get body field.
   *
   * @param int $index
   *   Index of the field.
   * @param string $fieldData
   *   Field data.
   *
   * @return string|null
   *   Return the string.
   */
  public function getBodyField($index, $fieldData) {
    if (empty($this->view->style_plugin) || !is_object($this->view->style_plugin) || empty($fieldData)) {
      return '';
    }

    $result = $fieldData;
    $fieldLines = explode("\n", $fieldData);
    foreach ($fieldLines as $lineIndex => $fieldLine) {
      if ($lineIndex == 0) {
        $result = NULL;
      }
      preg_match_all('/\{{([A-Za-z0-9_ ]+?)\}}/', $fieldLine, $fieldArray);
      foreach ($fieldArray[0] as $i => $val) {
        if (isset($fieldArray[0][$i])
          && !empty($fieldArray[0][$i])
          && isset($fieldArray[1][$i])
          && !empty($fieldArray[1][$i])
        ) {
          $field_id = $fieldArray[1][$i] ?? '';
          /** @var \Drupal\Core\Render\Markup $data */
          $data = $this->getField($index, trim($field_id));
          if (is_object($data)) {
            $data = $data->__toString();
          }
          $fieldLine = str_replace($fieldArray[0][$i], $data ?? '', $fieldLine);
        }
      }
      $result .= $fieldLine . PHP_EOL;
    }
    return $result;
  }

  /**
   * Retrieves a views field value from the style plugin.
   *
   * @param int $index
   *   The index count of the row as expected by views_plugin_style::getField().
   * @param string $field_id
   *   The ID assigned to the required field in the display.
   *
   * @return string|null|\Drupal\Component\Render\MarkupInterface
   *   An empty string if there is no style plugin, or the field ID is empty.
   *   NULL if the field value is empty. If neither of these conditions apply,
   *   a MarkupInterface object containing the rendered field value.
   */
  public function getField($index, $field_id) {
    if (empty($this->view->style_plugin) || !is_object($this->view->style_plugin) || empty($field_id)) {
      return '';
    }

    return $this->view->style_plugin->getField($index, $field_id);
  }

  /**
   * Convert a rendered URL string to an absolute URL.
   *
   * @param string $url_string
   *   The rendered field value ready for display in a normal view.
   *
   * @return string
   *   A string with an absolute URL.
   */
  protected function getAbsoluteUrl($url_string) {
    // If the given URL already starts with a leading slash, it's been processed
    // and we need to simply make it an absolute path by prepending the host.
    if (strpos($url_string, '/') === 0) {
      $host = $this->request->getSchemeAndHttpHost();
      // @todo Views should expect and store a leading /.
      // @see https://www.drupal.org/node/2423913
      return $host . $url_string;
    }

    // Otherwise, this is an unprocessed path (e.g. node/123) and we need to run
    // it through a Url object to allow outbound path processors to run (path
    // aliases, language prefixes, etc).
    return Url::fromUserInput('/' . $url_string)->setAbsolute()->toString();
  }

}
