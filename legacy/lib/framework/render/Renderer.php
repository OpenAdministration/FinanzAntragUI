<?php

namespace framework\render;

use App\Exceptions\LegacyDieException;
use framework\DBConnector;
use ReflectionFunction;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

abstract class Renderer extends EscFunc
{
    public const ALERT_WARNING = 'warning';

    public const ALERT_INFO = 'info';

    public const ALERT_DANGER = 'danger';

    public const ALERT_SUCCESS = 'success';

    protected $routeInfo;

    protected Request $request;

    public function __construct(array $routeInfo = [])
    {
        $this->routeInfo = $routeInfo;
        $this->request = Request::createFromGlobals();
    }

    public function render(): void
    {
        $action = $this->routeInfo['action'];
        $methodName = 'action'.str_replace('-', '', ucwords($action, '-'));
        if (method_exists($this, $methodName)) {
            echo $this->$methodName();
        } else {
            throw new LegacyDieException(404, "Methode $methodName not found in ".__CLASS__);
        }
    }

    protected function renderTable(
        array $header, array $groupedContent, array $keys = [], array $escapeFunctions = [], array $footer = []
    ): void {
        $defaultFunction = [__CLASS__, 'defaultEscapeFunction'];

        // throw away the keys (needed later), numeric keys need to be used
        $escapeFunctions = array_values($escapeFunctions);
        // set every function which is null or empty to default function
        array_walk(
            $escapeFunctions,
            static function (&$val) use ($defaultFunction) {
                if (! isset($val) || empty($val)) {
                    $val = $defaultFunction;
                }
            }
        );
        // count the parameters of all functions, which can be used
        $reflectionsOfFunctions = [];
        $isReflectionMethods = [];
        $paramSum = 0;

        foreach ($escapeFunctions as $idx => $escapeFunction) {
            if (is_array($escapeFunction)) {
                $rf = new ReflectionMethod($escapeFunction[0], $escapeFunction[1]);
                $isReflectionMethods[] = true;
            } else {
                $rf = new ReflectionFunction($escapeFunction);
                $isReflectionMethods[] = false;
            }
            // let some space in the idx
            $reflectionsOfFunctions[$idx] = $rf;
            $paramSum += $rf->getNumberOfParameters();
        }
        // if there are to fewer parameters - add some default functions.
        $diff = count($header) - count($escapeFunctions);
        if ($diff > 0) {
            $paramSum += $diff;
            $escapeFunctions = array_merge(
                $escapeFunctions,
                array_fill(0, $diff, $defaultFunction)
            );
            $isReflectionMethods = array_merge($isReflectionMethods, array_fill(0, $diff, true));
            $reflectionsOfFunctions = array_merge(
                $reflectionsOfFunctions,
                array_fill(
                    0,
                    $diff,
                    new ReflectionMethod(
                        $defaultFunction[0], $defaultFunction[1]
                    )
                )
            );
        }
        foreach ($groupedContent as $groupName => $content) {
            if (empty($content)) {
                continue;
            }
            if (count(reset($content)) !== $paramSum && count($keys) !== $paramSum) {
                throw new LegacyDieException(500,
                    "In Gruppe '$groupName' passt Spaltenzahl (".count(
                        reset($content)
                    ).') bzw. Key Anzahl ('.count(
                        $keys
                    ).") nicht zur benötigten Parameterzahl $paramSum \n es wurden ".count(
                        $escapeFunctions
                    ).' Funktionen übergeben '.$diff.' wurde(n) hinzugefügt.'
                );
            }
        }

        if (count($keys) === 0) {
            $keys = range(0, $paramSum);
            $assoc = false;
        } else {
            $assoc = true;
        } ?>
        <table class="table">
            <thead>
            <tr>
                <?php
                foreach ($header as $titel) {
                    echo "<th>$titel</th>";
                } ?>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($groupedContent as $groupName => $rows) {
                if (! is_int($groupName)) { ?>
                    <tr>
                        <th class="bg-info" colspan="<?php echo count($header); ?>"><?php echo $groupName; ?></th>
                    </tr>
                <?php }
                foreach ($rows as $row) {
                    // echo count($row) . "-". count($escapeFunctions) . "-". count($header);
                    ?>
                    <tr>
                        <?php
                        // throw away keys
                        if (! $assoc) {
                            $row = array_values($row);
                        }

                    $shiftIdx = 0;
                    foreach ($reflectionsOfFunctions as $idx => $reflectionOfFunction) {
                        // var_export($keys);
                        $arg_keys = array_slice(
                            $keys,
                            $shiftIdx,
                            $reflectionOfFunction->getNumberOfParameters()
                        );
                        $args = [];
                        foreach ($arg_keys as $arg_key) {
                            $args[] = $row[$arg_key];
                        }
                        // var_export($args);
                        // var_export($row);
                        // var_export($reflectionOfFunction->getNumberOfParameters());
                        $shiftIdx += $reflectionOfFunction->getNumberOfParameters();
                        if ($isReflectionMethods[$idx]) {
                            echo '<td>'.call_user_func_array($escapeFunctions[$idx], $args).'</td>';
                        } else {
                            echo '<td>'.$reflectionOfFunction->invokeArgs($args).'</td>';
                        }
                    } ?>
                    </tr>
                    <?php
                } ?>
                <?php
            } ?>
            </tbody>
            <?php if ($footer && is_array($footer) && count($footer) > 0) { ?>
                <tfoot> <?php
                if (! is_array(array_values($footer)[0])) {
                    $footer = [$footer];
                }
                foreach ($footer as $foot_line) {
                    echo '<tr>';
                    foreach ($foot_line as $foot) {
                        echo "<th>$foot</th>";
                    }
                    echo '</tr>';
                }
                ?>
                </tfoot>
            <?php } ?>
        </table>
        <?php
    }

