<?php

namespace Drupal\insert_view\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * The plugin for insert_view .
 *
 * @CKEditorPlugin(
 *   id = "insert_view",
 *   label = @Translation("Insert View WYSIWYG")
 * )
 */
class InsertView extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFile() {
        return drupal_get_path('module', 'insert_view') . '/plugin/plugin.js';
    }

    /**
     * {@inheritdoc}
     */
    public function getButtons() {
        return [
            'insert_view' => [
                'label' => $this->t('Insert View'),
                'image' => drupal_get_path('module', 'insert_view') . '/plugin/icon.png',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(Editor $editor) {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
        $editor_settings = $editor->getSettings();
        $plugin_settings = NestedArray::getValue($editor_settings, [
            'plugins',
            'insert_view',
            'defaults',
            'children',
        ]);
        $settings = $plugin_settings ?: [];

        $form['defaults'] = [
            '#title' => $this->t('Default Settings'),
            '#type' => 'fieldset',
            '#tree' => TRUE,
        ];
        return $form;
    }
}