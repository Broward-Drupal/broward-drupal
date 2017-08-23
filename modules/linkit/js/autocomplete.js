/**
 * @file
 * Linkit Autocomplete based on jQuery UI.
 */

(function ($, Drupal, _) {

  'use strict';

  var autocomplete;

  /**
   * JQuery UI autocomplete source callback.
   *
   * @param {object} request
   *   The request object.
   * @param {function} response
   *   The function to call with the response.
   */
  function sourceData(request, response) {
    var elementId = this.element.attr('id');

    if (!(elementId in autocomplete.cache)) {
      autocomplete.cache[elementId] = {};
    }

    /**
     * Transforms the data object into an array and update autocomplete results.
     *
     * @param {object} data
     *   The data sent back from the server.
     */
    function sourceCallbackHandler(data) {
      autocomplete.cache[elementId][term] = data.suggestions;
      response(data.suggestions);
    }

    // Get the desired term and construct the autocomplete URL for it.
    var term = request.term;

    // Check if the term is already cached.
    if (autocomplete.cache[elementId].hasOwnProperty(term)) {
      response(autocomplete.cache[elementId][term]);
    }
    else {
      var options = $.extend({
                               success: sourceCallbackHandler,
                               data: {q: term}
                             }, autocomplete.ajax);
      $.ajax(this.element.attr('data-autocomplete-path'), options);
    }
  }

  /**
   * Handles an autocomplete select event.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   * @param {object} ui
   *   The jQuery UI settings object.
   *
   * @return {boolean}
   *   False to prevent further handlers.
   */
  function selectHandler(event, ui) {
    var $form = $(event.target).closest('form');

    if (!ui.item.path) {
      throw 'Missing path param.' + JSON.stringify(ui.item);
    }

    $('input[name="href_dirty_check"]', $form).val(ui.item.path);

    if (ui.item.entity_type_id || ui.item.entity_uuid || ui.item.substitution_id) {
      if (!ui.item.entity_type_id || !ui.item.entity_uuid || !ui.item.substitution_id) {
        throw 'Missing path param.' + JSON.stringify(ui.item);
      }

      $('input[name="attributes[data-entity-type]"]', $form).val(ui.item.entity_type_id);
      $('input[name="attributes[data-entity-uuid]"]', $form).val(ui.item.entity_uuid);
      $('input[name="attributes[data-entity-substitution]"]', $form).val(ui.item.substitution_id);
    }

    event.target.value = ui.item.path;

    return false;
  }

  /**
   * Override jQuery UI _renderItem function to output HTML by default.
   *
   * @param {object} ul
   *   The <ul> element that the newly created <li> element must be appended to.
   * @param {object} item
   *  The list item to append.
   *
   * @return {object}
   *   jQuery collection of the ul element.
   */
  function renderItem(ul, item) {
    var $line = $('<li>').addClass('linkit-result');
    $line.append($('<span>').html(item.label).addClass('linkit-result--title'));

    if (item.hasOwnProperty('description')) {
      $line.append($('<span>').html(item.description).addClass('linkit-result--description'));
    }

    return $line.appendTo(ul);
  }

  /**
   * Override jQuery UI _renderMenu function to handle groups.
   *
   * @param {object} ul
   *   An empty <ul> element to use as the widget's menu.
   * @param {array} items
   *   An Array of items that match the user typed term.
   */
  function renderMenu(ul, items) {
    var self = this.element.autocomplete('instance');

    var grouped_items = _.groupBy(items, function (item) {
      return item.hasOwnProperty('group') ? item.group : '';
    });

    $.each(grouped_items, function (group, items) {
      if (group.length) {
        ul.append('<li class="linkit-result--group">' + group + '</li>');
      }

      $.each(items, function (index, item) {
        self._renderItemData(ul, item);
      });
    });
  }

  /**
   * Attaches the autocomplete behavior to all required fields.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the autocomplete behaviors.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the autocomplete behaviors.
   */
  Drupal.behaviors.linkit_autocomplete = {
    attach: function (context) {
      // Act on textfields with the "form-linkit-autocomplete" class.
      var $autocomplete = $(context).find('input.form-linkit-autocomplete').once('linkit-autocomplete');
      if ($autocomplete.length) {
        $.widget('custom.autocomplete', $.ui.autocomplete, {
          _create: function () {
            this._super();
            this.widget().menu('option', 'items', '> :not(.linkit-result--group)');
          },
          _renderMenu: autocomplete.options.renderMenu,
          _renderItem: autocomplete.options.renderItem
        });

        // Use jQuery UI Autocomplete on the textfield.
        $autocomplete.autocomplete(autocomplete.options);
        $autocomplete.autocomplete('widget').addClass('linkit-ui-autocomplete');

        $autocomplete.click(function () {
          $autocomplete.autocomplete('search', $autocomplete.val());
        });
      }
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        $(context).find('input.form-linkit-autocomplete')
          .removeOnce('linkit-autocomplete')
          .autocomplete('destroy');
      }
    }
  };

  /**
   * Autocomplete object implementation.
   */
  autocomplete = {
    cache: {},
    options: {
      source: sourceData,
      renderItem: renderItem,
      renderMenu: renderMenu,
      select: selectHandler,
      minLength: 1
    },
    ajax: {
      dataType: 'json'
    }
  };

})(jQuery, Drupal, _);
