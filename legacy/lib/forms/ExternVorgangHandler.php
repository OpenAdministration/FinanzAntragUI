<?php

namespace forms;

use App\Exceptions\LegacyDieException;
use framework\DBConnector;
use framework\Helper;
use framework\render\JsonController;
use Illuminate\Support\Str;

class ExternVorgangHandler extends FormHandlerInterface
{
    private $id;

    protected $routeInfo;

    private $data;

    private $meta_data;

    public function __construct($routeInfoOrId)
    {
        if (is_array($routeInfoOrId)) {
            if (isset($routeInfoOrId['vid'], $routeInfoOrId['eid'])) {
                $vId = $routeInfoOrId['vid'];
                $eId = $routeInfoOrId['eid'];
                $where = ['vorgang_id' => $vId, 'extern_id' => $eId];
            } else {
                throw new LegacyDieException(400, 'non valid array. vid or eid is not set');
            }
            $this->routeInfo = $routeInfoOrId;
        } else {
            $where = ['extern_data.id' => $routeInfoOrId];
        }
        $this->data = DBConnector::getInstance()->dbFetchAll(
            'extern_data',
            [DBConnector::FETCH_ASSOC],
            ['extern_data.*', 'titel_nr', 'titel_name'],
            $where,
            [
                ['table' => 'haushaltstitel', 'type' => 'left', 'on' => ['haushaltstitel.id', 'extern_data.titel_id']],
            ]
        );
        if (! is_array($this->data) || count($this->data) !== 1) {
            throw new LegacyDieException(400, 'Datensatz konnte nicht gefunden werden');
        }
        $this->data = $this->data[0];
        $this->meta_data = DBConnector::getInstance()->dbFetchAll(
            'extern_meta',
            [DBConnector::FETCH_ASSOC],
            [],
            [
                'id' => $this->data['extern_id'],
            ]
        );
        if (! is_array($this->meta_data) || count($this->meta_data) !== 1) {
            throw new LegacyDieException(400, 'Datensatz konnte nicht gefunden werden');
        }
        $this->meta_data = $this->meta_data[0];
    }

    public static function initStaticVars()
    {
        // TODO: Implement initStaticVars() method.
    }

    public static function getStateStringFromName($statename)
    {
        // TODO: Implement getStateStringFromName() method.
    }

    public function updateSavedData($data)
    {
        // TODO: Implement updateSavedData() method.
    }

    public function state_change($stateName, $etag): void
    {
        // TODO: Implement method and use etag :/
        switch ($stateName) {
            case 'instructed':
            case 'payed':
            case 'booked':
                $colName = "state_$stateName";
                break;
            default:
                throw new LegacyDieException(400, "Wrong State $stateName in External");
                break;
        }
        $newEtag = Str::random(32);
        // TODO: also Version number tracking?
        DBConnector::getInstance()->dbUpdate(
            'extern_data',
            ['id' => $this->id, 'etag' => $etag],
            [
                $colName => DBConnector::getInstance()->getUser()['fullname'].';'.date_create()->format(DateTime),
                'etag' => $newEtag,
            ]
        );
    }

    public function setState($stateName)
    {
        // TODO: Implement setState() method.
    }

    public function state_change_possible($nextState)
    {
        // FIXME
        return true;
    }

    public function getStateString()
    {
        // FIXME
        return 'I have no fucking state';
    }

    public function getNextPossibleStates()
    {
        // TODO: Implement getNextPossibleStates() method.
    }

    public function getID(): ?int
    {
        // TODO: Implement getID() method.
    }

    public function render(): void
    {
        // TODO: Implement render() method.
    }

    public function handlePost(): void
    {
        if (isset($this->routeInfo['mfunction'])) {
            switch ($this->routeInfo['mfunction']) {
                case 'zahlungsanweisung':
                    $this->post_pdf_zahlungsanweisung($_POST['d'] === '0');
                    break;
                default:
                    throw new LegacyDieException(400, 'mfunction '.$this->routeInfo['mfunction'].' not known');
                    break;
            }
        }
    }

