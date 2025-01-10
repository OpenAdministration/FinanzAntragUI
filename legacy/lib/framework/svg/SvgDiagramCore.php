<?php

namespace framework\svg;

/**
 * super class for diagram generation
 *
 * @author 		Michael Gnehr <michael@gnehr.de>
 *
 * @category    framework
 *
 * @since 		09.08.2016
 *
 * @version 	02.0.0 since 01.07.2018
 *
 * @copyright 	Copyright (C) 2016-Today - All rights reserved - do not copy or reditribute
 */
abstract class SvgDiagramCore
{
    /* ------ PROTECTED MEMBERS ------ */
    /**
     * given dataset
     *
     * @var array
     */
    protected $dataset = [];

    /**
     * diagram type
     *
     * @var string
     */
    protected $type;

    /**
     * diagram settings
     *
     * @var array
     */
    protected $settings = [];

    /**
     * if true scaled image will keep given radio
     * use setter to update
     *
     * @var string
     */
    protected $serverAspectRadio;

    /**
     * list of different colors
     *
     * @var array
     */
    protected $colorMap;

    /**
     * implements translator interface
     * used to translate some svg texts
     * provides function translate();
     *
     * @var Translator
     */
    protected $translator = null;

    /**
     * svg result string
     *
     * @var string
     */
    protected $result;

    /**
     * add extra elemnts to result on generation
     *
     * @var string
     */
    protected $resultAdditional = [];

    // CLASS CONSTRUCTOR --------------------------------------

    /**
     * constructor
     */
    public function __construct()
    {
        // init variables
        $this->colorMap = ['red', 'blue', 'green',
            'yellow', 'purple', 'cyan',
            '#ef888d', '#d2a7e5', '#e5cd87',
            '#c639f9', '#e5c67e', '#2bc6bf',
            '#9ef7df', '#f2d42e', '#e5c97b',
            '#e2ae53', '#d1a429', '#d35d86',
            '#caf963', '#de54f9', '#aae06d',
            '#db76f2', '#ff0c51', '#b6f7a3',
            '#ea7598', '#09627c', '#2547dd',
            '#99bedb', '#b73331', '#aaffbd',
            '#ce0a04', '#dab0fc', '#e8d140',
            '#b1ef77', '#506cc9', '#ed07ca',
        ];
        $this->setServerAspectRadio(false);
        $this->settings = [
            'padding' => 15,
            'height' => 480,
            'width' => 640,
            'fontsize' => 10,
        ];
    }

    /**
     * renders svg and set result $this->result
     */
    abstract protected function render();

    // GETTER / SETTER ----------------------------------------

    /**
     * set Translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * set svg result
     *
     * @param  string  $svg  svg text or elements
     * @param  bool  $capsule  add svg sags and hoverscripts
     */
    protected function setSvgResult($svg, $capsule = false)
    {
        $this->result = (($capsule) ? $this->capsuleSvg($svg, true) : $svg);
    }

    /**
     * set Settings variables
     *
     * @param  string|number  $key  : 'padding'|'height'|'width'|'fontsize'
     * @param  mixed  $value
     */
    public function setSetting($key, $value)
    {
        $key = strtolower($key);
        if (array_key_exists($key, $this->settings) && $key) {
            $this->settings[$key] = $value;
        }
    }

    /**
     * if true scaling image will keep radio
     *
     * @param  bool  $boolean
     */
    public function setServerAspectRadio($boolean)
    {
        if (is_bool($boolean)) {
            if ($boolean) {
                $this->serverAspectRadio = '';
            } else {
                $this->serverAspectRadio = ' preserveAspectRatio="none"';
            }
        }
    }

    /**
     * override diagramm colors
     *
     * @param  mixed  $set  form: array('red', 'blue', '#ffff00',...);
     */
    public function overrideColorArray($set)
    {
        $i = 0;
        foreach ($set as $value) {
            $this->colorMap[$i] = $value;
            $i++;
        }
    }

    /* ------ DATA SETTER ------ */

    /**
     * set whole data array
     *
     * @param  mixed  $dataset  array of arrays
     */
    public function setData($dataset)
    {
        if (is_array($dataset)) {
            foreach ($dataset as $key => $data) {
                $this->appendData($key, $data);
            }
        }
    }

