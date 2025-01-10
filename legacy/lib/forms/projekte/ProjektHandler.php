<?php

namespace forms\projekte;

use App\Exceptions\LegacyDieException;
use App\Models\User;
use forms\chat\ChatHandler;
use forms\FormHandlerInterface;
use forms\FormTemplater;
use forms\projekte\auslagen\AuslagenHandler2;
use forms\projekte\exceptions\IllegalStateException;
use forms\projekte\exceptions\InvalidDataException;
use forms\projekte\exceptions\WrongVersionException;
use framework\auth\AuthHandler;
use framework\DBConnector;
use framework\render\HTMLPageRenderer;
use PDOException;

class ProjektHandler extends FormHandlerInterface
{
    private static $emptyData;

    private static $states;

    private static $stateChanges;

    private static $printModes;

    private static $visibleFields;

    private static $writePermissionAll;

    private static $writePermissionFields;

    private $templater;

    private $stateHandler;

    /**
     * @var PermissionHandler
     */
    private $permissionHandler;

    private $id;

    private $action;

    private $data;

    public function __construct($pathInfo)
    {
        // print_r($pathInfo);
        self::initStaticVars();
        if (! isset($pathInfo['action'])) {
            throw new LegacyDieException(400, 'Aktion nicht gesetzt');
        }
        $this->action = $pathInfo['action'];
        if ($this->action === 'create' || ! isset($pathInfo['pid'])) {
            $this->data = self::$emptyData;
            $stateNow = 'draft';
        } else {
            $this->id = $pathInfo['pid'];
            $res = DBConnector::getInstance()->dbFetchAll(
                'projekte',
                [DBConnector::FETCH_ASSOC],
                ['projekte.*'],
                ['projekte.id' => $this->id],
                [
                    ['type' => 'left', 'table' => 'user', 'on' => [['user.id', 'projekte.creator_id']]],
                ],
                ['version' => true]
            );
            if (! empty($res)) {
                $this->data = $res[0];
            } else {
                abort(404);
            }
            $tmp = DBConnector::getInstance()->dbFetchAll(
                'projektposten',
                [DBConnector::FETCH_ASSOC],
                [],
                ['projekt_id' => $this->id]
            );
            foreach ($tmp as $row) {
                $idx = $row['id'];
                $this->data['posten-name'][$idx] = $row['name'];
                $this->data['posten-bemerkung'][$idx] = $row['bemerkung'];
                $this->data['posten-einnahmen'][$idx] = $row['einnahmen'];
                $this->data['posten-ausgaben'][$idx] = $row['ausgaben'];
                $this->data['posten-titel'][$idx] = $row['titel_id'];
            }
            $stateNow = $this->data['state'];
        }

        $editMode = $this->action === 'create' || $this->action === 'edit';
        $owners = [
            'gremien' => $this->data['org'],
            'mail' => $this->data['responsible'].'@'.ORG_DATA['mail-domain'],
        ];
        $this->stateHandler = new StateHandler('projekte', self::$states, self::$stateChanges, [], [], $stateNow, $owners);
        $this->permissionHandler = new PermissionHandler(
            self::$emptyData,
            $this->stateHandler,
            self::$writePermissionAll,
            self::$writePermissionFields,
            self::$visibleFields,
            $editMode
        );
        $this->templater = new FormTemplater($this->permissionHandler);
    }