    protected function renderClearFix(): void
    {
        echo "<div class='clearfix'></div>";
    }

    protected function renderHeadline($text, int $headlineNr = 1): void
    {
        echo '<h'.htmlspecialchars($headlineNr).'>'.htmlspecialchars($text).'</h'.htmlspecialchars(
            $headlineNr
        ).'>';
    }

    protected function formatDateToMonthYear($dateString)
    {
        return ! empty($dateString) ? strftime('%b %Y', strtotime($dateString)) : '';
    }

    protected function renderHiddenInput($name, $value): void
    { ?>
        <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>">
        <?php
    }

    /**
     * @param  $data  array
     */
    protected function renderAccordionPanels(array $data, $groupHeaderFun, $innerHeaderHeadlineFun, $innerHeaderFun, $innerContentFun): void
    { ?>
        <div class="panel-group" id="accordion">
            <?php $i = 0;
        if (isset($data) && ! empty($data) && $data) {
            foreach ($data as $groupHeadline => $groupContent) {
                if (count($groupContent) === 0) {
                    continue;
                } ?>
                    <div class="panel panel-default">
                        <div class="panel-heading collapsed" data-toggle="collapse" data-parent="#accordion"
                             href="#collapse<?php echo $i; ?>">
                            <h4 class="panel-title">
                                <i class="fa fa-fw fa-togglebox"></i>&nbsp;<?php echo $groupHeaderFun($groupHeadline); ?>
                            </h4>
                        </div>
                        <div id="collapse<?php echo $i; ?>" class="panel-collapse collapse">
                            <div class="panel-body">
                                <?php $j = 0; ?>
                                <div class="panel-group" id="accordion<?php echo $i; ?>">
                                    <?php foreach ($groupContent as $content) { ?>
                                        <div class="panel panel-default">
                                            <div class="panel-link">
                                                <?php echo $innerHeaderHeadlineFun($content); ?>
                                            </div>
                                            <div class="panel-heading collapsed <?php echo (! isset($content['subcontent'])
                                            || count($content['subcontent']) === 0) ? 'empty' : ''; ?>"
                                                 data-toggle="collapse" data-parent="#accordion<?php echo $i; ?>"
                                                 href="#collapse<?php echo $i.'-'.$j; ?>">
                                                <h4 class="panel-title">
                                                    <i class="fa fa-togglebox"></i>
                                                    <span class="panel-projekt-name">
                                                        <?php echo $innerHeaderFun($content); ?>
                                                    </span>
                                                </h4>
                                            </div>
                                            <?php if (isset($content['subcontent']) && count($content['subcontent']) > 0) { ?>
                                                <div id="collapse<?php echo $i.'-'.$j; ?>"
                                                     class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <?php echo $innerContentFun($content['subcontent']); ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <?php $j++;
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    $i++;
            }
        } else {
            $this->renderAlert(
                'Warnung',
                "In deinen Gremien wurden in diesem Haushaltsjahr noch keine Projekte angelegt. Fange doch jetzt damit an! <a href='".URIBASE."projekt/create'>Neues Projekt erstellen</a>",
                'warning'
            );
        } ?>
        </div>
        <?php
    }

    protected function renderNonce(): void
    {
        $this->renderHiddenInput('nonce', csrf_token());
    }

    /**
     * @param  $type  string has to be <i>"success"</i>, "info", "warning" or "danger"
     */
    protected function renderAlert($strongMsg, $msg, string $type = self::ALERT_SUCCESS): void
    {
        if (! in_array($type, [self::ALERT_SUCCESS, self::ALERT_INFO, self::ALERT_WARNING, self::ALERT_DANGER])) {
            throw new LegacyDieException(500, 'Falscher Datentyp in renderAlert()');
        }
        if (is_array($msg)) {
            $msg = $this->arrayToListEscapeFunction($msg);
        } ?>
        <div class="alert alert-<?php echo $type; ?>">
            <strong><?php echo $strongMsg; ?></strong> <?php echo $msg; ?>
        </div>
        <?php
    }

    protected function makeClickableMails($text)
    {
        // $text = htmlspecialchars($text);
        $matches = [];
        preg_match_all('#[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}#', $text, $matches);
        // var_dump($matches[0]);
        foreach ($matches[0] as $match) {
            $text = str_replace($match, $this->mailto($match), $text);
        }

        return $text;
    }

    protected function mailto(string $address): string
    {
        return "<a href='mailto:$address'><i class='fa fa-fw fa-envelope'></i>$address</a>";
    }

    protected function makeProjektsClickable($text)
    {
        $matches = [];
        $text = htmlspecialchars($text);
        preg_match("/IP-[\d]{2,4}-[\d]+-A[\d]+/", $text, $matches);
        foreach ($matches as $match) {
            $array = explode('-', $match);
            $auslagen_id = substr(array_pop($array), 1);
            $projekt_id = array_pop($array);
            $text = str_replace(
                $match,
                "<a target='_blank' href='".URIBASE."projekt/$projekt_id/auslagen/$auslagen_id'><i class='fa fa-fw fa-chain'></i>$match</a>",
                $text
            );
        }

        return $text;
    }

    protected function renderRadioButtons($textValueArray, $formName): void
    {
        $formName = htmlspecialchars(strip_tags($formName));
        foreach ($textValueArray as $value => $text) {
            $text = htmlspecialchars($text);
            echo "<div class='radio'><label><input type='radio' value='$value' name='$formName'>$text</label></div>";
        }
    }

    protected function renderInternalHyperLink($text, $dest)
    {
        echo $this->internalHyperLinkEscapeFunction($text, $dest);
    }

    protected function renderHHPSelector($routeInfo, $urlPrefix = URIBASE, $urlSuffix = '/')
    {
        $hhps = DBConnector::getInstance()->dbFetchAll(
            'haushaltsplan',
            [
                DBConnector::FETCH_ASSOC,
                DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY,
            ],
            [],
            [],
            [],
            ['von' => false]
        );
        if (! isset($hhps) || empty($hhps)) {
            throw new LegacyDieException(500, 'Konnte keine Haushaltspläne finden');
        }
        if (! isset($routeInfo['hhp-id'])) {
            foreach (array_reverse($hhps, true) as $id => $hhp) {
                if ($hhp['state'] === 'final') {
                    $routeInfo['hhp-id'] = $id;
                }
            }
        } ?>
        <form action="<?php echo $urlPrefix.$routeInfo['hhp-id'].$urlSuffix; ?>"
              data-action='<?php echo $urlPrefix.'%%'.$urlSuffix; ?>'>
            <div class="input-group col-xs-2 pull-right hhp-selector">
                <select class="selectpicker" id="hhp-id"><?php
                    foreach ($hhps as $id => $hhp) {
                        $von = date_create($hhp['von'])->format('M Y');
                        $bis = ! empty($hhp['bis']) ? date_create($hhp['bis'])->format('M Y') : false;
                        $name = $bis ? $von.' bis '.$bis : 'ab '.$von; ?>
                        <option value="<?php echo $id; ?>" <?php echo $id == $routeInfo['hhp-id'] ? 'selected' : ''; ?>
                                data-subtext="<?php echo $hhp['state']; ?>"><?php echo $name; ?>
                        </option>
                        <?php
                    } ?>
                </select>
                <div class="input-group-btn">
                    <button type="submit" class="btn btn-primary load-hhp"><i class="fa fa-fw fa-refresh"></i>
                        Aktualisieren
                    </button>
                </div>
            </div>
        </form>
        <?php
        return [$hhps, $routeInfo['hhp-id']];
    }

    protected function renderList($items, $escapeItems = true, $unorderedList = true): void
    {
        if ($unorderedList) {
            $el = 'ul';
        } else {
            $el = 'ol';
        }
        echo "<$el>";
        foreach ($items as $item) {
            if ($escapeItems) {
                $item = htmlspecialchars($item);
            }
            echo "<li>$item</li>";
        }
        echo "</$el>";
    }
}