    /**
     * Append data to Dataset
     *
     * @param  string|number  $key  xAchsis description on Block and Line Diagramms, explanation on Pie charts
     * @param  mixerd  $set  valuesetfor multiple bars or lines presend multiple values on same position
     */
    public function appendData($key, $set)
    {
        if ((is_string($key) || is_numeric($key)) && is_array($set)) {
            $this->dataset[$key] = $set;
        }
    }

    /**
     * clear data array
     */
    public function clearData()
    {
        $this->dataset = [];
    }

    /* ------ RESULT ADDONS ------ */

    /**
     * clear additional result array
     */
    public function clearResultAddons()
    {
        $this->resultAdditional = [];
    }

    /**
     * add additional result array
     *
     * @param  string  $content
     */
    public function addResultAddons($content)
    {
        $this->resultAdditional[] = $content;
    }

    /**
     * get additional result array
     *
     * @return array the $resultAdditional
     */
    public function getResultAddons()
    {
        return $this->resultAdditional;
    }

    /* ------ HELPER FUNCTIONS ------ */
    /**
     * helper-function:
     * converts timestring in minutes (integer)
     *
     * @param  string  $time  format: H:i | H:i:s
     */
    protected function timeToMinutes($time)
    {
        $arr = explode(':', $time);

        return 60 * $arr[0] + $arr[1];
    }

    /**
     * helper-function:
     * converts minutes to timeString
     *
     * @param  int  $time  Minutes
     * @param  string  $format
     * @return void|string
     */
    protected function convertToHoursMins($time, $format = '%02d:%02d')
    {
        if ($time < 1) {
            return;
        }
        $hours = floor($time / 60);
        $minutes = ($time % 60);

        return sprintf($format, $hours, $minutes);
    }

    /**
     * creates 4 entry array from string or smaller arrays, like css does on padding or margin
     *
     * @param  string  $in
     * @return array
     */
    protected function toCssFourValue($in)
    {
        $out = [
            0,
            0,
            0,
            0,
        ];
        if (! is_array($in)) {
            $out = [$in, $in, $in, $in];
        } else {
            switch (count($in)) {
                case 1:
                    $out = [$in[0], $in[0], $in[0], $in[0]];
                    break;
                case 2:
                    $out = [$in[0], $in[1], $in[0], $in[1]];
                    break;
                case 3:
                    $out = [$in[0], $in[1], $in[2], $in[1]];
                    break;
                case 4:
                    $out = $in;
                    break;
            }
        }

        return $out;
    }

    /* ------ DRAW FUNCTIONS ------ */

    /**
     * generates SVG Element: Text
     *
     * @param  string  $str  Text to display
     * @param  number  $x  xPosition (center if NULL)
     * @param  number  $y  yPosition (center if NULL)
     * @param  string  $anchor  start|middle|end ('middle' if NULL)
     * @param  string  $color
     * @param  string  $weight  NULL|bold|normal
     * @param  number  $size  Fontsize
     * @param  number  $rotate  rotate Text to degree of
     * @param  array|null  $attr  additional attributes
     * @return string
     */
    protected function drawText($str, $x = null, $y = null, $anchor = null, $color = null, $weight = null, $size = null, $rotate = null, $family = null, $attr = null)
    {
        $anch = 'middle';
        $xx = $this->settings['width'] / 2;
        $yy = $this->settings['height'] / 2;
        $stroke = 'black';
        $family = 'Helvetica, Arial, sans-serif';
        $wei = '';
        $siz = 30;
        $at = '';
        if ($x !== null) {
            $xx = $x;
        }
        if ($y !== null) {
            $yy = $y;
        }
        if ($anchor !== null) {
            $anch = $anchor;
        }
        if ($color !== null) {
            $stroke = $color;
        }
        if ($size !== null) {
            $siz = $size;
        }
        if ($weight !== null) {
            $wei = ' font-weight="'.$weight.'"';
        }
        if ($attr !== null) {
            foreach ($attr as $k => $v) {
                $at .= " $k=\"$v\"";
            }
        }

        return '<text'.$wei.' xml:space="preserve" text-anchor="'.$anch.'" font-family="'.$family.'" font-size="'.
            $siz.'" y="'.$yy.'" x="'.$xx.'" stroke-width="0" stroke="'.$stroke.'" fill="'.$stroke.'"'.
            (($rotate === null) ? '' : (' transform="rotate('.$rotate.','.$xx.','.$yy.')"')).$at.'>'.$str.'</text>';
    }

