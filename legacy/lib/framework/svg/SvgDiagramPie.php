<?php

namespace framework\svg;

/**
 * Pie Diagram Class
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
class SvgDiagramPie extends SvgDiagramCore
{
    /**
     * this class implements following diagram types
     *
     * @var array
     */
    private static $types = [
        'Pie',
    ];

    // CLASS CONSTRUCTOR --------------------------------------

    /**
     * constructor
     *
     * @param  string  $type
     */
    public function __construct($type)
    {
        if (in_array($type, self::$types, true)) {
            $this->type = $type;
        } else {
            $this->type = self::$types[0];
        }
        parent::__construct();
        $this->settings['PIE'] = [
            'perExplanationLineRight' => 1,
            'perExplanationLineBelow' => 4,
            'explanationLineHeight' => 30,
            'radiusOffset' => 0,
            'drawStroke' => true,
            'percentageOnExplanation' => true,
        ];
    }

    // TYPE SETTING  --------------------------------------

    /**
     * force pie diagram explanation to right side
     *
     * @var bool
     */
    protected $forceExplanationRight = false;

    /**
     * force pie diagram explanation to right side
     *
     * @param  bool  $boolean
     */
    protected $forceExplanationBottom = false;

    // TYPE SETTING / GETTER/SETTER -----------------------

    /**
     * set Pie Settings variables
     *
     * @param  string|number  $key  : 'perExplanationLineRight'|'perExplanationLineBelow'|'explanationLineHeight'|'radiusOffset'|'drawStroke'|'percentageOnExplanation'
     * @param  mixed  $value
     */
    public function setPieSetting($key, $value)
    {
        if (array_key_exists($key, $this->settings['PIE'])) {
            $this->settings['PIE'][$key] = $value;
        }
    }

    /**
     * force pie diagram explanation to right side
     *
     * @param  bool  $boolean
     */
    public function setForceCircleRight($boolean)
    {
        if (is_bool($boolean)) {
            if ($boolean) {
                $this->forceExplanationRight = true;
                $this->forceExplanationBottom = false;
            } else {
                $this->forceExplanationRight = false;
            }
        }
    }

    /**
     * force pie diagram explanation to the bottom
     *
     * @param  bool  $boolean
     */
    public function setForceCircleBottom($boolean)
    {
        if (is_bool($boolean)) {
            if ($boolean) {
                $this->forceExplanationBottom = true;
                $this->forceExplanationRight = false;
            } else {
                $this->forceExplanationBottom = false;
            }
        }
    }

    // TYPE IMPLEMENTATION --------------------------------------

    /**
     * generate pie chart from data
     *
     * @see SvgDiagramCore::render()
     */
    public function render(): void
    {
        $chartcontent = '';

        $entrySum = 0;
        foreach ($this->dataset as $set) {
            $entrySum += $set[0];
        }
        $xAchsisYPos = 0;
        $xAchsisLength = 0;
        $yAchsisLength = 0;
        $circleX = 0;
        $circleX_offset = 0;
        $circleY = 0;
        $radius = 0;
        if (($this->settings['height'] > $this->settings['width'] * 2 / 3 && $this->forceExplanationRight == false) || ($this->forceExplanationBottom == true)) { // explanation below diagramm
            $xAchsisYPos = $this->settings['height'] - $this->settings['padding'];
            if (count($this->dataset) > 0) {
                $xAchsisYPos = $xAchsisYPos - ceil(count($this->dataset) / $this->settings['PIE']['perExplanationLineBelow']) * $this->settings['PIE']['explanationLineHeight'];
            }
            $xAchsisYPos -= $this->settings['PIE']['explanationLineHeight'];
            $xAchsisLength = $this->settings['width'] - 2 * $this->settings['padding'];
            $yAchsisLength = $xAchsisYPos - $this->settings['padding'];
            $circleX = $xAchsisLength / 2 + $this->settings['padding'];
            $circleY = $yAchsisLength / 2 + $this->settings['padding'];
            $radius = min($xAchsisLength, $yAchsisLength) / 2 - $this->settings['PIE']['radiusOffset'];
        } else { // explanation to the right of diagramm
            $xAchsisYPos = $this->settings['height'] - $this->settings['padding'];
            $xAchsisLength = $this->settings['width'] - 2 * $this->settings['padding'];
            $yAchsisLength = $xAchsisYPos - $this->settings['padding'];
            $circleX = $xAchsisLength / 2 + $this->settings['padding'];
            $circleY = $yAchsisLength / 2 + $this->settings['padding'];
            $radius = min($xAchsisLength, $yAchsisLength) / 2 - $this->settings['PIE']['radiusOffset'];
            if ($circleX / $radius > 2) {
                $circleX_offset = $radius * ($circleX / $radius - 2);
            }
        }

        // draw explanation and chart parts
        $lastX1 = $circleX - $circleX_offset;
        $lastY1 = $circleY - $radius;
        $i = 0;
        $winkel = 0;
        $y = 0;
        $x_width = 0;
        if (($this->settings['height'] > $this->settings['width'] * 2 / 3 && $this->forceExplanationRight == false) || ($this->forceExplanationBottom == true)) { // explanation below diagramm
            $y = $xAchsisYPos + $this->settings['PIE']['explanationLineHeight'];
            $x_width = ($this->settings['width'] - 2 * $this->settings['padding']) / $this->settings['PIE']['perExplanationLineBelow'];
        } else {
            $y = $this->settings['padding'];
            $x_width = ($this->settings['width'] - ($circleX + $radius + $this->settings['PIE']['explanationLineHeight'])) / $this->settings['PIE']['perExplanationLineRight'];
        }
        foreach ($this->dataset as $leg => $set) {
            // pie chart
            $part = $set[0] / $entrySum;
            $alphaDeg = (360 * $set[0]) / $entrySum;
            if ($set[0] == $entrySum) {
                $alphaDeg = 359.9999;
            }
            $winkel += $alphaDeg;
            $alphaRad = ($winkel * pi()) / 180;
            $x2 = $circleX - $circleX_offset + $radius * sin($alphaRad);
            $y2 = $circleY - $radius * cos($alphaRad);
            $arcflag = ($alphaDeg <= 180) ? '0,1' : '1,1';
            $d = 'M'.($circleX - $circleX_offset).','.$circleY.' L'.$lastX1.','.$lastY1.' A'.$radius.','.$radius.
            ' 0 '.$arcflag.' '.$x2.','.$y2.' Z';
            $lastX1 = $x2;
            $lastY1 = $y2;
            $pathObj = '<path d="'.$d.'" fill="'.$this->colorMap[$i].'"'.(($this->settings['PIE']['drawStroke']) ? ' stroke="black" stroke-width="2"' : '').'>'.
                '<title>'.$set[0].'</title></path>';

            // daw explanation
            if (($this->settings['height'] > $this->settings['width'] * 2 / 3 && $this->forceExplanationRight == false) || ($this->forceExplanationBottom == true)) { // explanation below diagramm
                $yy = $y + ($this->settings['PIE']['explanationLineHeight'] * floor($i / $this->settings['PIE']['perExplanationLineBelow']));
                $xx = $this->settings['padding'] + ($i % $this->settings['PIE']['perExplanationLineBelow']) * $x_width;
                $pathObj .= $this->drawBar($xx, $yy + 5, $this->settings['PIE']['explanationLineHeight'], $this->settings['PIE']['explanationLineHeight'] - 10,
                    $this->colorMap[$i], 'black', 1, (($this->translator !== null) ? $this->translator->translate('explanation') : 'explanation'));
                $pathObj .= $this->drawText(
                    $leg.(($this->settings['PIE']['percentageOnExplanation']) ? ' ('.round((($set[0] * 100) / $entrySum), 2).'%)' : ''),
                    $xx + $this->settings['PIE']['explanationLineHeight'] + 7,
                    $yy - 7 + $this->settings['PIE']['explanationLineHeight'],
                    'start',
                    'black',
                    'bold',
                    20);
            } else {
                $yy = $y + ($this->settings['BLOCK']['explanationLineHeight'] * floor($i / $this->settings['PIE']['perExplanationLineRight']));
                $xx = ($circleX + $radius + $this->settings['PIE']['explanationLineHeight']) + ($i % $this->settings['PIE']['perExplanationLineRight']) * $x_width;
                $pathObj .= $this->drawBar($xx, $yy + 5, $this->settings['BLOCK']['explanationLineHeight'], $this->settings['PIE']['explanationLineHeight'] - 10,
                    $this->colorMap[$i], 'black', 1, (($this->translator !== null) ? $this->translator->translate('explanation') : 'explanation'));
                $pathObj .= $this->drawText(
                    $leg.(($this->settings['PIE']['percentageOnExplanation']) ? ' ('.round((($set[0] * 100) / $entrySum), 2).'%)' : ''),
                    $xx + $this->settings['PIE']['explanationLineHeight'] + 7,
                    $yy - 7 + $this->settings['PIE']['explanationLineHeight'],
                    'start',
                    'black',
                    'bold',
                    20);
            }

            // add to svg
            $chartcontent .= $this->suroundElementWithMouseHilight($pathObj);
            $i++;
        }
        $this->setSvgResult($chartcontent, true);
    }
}
