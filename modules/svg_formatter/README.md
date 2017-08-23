# SVG Formatter

## CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Using the module
  * Author

## INTRODUCTION

Standard image field in Drupal 8 doesn't support SVG images. If you really want
to display SVG images on your website then you need another solution. This
module adds a new formatter for the file field, which allows any file extension
to be uploaded. In the formatter settings you can set default image size and 
enable alt and title attributes. If you want to add some CSS and JavaScript 
magic to your SVG images, then use inline SVG option.

## REQUIREMENTS

None.

## INSTALLATION

1. Install module as usual via Drupal UI, Drush or Composer.
2. Go to "Extend" and enable the SVG Formatter module.

## USING THE MODULE

1. Add a file field to your content type or taxonomy vocabulary and add svg to 
the allowed file extensions.
2. Go to the 'Manage display' and change the field format to 'SVG Formatter'.
3. Set image dimensions if you want and enable or disable attributes.

### AUTHOR

Goran Nikolovski  
Website: http://gorannikolovski.com  
Drupal: https://www.drupal.org/user/3451979  
Email: nikolovski84@gmail.com  

Company: Studio Present, Subotica, Serbia  
Website: http://www.studiopresent.com  
Drupal: https://www.drupal.org/studio-present  
Email: info@studiopresent.com  