    /**
     * generates SVG Element: Horizontal Line
     *
     * @param  number  $y  yPosition (center if NULL)
     * @param  number  $x  xPosition (center if NULL)
     * @param  int  $length  line-length, if NULL Line starts at padding end ends with padding
     * @param  string  $title  some Browsers show titles as tooltip, can be NULL
     * @param  int  $strokeWidth
     * @param  string  $color
     * @param  int  $padding  set padding, if NULL settigns padding is used
     * @return string
     */
    protected function drawHLine($y, $x = null, $length = null, $title = null, $strokeWidth = 1, $color = '#000000', $padding = null)
    {
        $x1 = 0;
        $x2 = 0;
        $y1 = 0;
        $y2 = 0;
        if ($y == null) {
            return '';
        } else {
            $y1 = $y;
            $y2 = $y;
        }
        if ($length === null) {
            $x1 = (($padding !== null) ? $padding : $this->settings['padding']);
            $x2 = $this->settings['width'] - (($padding !== null) ? $padding : $this->settings['padding']);
        } else {
            if ($x === null) {
                return '';
            } else {
                $x1 = $x;
                $x2 = $length + $x1;
            }
        }

        return $this->drawLine($x1, $y1, $x2, $y2, $strokeWidth, $color, $title);
    }

    /**
     * generates SVG Element: Vertical Line
     *
     * @param  number  $x  xPosition (center if NULL)
     * @param  number  $y  yPosition (center if NULL)
     * @param  int  $length  line-length, if NULL Line starts at padding end ends with padding
     * @param  string  $title  some Browsers show titles as tooltip, can be NULL
     * @param  int  $strokeWidth
     * @param  string  $color
     * @param  int  $padding  set padding, if NULL settigns padding is used
     * @return string
     */
    protected function drawVLine($x, $y = null, $length = null, $title = null, $strokeWidth = 1, $color = '#000000', $padding = null)
    {
        $x1 = 0;
        $x2 = 0;
        $y1 = 0;
        $y2 = 0;
        if ($x == null) {
            return '';
        } else {
            $x1 = $x;
            $x2 = $x;
        }
        if ($length === null) {
            $y1 = (($padding !== null) ? $padding : $this->settings['padding']);
            $y2 = $this->settings['height'] - (($padding !== null) ? $padding : $this->settings['padding']);
        } else {
            if ($y === null) {
                return '';
            } else {
                $y1 = $y;
                $y2 = $length + $y1;
            }
        }

        return $this->drawLine($x1, $y1, $x2, $y2, $strokeWidth, $color, $title);
    }

    /**
     * generates SVG Element: Line
     *
     * @param  number  $x1
     * @param  number  $y1
     * @param  number  $x2
     * @param  number  $y2
     * @param  number  $strokeWidth
     * @param  string  $color
     * @param  string  $title  some Browsers show titles as tooltip, can be NULL
     * @return string
     */
    protected function drawLine($x1, $y1, $x2, $y2, $strokeWidth, $color, $title = null)
    {
        $tit = '';
        if ($title != null) {
            $tit = '<title>'.$title.'</title>';
        }

        return '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="'.$color.'" stroke-width="'.$strokeWidth.'">'.$tit.'</line>';
    }

