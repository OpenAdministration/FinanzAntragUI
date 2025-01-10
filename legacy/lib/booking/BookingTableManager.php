<?php

namespace booking;

use App\Exceptions\LegacyDieException;
use framework\auth\AuthHandler;
use framework\DBConnector;
use framework\render\Renderer;

class BookingTableManager extends Renderer
{
    private $col_zahlung;

    private $col_auslagen;

    private $col_posten;

    private $col_rest;

    private $zahlung_lastValue;

    private $auslage_lastValue;

    private $posten_lastValue;

    private $actual_instruction;

    private $table_tmp;

    private $table;

    private $executed;

    private $instructions;

    private $zahlungDB;

    private $belegeDB;

    private $kontoTypes;

    public function __construct($instructionsWhitelist = [])
    {
        $this->col_zahlung = 0;
        $this->col_auslagen = 0;
        $this->col_posten = 0;
        $this->col_rest = 0;
        $this->table = [];
        $this->table_tmp = [];
        $this->posten_lastValue = '';
        $this->auslage_lastValue = '';
        $this->zahlung_lastValue = '';
        $this->executed = false;
        $this->kontoTypes = [];

        $this->fetchFromDB($instructionsWhitelist);
    }

    private function fetchFromDB($instructionsWhitelist): void
    {
        $zahlungenDB = [];
        $belegeDB = [];

        if (! empty($instructionsWhitelist)) {
            $where = ['booking_instruction.id' => ['IN', $instructionsWhitelist], 'booking_instruction.done' => 0];
        } else {
            $where = ['booking_instruction.done' => 0];
        }

        $this->kontoTypes = DBConnector::getInstance()->dbFetchAll(
            'konto_type',
            [DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY]
        );

        $this->instructions = DBConnector::getInstance()->dbFetchAll(
            'booking_instruction',
            [DBConnector::FETCH_GROUPED],
            ['booking_instruction.id', 'zahlung', 'beleg', 'beleg_type', 'user.id', 'fullname' => 'user.name', 'zahlung_type'],
            $where,
            [
                ['table' => 'user', 'type' => 'left', 'on' => ['booking_instruction.by_user', 'user.id']],
            ],
            ['booking_instruction.id' => true]
        );

        // $this->instructions = array_intersect_key($this->instructions, array_flip([11, 6]));  // FIXME DELETEME
        // echo "<pre>";
        // var_dump($this->instructions[60]);
        // echo "</pre>";
        foreach ($this->instructions as $instruct_id => $instruction) {
            $zahlungen = [];
            $extern_ids = [];
            $auslagen_ids = [];
            foreach ($instruction as $row) {
                $zahlungen[$row['zahlung_type']][] = $row['zahlung'];
                switch ($row['beleg_type']) {
                    case 'belegposten':
                        $auslagen_ids[] = $row['beleg'];
                        break;
                    case 'extern':
                        $extern_ids[] = $row['beleg'];
                        break;
                    default:
                        throw new LegacyDieException(400, 'Beleg'.$row['beleg_type']);
                        break;
                }
            }
            $where = [];
            foreach ($zahlungen as $type => $z) {
                $where[] = ['id' => ['IN', $z], 'konto_id' => $type];
            }
            // titel_id, kostenstelle, zahlung_id, beleg_id, user_id, comment, value
            $zahlungenDB[$instruct_id] = DBConnector::getInstance()->dbFetchAll(
                'konto',
                [DBConnector::FETCH_ASSOC],
                [],
                $where,
                [],
                ['value' => false]
            );
            if (! empty($auslagen_ids)) {
                $auslagen = DBConnector::getInstance()->dbFetchAll(
                    'auslagen',
                    [DBConnector::FETCH_ASSOC],
                    [
                        'auslagen.projekt_id',
                        'auslagen_id' => 'auslagen.id',
                        'belege_id' => 'belege.id',
                        'titel_name',
                        'projekt_name' => 'projekte.name',
                        'projekt_createdate' => 'projekte.createdat',
                        'auslagen_name' => 'name_suffix',
                        'titel_nr',
                        'titel_id' => 'haushaltstitel.id',
                        'titel_type' => 'haushaltsgruppen.type',
                        'posten_id' => 'beleg_posten.id',
                        'posten_short' => 'beleg_posten.short',
                        'belege_short' => 'belege.short',
                        'beleg_posten.einnahmen',
                        'beleg_posten.ausgaben',
                        'etag',
                    ],
                    ['auslagen.id' => ['IN', $auslagen_ids]],
                    [
                        ['table' => 'projekte', 'type' => 'inner', 'on' => ['projekte.id', 'auslagen.projekt_id']],
                        ['table' => 'belege', 'type' => 'inner', 'on' => ['belege.auslagen_id', 'auslagen.id']],
                        ['table' => 'beleg_posten', 'type' => 'inner', 'on' => ['beleg_posten.beleg_id', 'belege.id']],
                        [
                            'table' => 'projektposten',
                            'type' => 'inner',
                            'on' => [
                                ['projektposten.id', 'beleg_posten.projekt_posten_id'],
                                ['auslagen.projekt_id', 'projektposten.projekt_id'],
                            ],
                        ],
                        [
                            'table' => 'haushaltstitel',
                            'type' => 'left',
                            'on' => ['projektposten.titel_id', 'haushaltstitel.id'],
                        ],
                        [
                            'table' => 'haushaltsgruppen',
                            'type' => 'left',
                            'on' => ['haushaltstitel.hhpgruppen_id', 'haushaltsgruppen.id'],
                        ],
                    ]
                );
                foreach ($auslagen as $id => $row) {
                    $auslagen[$id]['value'] = (float) $row['einnahmen'] - (float) $row['ausgaben'];
                    $auslagen[$id]['type'] = 'belegposten';
                }
            } else {
                $auslagen = [];
            }
            if (! empty($extern_ids)) {
                $extern = DBConnector::getInstance()->dbFetchAll(
                    'extern_data',
                    [DBConnector::FETCH_ASSOC],
                    [
                        'extern_data.id',
                        'extern_id',
                        'vorgang_id',
                        'projekt_name',
                        'org_name',
                        'flag_vorkasse',
                        'flag_rueckforderung',
                        'flag_pruefbescheid',
                        'titel_nr',
                        'titel_name',
                        'titel_id' => 'haushaltstitel.id',
                        'titel_type' => 'haushaltsgruppen.type',
                        'extern_data.value',
                        'etag',
                    ],
                    ['extern_data.id' => ['IN', $extern_ids]],
                    [
                        [
                            'table' => 'extern_meta',
                            'type' => 'inner',
                            'on' => ['extern_meta.id', 'extern_data.extern_id'],
                        ],
                        [
                            'table' => 'haushaltstitel',
                            'type' => 'left',
                            'on' => ['extern_data.titel_id', 'haushaltstitel.id'],
                        ],
                        [
                            'table' => 'haushaltsgruppen',
                            'type' => 'left',
                            'on' => ['haushaltstitel.hhpgruppen_id', 'haushaltsgruppen.id'],
                        ],
                    ]
                );
                foreach ($extern as $id => $row) {
                    $extern[$id]['type'] = 'extern';
                    $vz = ($row['flag_vorkasse'] === '1' || $row['flag_pruefbescheid'] === '1') ? -1 : 1;
                    $extern[$id]['value'] = $vz * (float) $row['value'];
                }
            } else {
                $extern = [];
            }
            // var_dump($auslagen_ids);
            $belegeDB[$instruct_id] = array_merge($auslagen, $extern);
            // echo "<pre>";
            // var_dump($belegeDB[$instruct_id]);
            // echo "</pre>";
            usort(
                $belegeDB[$instruct_id],
                static function ($a, $b) {
                    return $b['value'] <=> $a['value'];
                }
            );

            // $belegeDB[$instruct_id] = array_reverse($belegeDB[$instruct_id]); //FIXME DELETEME
            // $zahlungenDB[$instruct_id] = array_reverse($zahlungenDB[$instruct_id]); //FIXME DELETEME
        }

        $this->zahlungDB = $zahlungenDB;
        $this->belegeDB = $belegeDB;
    }

