<?php

/**
  Dynamic Dummy Image Generator - DummyImage.com
  Copyright (c) 2011 Russell Heimlich

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in
  all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
  THE SOFTWARE.
*/

// GET the query string from the URL. x would = 600x400 if the url was http://dummyimage.com/600x400
$x = strtolower($_GET["x"]);
$x_pieces = explode('/', $x);

//Find the background color which is always after the 2nd slash in the url.
$bg_color = explode('.', @$x_pieces[1]);
$bg_color = $bg_color[0];
if (!$bg_color) {
  $bg_color = 'ccc'; //defaults to gray if no background color is set.
}
$background = new color();
$background->set_hex($bg_color);

//Find the foreground color which is always after the 3rd slash in the url.
$fg_color = explode('.', @$x_pieces[2]);
$fg_color = $fg_color[0];
if (!$fg_color) {
  $fg_color = 000; //defaults to black if no foreground color is set.
}
$foreground = new color();
$foreground->set_hex($fg_color);

//Determine the file format. This can be anywhere in the URL.
$file_format = 'gif';
preg_match_all('/(png|jpg|jpeg)/', $x, $result);
if (!empty($result[0][0])) {
  $file_format = $result[0][0];
}

//Find the image dimensions
if (substr_count($x_pieces[0], ':') > 1) {
  die('Too many colons in the dimension paramter! There should be 1 at most.');
}
if (strstr($x_pieces[0], ':') && !strstr($x_pieces[0], 'x')) {
  die('To calculate a ratio you need to provide a height!');
}

// Dimensions are always the first paramter in the URL.
$dimensions = explode('x', $x_pieces[0]);

// Filters out any characters that are not numbers, colons or decimal points.
$width = $height = preg_replace('/[^\d:\.]/i', '', $dimensions[0]);
if ($dimensions[1]) {
  // Filters out any characters that are not numbers, colons or decimal points.
  $height = preg_replace('/[^\d:\.]/i', '', $dimensions[1]);
}

// If it is too small we kill the script.
if ($width < 1 || $height < 1) {
  die("Too small of an image!");
}

/**
 * If one of the dimensions has a colon in it, we can calculate the aspect ratio.
 * Chances are the height will contain a ratio, so we'll check that first.
 */
if (preg_match('/:/', $height))
{
  $ratio = explode(':', $height);

  // If we only have one ratio value, set the other value to the same value of the first making it a ratio of 1.
  if (!$ratio[1]) {
    $ratio[1] = $ratio[0];
  }
  if (!$ratio[0]) {
    $ratio[0] = $ratio[1];
  }

  $height = ($width * $ratio[1]) / $ratio[0];
}
else if (preg_match('/:/', $width))
{
  $ratio = explode(':', $width);
  // If we only have one ratio value, set the other value to the same value of the first making it a ratio of 1.
  if (!$ratio[1]) {
    $ratio[1] = $ratio[0];
  }
  if (!$ratio[0]) {
    $ratio[0] = $ratio[1];
  }
  $width = ($height * $ratio[0]) / $ratio[1];
}

$area = $width * $height;
// Limit the size of the image to no more than an area of 16,000,000.
if ($area >= 16000000 || $width > 9999 || $height > 9999)
{
  // If it is too big we kill the script.
  die("Too big of an image!");
}

// Let's round the dimensions to 3 decimal places for aesthetics
$width = round($width, 3);
$height = round($height, 3);

// I don't use this but if you wanted to angle your text you would change it here.
$text_angle = 0;

$font = __DIR__ ."/../data/fonts/arial.ttf";

$img = imageCreate($width, $height); //Create an image.
$bg_color = imageColorAllocate(
  $img, (int) $background->get_rgb('r'),
  (int) $background->get_rgb('g'), (int) $background->get_rgb('b')
);
$fg_color = imageColorAllocate(
  $img, (int) $foreground->get_rgb('r'),
  (int) $foreground->get_rgb('g'), (int) $foreground->get_rgb('b')
);