    /**
     * generates SVG Element: Manhatten Line
     *
     * @param  number  $x1  start position
     * @param  number  $y1
     * @param  number  $x2  end position
     * @param  number  $y2
     * @param  number  $r  radius
     * @param  bool  $direction:  0 -> first horizon line ; 1 -> vertival line first
     * @param  number  $strokeWidth
     * @param  array|null  $arrayStart
     * @param  array|null  $arrayEnd
     * @param  string  $fill  color
     * @param  string  $stroke  color
     * @param  string  $title  some Browsers show titles as tooltip, can be NULL
     * @return string
     */
    protected function drawManhattenLine($x1, $y1, $x2, $y2, $r, $direction = 0, $strokeWidth = 1, $arrowStart = null, $arrowEnd = null, $fill = 'none', $stroke = 'black', $title = null)
    {
        $p = "M $x1,$y1 ";
        $size = [];
        $size['ash'] = (is_array($arrowStart) && isset($arrowStart['h'])) ? $arrowStart['h'] : 6;
        $size['asa'] = (is_array($arrowStart) && isset($arrowStart['a'])) ? $arrowStart['a'] : 5;
        $size['asf'] = (is_array($arrowStart) && isset($arrowStart['fill'])) ? $arrowStart['fill'] : 'black';
        $size['ass'] = (is_array($arrowStart) && isset($arrowStart['stroke'])) ? $arrowStart['stroke'] : $stroke;
        $size['asw'] = (is_array($arrowStart) && isset($arrowStart['stroke-width'])) ? $arrowStart['stroke-width'] : $strokeWidth;
        $size['aeh'] = (is_array($arrowEnd) && isset($arrowEnd['h'])) ? $arrowEnd['h'] : 6;
        $size['aea'] = (is_array($arrowEnd) && isset($arrowEnd['a'])) ? $arrowEnd['a'] : 5;
        $size['aef'] = (is_array($arrowEnd) && isset($arrowEnd['fill'])) ? $arrowEnd['fill'] : 'black';
        $size['aes'] = (is_array($arrowEnd) && isset($arrowEnd['stroke'])) ? $arrowEnd['stroke'] : $stroke;
        $size['aew'] = (is_array($arrowEnd) && isset($arrowEnd['stroke-width'])) ? $arrowEnd['stroke-width'] : $strokeWidth;

        // h line first if direction == 0
        $p .= (! $direction) ? 'H '.(($x2) + (($x2 > $x1) ? -abs($r) : abs($r))).' ' : 'V '.(($y2) + (($y2 > $y1) ? -abs($r) : abs($r))).' ';
        $a = '';
        if ($direction) {
            if ($x1 < $x2 && $y1 < $y2) {
                if ($r) {
                    $p .= 'a '.abs($r).' '.abs($r).' 0 0 0 '.abs($r).' '.abs($r).' ';
                }
                if ($arrowStart) {
                    $a .= $this->drawTriangle($x1, $y1, $size['ash'], $size['asa'], -90, $size['asf'], $size['ass'], $size['asw']);
                }
                if ($arrowEnd) {
                    $a .= $this->drawTriangle($x2, $y2, $size['aeh'], $size['aea'], 0, $size['aef'], $size['aes'], $size['aew']);
                }
            } elseif ($x1 < $x2 && $y1 >= $y2) {
                if ($r) {
                    $p .= 'a '.abs($r).' '.abs($r).' 0 0 1 '.abs($r).' '.-abs($r).' ';
                }
                if ($arrowStart) {
                    $a .= $this->drawTriangle($x1, $y1, $size['ash'], $size['asa'], 90, $size['asf'], $size['ass'], $size['asw']);
                }
                if ($arrowEnd) {
                    $a .= $this->drawTriangle($x2, $y2, $size['aeh'], $size['aea'], 0, $size['aef'], $size['aes'], $size['aew']);
                }
            } elseif ($x1 >= $x2 && $y1 >= $y2) {
                if ($r) {
                    $p .= 'a '.abs($r).' '.abs($r).' 0 0 0 '.-abs($r).' '.-abs($r).' ';
                }
                if ($arrowStart) {
                    $a .= $this->drawTriangle($x1, $y1, $size['ash'], $size['asa'], 90, $size['asf'], $size['ass'], $size['asw']);
                }
                if ($arrowEnd) {
                    $a .= $this->drawTriangle($x2, $y2, -$size['aeh'], $size['aea'], 0, $size['aef'], $size['aes'], $size['aew']);
                }
            } elseif ($x1 >= $x2 && $y1 < $y2) {
                if ($r) {
                    $p .= 'a '.abs($r).' '.abs($r).' 0 0 1 '.-abs($r).' '.abs($r).' ';
                }
                if ($arrowStart) {
                    $a .= $this->drawTriangle($x1, $y1, $size['ash'], $size['asa'], -90, $size['asf'], $size['ass'], $size['asw']);
                }
                if ($arrowEnd) {
                    $a .= $this->drawTriangle($x2, $y2, -$size['aeh'], $size['aea'], 0, $size['aef'], $size['aes'], $size['aew']);
                }
            }
        } else {
            if ($x1 < $x2 && $y1 < $y2) {
                if ($r) {
                    $p .= 'a '.abs($r).' '.abs($r).' 0 0 1 '.abs($r).' '.abs($r).' ';
                }
                if ($arrowStart) {
                    $a .= $this->drawTriangle($x1, $y1, -$size['ash'], $size['asa'], 0, $size['asf'], $size['ass'], $size['asw']);
                }
                if ($arrowEnd) {
                    $a .= $this->drawTriangle($x2, $y2, $size['aeh'], $size['aea'], 90, $size['aef'], $size['aes'], $size['aew']);
                }
            } elseif ($x1 < $x2 && $y1 >= $y2) {
                if ($r) {
                    $p .= 'a '.abs($r).' '.abs($r).' 0 0 0 '.abs($r).' '.-abs($r).' ';
                }
                if ($arrowStart) {
                    $a .= $this->drawTriangle($x1, $y1, -$size['ash'], $size['asa'], 0, $size['asf'], $size['ass'], $size['asw']);
                }
                if ($arrowEnd) {
                    $a .= $this->drawTriangle($x2, $y2, $size['aeh'], $size['aea'], -90, $size['aef'], $size['aes'], $size['aew']);
                }
            } elseif ($x1 >= $x2 && $y1 >= $y2) {
                if ($r) {
                    $p .= 'a '.abs($r).' '.abs($r).' 0 0 1 '.-abs($r).' '.-abs($r).' ';
                }
                if ($arrowStart) {
                    $a .= $this->drawTriangle($x1, $y1, $size['ash'], $size['asa'], 0, $size['asf'], $size['ass'], $size['asw']);
                }
                if ($arrowEnd) {
                    $a .= $this->drawTriangle($x2, $y2, $size['aeh'], $size['aea'], -90, $size['aef'], $size['aes'], $size['aew']);
                }
            } elseif ($x1 >= $x2 && $y1 < $y2) {
                if ($r) {
                    $p .= 'a '.abs($r).' '.abs($r).' 0 0 0 '.-abs($r).' '.abs($r).' ';
                }
                if ($arrowStart) {
                    $a .= $this->drawTriangle($x1, $y1, $size['ash'], $size['asa'], 0, $size['asf'], $size['ass'], $size['asw']);
                }
                if ($arrowEnd) {
                    $a .= $this->drawTriangle($x2, $y2, $size['aeh'], $size['aea'], 90, $size['aef'], $size['aes'], $size['aew']);
                }
            }
        }
        $p .= (! $direction) ? "M $x2,".($y1 + (($y2 > $y1) ? abs($r) : -abs($r))).' ' : 'M '.(($x1) + (($x2 > $x1) ? abs($r) : -abs($r))).",$y2 ";
        // v line second if direction == 0
        $p .= "L $x2,$y2";
        $p = $this->drawPath($p, $title, [], $fill, $stroke, $strokeWidth);

        return $p.$a;
    }