    public function getZahlungDB(): array
    {
        return $this->zahlungDB;
    }

    /**
     * @return array "instruct_id" => [
     *               [beleg]
     *               ]
     */
    public function getBelegeDB(): array
    {
        return $this->belegeDB;
    }

    public function getInstructions(): array
    {
        return $this->instructions;
    }

    public function render(): void
    {
        if ($this->executed === false) {
            $this->run();
        }
        $header = [
            'zahlung' => 'Zahlung',
            'zahlung-value' => 'Betrag Zahlung',
            'beleg' => 'Beleg',
            'posten' => 'Posten/Vorgang',
            'titel' => 'Titel',
            'posten-ist' => 'Betrag Buchung',
            'posten-soll' => 'Betrag Beleg',
            'text' => 'Buchungstext',
        ];
        $table = $this->getTable(); ?>
        <form method="POST" action="<?php echo URIBASE; ?>rest/booking/instruct/save" class="ajax-form">
            <table class="table">
                <thead>
                <tr>
					<?php
                    foreach ($header as $name) {
                        echo "<th>$name</th>";
                    } ?>
                </tr>
                </thead>
                <tbody>
				<?php
                foreach ($this->instructions as $instruct_id => $instruction) {
                    echo "<tr><td class='bg-info' colspan='".count($header)."'>";
                    echo "<input type='checkbox' class='form-check-input' name='activeInstruction[$instruct_id]'>";
                    $zCount = count($this->zahlungDB[$instruct_id]);
                    $bCount = count($this->belegeDB[$instruct_id]);
                    echo "<strong>Angewiesener Vorgang $instruct_id</strong> - ".$zCount.' Zahlung'.($zCount === 1
                            ? '' : 'en').' und '.$bCount.' Belegposten';
                    echo ' - Angewiesen von: '.array_values($instruction)[0]['fullname']; ?>
                    <form id="#form-delete-instruction-<?php echo $instruct_id; ?>" method="POST" class="ajax-form"
                          action="<?php echo URIBASE; ?>rest/booking/instruct/<?php echo $instruct_id; ?>/delete">
                        <?php $this->renderNonce(); ?>
                        <button type="submit" formaction="<?php echo URIBASE; ?>rest/booking/instruct/<?php echo $instruct_id; ?>/delete">
                            <i class="fa fa-fw fa-trash"></i>
                        </button>
                    </form>
                    <?php
                    echo '</td></tr>';

                    foreach ($table[$instruct_id] as $nr_of_rows => $row) {
                        echo '<tr>';
                        foreach ($header as $key => $text) {
                            if (isset($row[$key])) {
                                $cell = $row[$key];
                                $title = $cell['title'] ?? '';
                                $colspan = $cell['colspan'] ?? 1;
                                $id = 'booking-table_'.$key.'-'.$nr_of_rows;
                                echo "<td id='$id' class='vertical-center no-wrap' colspan='$colspan' rowspan='{$cell['rowspan']}' title='$title'>{$cell['val']}</td>";
                            }
                        }
                        echo '</tr>';
                    }
                } ?>
                </tbody>
            </table>
			<?php
            $this->renderNonce(); ?>
            <button class="btn btn-primary pull-right"
				<?php echo ! AuthHandler::getInstance()->hasGroup('ref-finanzen-kv') ?
                    "disabled title='Nur Kassenverantwortliche können eine Buchung durchführen!'" : ''; ?>
            >
                Buchung(en) durchführen
            </button>
        </form>
		<?php
    }