    public static function initStaticVars(): bool
    {
        if (isset(self::$states)) {
            return false;
        }
        self::$states = [
            'draft' => ['Entwurf'],
            'wip' => ['Beantragt', 'beantragen'],
            'ok-by-hv' => ['Genehmigt durch HV (nicht verkündet)'],
            'need-stura' => ['Warte auf StuRa-Beschluss'],
            'ok-by-stura' => ['Genehmigt durch StuRa-Beschluss'],
            'done-hv' => ['verkündet durch HV'],
            'done-other' => ['Genehmigt'],
            'revoked' => [
                'Abgelehnt / Zurückgezogen (KEINE Genehmigung oder Antragsteller verzichtet)',
                'zurückziehen / ablehnen',
            ],
            'terminated' => ['Abgeschlossen (keine weiteren Ausgaben)', 'beenden'],
        ];
        self::$stateChanges = [
            'draft' => [
                'wip' => ['owner' => true, 'groups' => ['ref-finanzen-belege']],
            ],
            'wip' => [
                'draft' => ['owner' => true, 'groups' => ['ref-finanzen-belege']],
                'need-stura' => ['groups' => ['ref-finanzen-hv']],
                'ok-by-hv' => ['groups' => ['ref-finanzen-hv']],
                'done-other' => ['groups' => ['ref-finanzen-hv']],
                'revoked' => ['owner' => true, 'groups' => ['ref-finanzen-belege']],
            ],
            'ok-by-hv' => [
                'done-hv' => ['groups' => ['ref-finanzen-hv']],
                'need-stura' => ['groups' => ['ref-finanzen-hv']],
            ],
            'need-stura' => [
                'ok-by-stura' => ['groups' => ['ref-finanzen-hv']],
                'ok-by-hv' => ['groups' => ['ref-finanzen-hv']],
                'revoked' => ['groups' => ['ref-finanzen-hv']],
            ],
            'done-hv' => [
                'terminated' => ['owner' => true, 'groups' => ['ref-finanzen-hv']],
            ],
            'done-other' => [
                'terminated' => ['owner' => true, 'groups' => ['ref-finanzen-hv']],
            ],
            'ok-by-stura' => [
                'terminated' => ['owner' => true, 'groups' => ['ref-finanzen-hv']],
            ],
            'revoked' => [
                'wip' => ['groups' => ['ref-finanzen-belege']],
                'draft' => ['owner' => true, 'groups' => ['ref-finanzen-belege']],
            ],
            'terminated' => [
                'done-hv' => ['owner' => true, 'groups' => ['ref-finanzen-hv']],
                'done-other' => ['owner' => true, 'groups' => ['ref-finanzen-hv']],
                'ok-by-stura' => ['owner' => true, 'groups' => ['ref-finanzen-hv']],
            ],
        ];
        self::$printModes = [
            'zahlungsanweisung' => [
                'title' => 'Titelseite drucken',
                'condition' => [
                    ['state' => 'draft', 'group' => 'ref-finanzen'],
                    ['state' => 'ok-by-stura', 'group' => 'ref-finanzen'],
                ],
            ],
        ];

        self::$emptyData = [
            'id' => '',
            'creator_id' => '',
            'createdat' => '',
            'lastupdated' => '',
            'version' => '1',
            'state' => 'draft',
            'stateCreator_id' => '',
            'name' => '',
            'responsible' => '',
            'org' => '',
            'org-mail' => '',
            'protokoll' => '',
            'beschreibung' => '',
            'recht' => '',
            'recht-additional' => '',
            'posten-name' => [1 => ''],
            'posten-bemerkung' => [1 => ''],
            'posten-titel' => [1 => ''],
            'posten-einnahmen' => [1 => 0],
            'posten-ausgaben' => [1 => 0],
            'date-start' => '',
            'date-end' => '',
        ];
        self::$visibleFields = [
            'recht' => [
                'wip',
                'ok-by-hv',
                'need-stura',
                'ok-by-stura',
                'done-hv',
                'done-other',
                'terminated',
            ],
            'posten-titel' => [
                'wip',
                'ok-by-hv',
                'need-stura',
                'ok-by-stura',
                'done-hv',
                'done-other',
                'terminated',
            ],
            'createdat' => [
                'wip',
                'ok-by-hv',
                'need-stura',
                'ok-by-stura',
                'done-hv',
                'done-other',
                'terminated',
            ],
        ];
        self::$writePermissionAll = [
            'draft' => ['groups' => ['login']],
            'wip' => ['groups' => ['ref-finanzen-hv']],
            'ok-by-hv' => ['groups' => ['ref-finanzen-hv']],
            'need-stura' => ['groups' => ['ref-finanzen-hv']],
            'ok-by-stura' => ['groups' => ['ref-finanzen-hv']],
            'done-hv' => ['groups' => ['ref-finanzen-hv']],
            'done-other' => ['groups' => ['ref-finanzen-hv']],
            'terminated' => [],
            'revoked' => [],
        ];
        self::$writePermissionFields = [
            'ok-by-hv' => [
                'recht-additional' => ['groups' => ['ref-finanzen-hv']],
            ],
        ];

        return true;
    }

    /**
     * @throws InvalidDataException
     * @throws PDOException
     */
    public static function createNewProjekt($data): ProjektHandler
    {
        $maxRows = max(
            count($data['posten-name']),
            count($data['posten-bemerkung']),
            count($data['posten-einnahmen']),
            count($data['posten-ausgaben'])
        );
        $minRows = min(
            count($data['posten-name']),
            count($data['posten-bemerkung']),
            count($data['posten-einnahmen']),
            count($data['posten-ausgaben'])
        );

        if ($maxRows !== $minRows) {
            throw new InvalidDataException('Projekt-Zeilen ungleichmäßig übertragen');
        }

        $user_id = DBConnector::getInstance()->getUser()['id'];
        $projekt_id = DBConnector::getInstance()->dbInsert(
            'projekte',
            [
                'creator_id' => $user_id,
                'createdat' => date('Y-m-d H:i:s'),
                'lastupdated' => date('Y-m-d H:i:s'),
                'version' => 1,
                'state' => 'draft',
                'stateCreator_id' => $user_id,
                'name' => $data['name'],
                'responsible' => $data['responsible'],
                'org' => $data['org'],
                'org-mail' => $data['org-mail'] ?? '',
                'protokoll' => $data['protokoll'] ?? '',
                'beschreibung' => $data['beschreibung'],
                'date-start' => $data['date-start'],
                'date-end' => $data['date-end'],
            ]
        );

        for ($i = 0; $i < $minRows - 1; $i++) {
            if ((float) $data['posten-ausgaben'][$i] > 0 && (float) $data['posten-einnahmen'][$i] > 0) {
                throw new InvalidDataException(
                    'Projektposten dürfen nicht gleichzeitig Einnahmen und Ausgaben enthalten.'
                );
            }
            DBConnector::getInstance()->dbInsert(
                'projektposten',
                [
                    'id' => $i + 1,
                    'projekt_id' => $projekt_id,
                    'einnahmen' => DBConnector::getInstance()->convertUserValueToDBValue(
                        $data['posten-einnahmen'][$i],
                        'money'
                    ),
                    'ausgaben' => DBConnector::getInstance()->convertUserValueToDBValue(
                        $data['posten-ausgaben'][$i],
                        'money'
                    ),
                    'name' => $data['posten-name'][$i],
                    'bemerkung' => $data['posten-bemerkung'][$i],
                ]
            );
        }

        return new ProjektHandler(['pid' => $projekt_id, 'action' => 'none']);
    }