    /**
     * generates SVG Element: Line
     *
     * @param  number  $x1
     * @param  number  $y1
     * @param  number  $x2
     * @param  number  $y2
     * @param  number  $strokeWidth
     * @param  string  $color
     * @param  string  $title  some Browsers show titles as tooltip, can be NULL
     * @param  bool  $direction  vertical 1 | hoizontal 0
     * @return string
     */
    protected function drawAutoBez($x1, $y1, $x2, $y2, $strokeWidth, $color, $title = null, $direction = 0)
    {
        $tit = '';
        if ($title != null) {
            $tit = '<title>'.$title.'</title>';
        }

        // center between points
        $xC = ($x1 + $x2) / 2;
        $yC = ($y1 + $y2) / 2;
        // direction: vertical 1 | hoizontal 0
        if ($direction) {
            $yB = ($yC + $y1) / 2;
            for ($i = 0; $i < 2; $i++) {
                $yB = ($yB + $yC) / 2;
            }
            $pX = $x1;
            $pY = $yB;
        } else {
            $xB = ($xC + $x1) / 2;
            for ($i = 0; $i < 2; $i++) {
                $xB = ($xB + $xC) / 2;
            }
            $pX = $xB;
            $pY = $y1;
        }

        return '<path d="M '."$x1,$y1 Q $pX,$pY $xC,$yC T $x2,$y2".' " fill="none" stroke="'.$color.'" stroke-width="'.$strokeWidth.'">'.$tit.'</path>';
    }

