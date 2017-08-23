<?php

namespace Drupal\webform;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines an interface for help classes.
 */
interface WebformHelpManagerInterface {

  /**
   * Get help.
   *
   * @param string|null $id
   *   (optional) Help id.
   *
   * @return array|mixed
   *   A single help item or all help.
   */
  public function getHelp($id = NULL);

  /**
   * Get video.
   *
   * @param string|null $id
   *   (optional) Video id.
   *
   * @return array|mixed
   *   A single help item or all videos.
   */
  public function getVideo($id = NULL);

  /**
   * Build help for specific route.
   *
   * @param string $route_name
   *   The route for which to find help.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object from which to find help.
   *
   * @return array
   *   An render array containing help for specific route.
   */
  public function buildHelp($route_name, RouteMatchInterface $route_match);

  /**
   * Build help (aka 'How can we help you?') menu (dropbutton) element.
   *
   * @return array
   *   An render array containing about (aka 'How can we help you?') operations
   *   (dropbutton) element.
   */
  public function buildHelpMenu();

  /**
   * Build the main help page for the Webform module.
   *
   * @return array
   *   An render array containing help for the Webform module.
   */
  public function buildIndex();

  /**
   * Build the about section.
   *
   * @return array
   *   An render array containing the about section.
   */
  public function buildAbout();

  /**
   * Build the elements section.
   *
   * @param bool $docs
   *   Set to TRUE to build exportable HTML documentation.
   *
   * @return array
   *   An render array containing the elements section.
   */
  public function buildElements($docs = FALSE);

  /**
   * Build the uses section.
   *
   * @param bool $docs
   *   Set to TRUE to build exportable HTML documentation.
   *
   * @return array
   *   An render array containing the uses section.
   */
  public function buildUses($docs = FALSE);

  /**
   * Build the videos section.
   *
   * @param bool $docs
   *   Set to TRUE to build exportable HTML documentation.
   *
   * @return array
   *   An render array containing the videos section.
   */
  public function buildVideos($docs = FALSE);

  /**
   * Build the add-ons section.
   *
   * @param bool $docs
   *   Set to TRUE to build exportable HTML documentation.
   *
   * @return array
   *   An render array containing the add-ons section.
   */
  public function buildAddOns($docs = FALSE);

  /**
   * Build the libraries section.
   *
   * @param bool $docs
   *   Set to TRUE to build exportable HTML documentation.
   *
   * @return array
   *   An render array containing the libraries section.
   */
  public function buildLibraries($docs = FALSE);

  /**
   * Build the comparison section.
   *
   * @param bool $docs
   *   Set to TRUE to build exportable HTML documentation.
   *
   * @return array
   *   An render array containing the comparison section.
   */
  public function buildComparison($docs = FALSE);
}
