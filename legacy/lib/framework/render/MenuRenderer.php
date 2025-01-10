<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 03.02.18
 * Time: 14:43
 */

namespace framework\render;

use App\Exceptions\LegacyDieException;
use forms\projekte\auslagen\AuslagenHandler2;
use forms\projekte\ProjektHandler;
use framework\auth\AuthHandler;
use framework\CryptoHandler;
use framework\DBConnector;

class MenuRenderer extends Renderer
{
    public const DEFAULT = 'mygremium';

    private $pathinfo;

    public function __construct($pathinfo = [])
    {
        if (! isset($pathinfo['action']) || empty($pathinfo)) {
            $pathinfo['action'] = self::DEFAULT;
        }
        $this->pathinfo = $pathinfo;
    }

    public function render(): void
    {
        switch ($this->pathinfo['action']) {
            case 'mygremium':
            case 'allgremium':
                HTMLPageRenderer::registerProfilingBreakpoint('start-rendering');
                $this->renderProjekte($this->pathinfo['action']);
                break;
            case 'search':
                $this->setOverviewTabs($this->pathinfo['action']);
                $this->renderSearch();
                break;
            case 'mystuff':
                $this->setOverviewTabs($this->pathinfo['action']);
                $this->renderAlert('Hinweis', 'Dieser Bereich befindet sich noch im Aufbau', 'info');
                break;
            case 'extern':
                $this->setOverviewTabs($this->pathinfo['action']);
                $this->renderExtern();
                break;
            case 'mykonto':
                $this->renderMyProfile();
                break;
            case 'stura':
                $this->renderStuRaView();
                break;
            case 'belege':
                $this->renderMissingBelege();
                break;
            case 'hv':
                $this->renderHVView();
                break;
            case 'kv':
                $this->renderKVView();
                break;
            case 'exportBank':
                $this->renderExportBank();
                break;
            default:
                throw new LegacyDieException(400, "{$this->pathinfo['action']} kann nicht interpretiert werden");
                break;
        }
    }