    /**
     * generates SVG Element: rect with rounded corners and text
     *
     * @param  number  $x  position
     * @param  number  $y  position
     * @param  number  $width
     * @param  number  $height
     * @param  number|array  $r  radius ; array: first index is top right - clockwise direction
     * @param  string|array  $text  as string or array with drawText values
     * @param  array  $options  ['stroke' => 'black', 'fill' => 'white']
     * @param  number  $id  set tag id
     * @return string
     */
    protected function drawShape($x, $y, $width, $height, $r, $text = '', $text_offset = 0, $options = ['stroke' => 'black', 'fill' => 'white'], $id = null, $title = null)
    {
        $tit = '';
        if ($title != null) {
            $tit = '<title>'.$title.'</title>';
        }
        // prepare
        $r = $this->toCssFourValue($r);
        $spc = ' '; // readable names for path drawing instruction
        $moveTo = 'M';
        $horizLineTo = 'h';
        $vertLineTo = 'v';
        $arcTo = 'a';
        $closePath = 'z';
        // shape out
        $shape = ''.// path data
            $moveTo.$spc.($x + abs($r[3])).$spc.$y.$spc.
            $horizLineTo.$spc.($width - abs($r[0]) - abs($r[3])).$spc.
            (($r[0]) ? $arcTo.$spc.abs($r[0]).$spc.abs($r[0]).$spc. 0 .$spc. 0 .$spc.(($r[0] > 0) ? 1 : 0).$spc.abs($r[0]).$spc.abs($r[0]).$spc : '').
            $vertLineTo.$spc.($height - abs($r[0]) - abs($r[1])).$spc.
            (($r[1]) ? $arcTo.$spc.abs($r[1]).$spc.abs($r[1]).$spc. 0 .$spc. 0 .$spc.(($r[1] > 0) ? 1 : 0).$spc.-abs($r[1]).$spc.abs($r[1]).$spc : '').
            $horizLineTo.$spc.(-$width + abs($r[1]) + abs($r[2])).$spc.
            (($r[2]) ? $arcTo.$spc.abs($r[2]).$spc.abs($r[2]).$spc. 0 .$spc. 0 .$spc.(($r[2] > 0) ? 1 : 0).$spc.-abs($r[2]).$spc.-abs($r[2]).$spc : '').
            $vertLineTo.$spc.(-$height + abs($r[2]) + abs($r[3])).$spc.
            (($r[3]) ? $arcTo.$spc.abs($r[3]).$spc.abs($r[3]).$spc. 0 .$spc. 0 .$spc.(($r[3] > 0) ? 1 : 0).$spc.abs($r[3]).$spc.-abs($r[3]).$spc : '').
            $closePath;
        $class = 'shape-body';
        $onclick = '';
        if (isset($options['cursor-pointer']) && $options['cursor-pointer']) {
            $class .= (($class != '') ? ' ' : '').'cursor-pointer';
        }
        if (isset($options['onclick']) && $options['onclick']) {
            $onclick = ' onclick="'.$options['onclick'].'"';
        }
        $out = '<path'.$onclick.' '.(($id) ? 'id="'.$id.'"' : '').(($class) ? ' class="'.$class.'"' : '').' fill="'.$options['fill'].'" stroke="'.$options['stroke'].'" d="'.$shape.'" >'.$tit.'</path>';
        // text out
        if ($text) {
            $fsize = (is_string($text) || ! isset($text['size'])) ? $this->settings['fontsize'] : $text['size'];
            $out .= $this->drawText(
                is_string($text) ? $text : $text['text'],
                $x + $width / 2,
                $y + $height / 2 + ($fsize / 4) + $text_offset,
                (is_string($text) || ! isset($text['anchor'])) ? 'middle' : $text['anchor'],
                (is_string($text) || ! isset($text['color'])) ? 'black' : $text['color'],
                (is_string($text) || ! isset($text['weight'])) ? null : $text['weight'],
                $fsize,
                (is_string($text) || ! isset($text['rotate'])) ? null : $text['rotate'],
                null,
                (is_string($text) || ! isset($text['attr'])) ? null : $text['attr']
            );
        }

        return $out; // return it from the function
    }

    /**
     * draw path
     *
     * @param  string  $p  path
     * @param  string  $title
     * @param  array  $attr
     * @param  string  $fill
     * @param  string  $stroke
     * @param  string  $strokeWidth
     * @return string
     */
    protected function drawPath($p, $title = null, $attr = [], $fill = 'none', $stroke = 'black', $strokeWidth = '1')
    {
        $tit = '';
        if ($title != null) {
            $tit = '<title>'.$title.'</title>';
        }
        if (! isset($attr['fill']) && $fill) {
            $attr['fill'] = $fill;
        }
        if (! isset($attr['stroke']) && $stroke) {
            $attr['stroke'] = $stroke;
        }
        if (! isset($attr['stroke-width']) && $strokeWidth) {
            $attr['stroke-width'] = $strokeWidth;
        }
        $a = '';
        foreach ($attr as $k => $v) {
            $a .= ' ';
            $a .= $k.'="'.$v.'"';
        }

        return '<path d="'.$p.'"'.$a.'>'.$tit.'</path>';
    }