    public function run(): void
    {
        foreach ($this->instructions as $instruction_id => $someNotUsedValue) {
            $this->nextInstruction($instruction_id); // set
            $zAll = $this->zahlungDB[$instruction_id];
            $bAll = $this->belegeDB[$instruction_id];
            foreach ($zAll as $z_key => $z) {
                $zVal = round((float) $z['value'], 2);
                // echo "Z".$zVal;
                foreach ($bAll as $b_key => $b) {
                    $bVal = round((float) $b['value'], 2);
                    // echo "B".$bVal;
                    if ($bVal === $zVal) {
                        $this->processLine($z, $b, $bVal);
                        unset($bAll[$b_key], $zAll[$z_key]);
                        break;
                    }
                }
            }
            if (count($bAll) === 1) {
                $b = array_values($bAll)[0];
                foreach ($zAll as $z) {
                    $zVal = (float) $z['value'];
                    $this->processLine($z, $b, $zVal);
                }
            } elseif (count($zAll) === 1) {
                $z = array_values($zAll)[0];
                foreach ($bAll as $b) {
                    $bVal = (float) $b['value'];
                    $this->processLine($z, $b, $bVal);
                }
            } else {
                $zValDone = 0;
                $bValDone = 0;
                foreach ($zAll as /* $z_key => */ $z) {
                    $zVal = (float) $z['value'];
                    foreach ($bAll as $b_key => $b) {
                        $bVal = (float) $b['value'];
                        /* var_dump([
                            "z_key" => $z_key,
                            "b_key" => $b_key,
                            "zValDone" => $zValDone,
                            "bValDone" => $bValDone,
                            "zVal" => $zVal,
                            "bVal" => $bVal,
                        ]); */
                        if ($zVal - $zValDone < $bVal - $bValDone) {
                            $zValDone = round($zValDone + $bVal - $bValDone, 2);
                            $this->processLine($z, $b, $bVal - $bValDone);
                            unset($bAll[$b_key]); // remove used belege - single use only
                            $bValDone = 0;
                        } else {
                            $this->processLine($z, $b, $zVal - $zValDone);
                            $bValDone = round($bValDone + $zVal - $zValDone, 2);
                            $zValDone = 0;
                            break; // next Zahlung
                        }
                    }
                }
            }
        }
        $this->executed = true;
    }

