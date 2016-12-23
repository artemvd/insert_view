/**
 * @file
 * The JavaScript file for the wysiwyg integration.
 */

(function ($) {

  /**
   * A CKEditor plugin for vido embeds.
   */
  CKEDITOR.plugins.add('insert_view', {

    /**
     * Set the plugin modes.
     */
    modes: {
      wysiwyg: 1
    },

    /**
     * Define the plugin requirements.
     */
    requires: 'widget',

    /**
     * Allow undo actions.
     */
    canUndo: true,

    /**
     * Init the plugin.
     */
    init: function (editor) {
      this.registerWidget(editor);
      this.addCommand(editor);
      this.addIcon(editor);
    },

    /**
     * Add the command to the editor.
     */
    addCommand: function (editor) {
      var self = this;
      var modalSaveWrapper = function (values) {
        editor.fire('saveSnapshot');
        self.modalSave(editor, values);
        editor.fire('saveSnapshot');
      };
      editor.addCommand('insert_view', {
        exec: function (editor, data) {
          // If the selected element while we click the button is an instance
          // of the insert_view widget, extract it's values so they can be
          // sent to the server to prime the configuration form.
          var existingValues = {};
          if (editor.widgets.focused && editor.widgets.focused.name == 'insert_view') {
            existingValues = editor.widgets.focused.data.json;
          }
          Drupal.ckeditor.openDialog(editor, Drupal.url('insert-view-wysiwyg/dialog/' + editor.config.drupal.format), existingValues, modalSaveWrapper, {
            title: Drupal.t('Insert View'),
            dialogClass: 'insert-view-dialog'
          });
        }
      });
    },

    /**
     * A callback that is triggered when the modal is saved.
     */
    modalSave: function (editor, values) {
      // Insert a video widget that understands how to manage a JSON encoded
      // object, provided the video_embed property is set.
      var widget = editor.document.createElement('p');
      widget.setHtml(JSON.stringify(values));
      editor.insertHtml(widget.getOuterHtml());
    },

    /**
     * Register the widget.
     */
    registerWidget: function (editor) {
      var self = this;
      editor.widgets.add('insert_view', {
        downcast: self.downcast,
        upcast: self.upcast,
        mask: true
      });
    },

    /**
     * Check if the element is an instance of the video widget.
     */
    upcast: function (element, data) {
      // Upcast check must be sensitive to both HTML encoded and plain text.
      if (element.getHtml().indexOf('inserted_view') == -1) {
        return;
      }
      data.json = JSON.parse(element.getHtml());
      element.setHtml(Drupal.theme('insertView', data.json));
      return element;
    },

    /**
     * Turns a transformed widget into the downcasted representation.
     */
    downcast: function (element) {
      element.setHtml(JSON.stringify(this.data.json));
    },

    /**
     * Add the icon to the toolbar.
     */
    addIcon: function (editor) {
      if (!editor.ui.addButton) {
        return;
      }
      editor.ui.addButton('insert_view', {
        label: Drupal.t('Insert View'),
        command: 'insert_view',
        icon: this.path + '/icon.png'
      });
    }
  });

  /**
   * The widget template viewable in the WYSIWYG after creating a video.
   */
  Drupal.theme.insertView = function (settings) {
    var view_arguments = '';
    if (settings.arguments !== null && settings.arguments.length > 0) {
      view_arguments = '=';
      for (var i = 0; i < settings.arguments.length; i++) {
        view_arguments = view_arguments + settings.arguments[i];
        if (i != (settings.arguments.length - 1)) {
          view_arguments = view_arguments + '/';
        }
      }
    }
    return [
      '<span class="insert-view-widget">',
      '[view:' + settings.inserted_view + '' + view_arguments + ']',
      '</span>'
    ].join('');
  };

})(jQuery);