    public static function getStateStringFromName($statename)
    {
        self::initStaticVars();

        return self::$states[$statename][0];
    }

    /**
     * @throws PDOException
     * @throws WrongVersionException
     * @throws InvalidDataException
     */
    public function updateSavedData($data): bool
    {
        $data = array_intersect_key($data, self::$emptyData);
        $version = (int) $data['version'];

        // check if version is the same
        if (((int) $version) !== ((int) $this->data['version'])) {
            throw new WrongVersionException('Projekt wurde zwischenzeitlich schon von jemand anderem bearbeitet!');
        }
        // check if row count is everywhere the same
        $maxRows = $minRows = 0;
        if (isset($data['posten-name'], $data['posten-bemerkung'], $data['posten-einnahmen'], $data['posten-ausgaben'])) {
            $maxRows = max(
                count($data['posten-name']),
                count($data['posten-bemerkung']),
                count($data['posten-einnahmen']),
                count($data['posten-ausgaben'])
            );
            $minRows = min(
                count($data['posten-name']),
                count($data['posten-bemerkung']),
                count($data['posten-einnahmen']),
                count($data['posten-ausgaben'])
            );
        }
        // wenn posten-titel nicht mit übertragen setze dummy an seine stelle
        if (! isset($data['posten-titel'])) {
            $data['posten-titel'] = array_fill(0, $maxRows, null);
        }

        // wenn anzahl der rows nicht identisch -> error
        if ($maxRows !== $minRows || count($data['posten-titel']) !== $minRows) {
            throw new InvalidDataException('Projekt-Zeilen ungleichmäßig übertragen');
        }
        // remove some Autogenerated values
        $generatedFields = [
            'id' => $this->id,
            'lastupdated' => date('Y-m-d H:i:s'),
            'version' => ($this->data['version'] + 1),
        ];
        // extract some fields for other db destination
        $extractFields = ['posten-name', 'posten-bemerkung', 'posten-einnahmen', 'posten-ausgaben', 'posten-titel'];
        $extractFields = array_intersect_key($data, array_flip($extractFields));
        $data = array_diff_key($data, $generatedFields, $extractFields);
        $recht_unset = false;
        if (isset($data['recht-additional'])) {
            if (! isset($data['recht']) && isset($this->data['recht'])) {
                $data['recht'] = $this->data['recht'];
                $recht_unset = true;
            }
            if (! isset($data['recht'])) {
                $data['recht-additional'] = '';
            } elseif (isset($data['recht-additional'][$data['recht']])) {
                $data['recht-additional'] = $data['recht-additional'][$data['recht']];
            } else {
                $data['recht-additional'] = '';
            }
        }

        if ($recht_unset) {
            unset($data['recht']);
        }

        // check if fields editable
        $fields = $generatedFields;
        foreach ($data as $name => $content) {
            if ($this->permissionHandler->isEditable($name) && $this->permissionHandler->isVisibleField($name)) {
                if (! empty($content)) {
                    $fields[$name] = $content;
                } else {
                    $fields[$name] = null;
                }
            } else {
                throw new LegacyDieException(403, "Du hast keine Berechtigung '$name' zu schreiben.");
            }
        }
        $retMetaUpdate = DBConnector::getInstance()->dbUpdate(
            'projekte',
            ['id' => $this->id, 'version' => $version],
            $fields
        ) === 1;

        if ($this->permissionHandler->isEditable(
            ['posten-name', 'posten-bemerkung', 'posten-einnahmen', 'posten-ausgaben'],
            'and'
        )) {
            // update old posten, create new, delete old
            $oldRows = count($this->data['posten-name']);

            // update old posten (last minrow is empty all the time
            $retUpdate = true;
            for ($i = 0; $i < $minRows - 1 && $i < $oldRows; $i++) {
                // would throw exception if not working
                $rowsUpdated = DBConnector::getInstance()->dbUpdate(
                    'projektposten',
                    [
                        'id' => $i + 1,
                        'projekt_id' => $this->id,
                    ],
                    [
                        'titel_id' => $extractFields['posten-titel'][$i] === '' ? null : $extractFields['posten-titel'][$i],
                        'einnahmen' => DBConnector::getInstance()->convertUserValueToDBValue(
                            $extractFields['posten-einnahmen'][$i],
                            'money'
                        ),
                        'ausgaben' => DBConnector::getInstance()->convertUserValueToDBValue(
                            $extractFields['posten-ausgaben'][$i],
                            'money'
                        ),
                        'name' => $extractFields['posten-name'][$i],
                        'bemerkung' => $extractFields['posten-bemerkung'][$i],
                    ],
                );
                // could return row count = 0 (if no changes happened)
                $retUpdate = $retUpdate && ($rowsUpdated <= 1);
            }

            // add new posten
            $retInsert = true;
            for ($i = $oldRows; $i < $minRows - 1; $i++) {
                $retInsert = $retInsert && (DBConnector::getInstance()->dbInsert(
                    'projektposten',
                    [
                        'id' => $i + 1,
                        'projekt_id' => $this->id,
                        'titel_id' => $extractFields['posten-titel'][$i] === '' ? null : $extractFields['posten-titel'][$i],
                        'einnahmen' => DBConnector::getInstance()->convertUserValueToDBValue(
                            $extractFields['posten-einnahmen'][$i],
                            'money'
                        ),
                        'ausgaben' => DBConnector::getInstance()->convertUserValueToDBValue(
                            $extractFields['posten-ausgaben'][$i],
                            'money'
                        ),
                        'name' => $extractFields['posten-name'][$i],
                        'bemerkung' => $extractFields['posten-bemerkung'][$i],
                    ]
                )) === '0'; // lastInsertedId returns "0" due to auto increment is not used (multikey)
            }
            // delete old ones
            $retDelete = true;
            if ($minRows - 1 < $oldRows) {
                $retDelete = DBConnector::getInstance()->dbDelete(
                    'projektposten',
                    [
                        'id' => ['>', $minRows - 1],
                        'projekt_id' => $this->id,
                    ]
                );
                $retDelete = $retDelete > 0;
            }

            return $retMetaUpdate && $retDelete && $retInsert && $retUpdate;
        }

        return $retMetaUpdate;
    }

