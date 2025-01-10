<?php

/**
 * FRAMEWORK JsonHandler
 *
 * @category          framework
 *
 * @author            michael g
 * @author            Stura - Referat IT <ref-it@tu-ilmenau.de>
 *
 * @since             17.02.2018
 *
 * @copyright         Copyright (C) 2018 - All rights reserved
 *
 * @platform          PHP
 *
 * @requirements      PHP 7.0 or higher
 */

namespace forms;

use App\Exceptions\LegacyDieException;
use App\Models\Legacy\BankTransaction;
use booking\BookingTableManager;
use booking\HHPHandler;
use booking\konto\FintsConnectionHandler;
use booking\konto\HibiscusXMLRPCConnector;
use Exception;
use forms\chat\ChatHandler;
use forms\projekte\auslagen\AuslagenHandler2;
use forms\projekte\exceptions\ActionNotSetException;
use forms\projekte\exceptions\IdNotSetException;
use forms\projekte\exceptions\IllegalStateException;
use forms\projekte\exceptions\IllegalTransitionException;
use forms\projekte\exceptions\InvalidDataException;
use forms\projekte\exceptions\WrongVersionException;
use forms\projekte\ProjektHandler;
use framework\auth\AuthHandler;
use framework\DBConnector;
use framework\render\ErrorHandler;
use framework\render\EscFunc;
use framework\render\JsonController;
use framework\Validator;
use PDOException;

class RestHandler extends EscFunc
{
    // ================================================================================================

    public function handlePost(?array $routeInfo = null): void
    {
        if (! \App::runningUnitTests()) {
            if (! isset($_POST['nonce']) || $_POST['nonce'] !== csrf_token() || isset($_POST['nononce'])) {
                throw new LegacyDieException(400, 'Das Formular ist nicht gültig, bitte lade die Seite neu');
            }
        }
        unset($_POST['nonce']);

        switch ($routeInfo['action']) {
            case 'projekt':
                $this->handleProjekt($routeInfo);
                break;
            case 'auslagen':
                $this->handleAuslagen($routeInfo);
                break;
            case 'extern':
                $this->handleExtern($routeInfo);
                break;
            case 'chat':
                $this->handleChat($routeInfo);
                break;
            case 'update-konto':
                $this->updateKonto($routeInfo);
                break;
            case 'new-booking-instruct':
                $this->newBookingInstruct($routeInfo);
                break;
            case 'delete-booking-instruct':
                $this->deleteBookingInstruction($routeInfo);
                break;
            case 'cancel-booking':
                $this->cancelBooking($routeInfo);
                break;
            case 'confirm-instruct':
                $this->saveConfirmedBookingInstruction();
                break;
            case 'save-new-kasse-entry':
                $this->saveNewKasseEntry();
                break;
            case 'save-hhp-import':
                $this->saveHhpImport($routeInfo);
                break;
            case 'save-new-konto-credentials':
                $this->newKontoCredentials($routeInfo);
                break;
            case 'save-default-tan-mode':
                $this->saveDefaultTanMode($routeInfo);
                break;
                /*case "login-credentials":
                    $this->loginCredentials($routeInfo);
                    break;*/
            case 'lock-credentials':
                $this->lockCredentials($routeInfo);
                break;
            case 'submit-tan':
                $this->submitTan($routeInfo);
                break;
            case 'abort-tan':
                $this->abortTan($routeInfo);
                break;
            case 'change-credential-password':
                $this->changeCredentialPassword($routeInfo);
                break;
            case 'delete-credentials':
                $this->deleteCredentials($routeInfo);
                break;
            case 'import-konto':
                $this->importKonto($routeInfo);
                break;
            case 'mirror':
                $this->mirrorInput();
                break;
            case 'clear-session':
                $this->clearFintsSession();
                break;
            case 'nononce':
            default:
                throw new LegacyDieException(400, 'Unknown Action: '.$routeInfo['action']);
                break;
        }
    }

    private function mirrorInput(): void
    {
        JsonController::print_json(
            [
                'success' => true,
                'status' => '200',
                'msg' => var_export($_POST, true),
                'type' => 'modal',
                'subtype' => 'server-warning',
                'headline' => 'Mirror des Inputs',
            ]
        );
    }

    private function handleExtern($routeInfo): void
    {
        $extHandler = new ExternVorgangHandler($routeInfo);
        $extHandler->handlePost();
    }