    /**
     * draw triangle
     *
     * @param  number  $x  position
     * @param  number  $y  position
     * @param  number  $h  height
     * @param  number  $a  width
     * @param  number  $rot  rotate
     * @param  string  $fill  fill color
     * @param  string  $stroke  stroke color
     * @param  string  $strokeWidth  stroke width
     * @param  string  $title
     * @return string
     */
    protected function drawTriangle($x, $y, $h, $a, $rot, $fill = 'none', $stroke = 'black', $strokeWidth = 1, $title = null)
    {
        $p = $this->drawPath(
            'M '."$x,$y L ".($x - $h).','.($y - $a / 2).' V '.($y + $a / 2).' Z',
            $title,
            [
                'fill' => $fill,
                'stroke' => $stroke,
                'stroke-width' => $strokeWidth,
            ]);
        if ($rot) {
            $p = '<g transform="rotate('."$rot $x $y".') ">'
                .$p.'</g>';
        }

        return $p;
    }

    /**
     * generates SVG Element: Rect
     *
     * @param  number  $lefttopX
     * @param  number  $lefttopY
     * @param  number  $width
     * @param  number  $height
     * @param  string  $colorFill
     * @param  string  $colorStroke
     * @param  number  $strokeWidth
     * @return string
     */
    protected function drawBar($lefttopX, $lefttopY, $width, $height, $colorFill = 'red', $colorStroke = 'transparent', $strokeWidth = 1, $title = null)
    {
        $tit = '';
        if ($title !== null) {
            $tit = '<title>'.$title.'</title>';
        }

        return '<rect x="'.$lefttopX.'" y="'.$lefttopY.'" height="'.$height.'" width="'.$width.'" style="fill:'.
            $colorFill.'; stroke-width:'.$strokeWidth.'; stroke: '.(($colorStroke === null) ? $colorFill : $colorStroke).';">'.$tit.'</rect>';
    }

    /**
     * generates SVG Element: Circle/Points
     *
     * @param  number  $X
     * @param  number  $Y
     * @param  number  $radius
     * @param  string  $colorFill
     * @param  string  $colorStroke
     * @param  number  $strokeWidth
     * @return string
     */
    protected function drawCircle($X, $Y, $radius, $colorFill = 'red', $colorStroke = 'transparent', $strokeWidth = 1, $title = null, $opacity = null)
    {
        $tit = '';
        if ($title !== null) {
            $tit = '<title>'.$title.'</title>';
        }

        return '<circle cx="'.$X.'" cy="'.$Y.'" r="'.$radius.'" style="fill:'.
            $colorFill.'; stroke-width:'.$strokeWidth.'; stroke: '.(($colorStroke === null) ? $colorFill : $colorStroke).';"'.(($opacity) ? ' opacity="'.$opacity.'"' : '').'>'.$tit.'</circle>';
    }

    /**
     * add svg tags with size attributes and hoverscripts (optional)
     *
     * @param  string  $svgElements  svg elements
     * @param  bool  $scripts  add scripts to svg
     * @param  bool  $addAddons  add additional svg content
     * @return string
     */
    protected function capsuleSvg($svgElements, $scripts = true, $addAddons = true)
    {
        $out = sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d"'.$this->serverAspectRadio.'>',
            $this->settings['width'],
            $this->settings['height']);
        if ($scripts) {
            $out .= $this->createHoverScripts();
        }
        if ($addAddons) {
            foreach ($this->resultAdditional as $a) {
                $out .= $a;
            }
        }