    /**
     * @throws IllegalStateException
     */
    public function setState($stateName): bool
    {
        if (! in_array($stateName, $this->getNextPossibleStates(), true)) {
            throw new IllegalStateException("In den Status $stateName kann nicht gewechselt werden");
        }

        $user_id = DBConnector::getInstance()->getUser()['id'];
        DBConnector::getInstance()->dbUpdate(
            'projekte',
            ['id' => $this->id, 'version' => $this->data['version']],
            [
                'state' => $stateName,
                'stateCreator_id' => $user_id,
                'lastupdated' => date('Y-m-d H:i:s'),
                'version' => ($this->data['version'] + 1),
            ]
        );
        $chat = new ChatHandler('projekt', $this->id);
        $chat->_createComment(
            'projekt',
            $this->id,
            date_create()->format('Y-m-d H:i:s'),
            'system',
            '',
            self::$states[$this->data['state']][0].' -> '.self::$states[$stateName][0],
            1
        );
        $this->stateHandler->transitionTo($stateName);

        return true;
    }

    public function getNextPossibleStates(): array
    {
        return $this->stateHandler->getNextStates(true);
    }

    public function render(): void
    {
        if ($this->action === 'create' || ! isset($this->id)) {
            $this->renderProjekt('neues Projekt anlegen');

            return;
        }

        switch ($this->action) {
            case 'edit':
                $this->renderBackButton();
                $this->renderProjekt('Projekt bearbeiten');
                break;
            case 'view':
                $this->renderInteractionPanel();
                // echo $this->templater->getStateChooser($this->stateHandler);
                $this->renderProjekt('Projekt '.$this->id);
                $this->render_chat_box();
                $this->renderProjektSizeGrafic();
                $this->renderAuslagenList();
                break;
            default:
                throw new LegacyDieException(404, "Aktion: $this->action bei Projekt $this->id nicht bekannt.");
                break;
        }
    }

