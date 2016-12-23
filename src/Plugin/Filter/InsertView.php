<?php

/**
 * @file
 * Contains \Drupal\insert_view\Plugin\Filter\InsertView.
 */

namespace Drupal\insert_view\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Views;

/**
 * Provides a filter for insert view.
 *
 * @Filter(
 *   id = "insert_view",
 *   module = "insert_view",
 *   title = @Translation("Insert View"),
 *   description = @Translation("Allows to embed views using the simple syntax: [view:name=display=args]"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class InsertView extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
      $matches = [];
      $result = new FilterProcessResult($text);
      // check first the direct input of shortcode
      $count = preg_match_all("/\[view:([^=\]]+)=?([^=\]]+)?=?([^\]]*)?\]/i", $text, $matches);
      if ($count) {
          $search = $replace = array();
          foreach ($matches[0] as $key => $value) {
              $view_name = $matches[1][$key];
              $display_id = ($matches[2][$key] && !is_numeric($matches[2][$key])) ? $matches[2][$key] : 'default';
              $args = $matches[3][$key];
              $view_output = $result->createPlaceholder('\Drupal\insert_view\Plugin\Filter\InsertView::build', array($view_name, $display_id, $args));
              $search[] = $value;
              $replace[] = $view_output;
          }
          $text = str_replace($search, $replace, $text);
          $result->setProcessedText($text)->setCacheTags(['insert_view']);
      }
      // check the view inserted from the CKeditor plugin
      $count = preg_match_all('/(<p>)?(?<json>{(?=.*inserted_view\b)(?=.*arguments\b)(.*)})(<\/p>)?/', $text, $matches);
      if ($count) {
          $search = $replace = array();
          foreach ($matches['json'] as $key => $value) {
              $inserted = json_decode($value, TRUE);
              if (!is_array($inserted) || empty($inserted)) {
                  continue;
              }
              $view_parts = explode('=', $inserted['inserted_view']);
              if (empty($view_parts)) {
                  continue;
              }
              $view_name = $view_parts[0];
              $display_id = ($view_parts[1] && !is_numeric($view_parts[1])) ? $view_parts[1] : 'default';
              $args = '';
              if (!empty($inserted['arguments'])) {
                  $args = implode('/',$inserted['arguments']);
              }
              $view_output = $result->createPlaceholder('\Drupal\insert_view\Plugin\Filter\InsertView::build', array($view_name, $display_id, $args));
              $search[] = $value;
              $replace[] = $view_output;
          }
          $text = str_replace($search, $replace, $text);
          $result->setProcessedText($text)->setCacheTags(['insert_view']);
      }

      return $result;
  }

    /**
     * @param $view_name
     * @param $display_id
     * @param $args
     * @return array|void
     */
  static public function build($view_name, $display_id, $args) {
      if (empty($view_name)) {
          return;
      }
      $view = Views::getView($view_name);
      if (empty($view)) {
          return;
      }
      if (!$view->access($display_id)) {
          return;
      }
      $current_path = \Drupal::service('path.current')->getPath();
      $url_args = explode('/', $current_path);
      foreach ($url_args as $id => $arg) {
          $args = str_replace("%$id", $arg, $args);
      }
      $args = preg_replace(',/?(%\d),', '', $args);
      $args = $args ? explode('/', $args) : array();

      $output = $view->preview($display_id, $args);

      $build = [
          '#markup' => render($output),
      ];

      return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
        $examples = [
            '[view:my_view]',
            '[view:my_view=my_display]',
            '[view:my_view=my_display=arg1/arg2/arg3]',
            '[view:my_view==arg1/arg2/arg3]',
        ];
        $items = [
            $this->t('Insert view filter allows to embed views using tags. The tag syntax is relatively simple: [view:name=display=args]'),
            $this->t('For example [view:tracker=page=1] says, embed a view named "tracker", use the "page" display, and supply the argument "1".'),
            $this->t('The <em>display</em> and <em>args</em> parameters can be omitted. If the display is left empty, the view\'s default display is used.'),
            $this->t('Multiple arguments are separated with slash. The <em>args</em> format is the same as used in the URL (or view preview screen).'),
            [
                'data' => $this->t('Valid examples'),
                'children' => $examples,
            ],
        ];
        $list = [
            '#type' => 'item_list',
            '#items' => $items,
        ];
        return $this->renderer->render($list, FALSE);
    }
    else {
      return t('You may use [view:<em>name=display=args</em>] tags to display views.');
    }
  }
}