if (!empty($_GET['text']))
{
  $_GET['text'] = preg_replace("#(0x[0-9A-F]{2})#e", "chr(hexdec('\\1'))", $_GET['text']);
  $lines = substr_count($_GET['text'], '|');
  $text = preg_replace('/\|/i', "\n", $_GET['text']);
}
else
{
  $lines = 1;
  $text = $width . " × " . $height;
}

$fontsize = max(min($width / strlen($text) * 1.15, $height * 0.5), 5);

$textBox = imagettfbbox_t($fontsize, $text_angle, $font, $text);
$textWidth = ceil(($textBox[4] - $textBox[1]) * 1.07);
$textHeight = ceil((abs($textBox[7]) + abs($textBox[1])) * 1);
$textX = ceil(($width - $textWidth) / 2);
$textY = ceil(($height - $textHeight) / 2 + $textHeight);

imageFilledRectangle($img, 0, 0, $width, $height, $bg_color);
imagettftext($img, $fontsize, $text_angle, $textX, $textY, $fg_color, $font, $text);

$offset = 60 * 60 * 24 * 14; //14 Days
$ExpStr = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
header($ExpStr);
header('Cache-Control:	max-age=120');
header("Last-Modified: " . gmdate("D, d M Y H:i:s", time() - $offset) . " GMT");
header('Content-type: image/' . $file_format);

//Create the final image based on the provided file format.
switch ($file_format) {
  case 'gif':
    imagegif($img);
    break;
  case 'png':
    imagepng($img);
    break;
  case 'jpg':
    imagejpeg($img);
    break;
  case 'jpeg':
    imagejpeg($img);
    break;
}

// Destroy the image to free memory.
imageDestroy($img);

/**
 * Ruquay K Calloway http://ruquay.com/sandbox/imagettf/ made a better function to
 * find the coordinates of the text bounding box so I used it.
 *
 * @param $size
 * @param $text_angle
 * @param $fontfile
 * @param $text
 *
 * @return array
 */
function imagettfbbox_t($size, $text_angle, $fontfile, $text)
{
  // compute size with a zero angle
  $coords = imagettfbbox($size, 0, $fontfile, $text);

  // convert angle to radians
  $a = deg2rad($text_angle);

  // compute some usefull values
  $ca = cos($a);
  $sa = sin($a);
  $ret = array();

  // perform transformations
  for ($i = 0; $i < 7; $i += 2)
  {
    $ret[$i] = round($coords[$i] * $ca + $coords[$i + 1] * $sa);
    $ret[$i + 1] = round($coords[$i + 1] * $ca - $coords[$i] * $sa);
  }

  return $ret;
}

/**
 * Class for converting colortypes
 *
 * The class includes the following colors formats and types:
 *
 *  - CMYK
 *  - RGB
 *  - Pantone (seperate include file: pantone.color.class.php)
 *  - HEX Codes for HTML
 *
 * @author    Sven Wagener <wagener_at_indot_dot_de>
 * @copyright Sven Wagener
 * @include    Funktion:_include_
 * @url       http://phpclasses.ehd.com.br/browse/package/804.html
 */
class color
{
  /**
   * @var  array $rgb
   * @access private
   * @desc  array for RGB colors
   */
  var $rgb = array(
    'r'=> 0,
    'g'=> 0,
    'b'=> 0
  );

  /**
   * @var  string $hex
   * @access private
   * @desc  variable for HTML HEX color
   */
  var $hex = '';

  /**
   * @var  array $cmyk
   * @access private
   * @desc  array for cmyk colors
   */
  var $cmyk = array(
    'c'=> 0,
    'm'=> 0,
    'y'=> 0,
    'b'=> 0
  );

  /**
   * Sets the RGB values
   * @param int $red number from 0-255 for blue color value
   * @param int $green number from 0-255 for green color value
   * @param int $blue number from 0-255 for blue color value
   * @access public
   * @desc Sets the RGB values
   */
  function set_rgb($red, $green, $blue)
  {

    $this->rgb['r'] = $red;
    $this->rgb['g'] = $green;
    $this->rgb['b'] = $blue;

    $this->convert_rgb_to_cmyk();
    $this->convert_rgb_to_hex();
  }