    private function renderProjekt($title): void
    {
        $auth = (AUTH_HANDLER);
        /* @var $auth AuthHandler */
        $auth = $auth::getInstance();
        $validateMe = false;
        $editable = $this->permissionHandler->isAnyDataEditable();
        // build dropdowns
        $selectable_gremien = FormTemplater::generateGremienSelectable();
        $selectable_gremien['values'] = $this->data['org'];

        $mailingLists = $auth->hasGroup('ref-finanzen') ? MAILINGLISTS : AuthHandler::getInstance()->getUserMailinglists();
        $selectable_mail = FormTemplater::generateSelectable($mailingLists);
        $selectable_mail['values'] = $this->data['org-mail'];

        $sel_recht = FormTemplater::generateSelectable(array_combine(
            array_keys(ORG_DATA['rechtsgrundlagen']),
            array_map(static function ($val) {
                return $val['label'];
            }, ORG_DATA['rechtsgrundlagen'])
        ));
        $sel_recht['values'] = $this->data['recht'];
        if (isset($this->data['createdat']) && ! empty($this->data['createdat'])) {
            $createDate = $this->data['createdat'];
        } else {
            $createDate = date_create()->format('Y-m-d');
        }
        $hhpId = DBConnector::getInstance()->dbFetchAll(
            'haushaltsplan',
            [DBConnector::FETCH_ASSOC],
            ['id'],
            [
                ['von' => ['<=', $createDate], 'bis' => ['>=', $createDate], 'state' => 'final'],
                ['von' => ['<=', $createDate], 'bis' => ['is', null], 'state' => 'final'],
            ]
        );
        if (empty($hhpId)) {
            throw new LegacyDieException(400, 'HHP-id kann nicht ermittelt werden. Bitte benachrichtigen sie den Administrator');
        }
        $hhpId = $hhpId[0]['id'];
        $selectable_titel = FormTemplater::generateTitelSelectable($hhpId); ?>
        <div class='col-xs-12 col-md-10'>
            <?php
            if ($editable) { ?>
            <form role="form" action="<?php echo URIBASE.'rest/forms/projekt'; ?>" method="POST"
                  enctype="multipart/form-data" class="ajax">
                <?php echo $this->templater->getHiddenActionInput(isset($this->id) ? 'update' : 'create'); ?>
                <input type="hidden" name="nonce" value="<?= csrf_token() ?>">
                <input type="hidden" name="version" value="<?php echo $this->data['version']; ?>">
                <?php if (isset($this->id)) { ?>
                    <input type="hidden" name="id" value="<?php echo $this->id; ?>">
                <?php } ?>
                <?php } // endif editable?>
                <?php if ($this->permissionHandler->isVisibleField('recht')) { ?>
                    <h2>Genehmigung</h2>
                    <div class="well">
                        <div class="hide-wrapper">
                            <div class="hide-picker">
                                <?php echo $this->templater->getDropdownForm(
                                    'recht',
                                    $sel_recht,
                                    6,
                                    'Wähle Rechtsgrundlage...',
                                    'Rechtsgrundlage',
                                    ['required'],
                                    false
                                ); ?>
                            </div>
                            <div class="hide-items">
                                <?php foreach (ORG_DATA['rechtsgrundlagen'] as $shortName => $def) { ?>
                                    <div id="<?php echo $shortName; ?>" class="form-group" style="display: none;">
                                        <?php if (isset($def['placeholder'], $def['label-additional'])) {
                                            echo $this->templater->getTextForm(
                                                "recht-additional[$shortName]",
                                                $this->data['recht-additional'],
                                                4,
                                                $def['placeholder'] ?? '',
                                                $def['label-additional'] ?? 'Zusatzinformationen',
                                                []
                                            );
                                        } ?>
                                        <span class="col-xs-12"><?php echo $def['hint-text'] ?? ''; ?></span>
                                    </div>
                                    <?php
                                }
                    ?>
                            </div>
                        </div>
                        <div class='clearfix'></div>
                    </div>
                <?php } ?>
                <h2><?php echo $title; ?></h2>
                <div class="well">
                    <?php echo $this->templater->getTextForm(
                        'name',
                        $this->data['name'],
                        6,
                        '',
                        'Projektname',
                        ['required']
                    ); ?>
                    <?php echo $this->templater->getMailForm(
                        'responsible',
                        $this->data['responsible'],
                        6,
                        'vorname.nachname',
                        'Projektverantwortlich (Mail)',
                        ['required', 'email'],
                        '@'.ORG_DATA['mail-domain']
                    ); ?>
                    <div class="clearfix"></div>
                    <?php echo $this->templater->getDropdownForm(
                        'org',
                        $selectable_gremien,
                        6,
                        'Wähle Gremium ...',
                        'Organisation',
                        ['required'],
                        true
                    ); ?>
                    <?php if (count(ORG_DATA['mailinglists']) > 0) {
                        echo $this->templater->getDropdownForm(
                            'org-mail',
                            $selectable_mail,
                            6,
                            'Wähle Mailingliste ...',
                            'Organisations-Mail',
                            ['required'],
                            true
                        );
                    } ?>
                    <?php
                    if (! in_array('hide-protokoll', ORG_DATA['projekt-form'], true)) {
                        echo $this->templater->getWikiLinkForm(
                            'protokoll',
                            $this->data['protokoll'],
                            12,
                            '...',
                            'Beschluss / Protokoll-Link',
                            ['required'],
                            ORG_DATA['projekt-form']['protokoll-prefix'] ?? ''
                        );
                    } ?>
                    <?php echo $this->templater->getDatePickerForm(
                        ['date-start', 'date-end'],
                        [$this->data['date-start'], $this->data['date-end']],
                        12,
                        ['Projekt-Start', 'Projekt-Ende'],
                        'Projektzeitraum',
                        ['required'],
                        true,
                        'today'
                    ); ?>
                    <?php echo $this->templater->getDatePickerForm(
                        'createdat',
                        $this->data['createdat'],
                        12,
                        '',
                        'Projekt erstellt am'
                    ); ?>

                    <div class='clearfix'></div>
                </div>
                <?php $tablePartialEditable = $this->permissionHandler->isEditable(
                    ['posten-name', 'posten-bemerkung', 'posten-einnahmen', 'posten-ausgaben'],
                    'and'
                ); ?>
                <table class="table table-striped summing-table <?php echo $tablePartialEditable ? 'dynamic-table' : 'dynamic-table-readonly'; ?>">
                    <thead>
                    <tr>
                        <th></th><!-- Nr.       -->
                        <th></th><!-- Trashbin  -->
                        <th class="">Ein/Ausgabengruppe</th>
                        <th class="">Bemerkung</th>
                        <th class=""><?php echo $this->permissionHandler->isVisibleField('posten-titel') ? 'Titel' : ''; ?></th>
                        <th class="col-xs-2">Einnahmen</th>
                        <th class="col-xs-2">Ausgaben</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    $this->data['posten-name'][] = '';

        foreach ($this->data['posten-name'] as $row_nr => $null) {
            $new_row = ($row_nr) === count($this->data['posten-name']);
            if ($new_row && ! $tablePartialEditable) {
                continue;
            }
            $sel_titel = $selectable_titel;
            if (isset($this->data['posten-titel'][$row_nr])) {
                $sel_titel['values'] = $this->data['posten-titel'][$row_nr];
            } ?>
                        <tr class="<?php echo $new_row ? 'new-table-row' : 'dynamic-table-row'; ?>">
                            <td class="row-number"> <?php echo $row_nr; ?>.</td>
                            <?php if ($tablePartialEditable) { ?>
                                <td class='delete-row'><a href='' class='delete-row'><i
                                            class='fa fa-fw fa-trash'></i></a></td>
                            <?php } else {
                                echo '<td></td>';
                            } ?>
                            <td><?php echo $this->templater->getTextForm(
                                'posten-name[]',
                                ! $new_row ? $this->data['posten-name'][$row_nr] : '',
                                null,
                                'Name des Postens',
                                '',
                                ['required']
                            ); ?></td>
                            <td><?php echo $this->templater->getTextForm(
                                'posten-bemerkung[]',
                                ! $new_row ? $this->data['posten-bemerkung'][$row_nr] : '',
                                null,
                                'optional',
                                '',
                                []
                            ); ?></td>
                            <td><?php echo $this->templater->getDropdownForm(
                                'posten-titel[]',
                                $sel_titel,
                                null,
                                'HH-Titel',
                                '',
                                [],
                                true
                            ); ?></td>
                            <td><?php echo $this->templater->getMoneyForm(
                                'posten-einnahmen[]',
                                ! $new_row ? $this->data['posten-einnahmen'][$row_nr] : 0,
                                null,
                                '',
                                '',
                                ['required'],
                                'einnahmen'
                            ); ?></td>
                            <td><?php echo $this->templater->getMoneyForm(
                                'posten-ausgaben[]',
                                ! $new_row ? $this->data['posten-ausgaben'][$row_nr] : 0,
                                null,
                                '',
                                '',
                                ['required'],
                                'ausgaben'
                            ); ?></td>
                        </tr>
                        <?php
        } ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th></th><!-- Nr.       -->
                        <th></th><!-- Trashbin  -->
                        <th></th><!-- Name      -->
                        <th></th><!-- Bemerkung -->
                        <th></th><!-- Titel -->
                        <th class="dynamic-table-cell cell-has-printSum">
                            <div class="form-group no-form-grp">
                                <div class="input-group input-group-static">
                                    <span class="input-group-addon">Σ</span>
                                    <div class="form-control-static nowrap text-right"
                                         data-printsum="einnahmen">0,00
                                    </div>
                                    <span class="input-group-addon">€</span>
                                </div>
                            </div>
                        </th><!-- einnahmen -->
                        <th class="dynamic-table-cell cell-has-printSum">
                            <div class="form-group no-form-grp">
                                <div class="input-group input-group-static">
                                    <span class="input-group-addon">Σ</span>
                                    <div class="form-control-static nowrap text-right"
                                         data-printsum="ausgaben">0,00
                                    </div>
                                    <span class="input-group-addon">€</span>
                                </div>
                            </div>
                        </th><!-- ausgaben -->
                    </tr>
                    </tfoot>
                </table>
                <?php echo $this->templater->getTextareaForm(
                    'beschreibung',
                    $this->data['beschreibung'],
                    12,
                    "In unserem Projekt geht es um ... \nHat einen Nutzen für die Studierendenschaft weil ... \nFindet dort und dort statt...\nusw.",
                    'Projektbeschreibung',
                    ['required', 'min-length' => 100],
                    5
                ); ?>

                <?php if ($editable) { ?>
                <!-- do not name it "submit": http://stackoverflow.com/questions/3569072/jquery-cancel-form-submit-using-return-false -->
                <div class="pull-right">
                    <?php

                    // foreach ($proposeNewState as $state){
                    // $isEditable = hasPermission($form, ["state" => $state], "canEdit");
                    // $stateTxt = "Entwurf";
                    // $state = "draft";

                    ?>
                    <button type="submit"
                            class='btn btn-success submit-form <?php echo ! $validateMe ? 'no-validate' : 'validate'; ?>'
                            data-name="state" data-value="<?php echo htmlspecialchars($this->stateHandler->getActualState()); ?>"
                            id="state-<?php echo htmlspecialchars($this->stateHandler->getActualState()); ?>">Speichern
                        als <?php echo htmlspecialchars($this->stateHandler->getFullStateName()); ?></button>
                </div>
            </form>
        <?php } ?>
        </div><!-- main-container -->
        <?php
    }

