/**
 * @file
 * Attaches behaviors for the Block Bootstrap Dropdown.
 *
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Prevent dropdown from closing click inside.
   *
   */
  Drupal.behaviors.BlockBootstrapDropdown = {
    attach: function (context) {
      $(document).on('click', '.block-bootstrap-dropdown .dropdown-menu', function(e) {
        e.stopPropagation()
      })
    }
  };

})(jQuery, Drupal);
