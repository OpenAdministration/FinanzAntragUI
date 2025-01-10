<?php

namespace framework\svg;

/**
 * Adding Beam (Block) Diagram Class
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
class SvgDiagramAddingBeam extends SvgDiagramBlock
{
    /**
     * this class implements following diagram types
     *
     * @var array
     */
    private static $types = [
        'AddingBeam',
    ];

    // CLASS CONSTRUCTOR --------------------------------------

    /**
     * constructor
     *
     * @param  string  $type
     */
    public function __construct($type)
    {
        parent::__construct($type);
        if (in_array($type, self::$types, true)) {
            $this->type = $type;
        } else {
            $this->type = self::$types[0];
        }
        $this->settings['ADDBLOCK'] = [
            'interpret_date' => false,
            'sub_x_description' => false,
            'sub_x_description_array' => null,
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
     * set AddBlock Settings variables
     *
     * @param  string|number  $key  : 'interpret_date'
     * @param  mixed  $value
     */
    public function setAddBeamSetting($key, $value)
    {
        if (array_key_exists($key, $this->settings['ADDBLOCK'])) {
            $this->settings['ADDBLOCK'][$key] = $value;
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
     * generate block chart from data - sums values per beam
     *
     * @see \intertopia\Classes\svg\SvgDiagramCore::render()
     */
    public function render()
    {
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
        if ($this->settings['ADDBLOCK']['sub_x_description']) {
            $xAchsisYPos -= 25;
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
                (! $this->settings['ADDBLOCK']['sub_x_description']) ? $xAchsisYPos + 25 : $xAchsisYPos + 50,
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
            if (! $this->settings['ADDBLOCK']['interpret_date']) {
                if (count($set) > 0) {
                    if (is_array(current($set))) {
                        foreach ($set as $setset) {
                            $yMaxEntryVal = max([$yMaxEntryVal, array_sum($setset)]);
                        }
                    } else {
                        $yMaxEntryVal = max([$yMaxEntryVal, array_sum($set)]);
                    }
                }
            } else {
                $data = [];
                if (! is_array(current($set))) {
                    $data = [$set];
                } else {
                    $data = $set;
                }
                foreach ($data as $setset) {
                    $sum = 0;
                    foreach ($setset as $val) {
                        $sum += $this->timeToMinutes($val);
                    }
                    $yMaxEntryVal = max([$yMaxEntryVal, $sum]);
                }
            }
        }

        // ----------- calculate y achsis --------------------
        // calculate scale
        $numlength = strlen((string) intval($yMaxEntryVal));
        $yAchsisMax = 10 ** $numlength;
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
                (! $this->settings['ADDBLOCK']['interpret_date']) ?
                ''.($yAchsisStep * ($i + 1)) :
                ''.$this->convertToHoursMins(($yAchsisStep * ($i + 1))),
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
                ((! $this->settings['ADDBLOCK']['interpret_date']) ? $xAchsisYPos : $xAchsisYPos + 25)
                + $this->settings['BLOCK']['AchsisDescriptionHeight'] + $xFontOffset - 5,
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

        // draw Bars
        $i = 0;
        foreach ($this->dataset as $key => $set) {
            $data = [];
            if (! is_array(current($set))) {
                $data = [$set];
            } else {
                $data = $set;
            }
            $barCount = count($data);

            $elementPosition = $this->settings['padding'] + $entryWidth * ($i + 1);
            $barWidth = $entryWidth / ($barCount + 2);
            $j = 0;

            foreach ($data as $sub_key => $dset) {
                $last_top = $xAchsisYPos;
                $colorpos = 0;
                foreach ($dset as $value) {
                    $barHeight = 0;
                    if (! $this->settings['ADDBLOCK']['interpret_date']) {
                        $barHeight = (($yAchsisLength) * $value) / $yAchsisMax;
                    } else {
                        $barHeight = (($yAchsisLength) * $this->timeToMinutes($value)) / $yAchsisMax;
                    }

                    $last_top = $last_top - $barHeight;
                    $chartcontent .= $this->suroundElementWithMouseHilight($this->drawBar(
                        $elementPosition + $barWidth * ($j + 1),
                        $last_top,
                        $barWidth,
                        $barHeight,
                        $this->colorMap[$colorpos],
                        'black',
                        2,
                        ''.$value)
                    );
                    $colorpos++;
                }

                // x Achsis description - sub_x_description
                if ($this->settings['ADDBLOCK']['sub_x_description']) {
                    $chartcontent .= $this->drawText(
                        ''.$this->settings['ADDBLOCK']['sub_x_description_array'][$sub_key],
                        $elementPosition + $barWidth * ($j + 1) + $barWidth / 2,
                        $xAchsisYPos + 25,
                        'middle',
                        'black',
                        null,
                        20);
                }

                $j++;
            }
            $i++;
        }

        // daw explanation
        if (count($this->explanation) > 0) {
            $i = 0;
            $y = $xAchsisYPos + $xFontOffset;
            if ($this->settings['ADDBLOCK']['sub_x_description']) {
                $y += 25;
            }
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
