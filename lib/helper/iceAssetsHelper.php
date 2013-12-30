<?php

function ice_cdn_domain($subdomain = null)
{
  return '';
//  return sprintf(
//    '//%s%s', $subdomain ? $subdomain .'.' : null, sfConfig::get('app_ice_assets_domain')
//  );
}

function ice_spacer_image($width = 1, $height = 1, $options = array())
{
  $options = array_merge($options, array('width' => $width, 'height' => $height));

  echo ice_cdn_image_tag('s.gif', 'assets', $options);
}

function ice_cdn_image_tag($image, $subdomain = null, $options = array())
{
  return image_tag(ice_cdn_image_src($image, $subdomain), $options);
}

function ice_cdn_image_src($image, $subdomain = null)
{
  return ice_cdn_domain($subdomain) .'/images/'. $image;
}

function ice_cdn_stylesheet_src($stylesheet, $subdomain = null)
{
  return ice_cdn_domain($subdomain) .'/css/'. $stylesheet;
}

function ice_cdn_javascript_src($javascript, $subdomain = null)
{
  return ice_cdn_domain($subdomain) .'/js/'. $javascript;
}

function ice_use_javascript($js, $position = '', $options = array())
{
  use_javascript('/assets/js/'. $js, $position, $options);
}

function ice_use_stylesheet($stylesheet, $position = '', $options = array())
{
  use_stylesheet('/assets/css/'. $stylesheet, $position, $options);
}

function ice_include_stylesheets()
{
  // Do not combine or do anything special if not in Production
  if (SF_ENV !== 'prod')
  {
    include_stylesheets();

    return;
  }

  /** @var $response sfWebResponse */
  $response = sfContext::getInstance()->getResponse();
  sfConfig::set('symfony.asset.stylesheets_included', true);

  if ($response->getStylesheets(sfWebResponse::FIRST))
  {
    $stylesheets = array();
    foreach (array_keys($response->getStylesheets(sfWebResponse::FIRST)) as $stylesheet)
    {
      if ($stylesheet[0] == '/' && substr($stylesheet, 0, 5) != '/css/')
      {
        $stylesheets[] = $stylesheet;
      }
    }

    ice_combine_stylesheets($stylesheets);
  }

  if ($response->getStylesheets(sfWebResponse::MIDDLE))
  {
    $stylesheets = array();
    foreach (array_keys($response->getStylesheets(sfWebResponse::MIDDLE)) as $stylesheet)
    {
      if ($stylesheet[0] == '/' && substr($stylesheet, 0, 5) != '/css/')
      {
        $stylesheets[] = $stylesheet;
      }
    }

    ice_combine_stylesheets($stylesheets);
  }

  if ($response->getStylesheets(sfWebResponse::LAST))
  {
    $stylesheets = array();
    foreach (array_keys($response->getStylesheets(sfWebResponse::LAST)) as $stylesheet)
    {
      if ($stylesheet[0] == '/' && substr($stylesheet, 0, 5) != '/css/')
      {
        $stylesheets[] = $stylesheet;
      }
    }

    ice_combine_stylesheets($stylesheets);
  }
}

function ice_include_javascripts()
{
  // Do not combine or do anything special if not in Production
  if (SF_ENV !== 'prod')
  {
    include_javascripts();

    return;
  }

  /** @var $response sfWebResponse */
  $response = sfContext::getInstance()->getResponse();
  sfConfig::set('symfony.asset.javascripts_included', true);

  if ($response->getJavascripts(sfWebResponse::FIRST))
  {
    $javascripts = array();
    foreach (array_keys($response->getJavascripts(sfWebResponse::FIRST)) as $javascript)
    {
      if ($javascript[0] == '/' && substr($javascript, 0, 3) != '/js/')
      {
        $javascripts[] = $javascript;
      }
    }

    ice_combine_javascripts($javascripts);
  }

  if ($response->getJavascripts(sfWebResponse::MIDDLE))
  {
    $javascripts = array();
    foreach (array_keys($response->getJavascripts(sfWebResponse::MIDDLE)) as $javascript)
    {
      if ($javascript[0] == '/' && substr($javascript, 0, 3) != '/js/')
      {
        $javascripts[] = $javascript;
      }
    }

    ice_combine_javascripts($javascripts);
  }

  if ($response->getJavascripts(sfWebResponse::LAST))
  {
    $javascripts = array();
    foreach (array_keys($response->getJavascripts(sfWebResponse::LAST)) as $javascript)
    {
      if ($javascript[0] == '/' && substr($javascript, 0, 3) != '/js/')
      {
        $javascripts[] = $javascript;
      }
    }

    ice_combine_javascripts($javascripts);
  }
}