        return $out.$svgElements.'</svg>';
    }

    /* ------ JS INTERACTIVE GROUPING ------ */

    /**
     * surrounds elements with <g> tag and adds hover js
     *
     * @param  string  $element  svg elements
     * @param  number  $opacity  0.0 - 1.0
     * @param  string|null  $bg  background color
     * @return string
     */
    protected function suroundElementWithMouseHilight($element, $opacity = 0.5, $bg = null)
    {
        $return = '<g xmlns="http://www.w3.org/2000/svg" onmousemove="setOpacityColor(evt,'."'$opacity'".',\''.($bg ? $bg : 'null').'\')" '.
            'onmouseout="setOpacityColor(evt,'."'1.0'".',\''.($bg ? 'reset' : 'null').'\')" fill-opacity="1.0">'.
            $element.
            '</g>';

        return $return;
    }

    /**
     * returns svg <defs> element with hover js
     *
     * @return string
     */
    protected function createHoverScripts()
    {
        $ret = <<<'HEREDOC'
<defs>
    <script type="text/javascript">
        function setOpacityColor(ev,opa,color)
        {
			var color_target = ev.target;
			var opactity_target = ev.target.parentNode;
			if (color_target.className.baseVal.indexOf('shape-text')!==-1){
				opactity_target = ev.target.previousSibling;
				color_target = color_target.previousSibling;
			} else if (color_target.className.baseVal.indexOf('shape-body')!==-1) {
				opactity_target = ev.target;
			}
	        switch(color){
				case 'null': break;
				case 'reset': {
					color_target.setAttribute("fill",color_target.dataset.color);
				} break;
				default: {
					if (typeof(color_target.dataset.color)=='undefined'){
						if (color_target.getAttribute("fill")){
							color_target.dataset.color = color_target.getAttribute("fill");
						} else {
							color_target.dataset.color = 'none';
						}
					}
					color_target.setAttribute("fill",color);
				} break;
			}
			
			if (parseFloat(opa) <= 1.0 || opa == '1.0') {
				opactity_target.setAttribute("fill-opacity",opa);
				//reset darken class
				if (opactity_target.className.baseVal.indexOf('darken-hover')!==-1){
					var old_class = ''+ opactity_target.className.baseVal;
					old_class = old_class.replace('darken-hover', '');
					opactity_target.className.baseVal = old_class;
				}
			} else {
				//may add darken class
				if (opactity_target.className.baseVal.indexOf('darken-hover')===-1){
					var old_class = ''+opactity_target.className.baseVal;
					opactity_target.className.baseVal = old_class + ((old_class!='')?' ':'') + 'darken-hover';
				}
			}
        }
       	function triggerEvent(name, detail){
        	var event = new CustomEvent('svg-trigger-'+name, { 'detail': detail });
        	document.dispatchEvent(event);
        	console.log('dispatch event...');
        	console.log(name);
        	console.log(detail);
    	}
    </script>
</defs>
<style>
    .cursor-pointer { cursor: pointer; }
	.shape-text { cursor: pointer; }
	.darken-hover {
		-webkit-filter: brightness(70%);
	    -webkit-transition: all .3s ease;
	    -moz-transition: all .3s ease;
	    -o-transition: all .3s ease;
	    -ms-transition: all .3s ease;
	    transition: all .3s ease;
	}
</style>
HEREDOC;

        return $ret;
    }

    /* ------ OUT FUNCTIONS ------ */

    /**
     * generates chart from data and settings
     * set all propperties and data before this call
     */
    public function generate()
    {
        $this->render();
    }

    /**
     * returns generated Chart as string
     * if called before generate String will be empty
     *
     * @return string
     */
    public function getChart()
    {
        return $this->result;
    }

    /**
     * returns generated Chart as Base64 encoded string
     * if called before generate String will be empty
     *
     * @return string
     */
    public function getChartBase64()
    {
        return base64_encode($this->result);
    }

    /**
     * returns generated Chart as PNG string
     * if called before generate String will be empty
     *
     * @return string
     */
    public function getPNGChart()
    {
        if (class_exists('\Imagick')) {
            $image = new \Imagick;
            $image->readImageBlob('<?xml version="1.0"?>'.$this->result);
            $image->setImageFormat('png24');

            // $image->resizeImage(1024, 768, \imagick::FILTER_LANCZOS, 1); //need resize?
            return $image;
        } else {
            $add = (extension_loaded('gd')) ? " Sadly GD don't support svg converting." : '';
            throw new \Exception('Imagick is not installed on this server.'.$add);
        }
    }

    /**
     * returns generated Chart as JPEG string
     * if called before generate String will be empty
     *
     * @return string
     */
    public function getJPEGChart()
    {
        echo '<pre>';
        var_dump(class_exists('\Imagick'));
        echo '</pre>';
        if (class_exists('\Imagick')) {
            $image = new \Imagick;
            $image->readImageBlob('<?xml version="1.0"?>'.$this->result);
            $image->setImageFormat('jpeg');

            // $image->adaptiveResizeImage(1024, 768); //need resize?
            return $image;
        } else {
            $add = (extension_loaded('gd')) ? " Sadly GD don't support svg converting." : '';
            throw new \Exception('Imagick is not installed on this server.'.$add);
        }
    }
}