    private function renderBackButton(): void
    {
        ?>
        <div class="">
            <a href="./">
                <button class="btn btn-primary"><i class="fa fa-fw fa-arrow-left"></i>&nbsp;Zurück</button>
            </a>
        </div>
        <?php
    }

    private function renderInteractionPanel(): void
    {
        $url = str_replace('//', '/', URIBASE.'projekt/'.$this->id.'/');
        $nextValidStates = $this->stateHandler->getNextStates(true);
        $disabledStates = array_diff($this->stateHandler->getAllAllowedTransitionableStates(), $nextValidStates); ?>
        <div>
            <ul class="nav nav-pills nav-stacked navbar-right navbar-fixed-right">
                <li class="label-info">
                    <?php echo htmlspecialchars($this->stateHandler->getFullStateName()); ?>
                </li>

                <?php if (count($nextValidStates) > 0) { ?>
                    <li><a href="#" data-toggle="modal" data-target="#editStateModal">Status ändern <i
                                class="fa fa-fw fa-refresh"></i></a></li>
                <?php } ?>
                <?php if (in_array($this->stateHandler->getActualState(), ['ok-by-stura', 'done-hv', 'done-other'])) { ?>
                    <li><a href="<?php echo $url; ?>auslagen" title="Neue Abrechnung/Rechnung">neue Abrechnung&nbsp;<i
                                class="fa fa-fw fa-plus" aria-hidden="true"></i></a></li>
                <?php } ?>
                <?php if ($this->permissionHandler->isAnyDataEditable(true) !== false) { ?>
                    <li><a href="<?php echo $url; ?>edit" title="Bearbeiten">Bearbeiten&nbsp;<i
                                class="fa fa-fw fa-pencil" aria-hidden="true"></i></a></li>
                <?php } ?>
                <li><a href="#" data-toggle="modal" data-target="#projekt-delete-dlg">Projekt löschen&nbsp;<i
                            class="fa fa-fw fa-trash"></i></a></li>

                <!--<li><a href="<?php echo ''; ?>" title="Drucken"><i class="fa fa-fw fa-print" aria-hidden="true"></i></a></li> -->
                <!--<li><a href="<?php echo ''; ?>" title="Exportieren"><i class="fa fa-fw fa-download" aria-hidden="true"></i></a></li>-->

                <!-- FIXME LIVE COMMENT ONLY
                <li><a href="<?php echo $url; ?>history" title="Verlauf">Historie <i class="fa fa-fw fa-history"
                                                                             aria-hidden="true"></i></a></li>
                <li><a href="<?php echo $url; ?>delete">Antrag löschen <i class="fa fa-trash" aria-hidden="true"></i></a></li>
                <li><a href="https://wiki.stura.tu-ilmenau.de/leitfaden/finanzenantraege">Hilfe
                        <i class="fa fa-question" aria-hidden="true"></i></a></li> -->
            </ul>
        </div>
        <?php if (count($nextValidStates) > 0) { ?>
        <!-- Modal Zustandsübergang zu anderem State -->
        <form id="stateantrag" role="form" action="<?php echo URIBASE.'rest/forms/projekt'; ?>"
              method="POST" enctype="multipart/form-data" class="ajax" data-toggle="validator">
            <div class="modal fade" id="editStateModal" tabindex="-1" role="dialog"
                 aria-labelledby="editStateModalLabel">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="editStateModalLabel">Status wechseln</h4>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="changeState">
                            <input type="hidden" name="nonce" value="<?= csrf_token() ?>">
                            <input type="hidden" name="version" value="<?php echo $this->data['version']; ?>">
                            <input type="hidden" name="id" value="<?php echo $this->getID(); ?>">
                            <div class="form-group">
                                <label for="newantragstate">Neuer Bearbeitungsstatus</label>
                                <select class="selectpicker form-control" name="newState" size="1"
                                        title="Neuer Bearbeitungsstatus" required="required" id="newantragstate">
                                    <optgroup label="Statuswechsel möglich">
                                        <?php
                                        foreach ($nextValidStates as $state) {
                                            echo '<option value="'.htmlspecialchars(
                                                $state
                                            ).'">'.htmlspecialchars(
                                                $this->stateHandler->getFullStateNameFrom($state)
                                            ).'</option>'.PHP_EOL;
                                        }
            ?>
                                    </optgroup>
                                    <optgroup label="Daten unvollständig">
                                        <?php

            foreach ($disabledStates as $state) {
                echo '<option disabled>'.$this->stateHandler->getFullStateNameFrom(
                    $state
                ).'</option>'.PHP_EOL;
            }
            ?>
                                    </optgroup>
                                </select>
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" name="absenden" class="btn btn-primary pull-right">Speichern
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php }
        $hasPermission = $this->isOwner() || AuthHandler::getInstance()->hasGroup('ref-finanzen-hv');
        $hasNoAbrechnungen = $this->hasAuslagen() === false;
        $permissionIcon = $hasPermission ? 'fa-check' : 'fa-ban';
        $abrechnungIcon = $hasNoAbrechnungen ? 'fa-check' : 'fa-ban';
        ?>
        <form action="<?= route('legacy.project.delete', ['project_id' => $this->id]) ?>" method="POST">
            <input type="hidden" name="nonce" value="<?= csrf_token() ?>">
            <?php
            HTMLPageRenderer::injectModal(
                'projekt-delete',
                "<div class='js-head'>Wirklich Löschen?</div>",
                "Dieses Projekt kann endgültig gelöscht werden wenn,
            <ul>
                <li>du Projektersteller*in oder Haushaltsverantwortliche*r bist <i class='fa $permissionIcon'></i></li>
                <li>im Projekt keine Abrechnungen (mehr) vorhanden sind <i class='fa $abrechnungIcon'></i></li>
            </ul>
            Wenn das Projekt gelöscht wird, werden alle Daten dazu entfernt und können nicht wieder hergestellt werden.
            ",
                'Abbrechen',
                'Unwiderruflich Löschen',
                'danger',
                canConfirm: function () use ($hasPermission, $hasNoAbrechnungen) {
                    return $hasPermission && $hasNoAbrechnungen;
                },
                actionButtonType: 'submit'
            );
        ?>
        </form>
        <?php
    }