    private function processLine($z, $b, $val): void
    {
        switch ($b['type']) {
            case 'belegposten':
                $prefilledText = $b['projekt_name'].' - '.$b['auslagen_name'];
                $newPostenName = 'P'.$b['posten_short'];
                $newPostenNameRaw = $b['posten_id'];
                $newBelegName = $this->auslagenLinkEscapeFunction(
                    $b['projekt_id'],
                    $b['auslagen_id'],
                    'B'.$b['belege_short']
                );
                break;
            case 'extern':
                $prefilledText = $b['projekt_name'].' - '.$b['org_name'];
                $newPostenName = 'V'.$b['vorgang_id'];
                $newPostenNameRaw = $b['id'];
                $newBelegName = 'E'.$b['extern_id'];
                break;
            default:
                throw new LegacyDieException(500, 'Unbekannter Typ: '.$b['type'], var_export($b, true));

                return;
        }

        $bVal = $b['value'];
        $zVal = $z['value'];

        $this->pushPosten(
            $newPostenName,
            $newPostenNameRaw,
            $newBelegName,
            $b['titel_id'],
            $b['titel_type'],
            $b['titel_nr'],
            $b['titel_name'],
            $bVal
        );
        $this->pushZahlung($z['id'], $z['konto_id'], $zVal);
        $this->pushBeleg($newBelegName, $b['type']);
        $this->pushNewPostenIst($val, $b['titel_type'], $prefilledText);
    }

    public function nextInstruction(int $i): void
    {
        if (isset($this->actual_instruction)) {
            $this->table[$this->actual_instruction] = $this->table_tmp;
            $this->table_tmp = [];
            $this->col_zahlung = 0;
            $this->col_auslagen = 0;
            $this->col_posten = 0;
            $this->col_rest = 0;
            $this->posten_lastValue = '';
            $this->auslage_lastValue = '';
            $this->zahlung_lastValue = '';
        }
        $this->actual_instruction = $i;
    }

    public function getTable($fullRows = false): array
    {
        $this->table[$this->actual_instruction] = $this->table_tmp;
        if ($fullRows === false) {
            return $this->table;
        }

        $ret_table = [];
        foreach ($this->table as $instruction_id => $rowGroups) {
            foreach ($rowGroups as $id => $row) {
                foreach (array_keys($row) as $key) {
                    $rowspan = $row[$key]['rowspan'];
                    unset($row[$key]['rowspan'], $row[$key]['colspan']);
                    $idx = 0;
                    while ($idx < $rowspan) {
                        $ret_table[$instruction_id][$id + $idx][$key] = $row[$key];
                        $idx++;
                    }
                }
            }
        }

        return $ret_table;
    }

    public function pushBeleg(string $belegValue, string $type): void
    {
        if ($this->auslage_lastValue === $belegValue) {
            $this->extendLastBeleg();
        } else {
            if (isset($this->table_tmp[$this->col_auslagen]['beleg']['rowspan'])) {
                $this->col_auslagen += $this->table_tmp[$this->col_auslagen]['beleg']['rowspan'];
            }
            $this->table_tmp[$this->col_auslagen]['beleg'] = [
                'val' => $belegValue,
                'rowspan' => 1,
                'colspan' => 1,
                'beleg-type' => $type,
            ];
            $this->auslage_lastValue = $belegValue;
        }
    }

