<?php

namespace framework\svg;

/**
 * Block (and Line) Diagram Class
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
class SvgDiagramBlock extends SvgDiagramCore
{
    /**
     * this class implements following diagram types
     *
     * @var array
     */
    private static $types = [
        'Block',
        'Line',
    ];

    // CLASS CONSTRUCTOR --------------------------------------

    /**
     * constructor
     *
     * @param  string  $type
     */
    public function __construct($type)
    {
        if (in_array($type, self::$types)) {
            $this->type = $type;
        } else {
            $this->type = self::$types[0];
        }
        parent::__construct();
        $this->settings['BLOCK'] = [
            'xFontOffset' => 30,
            'xAchsisDescLineLength' => 3,
            'yAchsisDescLineLength' => 6,
            'AchsisDescriptionHeight' => 30,
            'ySteps' => 5,
            'perExplanationLine' => 4,
            'explanationLineHeight' => 30,
            'yGrid' => true,
        ];
        $this->explanation = [];
        $this->achsisDescription = ['x' => null, 'y' => null];
    }

    // TYPE SETTING  --------------------------------------

    /**
     * sets Explanation array -> creates color fields with description
     *
     * @var array
     */
    protected $explanation = [];

    /**
     * achsis description
     *
     * @param  mixed  $set  form: array('x' => $Xvalue, 'y' => $Yvalue);
     */
    protected $achsisDescription;

    // TYPE SETTING / GETTER/SETTER -----------------------

    /**
     * set Block Settings variables
     *
     * @param  string|number  $key  : 'xFontOffset'|'xAchsisDescLineLength'|'yAchsisDescLineLength'
     *                              |'AchsisDescriptionHeight'|'ySteps'|'perExplanationLine'
     *                              |'explanationLineHeight'|'yGrid',
     * @param  mixed  $value
     */
    public function setBlockSetting($key, $value)
    {
        if (array_key_exists($key, $this->settings['BLOCK'])) {
            $this->settings['BLOCK'][$key] = $value;
        }
    }

    /**
     * sets Explanation array -> creates color fields with description
     *
     * @param  mixed  $set
     */
    public function setExplanation($set)
    {
        if (is_array($set)) {
            $this->explanation = $set;
        }
    }

    /**
     * set achsis description
     *
     * @param  mixed  $set  form: array('x' => $Xvalue, 'y' => $Yvalue);
     */
    public function setAchsisDescription($set)
    {
        if (is_array($set) && array_key_exists('x', $set) && array_key_exists('y', $set)) {
            $this->achsisDescription['x'] = $set['x'];
            $this->achsisDescription['y'] = $set['y'];
        }
    }

    // TYPE IMPLEMENTATION --------------------------------------

    /**
     * (non-PHPdoc)
     * generate block or line chart from data
     *
     * @see \intertopia\Classes\svg\SvgDiagramCore::render()
     */
    public function render()
    {
        $line = ($this->type === 'Line') ? true : null;
        $xFontOffset = $this->settings['BLOCK']['xFontOffset'];
        $xAchsisDescLineLength = $this->settings['BLOCK']['xAchsisDescLineLength'];
        $yAchsisDescLineLength = $this->settings['BLOCK']['yAchsisDescLineLength'];
        $yMaxEntryVal = 0;
        $chartcontent = '';

        $parts = count($this->dataset);
        $entryWidth = ((float) ($this->settings['width'] - 2 * $this->settings['padding'])) / ((float) ($parts + 1));
        $xAchsisYPos = $this->settings['height'] - $this->settings['padding'] - $xFontOffset;
        if ($this->achsisDescription['x'] !== null) {
            $xAchsisYPos = $xAchsisYPos - $this->settings['BLOCK']['AchsisDescriptionHeight'];
        }
        if (count($this->explanation) > 0) {
            $xAchsisYPos = $xAchsisYPos -
            ceil(count($this->explanation) / $this->settings['BLOCK']['perExplanationLine']) * $this->settings['BLOCK']['explanationLineHeight'];
        }
        $xAchsisLength = $this->settings['width'] - 2 * $this->settings['padding'] - $entryWidth;
        $yAchsisXPos = $this->settings['padding'] + $entryWidth;
        $yAchsisLength = $xAchsisYPos - $this->settings['padding'];

        // generate x achsis
        $chartcontent .= $this->drawHLine(
            $xAchsisYPos,
            $entryWidth + $this->settings['padding'],
            $xAchsisLength);
        // generate y achsis
        $chartcontent .= $this->drawVLine(
            $yAchsisXPos,
            $this->settings['padding'],
            $yAchsisLength);

        // x Achsis description && get yMaxEntryVal
        $i = 0;
        foreach ($this->dataset as $key => $set) {
            $chartcontent .= $this->drawText(
                $key,
                $this->settings['padding'] + $entryWidth * ($i + 1) + $entryWidth / 2,
                $xAchsisYPos + 25,
                'middle',
                'black',
                null,
                20);
            $chartcontent .= $this->drawVLine(
                $this->settings['padding'] + $entryWidth * ($i + 1) + $entryWidth / 2,
                $xAchsisYPos,
                $yAchsisDescLineLength,
                null,
                2);
            $i++;
            // calculate y max value
            $yMaxEntryVal = max([$yMaxEntryVal, max($set)]);
        }

        // ----------- calculate y achsis --------------------
        // calculate scale
        $numlength = strlen((string) (int) $yMaxEntryVal);
        $yAchsisMax = pow(10, $numlength);
        $shrinkFlag = 0;
        while (true) {
            if ($yMaxEntryVal == null) {
                break;
            }
            if ($yAchsisMax % 2 == 0 && ($yAchsisMax / 2) >= $yMaxEntryVal && ($yAchsisMax / 2) % $this->settings['BLOCK']['ySteps'] == 0) {
                $yAchsisMax = ($yAchsisMax / 2);
            } else {
                if ($yAchsisMax - $yAchsisMax / ($this->settings['BLOCK']['ySteps'] - $shrinkFlag) >= $yMaxEntryVal) {
                    $yAchsisMax = $yAchsisMax - $yAchsisMax / ($this->settings['BLOCK']['ySteps'] - $shrinkFlag);
                    $shrinkFlag++;
                } else {
                    break;
                }
            }
        }
        $yAchsisStep = $yAchsisMax / ($this->settings['BLOCK']['ySteps'] - $shrinkFlag);
        $yAchsisStepHeight = ($yAchsisLength) / ($this->settings['BLOCK']['ySteps'] - $shrinkFlag);

        // draw step description
        for ($i = 0; $i < ($this->settings['BLOCK']['ySteps'] - $shrinkFlag); $i++) {
            $chartcontent .= $this->drawHLine(
                $xAchsisYPos - $yAchsisStepHeight * ($i + 1),
                $this->settings['padding'] + $entryWidth - $yAchsisDescLineLength / 2,
                $yAchsisDescLineLength);

            if ($this->settings['BLOCK']['yGrid']) {
                for ($ii = 0; $ii < ($this->settings['BLOCK']['ySteps']); $ii++) {
                    $chartcontent .= $this->drawHLine(
                        $xAchsisYPos - $yAchsisStepHeight * ($i + 1) + $ii * ($yAchsisStepHeight / ($this->settings['BLOCK']['ySteps'])),
                        $this->settings['padding'] + $entryWidth,
                        $xAchsisLength);
                }
            }

            $chartcontent .= $this->drawText(
                ''.($yAchsisStep * ($i + 1)),
                $this->settings['padding'] + $entryWidth - $yAchsisDescLineLength / 2 - 5,
                $xAchsisYPos - $yAchsisStepHeight * ($i + 1) + 10,
                'end',
                'black',
                null,
                20);
        }

        // draw achsis description
        if ($this->achsisDescription['x'] !== null) {
            $chartcontent .= $this->drawText(
                $this->achsisDescription['x'],
                null,
                $xAchsisYPos + $this->settings['BLOCK']['AchsisDescriptionHeight'] + $xFontOffset - 5,
                'middle',
                'black',
                null,
                20);
        }

        if ($this->achsisDescription['y'] !== null) {
            $chartcontent .= $this->drawText(
                $this->achsisDescription['y'],
                $yAchsisXPos - 2 * $this->settings['BLOCK']['AchsisDescriptionHeight'] + 10,
                null,
                'middle',
                'black',
                null,
                20,
                270);
        }

        // draw Bars/Lines
        $i = 0;
        $lines = [];
        foreach ($this->dataset as $key => $set) {
            $barCount = count($set);
            $elementPosition = $this->settings['padding'] + $entryWidth * ($i + 1);
            $barWidth = $entryWidth / ($barCount + 2);
            $j = 0;
            foreach ($set as $value) {
                $barHeight = (($yAchsisLength) * $value) / $yAchsisMax;
                if ($line === true) { // line diagramm -> fetch points
                    if ($value === null && $this->settings['LINE']['ignore_null']) {
                        continue;
                    }
                    $lines[$j][] = ['x' => $this->settings['padding'] + $entryWidth * ($i + 1.5), 'y' => $xAchsisYPos - $barHeight, 'value' => $value];
                    $j++;
                } else {
                    $chartcontent .= $this->suroundElementWithMouseHilight($this->drawBar(
                        $elementPosition + $barWidth * ($j + 1),
                        $xAchsisYPos - $barHeight,
                        $barWidth,
                        $barHeight,
                        $this->colorMap[$j],
                        'black',
                        2,
                        ''.$value));
                    $j++;
                }
            }

            $i++;
        }
        // draw lines & points
        if ($line === true) {
            $lastX = 0;
            $lastY = 0;
            $lineNumber = 0;
            foreach ($lines as $linePoints) {
                $lineObj = '';
                $color = $this->colorMap[$lineNumber];
                $pointIndex = 0;
                foreach ($linePoints as $point) {
                    // draw point
                    $lineObj .= $this->drawCircle($point['x'], $point['y'], $this->settings['LINE']['pointRadius'], $color, $color, 1, $point['value']);
                    if ($pointIndex > 0) { // draw lines
                        $lineObj .= $this->drawLine(
                            $linePoints[$pointIndex - 1]['x'],
                            $linePoints[$pointIndex - 1]['y'],
                            $point['x'],
                            $point['y'],
                            $this->settings['LINE']['line-width'],
                            $color);
                    }
                    $pointIndex++;
                }
                $lineNumber++;
                $chartcontent .= $this->suroundElementWithMouseHilight($lineObj);
            }
        }

        // daw explanation
        if (count($this->explanation) > 0) {
            $i = 0;
            $y = $xAchsisYPos + $xFontOffset;
            if ($this->achsisDescription['x'] !== null) {
                $y = $y + $this->settings['BLOCK']['AchsisDescriptionHeight'];
            }
            $x_width = ($this->settings['width'] - 2 * $this->settings['padding']) / $this->settings['BLOCK']['perExplanationLine'];
            foreach ($this->explanation as $leg) {
                $yy = $y + ($this->settings['BLOCK']['explanationLineHeight'] * floor($i / $this->settings['BLOCK']['perExplanationLine']));
                $xx = $this->settings['padding'] + ($i % $this->settings['BLOCK']['perExplanationLine']) * $x_width;
                $chartcontent .= $this->drawBar($xx, $yy + 5, $this->settings['BLOCK']['explanationLineHeight'],
                    $this->settings['BLOCK']['explanationLineHeight'] - 10,
                    $this->colorMap[$i], 'black', 1, (($this->translator !== null) ? $this->translator->translate('explanation') : 'explanation'));
                $chartcontent .= $this->drawText($leg,
                    $xx + $this->settings['BLOCK']['explanationLineHeight'] + 7,
                    $yy - 7 + $this->settings['BLOCK']['explanationLineHeight'],
                    'start',
                    'black',
                    'bold',
                    20);
                $i++;
            }
        }
        $this->setSvgResult($chartcontent, true);
    }
}