    private function post_pdf_zahlungsanweisung($modal = false): void
    {
        $details = [];
        // var_dump($this->auslagen_data["belege"]);
        $einnahme = 0;
        $ausgabe = 0;
        switch ('1') {
            case $this->data['flag_vorkasse']:
                $ausgabe = $this->data['value'];
                $name = 'Auszahlung Vorkasse';
                break;
            case $this->data['flag_pruefbescheid']:
                $ausgabe = $this->data['value'];
                $name = 'Auszahlung Prüfbescheid';
                break;
            case $this->data['flag_rueckforderung']:
                $einnahme = $this->data['value'];
                $name = 'Rückforderungsbescheid';
                break;
        }
        $details[] = [
            'beleg-id' => 'V'.$this->data['vorgang_id'],
            'projektposten' => '',
            'titel' => $this->data['titel_nr'],
            'einnahmen' => $einnahme,
            'ausgaben' => $ausgabe,
        ];
        $recht = 'StuRa-Beschluss: '.$this->meta_data['beschluss_nr'];

        $out = [
            'APIKEY' => FUI2PDF_APIKEY,
            'action' => 'zahlungsanweisung',

            'short-type-projekt' => 'EP',
            'projekt-id' => $this->data['extern_id'],
            'projekt-name' => $this->meta_data['projekt_name'],
            'projekt-org' => $this->meta_data['org_name'],
            'projekt-recht' => $recht,
            'projekt-create' => $this->data['date'],

            'short-type-auslage' => 'V',
            'auslage-id' => $this->data['vorgang_id'],
            'auslage-name' => $name,

            'zahlung-name' => $this->meta_data['zahlung_empf'],
            'zahlung-iban' => $this->meta_data['zahlung_iban'], // TODO: de- and encryprion
            'zahlung-value' => ($einnahme - $ausgabe),
            'zahlung-adresse' => $this->meta_data['org_address'],
            'angewiesen-date' => $this->data['date'],

            'details' => $details,
        ];
        $result = Helper::do_post_request2(FUI2PDF_URL.'/pdfbuilder', $out, FUI2PDF_AUTH);
        // return result to
        if ($result['success'] && $modal) {
            if (isset($result['data']['success']) && $result['data']['success']) {
                JsonController::print_json(
                    [
                        'success' => true,
                        'type' => 'modal',
                        'subtype' => 'file',
                        'container' => 'object',
                        'headline' =>
                        // direct link
                            '<form method="POST" action="'.URIBASE.'index.php'.$this->routeInfo['path'].'"><a '.
                            '" href="#" class="modal-form-fallback-submit text-white">'.
                            'Zahlungsanweisung-E'.
                            str_pad($this->data['extern_id'], 3, '0', STR_PAD_LEFT).
                            '-V'.
                            str_pad($this->data['vorgang_id'], 3, '0', STR_PAD_LEFT).
                            '.pdf'.
                            '</a>'.
                            '<input type="hidden" name="auslagen-id" value="'.$this->data['vorgang_id'].'">'.
                            '<input type="hidden" name="projekt-id" value="'.$this->data['extern_id'].'">'.
                            '<input type="hidden" name="d" value="1">'.'</form>',
                        'attr' => [
                            'type' => 'application/pdf',
                            'download' => 'Zahlungsanweisung-E'.
                                str_pad($this->data['extern_id'], 3, '0', STR_PAD_LEFT).
                                '-V'.
                                str_pad($this->data['vorgang_id'], 3, '0', STR_PAD_LEFT).
                                '.pdf',
                        ],
                        'fallback' => '<form method="POST" action="'.URIBASE.'index.php'.$this->routeInfo['path'].'">Die Datei kann leider nicht angezeigt werden, kann aber unter diesem <a '.
                            '" href="#" class="modal-form-fallback-submit">Link</a> heruntergeladen werden.'.
                            '<input type="hidden" name="auslagen-id" value="'.$this->data['vorgang_id'].'">'.
                            '<input type="hidden" name="projekt-id" value="'.$this->data['extern_id'].'">'.
                            '<input type="hidden" name="d" value="1">'.
                            '</form>',
                        'datapre' => 'data:application/pdf;base64,',
                        'data' => $result['data']['data'],
                    ]
                );
            } else {
                JsonController::print_json(
                    [
                        'success' => false,
                        'type' => 'modal',
                        'subtype' => 'server-error',
                        'status' => '200',
                        'msg' => '<div style="white-space: pre-wrap;">'.print_r(
                            $result['data']['error'] ?? $result['data'],
                            true
                        ).'</div>',
                    ]
                );
            }
        } elseif ($result['success'] && ! $modal) {
            header('Content-Type: application/pdf');
            header(
                'Content-Disposition: attachment; filename="'.'Belegvorlage_P'.
                str_pad($this->data['extern_id'], 3, '0', STR_PAD_LEFT).
                '-A'.
                str_pad($this->data['vorgang_id'], 3, '0', STR_PAD_LEFT).
                '.pdf'
                .'"'
            );
            echo base64_decode($result['data']['data']);
            exit();
        } else {
            throw new LegacyDieException(400, print_r($result, true), '['.get_class($this).'][PDF-Creation]');
            $this->error = 'Error during PDF creation.';
        }
    }
}