    public function saveNewKasseEntry(): void
    {
        if (((int) $_REQUEST['konto-id']) > 0) {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '200',
                    'msg' => 'Konto ist keine Kasse',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Fehler bei der Verarbeitung',
                ]
            );
        }
        $fields = [];
        $fields['id'] = strip_tags($_REQUEST['new-nr']);
        $fields['konto_id'] = strip_tags($_REQUEST['konto-id']);
        $fields['date'] = strip_tags($_REQUEST['new-date']);
        $fields['valuta'] = strip_tags($_REQUEST['new-date']);
        $fields['zweck'] = strip_tags($_REQUEST['new-desc']);
        $fields['value'] = (float) strip_tags($_REQUEST['new-money']);
        $fields['saldo'] = (float) strip_tags($_REQUEST['new-saldo']);
        if (strlen($fields['zweck']) < 5) {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '200',
                    'msg' => 'Der Beschreibungstext muss mindestens 5 Zeichen lang sein!',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Fehler bei der Verarbeitung',
                ]
            );
        }
        $fields['type'] = 'BAR-'.($fields['value'] > 0 ? 'EIN' : 'AUS');
        if ($fields['id'] === '1') {
            DBConnector::getInstance()->dbInsert('konto', $fields);
        } else {
            $last = BankTransaction::where('konto_id', '=', $fields['konto_id'])
                ->orderBy('id', 'desc')
                ->first()?->toArray();

            if (abs($last['saldo'] + $fields['value'] - $fields['saldo']) < 0.01) {
                DBConnector::getInstance()->dbInsert('konto', $fields);
            } else {
                JsonController::print_json([
                    'success' => false,
                    'status' => '200',
                    'msg' => "Alter Saldo ({$last['saldo']}) und neuer Wert ({$fields['value']}) ergeben nicht neuen Saldo ({$fields['saldo']})!",
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Fehler bei der Verarbeitung',
                ]);
            }
        }
        JsonController::print_json([
            'success' => true,
            'status' => '200',
            'msg' => 'Die Seite wird gleich neu geladen',
            'type' => 'modal',
            'subtype' => 'server-success',
            'reload' => 1000,
            'headline' => 'Erfolgreich gespeichert',
        ]);
    }

    public function handleProjekt($routeInfo = null): void
    {
        $ret = false;
        $msgs = [];
        $projektHandler = null;
        $dbret = false;

        if (DEV) {
            $msgs[] = print_r($_POST, true);
        }

        try {

            if (! isset($_POST['action'])) {
                throw new ActionNotSetException('Es wurde keine Aktion übertragen');
            }

            if (DBConnector::getInstance()->dbBegin() === false) {
                throw new PDOException('cannot start DB transaction');
            }

            switch ($_POST['action']) {
                case 'create':
                    $projektHandler = ProjektHandler::createNewProjekt($_POST);
                    if ($projektHandler !== null) {
                        $ret = true;
                    }
                    break;
                case 'changeState':
                    if (! isset($_POST['id']) || ! is_numeric($_POST['id'])) {
                        throw new IdNotSetException('ID nicht gesetzt.');
                    }
                    $projektHandler = new ProjektHandler(['pid' => $_POST['id'], 'action' => 'none']);
                    $ret = $projektHandler->setState($_POST['newState']);
                    break;
                case 'update':
                    if (! isset($_POST['id']) || ! is_numeric($_POST['id'])) {
                        throw new IdNotSetException('ID nicht gesetzt.');
                    }
                    $projektHandler = new ProjektHandler(['pid' => $_POST['id'], 'action' => 'edit']);
                    $ret = $projektHandler->updateSavedData($_POST);
                    $msgs[] = 'Try to update';
                    break;
                default:
                    throw new ActionNotSetException('Unbekannte Aktion verlangt!');
            }
        } catch (ActionNotSetException|IdNotSetException|WrongVersionException|
        InvalidDataException|PDOException|IllegalTransitionException $exception) {
            $ret = false;
            $msgs[] = 'Ein Fehler ist aufgetreten';
            $msgs[] = $exception->getMessage();
        } catch (IllegalStateException $exception) {
            $ret = false;
            $msgs[] = 'In diesen Status darf nicht gewechselt werden!';
            $msgs[] = $exception->getMessage();
        } finally {
            if ($ret) {
                $dbret = DBConnector::getInstance()->dbCommit();
            }
            if ($ret === false || $dbret === false) {
                DBConnector::getInstance()->dbRollBack();
                $msgs[] = 'Deine Änderungen wurden nicht gespeichert (DB Rollback)';
            } else {
                $msgs[] = 'Daten erfolgreich gespeichert!';
                $target = URIBASE.'projekt/'.$projektHandler->getID();
            }

        }

        $json = [
            'success' => ($ret !== false),
            'status' => '200',
            'msg' => $msgs,
            'type' => 'modal',
        ];
        if (isset($target)) {
            $json['redirect'] = $target;
        }
        if ($ret === false) {
            $json['subtype'] = 'server-error';
        } else {
            $json['reload'] = 1000;
            $json['subtype'] = 'server-success';
        }

        JsonController::print_json($json);
    }

    /**
     * handle auslagen posts
     */
    public function handleAuslagen(array $routeInfo = []): void
    {
        if (! isset($routeInfo['mfunction'])) {
            if (isset($_POST['action'])) {
                $routeInfo['mfunction'] = $_POST['action'];
            } else {
                throw new LegacyDieException(400, 'No Action and mfunction.');
            }
        }

        // validate
        $vali = new Validator;
        $validator_map = [];
        switch ($routeInfo['mfunction']) {
            case 'updatecreate':
                $validator_map = [
                    'version' => [
                        'integer',
                        'min' => '1',
                        'error' => 'Ungültige Versionsnummer.',
                    ],
                    'etag' => [
                        'regex',
                        'pattern' => '/^(0|([a-f0-9]){32})$/',
                        'error' => 'Ungültige Version.',
                    ],
                    'projekt-id' => [
                        'integer',
                        'min' => '1',
                        'error' => 'Ungültige Projekt ID.',
                    ],
                    'auslagen-id' => [
                        'regex',
                        'pattern' => '/^(NEW|[1-9]\d*)$/',
                        'error' => 'Ungültige Erstattungs ID.',
                    ],
                    'auslagen-name' => [
                        'regex',
                        'pattern' => '/^[a-zA-Z0-9\-_ :,;%$§\&\+\*\.!\?\/\\\[\]\'"#~()äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]*$/',
                        'maxlength' => '255',
                        'minlength' => '2',
                        'error' => 'Ungültiger oder leerer Abrechnungsname.',
                    ],
                    'zahlung-name' => [
                        'regex',
                        'pattern' => '/^[a-zA-Z0-9\-_ :,;%$§\&\+\*\.!\?\/\\\[\]\'"#~()äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]*$/',
                        'maxlength' => '127',
                        'empty',
                        'error' => 'Ungültiger Zahlungsempfänger.',
                    ],
                    'zahlung-iban' => [
                        'iban',
                        'empty',
                        'error' => 'Ungültige Iban.',
                    ],
                    'zahlung-vwzk' => [
                        'regex',
                        'pattern' => '/^[a-zA-Z0-9\-_,$§:;\/\\\\()!?& \.\[\]%\'"#~\*\+äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]*$/',
                        'empty',
                        'maxlength' => '127',
                    ],
                    'address' => [
                        'regex',
                        'pattern' => '/^[a-zA-Z0-9\-_,:;\/\\\\()& \n\r\.\[\]%\'"#\*\+äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]*$/',
                        'empty',
                        'maxlength' => '1023',
                        'error' => 'Adressangabe fehlerhaft.',
                    ],
                    'belege' => [
                        'array',
                        'optional',
                        'minlength' => 1,
                        'key' => [
                            'regex',
                            'pattern' => '/^(new_)?(\d+)$/',
                        ],
                        'validator' => [
                            'arraymap',
                            'required' => true,
                            'map' => [
                                'datum' => [
                                    'date',
                                    'empty',
                                    'format' => 'Y-m-d',
                                    'parse' => 'Y-m-d',
                                    'error' => 'Ungültiges Beleg Datum.',
                                ],
                                'beschreibung' => [
                                    'text',
                                    'strip',
                                    'trim',
                                ],
                                'posten' => [
                                    'array',
                                    'optional',
                                    'minlength' => 1,
                                    'key' => [
                                        'regex',
                                        'pattern' => '/^(new_)?(\d+)$/',
                                    ],
                                    'validator' => [
                                        'arraymap',
                                        'required' => true,
                                        'map' => [
                                            'projekt-posten' => [
                                                'integer',
                                                'min' => '1',
                                                'error' => 'Invalid Projektposten ID.',
                                            ],
                                            'in' => [
                                                'float',
                                                'step' => '0.01',
                                                'format' => '2',
                                                'min' => '0',
                                                // 'error' => 'Posten - Einnahmen: Ungültiger Wert'
                                            ],
                                            'out' => [
                                                'float',
                                                'step' => '0.01',
                                                'format' => '2',
                                                'min' => '0',
                                                // 'error' => 'Posten - Ausgaben: Ungültiger Wert'
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
                break;
            case 'filedelete':
                $validator_map = [
                    'etag' => [
                        'regex',
                        'pattern' => '/^(0|([a-f0-9]){32})$/',
                        'error' => 'Ungültige Version.',
                    ],
                    'projekt-id' => [
                        'integer',
                        'min' => '1',
                        'error' => 'Ungültige Projekt ID.',
                    ],
                    'auslagen-id' => [
                        'integer',
                        'min' => '1',
                        'error' => 'Ungültige Abrechnungs ID.',
                    ],
                    'fid' => [
                        'integer',
                        'min' => '1',
                        'error' => 'Ungültige Datei ID.',
                    ],
                ];
                break;
            case 'state':
                $validator_map = [
                    'etag' => [
                        'regex',
                        'pattern' => '/^(0|([a-f0-9]){32})$/',
                        'error' => 'Ungültige Version.',
                    ],
                    'projekt-id' => [
                        'integer',
                        'min' => '1',
                        'error' => 'Ungültige Projekt ID.',
                    ],
                    'auslagen-id' => [
                        'integer',
                        'min' => '1',
                        'error' => 'Ungültige Abrechnungs ID.',
                    ],
                    'state' => [
                        'regex',
                        'pattern' => '/^(draft|wip|ok|instructed|booked|revocation|payed|ok-hv|ok-kv|ok-belege|revoked|rejected)$/',
                        'error' => 'Ungültiger Status.',
                    ],
                ];
                break;
            case 'zahlungsanweisung':
            case 'belegpdf':
                $validator_map = [
                    'projekt-id' => [
                        'integer',
                        'min' => '1',
                        'error' => 'Ungültige Projekt ID.',
                    ],
                    'auslagen-id' => [
                        'integer',
                        'min' => '1',
                        'error' => 'Ungültige Abrechnungs ID.',
                    ],
                    'd' => [
                        'integer',
                        'optional',
                        'min' => '0',
                        'max' => '1',
                        'error' => 'Ungültige Parameter.',
                    ],
                ];
                break;
            default:
                throw new LegacyDieException(400, 'Unknown Action.');
                break;
        }
        $vali->validateMap($_POST, $validator_map);
        // return error if validation failed
        if ($vali->getIsError()) {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '200',
                    'msg' => $vali->getLastErrorMsg(),
                    'type' => 'validator',
                    'field' => $vali->getLastMapKey(),
                ]
            );
        }

        $validated = $vali->getFiltered();

        if ($routeInfo['mfunction'] === 'updatecreate') {
            // may add nonexisting arrays
            if (! isset($validated['belege'])) {
                $validated['belege'] = [];
            }
            foreach ($validated['belege'] as $k => $v) {
                if (! isset($v['posten'])) {
                    $validated['belege'][$k]['posten'] = [];
                }
            }
            // check all values empty?
            $empty = strtolower($validated['auslagen-id'] === 'new');
            $auslagen_test_empty = [
                'auslagen-name',
                'zahlung-name',
                'zahlung-iban',
                'zahlung-vwzk',
                'belege',
                'address',
            ];
            $belege_test_empty = ['datum', 'beschreibung', 'posten'];
            $posten_text_empty = ['out', 'in'];
            if ($empty) {
                foreach ($auslagen_test_empty as $e) {
                    if ((is_string($validated[$e]) && $validated[$e])
                        || (is_array($validated[$e]) && count($validated[$e]))) {
                        $empty = false;
                        break;
                    }
                }
            }
            if ($empty) {
                foreach ($validated['belege'] as $kb => $belege) {
                    foreach ($belege_test_empty as $e) {
                        if ((is_string($belege[$e]) && $belege[$e])
                            || (is_array($belege[$e]) && count($belege[$e]))) {
                            $empty = false;
                            break 2;
                        }
                    }
                    foreach ($belege['posten'] as $posten) {
                        foreach ($posten_text_empty as $e) {
                            if ((is_string($posten[$e]) && $posten[$e])
                                || (is_array($posten[$e]) && count($posten[$e]))) {
                                $empty = false;
                                break 3;
                            }
                        }
                    }

                    // check file non empty
                    $fileIdx = 'beleg_'.$kb;
                    if (isset($_FILES[$fileIdx]['error']) && $_FILES[$fileIdx]['error'] === 0) {
                        $empty = false;
                        break;
                    }
                }
            }
            // error reply
            if ($empty) {
                JsonController::print_json(
                    [
                        'success' => false,
                        'status' => '200',
                        'msg' => 'Leere Erstattungen können nicht gespeichert werden.',
                        'type' => 'modal',
                        'subtype' => 'server-error',
                    ]
                );
            }
        }
        $routeInfo['pid'] = $validated['projekt-id'];
        if ($validated['auslagen-id'] !== 'NEW') {
            $routeInfo['aid'] = $validated['auslagen-id'];
        }
        $routeInfo['validated'] = $validated;
        $routeInfo['action'] = 'post';
        // call auslagen handler
        $handler = new AuslagenHandler2($routeInfo);
        $handler->handlePost();

        // error reply
        if ($empty) {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '200',
                    'msg' => 'Der Posthandler hat die Anfrage nicht beantwortet.',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                ]
            );
        }
    }

    private function handleChat($routeInfo): void
    {
        $db = DBConnector::getInstance();
        $chat = new ChatHandler(null, null);
        $valid = $chat->validatePost($_POST);
        $auth = (AUTH_HANDLER);
        /* @var $auth AuthHandler */
        $auth = $auth::getInstance();
        if ($valid) {
            // access permission control
            switch ($valid['target']) {
                case 'projekt':
                    try {
                        $r = $db->dbFetchAll(
                            'projekte',
                            [DBConnector::FETCH_ASSOC],
                            ['projekte.*', 'user.username', 'user.email'],
                            ['projekte.id' => $valid['target_id']],
                            [
                                ['type' => 'left', 'table' => 'user', 'on' => [['user.id', 'projekte.creator_id']]],
                            ]
                        );
                    } catch (Exception $e) {
                        ErrorHandler::handleException($e);
                        break;
                    }
                    if (! $r || count($r) === 0) {
                        break;
                    }
                    $pdate = date_create(substr($r[0]['createdat'], 0, 4).'-01-01 00:00:00');
                    $pdate->modify('+1 year');
                    $now = date_create();
                    // info mail
                    $mail = [];
                    // ACL --------------------------------
                    // action
                    switch ($valid['action']) {
                        case 'gethistory':
                            $map = ['0', '1'];
                            if ($auth->hasGroup('admin')) {
                                $map[] = '2';
                            }
                            if ($auth->hasGroup('ref-finanzen')) {
                                $map[] = '3';
                            }
                            if ($auth->hasGroup('ref-finanzen')
                                || (isset($r[0]['username']) && $r[0]['username'] === $auth->getUsername())) {
                                $map[] = '-1';
                            }
                            $chat->setKeep($map);
                            break;
                        case 'newcomment':
                            // allow chat only 90 days into next year
                            if ($now->getTimestamp() - $pdate->getTimestamp() > 86400 * 90) {
                                break 2;
                            }
                            // new message - info mail
                            $tMail = [];
                            if (! preg_match(
                                '/^(draft|wip|revoked|ok-by-hv|need-stura|done-hv|done-other|ok-by-stura)/',
                                $r[0]['state']
                            )) {
                                break 2;
                            }
                            // switch type
                            switch ($valid['type']) {
                                case '-1':
                                    if (! $auth->hasGroup('ref-finanzen')
                                        && (! isset($r[0]['username']) || $r[0]['username'] !== $auth->getUsername())) {
                                        break 3;
                                    }
                                    if (! $auth->hasGroup('ref-finanzen')) {
                                        $tMail['to'][] = 'ref-finanzen@tu-ilmenau.de';
                                    } else {
                                        $tMail['to'][] = $r[0]['email'];
                                    }
                                    break;
                                case '0':
                                    if (! $auth->hasGroup('ref-finanzen')) {
                                        $tMail['to'][] = 'ref-finanzen@tu-ilmenau.de';
                                    } else {
                                        $tMail['to'][] = $r[0]['responsible'];
                                    }
                                    break;
                                case '2':
                                    if (! $auth->hasGroup('admin')) {
                                        break 3;
                                    }
                                    break;
                                case '3':
                                    if (! $auth->hasGroup('ref-finanzen')) {
                                        break 3;
                                    }
                                    $tMail['to'][] = 'ref-finanzen@tu-ilmenau.de';
                                    break;
                                default:
                                    break 3;
                            }
                            if (count($tMail) > 0) {
                                $tMail['param']['msg'][] = 'Im %Projekt% #'.$r[0]['id'].' gibt es eine neue Nachricht.';
                                $tMail['param']['link']['Projekt'] = BASE_URL.URIBASE.'projekt/'.$r[0]['id'].'#projektchat';
                                $tMail['param']['headline'] = 'Projekt - Neue Nachricht';
                                $tMail['subject'] = 'Stura-Finanzen: Neue Nachricht in Projekt #'.$r[0]['id'];
                                $tMail['template'] = 'projekt_default';
                                $mail[] = $tMail;
                            }
                            break;
                        default:
                            break 2;
                    }
                    // all ok -> handle all
                    $chat->answerAll($_POST);
                    if (count($mail) > 0) {
                        // $mh = MailHandler::getInstance();
                        foreach ($mail as $m) {
                            // create and send email
                            //	$mail_result = $mh->easyMail($m);
                        }
                    }

                    return;

                    break;
                case 'auslagen':
                    $r = [];
                    try {
                        $r = $db->dbFetchAll(
                            'auslagen',
                            [DBConnector::FETCH_ASSOC],
                            ['auslagen.*'],
                            ['auslagen.id' => $valid['target_id']],
                            []
                        );
                    } catch (Exception $e) {
                        ErrorHandler::handleException($e);
                        break;
                    }
                    if (! $r || count($r) === 0) {
                        break;
                    }
                    $pdate = date_create(substr($r[0]['created'], 0, 4).'-01-01 00:00:00');
                    $pdate->modify('+1 year');
                    $now = date_create();
                    // info mail
                    $mail = [];
                    // ACL --------------------------------
                    // action
                    switch ($valid['action']) {
                        case 'gethistory':
                            $map = ['1'];
                            if ($auth->hasGroup('ref-finanzen')) {
                                $map[] = '3';
                            }
                            $tmpsplit = explode(';', $r[0]['created']);
                            if ($auth->hasGroup('ref-finanzen') || $tmpsplit[1] === $auth->getUsername()) {
                                $map[] = '-1';
                            }
                            $chat->setKeep($map);
                            break;
                        case 'newcomment':
                            // allow chat only 90 days into next year
                            if ($now->getTimestamp() - $pdate->getTimestamp() > 86400 * 90) {
                                break 2;
                            }
                            // new message - info mail
                            $tMail = [];
                            // switch type
                            switch ($valid['type']) {
                                case '-1':
                                    if (! $auth->hasGroup('ref-finanzen') &&
                                        $auth->getUsername() !== AuslagenHandler2::state2stateInfo('wip;'.$r[0]['created'])['user']) {
                                        break 3;
                                    }
                                    if (! $auth->hasGroup('ref-finanzen')) {
                                        $tMail['to'][] = 'ref-finanzen@tu-ilmenau.de';
                                    } else {
                                        $u = $db->dbFetchAll(
                                            'user',
                                            [DBConnector::FETCH_ASSOC],
                                            ['email', 'id'],
                                            [
                                                'username' => AuslagenHandler2::state2stateInfo(
                                                    'wip;'.$r[0]['created']
                                                )['user'],
                                            ]
                                        );
                                        if ($u && count($u) > 0) {
                                            $tMail['to'][] = $u[0]['email'];
                                        }
                                    }
                                    break;
                                case '3':
                                    if (! $auth->hasGroup('ref-finanzen')) {
                                        break 3;
                                    }
                                    $tMail['to'][] = 'ref-finanzen@tu-ilmenau.de';
                                    break;
                                default:
                                    break 3;
                            }
                            if (count($tMail) > 0) {
                                $tMail['param']['msg'][] = 'In der %Abrechnung% #'.$r[0]['id'].' gibt es eine neue Nachricht.';
                                $tMail['param']['link']['Abrechnung'] = BASE_URL.URIBASE.'projekt/'.$r[0]['projekt_id'].'/auslagen/'.$r[0]['id'].'#auslagenchat';
                                $tMail['param']['headline'] = 'Auslagen - Neue Nachricht';
                                $tMail['subject'] = 'Stura-Finanzen: Neue Nachricht in Abrechnung #'.$r[0]['id'];
                                $tMail['template'] = 'projekt_default';
                                $mail[] = $tMail;
                            }
                            break;
                        default:
                            break 2;
                    }
                    // all ok -> handle all
                    $chat->answerAll($_POST);
                    if (count($mail) > 0) {
                        // $mh = MailHandler::getInstance();
                        foreach ($mail as $m) {
                            // create and send email
                            // $mail_result = $mh->easyMail($m);
                        }
                    }
                    exit;

                    break;
                default:
                    break;
            }
        }
        $chat->setErrorMessage('Access Denied.');
        $chat->answerError();

    }

    private function updateKonto($routeInfo): void
    {
        $auth = AUTH_HANDLER;
        /* @var $auth AuthHandler */
        $auth = $auth::getInstance();
        $auth->requireGroup('ref-finanzen-kv');

        $ret = true;
        if (! DBConnector::getInstance()->dbBegin()) {
            throw new LegacyDieException(500,
                'Kann keine Verbindung zur SQL-Datenbank aufbauen. Bitte versuche es später erneut!'
            );
        }
        [$success, $msg_xmlrpc, $allZahlungen] = HibiscusXMLRPCConnector::getInstance()->fetchAllUmsatz();

        if ($success === false) {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => 'Konnte keine Verbindung mit Onlinebanking Service aufbauen',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                ]
            );
        }
        /*$lastId = DBConnector::getInstance()->dbFetchAll(
            "konto",
            [DBConnector::FETCH_ASSOC],
            ["id" => ["id", DBConnector::GROUP_MAX]]
        );
        if (is_array($lastId)){
            $lastId = $lastId[0]["id"];
        }*/
        $msg = [];
        $inserted = [];
        foreach ($allZahlungen as $zahlung) {
            $fields = [];
            $fields['id'] = $zahlung['id'];
            $fields['konto_id'] = $zahlung['konto_id'];
            $fields['date'] = $zahlung['datum'];
            $fields['type'] = $zahlung['art'];
            $fields['valuta'] = $zahlung['valuta'];
            $fields['primanota'] = $zahlung['primanota'];
            $fields['value'] = DBConnector::getInstance()->convertUserValueToDBValue($zahlung['betrag'], 'money');
            $fields['empf_name'] = $zahlung['empfaenger_name'];
            $fields['empf_iban'] = $zahlung['empfaenger_konto'];
            $fields['empf_bic'] = $zahlung['empfaenger_blz'];
            $fields['saldo'] = $zahlung['saldo'];
            $fields['gvcode'] = $zahlung['gvcode'];
            $fields['zweck'] = $zahlung['zweck'];
            $fields['comment'] = $zahlung['kommentar'];
            $fields['customer_ref'] = $zahlung['customer_ref'];
            // $msgs[]= print_r($zahlung,true);
            DBConnector::getInstance()->dbInsert('konto', $fields);
            if (isset($inserted[$zahlung['konto_id']])) {
                $inserted[$zahlung['konto_id']]++;
            } else {
                $inserted[$zahlung['konto_id']] = 1;
            }

            $matches = [];
            if (preg_match("/IP-[\d]{2,4}-[\d]+-A[\d]+/u", $zahlung['zweck'], $matches)) {
                $beleg_sum = 0;
                $ahs = [];
                foreach ($matches as $match) {
                    $arr = explode('-', $match);
                    $auslagen_id = substr(array_pop($arr), 1);
                    $projekt_id = array_pop($arr);
                    $ah = new AuslagenHandler2(['pid' => $projekt_id, 'aid' => $auslagen_id, 'action' => 'none']);
                    $pps = $ah->getBelegPostenFiles();
                    foreach ($pps as $pp) {
                        foreach ($pp['posten'] as $posten) {
                            if ($posten['einnahmen']) {
                                $beleg_sum += $posten['einnahmen'];
                            }
                            if ($posten['ausgaben']) {
                                $beleg_sum -= $posten['ausgaben'];
                            }
                        }
                    }
                    $ahs[] = $ah;
                }
                if (abs($beleg_sum - $fields['value']) < 0.01) {
                    foreach ($ahs as $ah) {
                        $ret = $ah->state_change('payed', $ah->getAuslagenEtag());
                        if ($ret !== true) {
                            $msg[] = 'Konnte IP'.$ah->getProjektID().'-A'.$ah->getID().
                                " nicht in den Status 'gezahlt' überführen. ".
                                'Bitte ändere das noch (per Hand) nachträglich!'.
                                $fields['date'];
                        }
                    }
                } else {
                    $msg[] = 'In Zahlung '.$zahlung['id'].' wurden folgende Projekte/Auslagen im Verwendungszweck gefunden: '.implode(
                        ' & ',
                        $matches
                    ).'. Dort stimmt die Summe der Belegposten ('.$beleg_sum.') nicht mit der Summe der Zahlung ('.$fields['value'].') überein. Bitte prüfe das noch per Hand, und setze ggf. die passenden Projekte auf bezahlt, so das es später keine Probleme beim Buchen gibt (nur gezahlte Auslagen können gebucht werden)';
                }
            }
        }

        $ret = DBConnector::getInstance()->dbCommit();

        if (! $ret) {
            DBConnector::getInstance()->dbRollBack();
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => array_merge($msg_xmlrpc, $msg),
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Ein Datenbank Fehler ist aufgetreten! (Rollback)',
                ]
            );
        } elseif (! empty($inserted)) {
            $type = (count($msg_xmlrpc) + count($msg)) > 1 ? 'warning' : 'success';

            foreach ($inserted as $konto_id => $number) {
                $msg[] = "$number neue Umsätze auf Konto $konto_id gefunden und hinzugefügt!";
            }
            $msg = array_reverse($msg);
            JsonController::print_json(
                [
                    'success' => true,
                    'status' => '200',
                    'msg' => array_merge($msg_xmlrpc, $msg),
                    'type' => 'modal',
                    'subtype' => 'server-'.$type,
                ]
            );
        } else {
            $msg = array_merge(['Keine neuen Umsätze gefunden.'], $msg);
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '200',
                    'msg' => array_merge($msg_xmlrpc, $msg),
                    'type' => 'modal',
                    'subtype' => 'server-warning',
                ]
            );
        }
    }

    private function deleteBookingInstruction($routeInfo): void
    {
        $instructId = $routeInfo['instruct-id'];
        $res = DBConnector::getInstance()->dbDelete('booking_instruction', ['id' => $instructId]);
        if ($res > 0) {
            JsonController::print_json(
                [
                    'success' => true,
                    'status' => '200',
                    'msg' => "Vorgang $instructId wurde zurückgesetzt.",
                    'type' => 'modal',
                    'subtype' => 'server-success',
                    'headline' => 'Erfolgreiche Datenübertragung',
                    'reload' => 2000,
                ]
            );
        } else {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => "Vorgang $instructId konnte nicht gefunden werden!",
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Fehler bei der Datenübertragung',
                ]
            );
        }
    }

    private function newBookingInstruct($routeInfo): void
    {
        $errorMsg = [];
        $zahlung = $_POST['zahlung'] ?? [];
        $zahlung_type = $_POST['zahlung-type'] ?? [];
        $auslage = $_POST['auslage'] ?? [];
        $externDataId = $_POST['extern'] ?? [];

        if (count($zahlung_type) !== count($zahlung)) {
            $errorMsg[] = 'Ungleiche Datenübertragung bei Zahlung, falls neu laden (Strg + F5) nichts hilft, kontaktiere bitte den Administrator.';
        }
        if ((count($zahlung) > 1 && (count($auslage) + count($externDataId)) > 1)
            || (count($auslage) === 0 && count($externDataId) === 0)
            || count($zahlung) === 0
        ) {
            $errorMsg[] = 'Es kann immer nur 1 Zahlung zu n Belegen oder 1 Beleg zu n Zahlungen zugeordnet werden. Andere Zuordnungen sind nicht möglich!';
        }
        $where = [];
        if (count($auslage) > 0) {
            $where[] = ['canceled' => 0, 'belege.auslagen_id' => ['IN', $auslage]];
        }
        if (count($externDataId) > 0) {
            $where[] = ['canceled' => 0, 'extern_data.id' => ['IN', $externDataId]];
        }

        // check if allready booked
        $bookingDBbelege = DBConnector::getInstance()->dbFetchAll(
            'booking',
            [DBConnector::FETCH_ASSOC],
            ['booking.beleg_id'],
            $where,
            [
                [
                    'table' => 'beleg_posten',
                    'type' => 'left',
                    'on' => [['beleg_posten.id', 'booking.beleg_id'], ['booking.beleg_type', 'belegposten']],
                ],
                ['table' => 'belege', 'type' => 'inner', 'on' => ['belege.id', 'beleg_posten.beleg_id']],
            ]
        );

        $zahlungByType = [];
        foreach ($zahlung as $key => $item) {
            $zahlungByType[$zahlung_type[$key]][] = $item;
        }
        $where = [];
        foreach ($zahlungByType as $type => $zahlungsArray) {
            $where[] = ['canceled' => 0, 'zahlung_id' => ['IN', $zahlungsArray], 'zahlung_type' => $type];
        }

        $bookingDBzahlung = DBConnector::getInstance()->dbFetchAll(
            'booking',
            [DBConnector::FETCH_ASSOC],
            ['zahlung_id', 'zahlung_type'],
            $where
        );

        if (count($bookingDBbelege) + count($bookingDBzahlung) > 0) {
            $errorMsg[] = 'Beleg oder Zahlung bereits verknüpft - '.print_r(
                array_merge($bookingDBzahlung, $bookingDBbelege),
                true
            );
        }

        if (! empty($errorMsg)) {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => $errorMsg,
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Fehler bei der Datenübertragung',
                ]
            );
        }
        DBConnector::getInstance()->dbBegin();
        $lastEntry = DBConnector::getInstance()->dbFetchAll(
            'booking_instruction',
            [DBConnector::FETCH_NUMERIC],
            [['id', DBConnector::GROUP_MAX]]
        );
        if (is_array($lastEntry) && ! empty($lastEntry)) {
            $nextId = $lastEntry[0][0] + 1;
        } else {
            $nextId = 1;
        }
        foreach ($zahlung as $zId => $zahl) {
            foreach ($auslage as $bel) {
                DBConnector::getInstance()->dbInsert(
                    'booking_instruction',
                    [
                        'id' => $nextId,
                        'zahlung' => $zahl,
                        'zahlung_type' => $zahlung_type[$zId],
                        'beleg' => $bel,
                        'beleg_type' => 'belegposten',
                        'by_user' => DBConnector::getInstance()->getUser()['id'],
                    ]
                );
            }
            foreach ($externDataId as $ext) {
                DBConnector::getInstance()->dbInsert(
                    'booking_instruction',
                    [
                        'id' => $nextId,
                        'zahlung' => $zahl,
                        'zahlung_type' => $zahlung_type[$zId],
                        'beleg' => $ext,
                        'beleg_type' => 'extern',
                        'by_user' => DBConnector::getInstance()->getUser()['id'],
                    ]
                );
            }
        }
        if (DBConnector::getInstance()->dbCommit()) {
            JsonController::print_json(
                [
                    'success' => true,
                    'status' => '200',
                    'msg' => 'Buchung wurde angewiesen',
                    'type' => 'modal',
                    'subtype' => 'server-success',
                    'reload' => 1000,
                    'headline' => 'Erfolgreich gespeichert',
                ]
            );
        } else {
            DBConnector::getInstance()->dbRollBack();
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => 'Fehler bei der Übertragung zur Datenbank',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Fehler',
                ]
            );
        }
    }

    private function saveConfirmedBookingInstruction(): void
    {
        // var_dump($_POST);
        $confirmedInstructions = array_keys($_REQUEST['activeInstruction']);
        $text = $_REQUEST['text'];

        if (empty($confirmedInstructions)) {
            JsonController::print_json(
                [
                    'success' => true,
                    'status' => '200',
                    'msg' => 'Es wurde kein Vorgang ausgewählt.',
                    'type' => 'modal',
                    'subtype' => 'server-warning',
                    // 'reload' => 2000,
                    'headline' => 'Fehlerhafte Eingabe',
                ]
            );
        }

        $btm = new BookingTableManager($confirmedInstructions);
        $btm->run();

        $zahlungenDB = $btm->getZahlungDB();
        $belegeDB = $btm->getBelegeDB();

        // start write action
        DBConnector::getInstance()->dbBegin();
        // check if transferable to new States (payed => booked)
        $stateChangeNotOk = [];
        $doneAuslage = [];
        // hydrate belege
        foreach ($confirmedInstructions as $instruction) {
            foreach ($belegeDB[$instruction] as $beleg) {
                switch ($beleg['type']) {
                    case 'auslage':
                        $ah = new AuslagenHandler2(
                            [
                                'aid' => $beleg['auslagen_id'],
                                'pid' => $beleg['projekt_id'],
                                'action' => 'none',
                            ]
                        );
                        if (! in_array('A'.$beleg['auslagen_id'], $doneAuslage, true)) {
                            if ($ah->state_change_possible('booked') !== true) {
                                $stateChangeNotOk[] = 'IP-'.date_create($beleg['projekt_createdate'])->format('y').'-'.
                                    $beleg['projekt_id'].'-A'.$beleg['auslagen_id'].' ('.$ah->getStateString().')';
                            } else {
                                $ah->state_change('booked', $beleg['etag']);
                                $doneAuslage[] = 'A'.$beleg['auslagen_id'];
                            }
                        }
                        break;
                    case 'extern':
                        $evh = new ExternVorgangHandler($beleg['id']);

                        if (! in_array('E'.$beleg['id'], $doneAuslage, true)
                            && $evh->state_change_possible('booked') !== true) {
                            $stateChangeNotOk[] = 'EP-'.
                                $beleg['extern_id'].'-V'.$beleg['vorgang_id'].' ('.$evh->getStateString().
                                ')';
                        } else {
                            $evh->state_change('booked', $beleg['etag']);
                            $doneAuslage[] = 'E'.$beleg['id'];
                        }

                        break;
                }
            }
        } // transferred states to booked - otherwise throw error
        if (! empty($stateChangeNotOk)) {
            DBConnector::getInstance()->dbRollBack();
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => array_merge(
                        ['Folgende Projekte lassen sich nicht von bezahlt in gebucht überführen: '],
                        $stateChangeNotOk
                    ),
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    // 'reload' => 2000,
                    'headline' => 'Konnte nicht gespeichert werden',
                ]
            );
        }

        $zahlung_sum = array_fill_keys($confirmedInstructions, 0);
        $belege_sum = array_fill_keys($confirmedInstructions, 0);
        $table = $btm->getTable();
        // sammle werte pro instruction auf
        foreach ($confirmedInstructions as $instruction) {
            $lastTitel = '';
            foreach ($table[$instruction] as $row) {
                if (isset($row['titel']['type'])) {
                    $lastTitel = $row['titel']['type'];
                }
                if ($lastTitel === 0) { // income title
                    $belege_sum[$instruction] += (float) $row['posten-ist']['val-raw'];
                }
                if ($lastTitel === 1) { // expenses title
                    $belege_sum[$instruction] -= (float) $row['posten-ist']['val-raw'];
                }
                if (isset($row['zahlung-value'])) {
                    $zahlung_sum[$instruction] += (float) $row['zahlung-value']['val-raw'];
                }
            }
        }
        foreach ($confirmedInstructions as $instruction) {
            if (count($table[$instruction]) !== count($text[$instruction])) {
                DBConnector::getInstance()->dbRollBack();
                JsonController::print_json(
                    [
                        'success' => false,
                        'status' => '500',
                        'msg' => "Falsche Daten wurden übertragen - Textfelder fehlen bei Vorgang $instruction",
                        'type' => 'modal',
                        'subtype' => 'server-error',
                        'reload' => 2000,
                        'headline' => 'Konnte nicht gespeichert werden',
                    ]
                );
            }
        }

        foreach ($confirmedInstructions as $instruction) {
            // check if algorithm  was correct :'D
            $diff = abs($zahlung_sum[$instruction] - $belege_sum[$instruction]);
            if ($diff >= 0.01) {
                DBConnector::getInstance()->dbRollBack();
                JsonController::print_json(
                    [
                        'success' => false,
                        'status' => '500',
                        'msg' => "Falsche Daten wurden übertragen: Differenz der Posten = $diff",
                        'type' => 'modal',
                        'subtype' => 'server-error',
                        // 'reload' => 2000,
                        'headline' => 'Konnte nicht gespeichert werden',
                    ]
                );
            }
        }

        $maxBookingId = DBConnector::getInstance()->dbFetchAll(
            'booking',
            [DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY],
            [['id', DBConnector::GROUP_MAX]]
        );
        $maxBookingId = array_keys($maxBookingId)[0];

        // save in booking-list
        $table = $btm->getTable(true);

        foreach ($confirmedInstructions as $instruction) {
            $idx = 0;
            $bookingText = array_values($text[$instruction]);
            foreach ($table[$instruction] as $row) {
                DBConnector::getInstance()->dbInsert(
                    'booking',
                    [
                        'id' => ++$maxBookingId,
                        'titel_id' => $row['titel']['val-raw'],
                        'zahlung_id' => $row['zahlung']['val-raw'],
                        'zahlung_type' => $row['zahlung']['zahlung-type'],
                        'beleg_id' => $row['posten']['val-raw'],
                        'beleg_type' => $row['beleg']['beleg-type'],
                        'user_id' => DBConnector::getInstance()->getUser()['id'],
                        'comment' => $bookingText[$idx++],
                        'value' => $row['posten-ist']['val-raw'],
                        'kostenstelle' => 0,
                    ]
                );
            }
        }

        // delete from instruction list
        DBConnector::getInstance()->dbUpdate('booking_instruction', ['id' => ['IN', $confirmedInstructions]], ['done' => 1]);
        DBConnector::getInstance()->dbCommit();
        JsonController::print_json(
            [
                'success' => true,
                'status' => '200',
                'msg' => 'Die Seite wird gleich neu geladen',
                'type' => 'modal',
                'subtype' => 'server-success',
                'reload' => 2000,
                'headline' => count($confirmedInstructions).(count($confirmedInstructions) >= 1 ? ' Vorgänge ' : ' Vorgang ').' erfolgreich gespeichert',
            ]
        );
    }

    private function cancelBooking($routeInfo): void
    {
        (AUTH_HANDLER)::getInstance()->requireGroup('ref-finanzen-hv');
        if (! isset($_REQUEST['booking_id'])) {
            $msgs[] = 'Daten wurden nicht korrekt übermittelt';

            return;
        }
        $booking_id = $_REQUEST['booking_id'];
        $ret = DBConnector::getInstance()->dbFetchAll('booking', [DBConnector::FETCH_ASSOC], [], ['id' => $booking_id]);
        $maxBookingId = DBConnector::getInstance()->dbFetchAll(
            'booking',
            [DBConnector::FETCH_ONLY_FIRST_COLUMN],
            [['id', DBConnector::GROUP_MAX]],
            ['id' => $booking_id]
        )[0];
        if ($ret !== false && ! empty($ret)) {
            $ret = $ret[0];
            if ($ret['canceled'] !== 0) {
                DBConnector::getInstance()->dbBegin();
                $user_id = DBConnector::getInstance()->getUser()['id'];
                DBConnector::getInstance()->dbInsert(
                    'booking',
                    [
                        'id' => $maxBookingId + 1,
                        'comment' => 'Rotbuchung zu B-Nr: '.$booking_id,
                        'titel_id' => $ret['titel_id'],
                        'belegposten_id' => $ret['belegposten_id'],
                        'zahlung_id' => $ret['zahlung_id'],
                        'kostenstelle' => $ret['kostenstelle'],
                        'user_id' => $user_id,
                        'value' => -$ret['value'], // negative old Value
                        'canceled' => $booking_id,
                    ]
                );
                DBConnector::getInstance()->dbUpdate(
                    'booking',
                    ['id' => $booking_id],
                    ['canceled' => $maxBookingId + 1]
                );
                if (! DBConnector::getInstance()->dbCommit()) {
                    DBConnector::getInstance()->dbRollBack();
                    JsonController::print_json(
                        [
                            'success' => false,
                            'status' => '500',
                            'msg' => 'Ein Server fehler ist aufgetreten',
                            'type' => 'modal',
                            'subtype' => 'server-error',
                            // 'reload' => 2000,
                            'headline' => 'Konnte nicht gespeichert werden',
                        ]
                    );
                } else {
                    JsonController::print_json(
                        [
                            'success' => true,
                            'status' => '200',
                            'msg' => 'Wurde erfolgreich gegen gebucht; die Seite wird gleich neu geladen.',
                            'type' => 'modal',
                            'subtype' => 'server-success',
                            'reload' => 1000,
                            'headline' => 'Daten gespeichert',
                        ]
                    );
                }
            }
        }
    }

    private function saveHhpImport($routeInfo): void
    {
        $hhpHandler = new HHPHandler($routeInfo);
        $db = DBConnector::getInstance();
        $db->dbBegin();
        $res = $hhpHandler->saveNewHHP();
        if ($res === true) {
            $res = $db->dbCommit();
        }
        if ($res === true) {
            JsonController::print_json(
                [
                    'success' => true,
                    'status' => '200',
                    'msg' => 'Erfolgreich gespeichert',
                    'type' => 'modal',
                    'subtype' => 'server-success',
                    'reload' => 1000,
                    'headline' => 'Daten gespeichert',
                    'redirect' => URIBASE.'hhp',
                ]
            );
        } else {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => 'Ein Fehler ist aufgetreten',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Daten nicht gespeichert',
                ]
            );
        }
    }

    private function saveDefaultTanMode($routeInfo): void
    {
        if (! isset($_POST['tan-mode-id'])) {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => 'Es wurde keine TAN Methode ausgewählt',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Daten nicht gespeichert',
                ]
            );
        }

        $credId = (int) $_POST['credential-id'];
        $fHandler = FintsConnectionHandler::load($credId);
        $tanMode = (int) $_POST['tan-mode-id'];
        $tanMediumName = $_POST['tan-medium-name'] ?? null;
        $ret = $fHandler->saveDefaultTanMode($credId, $tanMode, $tanMediumName);

        if ($ret === true) {
            if ($tanClosed = $fHandler->hasTanSessionInformation()) {
                $fHandler->deleteTanSessionInformation();
            }
            $redirectUrl = $tanMediumName === null ? URIBASE."konto/credentials/$credId/tan-mode/$tanMode/medium" : URIBASE.'konto/credentials/';
            JsonController::print_json(
                [
                    'success' => true,
                    'status' => '200',
                    'msg' => "Tan $tanMode für Zugangsdaten $credId gespeichert".($tanClosed ? ' - offene Tans wurden abgebrochen' : ''),
                    'type' => 'modal',
                    'subtype' => 'server-success',
                    'reload' => 1000,
                    'headline' => 'Daten gespeichert',
                    'redirect' => $redirectUrl,
                ]
            );
        } else {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => 'Default Tan-Methode kann nicht gespeichert werden.',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Daten nicht gespeichert',
                ]
            );
        }
    }

    private function submitTan(array $routeInfo): void
    {
        $credId = (int) $_POST['credential-id'];
        $fHandler = FintsConnectionHandler::load($credId);

        $tan = $_POST['tan'];
        [$ret, $msg] = $fHandler->submitTan($tan);
        if ($ret === true) {
            JsonController::print_json(
                [
                    'success' => true,
                    'status' => '200',
                    'msg' => $msg,
                    'type' => 'modal',
                    'subtype' => 'server-success',
                    'reload' => 1000,
                    'headline' => 'Daten erfolgreich übertragen',
                ]
            );
        } else {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => $msg,
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Daten nicht korrekt',
                ]
            );
        }
    }

    private function importKonto(array $routeInfo): void
    {
        $syncFrom = date_create($_POST['sync-from'])->format('Y-m-d');
        $kontoIban = $_POST['konto-iban'];
        $ibanCorrect = Validator::_checkIBAN($kontoIban, false);
        $kontoName = substr(htmlspecialchars(strip_tags(trim($_POST['konto-name']))), 0, 32);
        $kontoShort = strtoupper(substr((string) $_POST['konto-short'], 0, 2));
        $credId = (int) $_POST['credential-id'];

        if ($ibanCorrect === false) {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => 'IBAN nicht korrekt',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Daten nicht gespeichert',
                ]
            );
        }

        $ret = DBConnector::getInstance()->dbInsert('konto_type', [
            'name' => $kontoName,
            'short' => $kontoShort,
            'sync_from' => $syncFrom,
            'iban' => $kontoIban,
        ]);

        if ((int) $ret === 1) {
            JsonController::print_json(
                [
                    'success' => true,
                    'status' => '200',
                    'msg' => 'Meta Daten des Kontos für den Import vorbereitet',
                    'type' => 'modal',
                    'subtype' => 'server-success',
                    'reload' => 1000,
                    'headline' => 'Daten gespeichert',
                    'redirect' => URIBASE."konto/credentials/$credId/sepa",
                ]
            );
        } else {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => 'Eingabe konnte nicht gesichert werden',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Daten nicht gespeichert',
                ]
            );
        }
    }

    private function clearFintsSession(): void
    {
        session()->forget('fints');
        JsonController::print_json(
            [
                'success' => true,
                'status' => '200',
                'msg' => 'Session zurückgesetzt',
                'type' => 'modal',
                'subtype' => 'server-success',
                'reload' => 1000,
                'headline' => 'Erfolgreich',
            ]
        );
    }

    private function lockCredentials(array $routeInfo): void
    {
        $credId = (int) $_POST['credential-id'];
        [$ret, $msg] = FintsConnectionHandler::lockCredentials($credId);

        if ($ret === true) {
            JsonController::print_json(
                [
                    'success' => true,
                    'status' => '200',
                    'msg' => $msg,
                    'type' => 'modal',
                    'subtype' => 'server-success',
                    'reload' => 1000,
                    'headline' => 'Zugangsdaten gesperrt',
                ]
            );
        } else {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => $msg,
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Ein Fehler ist aufgetreten',
                ]
            );
        }
    }

    private function abortTan(array $routeInfo): void
    {
        $credId = (int) $_POST['credential-id'];
        $fHandler = FintsConnectionHandler::load($credId);
        $fHandler->deleteTanSessionInformation();

        JsonController::print_json(
            [
                'success' => true,
                'status' => '200',
                'msg' => 'Du wirst gleich weitergeleitet',
                'type' => 'modal',
                'subtype' => 'server-success',
                'reload' => 1000,
                'redirect' => URIBASE.'konto/credentials/'.$credId.'/sepa',
                'headline' => 'Tan Verfahren abgebrochen',
            ]
        );
    }

    private function deleteCredentials(array $routeInfo): void
    {
        $credId = (int) $_POST['credential-id'];
        $ret = FintsConnectionHandler::deleteCredential($credId);

        if ($ret === true) {
            JsonController::print_json(
                [
                    'success' => true,
                    'status' => '200',
                    'msg' => 'Zugangsdaten wurden erfolgreich gelöscht',
                    'type' => 'modal',
                    'subtype' => 'server-success',
                    'reload' => 1000,
                    'headline' => 'Zugangsdaten gelöscht',
                ]
            );
        } else {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => 'Zugangsdaten konnten nicht gelöscht werden',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Ein Fehler ist aufgetreten',
                ]
            );
        }
    }

    private function changeCredentialPassword(array $routeInfo): void
    {
        $credId = (int) $_POST['credential-id'];
        $pw = (string) $_POST['password'];
        $pw_repeat = (string) $_POST['password-repeat'];
        if ($pw !== $pw_repeat) {
            JsonController::print_json(
                [
                    'success' => false,
                    'status' => '500',
                    'msg' => 'Passwörter stimmen nicht überein',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                    'headline' => 'Ein Fehler ist aufgetreten',
                ]
            );
        }
        $fHandler = FintsConnectionHandler::load($credId);
        [$success, $msg] = $fHandler->changePassword($pw);
    }
}