  /**
   * Sets the HEX HTML color value
   * @param string $hex 6,3,2, or 1 characters long.
   * @access public
   * @desc Sets the HEX HTML color value like ffff00. It will convert shorthand to a 6 digit hex.
   */
  function set_hex($hex)
  {
    //$hex = settype($hex, 'string');
    $hex = strtolower($hex);
    $hex = preg_replace('/#/', '', $hex); //Strips out the # character
    $hexlength = strlen($hex);
    $input = $hex;
    switch ($hexlength) {
      case 1:
        $hex = $input . $input . $input . $input . $input . $input;
        break;
      case 2:
        $hex = $input[0] . $input[1] . $input[0] . $input[1] . $input[0] . $input[1];
        break;
      case 3:
        $hex = $input[0] . $input[0] . $input[1] . $input[1] . $input[2] . $input[2];
        break;
    }
    $this->hex = $hex;

    $this->convert_hex_to_rgb();
    $this->convert_rgb_to_cmyk();
  }

  /**
   * Sets the HTML color name, converting it to a 6 digit hex code.
   * @param string $name The name of the color.
   * @access public
   * @desc Sets the HTML color name, converting it to a 6 digit hex code.
   */
  function set_name($name)
  {
    $this->hex = $this->convert_name_to_hex($name);

    $this->convert_hex_to_rgb();
    $this->convert_rgb_to_cmyk();
  }

  /**
   * Sets the CMYK color values
   * @param int $c number from 0-100 for c color value
   * @param int $m number from 0-100 for m color value
   * @param int $y number from 0-100 for y color value
   * @param int $b number from 0-100 for b color value
   * @access public
   * @desc Sets the CMYK color values
   */
  function set_cmyk($c, $m, $y, $b)
  {
    $this->cmyk['c'] = $c;
    $this->cmyk['m'] = $m;
    $this->cmyk['y'] = $y;
    $this->cmyk['b'] = $b;

    $this->convert_cmyk_to_rgb();
    $this->convert_rgb_to_hex();
  }

  /**
   * Sets the pantone color value
   * @param string $pantone_name name of the pantone color
   * @access public
   * @desc Sets the pantone color value
   */
  function set_pantone($pantone_name)
  {
    $this->pantone = $pantone_name;
    $this->cmyk['c'] = $this->pantone_pallete[$pantone_name]['c'];
    $this->cmyk['m'] = $this->pantone_pallete[$pantone_name]['m'];
    $this->cmyk['y'] = $this->pantone_pallete[$pantone_name]['y'];
    $this->cmyk['b'] = $this->pantone_pallete[$pantone_name]['b'];

    $this->convert_cmyk_to_rgb();
    $this->convert_rgb_to_hex();
  }

  /**
   * Sets the pantone pc color value
   * @param $pantone_name
   * @access public
   * @desc Sets the pantone pc color value
   */
  function set_pantone_pc($pantone_name)
  {
    $this->pantone_pc = $pantone_name;
    $this->cmyk['c'] = $this->pantone_pallete_pc[$pantone_name]['c'];
    $this->cmyk['m'] = $this->pantone_pallete_pc[$pantone_name]['m'];
    $this->cmyk['y'] = $this->pantone_pallete_pc[$pantone_name]['y'];
    $this->cmyk['b'] = $this->pantone_pallete_pc[$pantone_name]['b'];

    $this->convert_cmyk_to_rgb();
    $this->convert_rgb_to_hex();
  }

  //include("pantone.color.class.php");

  /**
   * Returns the RGB values of a set color
   * @param $val
   * @return array $rgb color values of red ($rgb['r']), green ($rgb['green') and blue ($rgb['b'])
   * @access public
   * @desc Returns the RGB values of a set color
   */
  function get_rgb($val)
  {
    if ($val) {
      return $this->rgb[$val];
    } else {
      return $this->rgb;
    }
  }