    public function renderProjekte($active): void
    {
        [$hhps, $hhp_id] = $this->renderHHPSelector($this->pathinfo, URIBASE."menu/$active/");
        echo "<div class='clearfix'></div>";
        $hhp_von = $hhps[$hhp_id]['von'];
        $hhp_bis = $hhps[$hhp_id]['bis'];
        $userGremien = AuthHandler::getInstance()->getUserGremien();
        /*
        $userGremien = array_filter(
            $userGremien,
            static function ($val) {
                foreach (GREMIUM_PREFIX as $prefix) {
                    if (str_starts_with($val, $prefix)) {
                        return true;
                    }
                }
                return false;
            }
        );
        */
        rsort($userGremien, SORT_STRING | SORT_FLAG_CASE);
        switch ($active) {
            case 'allgremium':
                if (is_null($hhp_bis)) {
                    $where = [
                        ['createdat' => ['>=', $hhp_von]],
                    ];
                } else {
                    $where = [
                        ['createdat' => ['BETWEEN', [$hhp_von, $hhp_bis]]],
                    ];
                }
                break;
            case 'mygremium':
                if (empty($userGremien)) {
                    $this->renderAlert(
                        'Schade!',
                        $this->makeClickableMails(
                            'Leider scheinst du noch kein Gremium zu haben.'
                        ),
                        'warning'
                    );

                    return;
                }
                if (is_null($hhp_bis)) {
                    $where = [
                        ['org' => ['in', $userGremien], 'createdat' => ['>=', $hhp_von]],
                        ['org' => ['is', null], 'createdat' => ['>=', $hhp_von]],
                        ['org' => '', 'createdat' => ['>=', $hhp_von]],
                    ];
                } else {
                    $where = [
                        ['org' => ['in', $userGremien], 'createdat' => ['BETWEEN', [$hhp_von, $hhp_bis]]],
                        ['org' => ['is', null], 'createdat' => ['BETWEEN', [$hhp_von, $hhp_bis]]],
                        ['org' => '', 'createdat' => ['BETWEEN', [$hhp_von, $hhp_bis]]],
                    ];
                }
                break;
            default:
                throw new LegacyDieException(400, 'Not known active Tab: '.$active);
                break;
        }

        $projekte = DBConnector::getInstance()->dbFetchAll(
            'projekte',
            [DBConnector::FETCH_ASSOC, DBConnector::FETCH_GROUPED],
            [
                'org',
                'projekte.*',
                'ausgaben' => ['projektposten.ausgaben', DBConnector::GROUP_SUM_ROUND2],
                'einnahmen' => ['projektposten.einnahmen', DBConnector::GROUP_SUM_ROUND2],
            ],
            $where,
            [
                ['table' => 'projektposten', 'type' => 'left', 'on' => ['projektposten.projekt_id', 'projekte.id']],
            ],
            ['org' => true, 'projekte.id' => false],
            ['projekte.id']
        );
        $pids = [];
        array_walk(
            $projekte,
            static function ($array, $gremien) use (&$pids) {
                array_walk(
                    $array,
                    static function ($res, $key) use (&$pids) {
                        $pids[] = $res['id'];
                    }
                );
            }
        );
        if (! empty($pids)) {
            $auslagen = DBConnector::getInstance()->dbFetchAll(
                'auslagen',
                [DBConnector::FETCH_ASSOC, DBConnector::FETCH_GROUPED],
                [
                    'projekt_id',  // group idx
                    'projekt_id',
                    'auslagen.id',
                    'name_suffix', // auslagen Link
                    'zahlung-name', // Empf. Name
                    'einnahmen' => ['einnahmen', DBConnector::GROUP_SUM_ROUND2],
                    'ausgaben' => ['ausgaben', DBConnector::GROUP_SUM_ROUND2],
                    'state',
                ],
                ['projekt_id' => ['IN', $pids]],
                [
                    ['table' => 'belege', 'type' => 'LEFT', 'on' => ['belege.auslagen_id', 'auslagen.id']],
                    ['table' => 'beleg_posten', 'type' => 'LEFT', 'on' => ['beleg_posten.beleg_id', 'belege.id']],
                ],
                ['auslagen.id' => true],
                ['auslagen_id']
            );
        }

        // var_dump(end(end($projekte)));
        $this->setOverviewTabs($active); ?>

        <div class="panel-group" id="accordion">
            <?php $i = 0;
        if (isset($projekte) && ! empty($projekte) && $projekte) {
            foreach ($projekte as $gremium => $inhalt) {
                if (count($inhalt) === 0) {
                    continue;
                } ?>
                    <div class="panel panel-default">
                        <div class="panel-heading collapsed" data-toggle="collapse" data-parent="#accordion"
                             href="#collapse<?php echo $i; ?>">
                            <h4 class="panel-title">
                                <?php
                                $titel = empty($gremium) ? 'Nicht zugeordnete Projekte' :
                                    // (in_array($gremium, $attributes["alle-gremien"], true) ? "" : "[INAKTIV] ") .
                                    $gremium; ?>
                                <i class="fa fa-fw fa-togglebox"></i>&nbsp;<?php echo $titel; ?>
                            </h4>
                        </div>
                        <div id="collapse<?php echo $i; ?>" class="panel-collapse collapse">
                            <div class="panel-body">
                                <?php $j = 0; ?>
                                <div class="panel-group" id="accordion<?php echo $i; ?>">
                                    <?php foreach ($inhalt as $projekt) {
                                        $id = $projekt['id'];
                                        $year = date('y', strtotime($projekt['createdat'])); ?>
                                        <div class="panel panel-default">
                                            <div class="panel-link"><?php echo generateLinkFromID(
                                                "IP-$year-$id",
                                                'projekt/'.$id
                                            ); ?>
                                            </div>
                                            <div class="panel-heading collapsed <?php echo (! isset($auslagen[$id]) || count(
                                                $auslagen[$id]
                                            ) === 0) ? 'empty' : ''; ?>"
                                                 data-toggle="collapse" data-parent="#accordion<?php echo $i; ?>"
                                                 href="#collapse<?php echo $i.'-'.$j; ?>">
                                                <h4 class="panel-title">
                                                    <i class="fa fa-togglebox"></i><span
                                                            class="panel-projekt-name"><?php echo htmlspecialchars($projekt['name']); ?></span>
                                                    <span class="panel-projekt-money text-muted hidden-xs ">
                                                        <?php echo number_format($projekt['ausgaben'], 2, ',', '.'); ?>
                                                    </span>
                                                    <span class="label label-info project-state-label"><?php echo ProjektHandler::getStateStringFromName($projekt['state']); ?>
                                                    </span>
                                                </h4>
                                            </div>
                                            <?php if (isset($auslagen[$id]) && count($auslagen[$id]) > 0) { ?>
                                                <div id="collapse<?php echo $i.'-'.$j; ?>"
                                                     class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <?php
                                                    $sum_a_in = 0;
                                                $sum_a_out = 0;
                                                $sum_e_in = 0;
                                                $sum_e_out = 0;
                                                foreach ($auslagen[$id] as $a) {
                                                    if (strpos($a['state'], 'booked') === 0 || strpos($a['state'], 'instructed') === 0) {
                                                        $sum_a_in += $a['einnahmen'];
                                                        $sum_a_out += $a['ausgaben'];
                                                    }
                                                    if (strpos($a['state'], 'revocation') !== 0 && strpos($a['state'], 'draft') !== 0) {
                                                        $sum_e_in += $a['einnahmen'];
                                                        $sum_e_out += $a['ausgaben'];
                                                    }
                                                }

                                                $this->renderTable(
                                                    [
                                                        'Name',
                                                        'Zahlungsempfänger',
                                                        'Einnahmen',
                                                        'Ausgaben',
                                                        'Status',
                                                    ],
                                                    [$auslagen[$id]],
                                                    [],
                                                    [
                                                        [$this, 'auslagenLinkEscapeFunction'],
                                                        // 3 Parameter
                                                        null,
                                                        // 1 parameter
                                                        [$this, 'moneyEscapeFunction'],
                                                        [$this, 'moneyEscapeFunction'],
                                                        function ($stateString) {
                                                            $text = AuslagenHandler2::getStateStringFromName(
                                                                AuslagenHandler2::state2stateInfo(
                                                                    $stateString
                                                                )['state']
                                                            );

                                                            return "<div class='label label-info'>$text</div>";
                                                        },
                                                    ],
                                                    [
                                                        [
                                                            '',
                                                            'Eingereicht:',
                                                            '&Sigma;: '.number_format(
                                                                $sum_e_in,
                                                                2
                                                            ).'&nbsp;€',
                                                            '&Sigma;: '.number_format(
                                                                $sum_e_out,
                                                                2
                                                            ).'&nbsp;€',
                                                            '&Delta;: '.number_format(
                                                                $sum_e_out - $sum_e_in,
                                                                2
                                                            ).'&nbsp;€',
                                                        ],
                                                        [
                                                            '',
                                                            'Ausgezahlt:',
                                                            '&Sigma;: '.number_format(
                                                                $sum_a_in,
                                                                2
                                                            ).'&nbsp€',
                                                            '&Sigma;: '.number_format(
                                                                $sum_a_out,
                                                                2
                                                            ).'&nbsp€',
                                                            '&Delta;: '.number_format(
                                                                $sum_a_out - $sum_a_in,
                                                                2
                                                            ).'&nbsp€',
                                                        ],
                                                    ]
                                                ); ?>
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

    public function renderExtern(): void
    {
        $extern_meta = DBConnector::getInstance()->dbFetchAll(
            'extern_meta',
            [DBConnector::FETCH_ASSOC, DBConnector::FETCH_GROUPED],
            ['org_name', '*'],
            [],
            [],
            ['org_name' => true, 'id' => false]
        );
        if (! is_array($extern_meta) || empty($extern_meta)) {
            $this->renderAlert('Schade', 'Hier existieren noch keine externen Anträge. Beschwere dich am besten bei Dave um das zu ändern!', 'info');

            return;
        }
        $idToKeys = [];
        foreach ($extern_meta as $org_name => $items) {
            foreach ($items as $nr => $item) {
                $idToKeys[$item['id']] = [$org_name, $nr];
            }
        }
        $extern_data = DBConnector::getInstance()->dbFetchAll(
            'extern_data',
            [DBConnector::FETCH_ASSOC, DBConnector::FETCH_GROUPED],
            [
                'extern_id',
                'extern_id',
                'extern_data.*',
                'hhp_id' => 'haushaltsgruppen.hhp_id',
                'titel_name' => 'haushaltstitel.titel_name',
                'titel_nr' => 'haushaltstitel.titel_nr',
            ],
            ['extern_id' => ['IN', array_keys($idToKeys)]],
            [
                ['table' => 'haushaltstitel', 'type' => 'LEFT', 'on' => ['extern_data.titel_id', 'haushaltstitel.id']],
                ['table' => 'haushaltsgruppen', 'type' => 'LEFT', 'on' => ['haushaltstitel.hhpgruppen_id', 'haushaltsgruppen.id']],
            ],
            ['extern_id' => true]
        );
        foreach ($extern_data as $extern_id => $data) {
            $extern_meta[$idToKeys[$extern_id][0]][$idToKeys[$extern_id][1]]['subcontent'] = $data;
        }

        $groupHeaderFun = function ($key) {
            return $key;
        };
        $innerHeaderHeadlineFun = function ($content) {
            $date = date_create($content['projekt_von'])->format('y');

            return "<a href=''>EP-".$date.'-'.$content['id'].'</a>';
        };
        $innerHeaderFun = function ($content) {
            return $content['projekt_name'];
        };
        $obj = $this;
        $innerContentFun = function ($subContent) use ($obj) {
            /*'extern_id' => '54',
              'id' => '57',
              'vorgang_id' => '1',
              'titel_id' => '162',
              'date' => '2018-10-15 00:00:00',
              'by_user' => NULL,
              'value' => '9000.00',
              'description' => NULL,
              'ok-hv' => 'Haushaltsverantwortliche/r',
              'ok-kv' => 'Kassenverantwortliche/r',
              'frist' => NULL,
              'flag_vorkasse' => '1',
              'flag_bewilligungsbescheid' => '0',
              'flag_pruefbescheid' => '0',
              'flag_rueckforderung' => '0',
              'flag_mahnung' => '0',
              'flag_done' => '0',
              'state_instructed' => 'done',
              'state_payed' => 'done',
              'state_booked' => 'Username;2019-02-24T15:34:52+01:00',
              'ref_file_id' => NULL,
              'flag_widersprochen' => '0',
              'widerspruch_date' => NULL,
              'widerspruch_file_id' => NULL,
              'widerspruch_text' => NULL,
              'etag' => 'bG90jIFqngQX5Lob5eISW7Y9Ikncm0Ao',*/
            $header = [
                'No',
                'Typ',
                'HHP-Titel',
                'Wert',
                'Zahlung',
                'Wiederspruch',
            ];

            $sum_value = 0;

            $escFun = [
                function ($extern_id, $vorgang_id) {
                    return "V$vorgang_id <a href='".URIBASE."/print/extern/$extern_id/vorgang/$vorgang_id'>".
                        "<i class='fa fa-fw fa-print'></i></a>";
                },
                function ($vorkasse, $bewilligung, $preuf, $rueck, $mahn) {
                    if ($vorkasse === '1') {
                        $str = 'Vorkasse';
                    } elseif ($bewilligung === '1') {
                        $str = 'Bewilligungsbescheid';
                    } elseif ($preuf === '1') {
                        $str = 'Prüfbescheid';
                    } elseif ($rueck === '1') {
                        $str = 'Rückforderungsbescheid';
                    } elseif ($mahn === '1') {
                        $str = 'Mahnung';
                    } else {
                        $str = '';
                    }

                    return $str;
                },
                function ($hhpId, $titelId, $titelName, $titelNr) {
                    return "<a title='$titelName' href='".URIBASE."/hhp/$hhpId/titel/$titelId' >HP$hhpId - ".
                        "$titelNr<i class='fa fa-fw fa-info' ></i></a>";
                },
                function ($value) use ($obj) {
                    return $obj->moneyEscapeFunction($value);
                },
                function ($value, $pruef, $rueck, $vorkasse) use ($obj, &$sum_value) {
                    if ($pruef === '1') {
                        $out_val = $value - $sum_value;
                        $sum_value = $value - $sum_value;
                    } elseif ($rueck === '1') {
                        $out_val = -$sum_value - $value;
                        $sum_value -= $value;
                    } elseif ($vorkasse === '1') {
                        $out_val = $value - $sum_value;
                        $sum_value = $value;
                    }

                    return $obj->moneyEscapeFunction($out_val);
                },
                function ($wiederspruch) {
                    return $wiederspruch === '1' ? 'Ja' : 'Nein';
                },
            ];
            $keys = [
                'id',
                'vorgang_id',

                'flag_vorkasse',
                'flag_bewilligungsbescheid',
                'flag_pruefbescheid',
                'flag_rueckforderung',
                'flag_mahnung',

                'hhp_id',
                'titel_id',
                'titel_name',
                'titel_nr',

                'value',

                'value',
                'flag_pruefbescheid',
                'flag_rueckforderung',
                'flag_vorkasse',

                'flag_widersprochen',
            ];

            $obj->renderAlert('Warnung', 'Die zweite Spalte könnte noch Rechenfehler beinhalten', 'warning');
            $obj->renderTable($header, [$subContent], $keys, $escFun);
            // return ??
        };

        // $this->renderAccordionPanels($test, $groupHeaderFun, $innerHeaderHeadlineFun, $innerHeaderFun, $innerContentFun);
        $this->renderAccordionPanels($extern_meta, $groupHeaderFun, $innerHeaderHeadlineFun, $innerHeaderFun, $innerContentFun);
        // $this->renderTable($header,[$extern],array_keys($header));
    }

    public function setOverviewTabs($active): void
    {
        $linkbase = URIBASE.'menu/';
        $tabs = [
            'mygremium' => "<i class='fa fa-fw fa-home'></i> Meine Gremien",
            'allgremium' => "<i class='fa fa-fw fa-globe'></i> Alle Gremien",
            // "mystuff" => "<i class='fa fa-fw fa-user-o'></i> Meine Anträge",
        ];
        if (AuthHandler::getInstance()->hasGroup('ref-finanzen')) {
            // $tabs["extern"] = "<i class='fa fa-fw fa-ticket'></i> Externe Anträge";
        }
        // $tabs["search"] = "<i class='fa fa-fw fa-search'></i> Suche";
        HTMLPageRenderer::setTabs($tabs, $linkbase, $active);
    }

    public function setTodoTabs($active): void
    {
        $linkbase = URIBASE.'menu/';
        $tabs = [
            'belege' => "<i class='fa fa-fw fa-folder-open-o'></i> Belege fehlen",
            'hv' => "<i class='fa fa-fw fa-legal'></i> Haushaltsverantwortliche*r",
            'kv' => "<i class='fa fa-fw fa-calculator'></i> Kassenverantwortliche*r",
        ];
        if (AuthHandler::getInstance()->hasGroup('ref-finanzen-kv')) {
            $tabs['kv/exportBank'] = "<i class='fa fa-fw fa-money'></i> Überweisungen";
        }
        // $tabs["search"] = "<i class='fa fa-fw fa-search'></i> Suche";
        HTMLPageRenderer::setTabs($tabs, $linkbase, $active);
    }

    private function renderSearch(): void
    {
        $this->renderAlert('Hinweis', 'Dieser Bereich befindet sich noch im Aufbau', 'info'); ?>
        <div class="input-group">
            <div class="input-group-addon"><i class="fa fa-fw fa-search"></i></div>
            <input class="form-control" placeholder="Suche ...">
        </div>
        <?php
    }

    public function renderMyProfile(): void
    {
        $user = DBConnector::getInstance()->getUser();
        $iban = $user['iban'] ?? ''; ?>

        <form id="editantrag" role="form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST"
              enctype="multipart/form-data" class="ajax">
            <?php $this->renderAlert('Hinweis', 'Dieses Formular funktioniert noch nicht :(', 'info'); ?>
            <div class="panel panel-default">
                <div class="panel-heading">Meine Daten aktualisieren</div>
                <div class="panel-body">
                    <input type="hidden" name="action" value="mykonto.update"/>
                    <?php $this->renderNonce(); ?>
                    <div class="form-group">
                        <label for="my-iban">Meine IBAN</label>
                        <input class="form-control" type="text" name="my-iban">
                    </div>
                    <div class="form-group">
                        <label for="my-adress">Meine Adresse</label>
                        <textarea class="form-control" name="my-adress"
                                  placeholder="Straße Nr&#10;98693 Ilmenau"></textarea>
                    </div>
                </div>
                <div class="panel-footer">
                    <a href="javascript:false;" class='btn btn-success submit-form validate pull-right'
                       data-name="iban"
                       data-value="" disabled="">Speichern</a>
                    <div class="clearfix"></div>
                </div>
            </div>
        </form>

        <?php
    }

    private function renderStuRaView(): void
    {
        $header = ['Projekte', 'Organisation', 'Projektbeginn'/* "Einnahmen", "Ausgaben" */];

        // TODO: also externe Anträge
        // $groups[] = ["name" => "Externe Anträge", "fields" => ["type" => "extern-express", "state" => "need-stura",]];
        [$header, $internContent, $escapeFunctions] = $this->fetchProjectsWithState('need-stura');
        [, $internContentHV] = $this->fetchProjectsWithState('ok-by-hv');
        $groups = [
            'Vom StuRa abzustimmen' => $internContent,
            'zur Verkündung (genehmigt von HV)' => $internContentHV,
        ];
        $this->renderHeadline('Projekte für die nächste StuRa Sitzung');
        $this->renderTable($header, $groups, [], $escapeFunctions);
    }

    /**
     * @return array [$header, $dbres, $escapeFunctions]
     */
    private function fetchProjectsWithState(string $statestring): array
    {
        $header = ['Projekt', 'Organisation', 'Einnahmen', 'Ausgaben', 'Projektbeginn'];
        $dbres = DBConnector::getInstance()->dbFetchAll(
            'projekte',
            [DBConnector::FETCH_NUMERIC],
            [
                'projekte.id',
                'createdat',
                'projekte.name',
                'org',
                'einnahmen' => ['projektposten.einnahmen', DBConnector::GROUP_SUM_ROUND2],
                'ausgaben' => ['projektposten.ausgaben', DBConnector::GROUP_SUM_ROUND2],
                'createdat',
            ],
            ['state' => $statestring],
            [['type' => 'inner', 'table' => 'projektposten', 'on' => ['projektposten.projekt_id', 'projekte.id']]],
            ['date-start' => true],
            ['projekte.id']
        );
        $escapeFunctionsIntern = [
            [$this, 'projektLinkEscapeFunction'],
            null,
            [$this, 'moneyEscapeFunction'],
            [$this, 'moneyEscapeFunction'],
            [$this, 'date2relstrEscapeFunction'],
        ];

        return [$header, $dbres, $escapeFunctionsIntern];
    }

    private function renderHVView(): void
    {
        // Projekte -------------------------------------------------------------------------------------------------
        [$headerIntern, $internWIP, $escapeFunctionsIntern] = $this->fetchProjectsWithState('wip');
        $groupsIntern['zu prüfende Projekte'] = $internWIP;

        // Auslagenerstattungen -------------------------------------------------------------------------------------
        [$headerAuslagen, $auslagenWIP, $escapeFunctionsAuslagen] = $this->fetchAuslagenWithState('wip', 'hv');
        $groupsAuslagen['Sachliche Richtigkeit der Auslagen prüfen'] = $auslagenWIP;

        // TODO: Implementierung vom rest
        // $groups[] = ["name" => "Externe Projekte für StuRa Situng vorbereiten", "fields" => ["type" => "extern-express", "state" => "draft"]];

        $this->setTodoTabs('hv');
        $this->renderTable($headerIntern, $groupsIntern, [], $escapeFunctionsIntern);
        $this->renderTable($headerAuslagen, $groupsAuslagen, [], $escapeFunctionsAuslagen);
    }

    /**
     * @param  string  $missingColumn  can be: hv, kv, belege
     * @return array [$header, $auslagen, $escapeFunctionAuslagen]
     */
    private function fetchAuslagenWithState(string $stateString, string $missingColumn): array
    {
        $headerAuslagen = ['Projekt', 'Abrechnung', 'Organisation', 'Einnahmen', 'Ausgaben', 'zuletzt geändert'];
        $auslagen = DBConnector::getInstance()->dbFetchAll(
            'auslagen',
            [DBConnector::FETCH_NUMERIC],
            [
                'projekte.id',
                'createdat',
                'name', // Projekte Link
                'projekte.id',
                'auslagen.id',
                'auslagen.name_suffix', // Auslagen Link
                'projekte.org', // Org
                'einnahmen' => ['beleg_posten.einnahmen', DBConnector::GROUP_SUM_ROUND2],
                'ausgaben' => ['beleg_posten.ausgaben', DBConnector::GROUP_SUM_ROUND2],
                'last_change',  // letzte änderung
            ],
            [
                'auslagen.state' => ['LIKE', "$stateString%"],
                "auslagen.ok-$missingColumn" => '',
            ],
            [
                ['table' => 'projekte', 'type' => 'inner', 'on' => ['projekte.id', 'auslagen.projekt_id']],
                ['table' => 'belege', 'type' => 'inner', 'on' => ['belege.auslagen_id', 'auslagen.id']],
                ['table' => 'beleg_posten', 'type' => 'inner', 'on' => ['belege.id', 'beleg_posten.beleg_id']],
            ],
            ['last_change' => true],
            ['auslagen.id']
        );
        $escapeFunctionsAuslagen = [
            [$this, 'projektLinkEscapeFunction'],
            [$this, 'auslagenLinkEscapeFunction'],
            null,
            [$this, 'moneyEscapeFunction'],
            [$this, 'moneyEscapeFunction'],
            [$this, 'date2relstrEscapeFunction'],
        ];

        return [$headerAuslagen, $auslagen, $escapeFunctionsAuslagen];
    }

    public function renderKVView(): void
    {
        // Auslagenerstattungen
        // $headerAuslagen = ["Projekt", "Erstattung", "Organisation", "zuletzt geändert"];

        [$headerAuslagen, $auslagenWIP, $escapeFunctionsAuslagen] = $this->fetchAuslagenWithState('wip', 'kv');

        // TODO: Implementierung vom rest
        // $groups[] = ["name" => "Externe Projekte für StuRa Situng vorbereiten", "fields" => ["type" => "extern-express", "state" => "draft"]];

        $this->setTodoTabs('kv');
        $this->renderTable($headerAuslagen, ['Rechnerische Richtigkeit der Auslagen prüfen' => $auslagenWIP], [], $escapeFunctionsAuslagen);

    }

    private function renderMissingBelege(): void
    {
        $this->setTodoTabs('belege');
        [$headerAuslagen, $auslagenWIP, $escapeFunctionsAuslagen] = $this->fetchAuslagenWithState('wip', 'belege');

        $this->renderTable($headerAuslagen, ['Belege fehlen' => $auslagenWIP], [], $escapeFunctionsAuslagen);

    }

    private function renderExportBank(): void
    {
        $this->setTodoTabs('kv/exportBank');
        $header = ['Abrechnung', 'Empfänger', 'IBAN', 'Verwendungszweck', 'Auszuzahlen'];
        $auslagen = DBConnector::getInstance()->dbFetchAll(
            'auslagen',
            [DBConnector::FETCH_NUMERIC],
            [
                'projekte.id',
                'auslagen.id',
                'auslagen.name_suffix', // Auslagenlink
                'auslagen.zahlung-name',
                'auslagen.zahlung-iban',
                'projekte.id',
                'projekte.createdat',
                'auslagen.id',
                'auslagen.zahlung-vwzk',
                'auslagen.name_suffix',
                'projekte.name', // verwendungszweck
                'ausgaben' => ['beleg_posten.ausgaben', DBConnector::GROUP_SUM_ROUND2],
                'einnahmen' => ['beleg_posten.einnahmen', DBConnector::GROUP_SUM_ROUND2],
            ],
            ['auslagen.state' => ['LIKE', 'ok%'], 'auslagen.payed' => ''],
            [
                ['type' => 'inner', 'table' => 'projekte', 'on' => ['projekte.id', 'auslagen.projekt_id']],
                ['type' => 'inner', 'table' => 'belege', 'on' => ['belege.auslagen_id', 'auslagen.id']],
                ['type' => 'inner', 'table' => 'beleg_posten', 'on' => ['beleg_posten.beleg_id', 'belege.id']],
            ],
            [],
            ['auslagen.id']
        );
        $obj = $this;
        $escapeFunctions = [
            [$this, 'auslagenLinkEscapeFunction'],                      // 3 Parameter
            null,                                                       // 1 Parameter
            function ($str) {
                $p = $str;
                if (! $p) {
                    return '';
                }
                $p = CryptoHandler::decrypt_by_key($p, $_ENV['IBAN_SECRET_KEY']);
                $p = CryptoHandler::unpad_string($p);

                return $p;
            },                                                       // 1 Parameter
            function ($pId, $pCreate, $aId, $vwdzweck, $aName, $pName) {  // 6 Parameter - Verwendungszweck
                $year = date('y', strtotime($pCreate));
                $ret = ["IP-$year-$pId-A$aId", $vwdzweck, $aName, $pName];
                $ret = array_filter(
                    $ret,
                    static function ($val) {
                        return ! empty(trim($val));
                    }
                );
                $ret = implode(' - ', $ret);
                if (strlen($ret) > 140) {
                    $ret = substr($ret, 0, 140);
                }

                return $ret;
            },
            function ($ausgaben, $einnahmen) use ($obj) {                 // 2 Parameter
                return $obj->moneyEscapeFunction((float) $ausgaben - (float) $einnahmen);
            },
        ];
        if (count($auslagen) > 0) {
            $this->renderTable($header, ['zu überweisen' => $auslagen], [], $escapeFunctions);
        } else {
            $this->renderHeadline('Aktuell liegen keine Überweisungen vor.', 2);
        }
    }
}