    public function pushZahlung(int $zahlungId, int $zahlungIdType, float $zahlungValue): void
    {
        $prefix = '';
        if (isset($this->kontoTypes[$zahlungIdType])) {
            $prefix = $this->kontoTypes[$zahlungIdType]['short'];
        } else {
            throw new LegacyDieException(500, "Konto Type $zahlungIdType nicht bekannt.");
        }
        $newValue = $prefix.$zahlungId;
        if ($this->zahlung_lastValue === $newValue) {
            $this->extendLastZahlung();
        } else {
            if (isset($this->table_tmp[$this->col_zahlung]['zahlung']['rowspan'])) {
                $this->col_zahlung += $this->table_tmp[$this->col_zahlung]['zahlung']['rowspan'];
            }
            $this->table_tmp[$this->col_zahlung]['zahlung'] = [
                'val' => $newValue,
                'val-raw' => $zahlungId,
                'rowspan' => 1,
                'colspan' => 1,
                'zahlung-type' => $zahlungIdType,
            ];
            $this->table_tmp[$this->col_zahlung]['zahlung-value'] = [
                'val' => $this->moneyEscapeFunction($zahlungValue),
                'val-raw' => $zahlungValue,
                'rowspan' => 1,
                'colspan' => 1,
            ];
            $this->zahlung_lastValue = $newValue;
        }
    }

    public function pushPosten(
        $newValue, $newValueRaw, $belegName, $titelId, $titelType, $titelNr, $titelName, $postenSoll
    ): void {
        if ($this->posten_lastValue === $newValue && $belegName === $this->auslage_lastValue) {
            $this->extendLastPosten();
        } else {
            if (isset($this->table_tmp[$this->col_posten]['posten']['rowspan'])) {
                $this->col_posten += $this->table_tmp[$this->col_posten]['posten']['rowspan'];
            }
            $this->table_tmp[$this->col_posten]['posten'] = [
                'val' => $newValue,
                'val-raw' => $newValueRaw,
                'rowspan' => 1,
                'colspan' => 1,
            ];
            $this->table_tmp[$this->col_posten]['posten-soll'] = [
                'val' => $this->moneyEscapeFunction($postenSoll),
                'val-raw' => $postenSoll,
                'rowspan' => 1,
                'colspan' => 1,
            ];
            $this->table_tmp[$this->col_posten]['titel'] = [
                'val' => $titelNr,
                'val-raw' => $titelId,
                'rowspan' => 1,
                'colspan' => 1,
                'title' => $titelName,
                'type' => $titelType,
            ];
            $this->posten_lastValue = $newValue;
        }
    }

    public function pushNewPostenIst($postenIstValue, $titel_type, $prefilledText = ''): void
    {
        if ($titel_type === 1) {
            $postenIstValue = -$postenIstValue;
        }
        $this->table_tmp[$this->col_rest]['posten-ist'] = [
            'val' => $this->moneyEscapeFunction($postenIstValue),
            'val-raw' => $postenIstValue,
            'rowspan' => 1,
            'colspan' => 1,
        ];
        $this->table_tmp[$this->col_rest]['text'] = [
            'val' => $this->textAreaEscapeFunction("text[$this->actual_instruction][]", $prefilledText, true),
            'rowspan' => 1,
            'colspan' => 1,
        ];
        $this->col_rest++;
    }

    public function extendLastBeleg(): bool
    {
        if (isset($this->table_tmp[$this->col_auslagen]['beleg'])) {
            $this->table_tmp[$this->col_auslagen]['beleg']['rowspan']++;

            return true;
        }

        return false;
    }

    public function extendLastZahlung(): bool
    {
        if (isset($this->table_tmp[$this->col_zahlung]['zahlung'], $this->table_tmp[$this->col_zahlung]['zahlung-value'])) {
            $this->table_tmp[$this->col_zahlung]['zahlung']['rowspan']++;
            $this->table_tmp[$this->col_zahlung]['zahlung-value']['rowspan']++;

            return true;
        }

        return false;
    }

    public function extendLastPosten(): bool
    {
        if (isset($this->table_tmp[$this->col_posten]['posten'])) {
            $this->table_tmp[$this->col_posten]['posten']['rowspan']++;
            $this->table_tmp[$this->col_posten]['posten-soll']['rowspan']++;
            $this->table_tmp[$this->col_posten]['titel']['rowspan']++;

            return true;
        }

        return false;
    }
}