    public function getID(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return User::find((int) $this->data['creator_id']);
    }

    public function isOwner(): bool
    {
        return isset($this->data['creator_id']) && \Auth::user()->id === $this->data['creator_id'];
    }

    private function render_chat_box(): void
    { ?>
        <div class='clearfix'></div>
        <div class="col-xs-12 col-md-10" id="projektchat">
            <?php
            $auth = (AUTH_HANDLER);
        /* @var $auth AuthHandler */
        $auth = $auth::getInstance();
        $btns = [];
        $pdate = date_create(substr($this->data['createdat'], 0, 4).'-01-01 00:00:00');
        $pdate->modify('+1 year');
        $now = date_create();
        // allow chat only 90 days into next year
        if ($now->getTimestamp() - $pdate->getTimestamp() <= 86400 * 90) {
            $btns[] = ['label' => 'Senden', 'color' => 'success', 'type' => '0'];
            /*
            if ($auth->hasGroup('ref-finanzen') || $auth->getUsername() === $this->data['username']) {
                $btns[] = [
                    'label' => 'Private Nachricht',
                    'color' => 'warning',
                    'type' => '-1',
                    'hover-title' => 'Private Nachricht zwischen Ref-Finanzen und dem Projekt-Ersteller'
                ];
            }
            */
            if ($auth->hasGroup('ref-finanzen')) {
                $btns[] = ['label' => 'Finanz Nachricht', 'color' => 'primary', 'type' => '3'];
            }
            if ($auth->hasGroup('admin')) {
                $btns[] = ['label' => 'Admin Nachricht', 'color' => 'danger', 'type' => '2'];
            }
        }
        ChatHandler::renderChatPanel(
            'projekt',
            $this->id,
            $auth->getUserFullName().' ('.$auth->getUsername().')',
            $btns
        ); ?>
        </div>
        <?php
    }

    private function renderProjektSizeGrafic(): void
    {
        /* echo '<div class="clearfix"></div>' . PHP_EOL;
        $ah = new AuslagenHandler2(['pid' => $this->id, 'action' => 'view']);
        $ah->render_auslagen_beleg_diagrams('Nice Diagrams'); */

    }

    private function renderAuslagenList(): void
    { ?>
        <div class="clearfix"></div>
        <div id='projekt-well' class="well col-xs-12 col-md-10">
            <?php
            $ah = new AuslagenHandler2(['pid' => $this->id, 'action' => 'view']);
        $ah->render_project_auslagen(true); ?>
        </div>
        <?php
    }

    private function hasAuslagen(): bool
    {
        return \DB::table('auslagen')->where('projekt_id', $this->id)->count() > 0;
    }
}