/**
 * Generates a <link> tag
 *
 * @param array $stylesheets
 */
function ice_combine_stylesheets($stylesheets)
{
  if (!empty($stylesheets) && is_array($stylesheets))
  {
    $url = sprintf(
      '%s/combine.php?type=css&files=%s',
      ice_cdn_domain(), implode(',', $stylesheets)
    );

    if (defined('SVN_REVISION'))
    {
      $url .= '&revision='. intval(SVN_REVISION);
    }
    if (class_exists('sfConfig') && sfConfig::get('sf_environment') !== 'prod')
    {
      $url .= '&cache=0';
    }

    echo '<link rel="stylesheet" type="text/css" href="', $url, '"/>';
  }
}

/**
 * Generates a <script> tag
 *
 * @param     array  $javascripts
 * @return    void
 */
function ice_combine_javascripts($javascripts)
{
  if (!empty($javascripts) && is_array($javascripts))
  {
    $url = sprintf(
      '%s/combine.php?type=javascript&files=%s',
      ice_cdn_domain(), implode(',', $javascripts)
    );

    if (defined('SVN_REVISION'))
    {
      $url .= '&revision='. intval(SVN_REVISION);
    }
    if (class_exists('sfConfig') && sfConfig::get('sf_environment') !== 'prod')
    {
      $url .= '&cache=0';
    }

    echo '<script type="text/javascript" src="', $url,'"></script>';
  }
}

/**
 * Dynamic Dummy Image Generator
 *
 * You can specify one dimension and a ratio and the script will calculate the right value.
 * Example: 640x4:3 or 16:9x1080
 *
 * @param     string|array  $size
 * @param     array         $options
 *
 * @return    string
 */
function ice_image_tag_placeholder($size, $options = array())
{
  if (is_array($size))
  {
    $size = implode('x', array_slice($size, 0, 2));
  }

  $text = isset($options['text']) ? (string) $options['text'] : null;
  $background_color = isset($options['background_color']) ? (string) $options['background_color'] : 'ccc';
  $foreground_color = isset($options['foreground_color']) ? (string) $options['foreground_color'] : '969696';

  // Unset the custom options
  unset($options['text'], $options['background_color'], $options['foreground_color']);

  $x = $size .'/'. $background_color .'/'. $foreground_color .'.png';

  return image_tag(ice_cdn_domain('assets') .'/placeholdr.php?x='. $x .'&text='. urlencode($text), $options);
}

/**
 * Get placeholders related to the site you are developing,
 * by pulling images from flickr based on tags
 *
 * @param     string|array  $size
 * @param     array         $options
 *
 * @return    string
 */
function ice_image_tag_flickholdr($size, $options = array())
{
  if (is_array($size))
  {
    $size = implode('/', array_slice($size, 0, 2));
  }
  else if (false !== stripos($size, 'x'))
  {
    $size = str_replace('x', '/', $size);
  }

  $i = isset($options['i']) ? (int) $options['i'] : 1;
  $tags = isset($options['tags']) ? (array) $options['tags'] : array('abstract');

  // Unset the custom options
  unset($options['i'], $options['tags']);

  return image_tag('http://lorempixel.com/'. $size .'/'. implode(',', (array) $tags) .'/'. $i, $options);
}