  /**
   * Returns the HEX HTML color value of a set color
   * @return string $hex HEX HTML color value
   * @access public
   * @desc Returns the HEX HTML color value of a set color
   */
  function get_hex()
  {
    return $this->hex;
  }

  /**
   * Returns the CMYK values of a set color
   * @return array $cmyk color values of c ($cmyk['c']), m ($cmyk['m'), y ($cmyk['blue']) and b ($cmyk['b'])
   * @access public
   * @desc Returns the CMYK values of a set color
   */
  function get_cmyk()
  {
    return $this->cmyk;
  }

  /**
   * Converts the RGB colors to HEX HTML colors
   * @access private
   * @desc Converts the RGB colors to HEX HTML colors
   */
  function convert_rgb_to_hex()
  {
    $this->hex = $this->hex_trip[$this->rgb['r']] . $this->hex_trip[$this->rgb['g']] . $this->hex_trip[$this->rgb['b']];
  }

  /**
   * Converts the RGB colors to CMYK colors
   * @access private
   * @desc Converts the RGB colors to CMYK colors
   */
  function convert_rgb_to_cmyk()
  {
    $c = (255 - $this->rgb['r']) / 255.0 * 100;
    $m = (255 - $this->rgb['g']) / 255.0 * 100;
    $y = (255 - $this->rgb['b']) / 255.0 * 100;

    $b = min(array($c, $m, $y));
    $c = $c - $b;
    $m = $m - $b;
    $y = $y - $b;

    $this->cmyk = array('c' => $c,
                        'm' => $m,
                        'y' => $y,
                        'b' => $b);
  }

  /**
   * Converts the CMYK colors to RGB colors
   * @access private
   * @desc Converts the CMYK colors to RGB colors
   */
  function convert_cmyk_to_rgb()
  {
    $red = $this->cmyk['c'] + $this->cmyk['b'];
    $green = $this->cmyk['m'] + $this->cmyk['b'];
    $blue = $this->cmyk['y'] + $this->cmyk['b'];

    $red = ($red - 100) * (-1);
    $green = ($green - 100) * (-1);
    $blue = ($blue - 100) * (-1);

    $red = round($red / 100 * 255, 0);
    $green = round($green / 100 * 255, 0);
    $blue = round($blue / 100 * 255, 0);

    $this->rgb['r'] = $red;
    $this->rgb['g'] = $green;
    $this->rgb['b'] = $blue;
  }

  /**
   * Converts the HTML HEX colors to RGB colors
   * @access private
   * @desc Converts the HTML HEX colors to RGB colors
   * @url http://css-tricks.com/snippets/php/convert-hex-to-rgb/
   */
  function convert_hex_to_rgb()
  {
    $red = substr($this->hex, 0, 2);
    $green = substr($this->hex, 2, 2);
    $blue = substr($this->hex, 4, 2);
    $this->rgb['r'] = hexdec($red);
    $this->rgb['g'] = hexdec($green);
    $this->rgb['b'] = hexdec($blue);
  }

  /**
   * Converts HTML color name to 6 digit HEX value.
   * @access private
   * @param string $name One of the offical HTML color names.
   * @return null
   * @desc Converts HTML color name to 6 digit HEX value.
   * @url http://en.wikipedia.org/wiki/HTML_color_names
   */
  function convert_name_to_hex($name)
  {
    $color_names = array(
      'aqua'    => '00ffff',
      'cyan'    => '00ffff',
      'gray'    => '808080',
      'grey'    => '808080',
      'navy'    => '000080',
      'silver'  => 'C0C0C0',
      'black'   => '000000',
      'green'   => '008000',
      'olive'   => '808000',
      'teal'    => '008080',
      'blue'    => '0000FF',
      'lime'    => '00FF00',
      'purple'  => '800080',
      'white'   => 'ffffff',
      'fuchsia' => 'FF00FF',
      'magenta' => 'FF00FF',
      'maroon'  => '800000',
      'red'     => 'FF0000',
      'yellow'  => 'FFFF00'
    );
    if (array_key_exists($name, $color_names)) {
      return $color_names[$name];
    }
    else {
      //error
    }

    return null;
  }
}
