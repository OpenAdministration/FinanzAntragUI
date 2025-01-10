<?php

// require_once (SYSBASE . "/lib/xmlrpc/xrpcClient.php");
// require_once 'XML/RPC2/Client.php';

namespace booking\konto;

use DateInterval;
use Exception;
use framework\DBConnector;
use framework\Singleton;
use XML_RPC2_Client;
use XML_RPC2_CurlException;

class HibiscusXMLRPCConnector extends Singleton
{
    private static $HIBISCUS_PASSWORD;

    private static $HIBISCUS_USERNAME;

    private static $HIBISCUS_BASE_URL;

    private static $HIBISCUS_RPCPATH;

    private $fetchableKontos;

    private $lastFetchedKontos;

    // private $xmlClient;

    protected function __construct()
    {
        // remove trailing slashes from URL
        if (self::$HIBISCUS_BASE_URL[strlen(self::$HIBISCUS_BASE_URL) - 1] === '/') {
            self::$HIBISCUS_BASE_URL = substr(self::$HIBISCUS_BASE_URL, 0, -1);
        }
        // DELETEME for new client
        self::$HIBISCUS_BASE_URL .= self::$HIBISCUS_RPCPATH;
        // DELETEME END
        $this->fetchableKontos = [];
        $this->lastFetchedKontos = [];
        /*$xmlClient = new \xmlrpc\xrpcClient(
            self::$HIBISCUS_BASE_URL,
            self::$HIBISCUS_USERNAME,
            self::$HIBISCUS_PASSWORD,
            self::$HIBISCUS_RPCPATH
        );*/
    }

    /**
     * @throws Exception
     */
    final protected static function static__set($name, $value): void
    {
        if (property_exists(__CLASS__, $name)) {
            self::$$name = $value;
        } else {
            throw new Exception("$name ist keine Variable in ".__CLASS__);
        }
    }

    /**
     * @return array [bool $success, string array $msgs]
     */
    public function updateKontoIDs(): array
    {
        if (! empty($this->fetchableKontos) && ! empty($this->lastFetchedKontos)) {
            return [true, []];
        }
        $ret = true;
        $msgs = [];
        $ktos = [];
        try {
            $client = XML_RPC2_Client::create(
                'https://'.rawurldecode(self::$HIBISCUS_USERNAME).':'.rawurlencode(self::$HIBISCUS_PASSWORD).
                '@'.rawurldecode(self::$HIBISCUS_BASE_URL).'xmlrpc/hibiscus.xmlrpc.konto',
                ['sslverify' => false, 'debug' => false, 'prefix' => 'hibiscus.xmlrpc.konto.']
            );
            $ktos = $client->find();
            if (count($ktos) === 0) {
                return [false, ['Konte kein Konto auf FINTS finden, bitte kontaktiere den Systemadministrator!']];
            }

            $dbKtos = DBConnector::getInstance()->dbFetchAll(
                'konto_type',
                [DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY]
            );

            foreach ($ktos as $kto) {
                // available keys: saldo_available, bezeichnung, saldo, unterkonto, blz, kundennummer, kontonummer, iban,
                // name, waehrung, saldo_datum, id, bic, kommentar
                $ktoId = $kto['id'];
                $ktoName = $kto['bezeichnung'];
                $ktoIBAN = $kto['iban'];
                $syncFrom = date_create('@0'); // frist Timestamp available
                foreach ($dbKtos as $dbKto) {
                    if ($ktoIBAN === $dbKto['iban'] && date_create($dbKto['sync_until'])->diff($syncFrom)->invert) {
                        $syncFrom = date_create($dbKto['sync_until'])->add(DateInterval::createFromDateString('1 day'));
                    }
                }

                if (! isset($dbKtos[$ktoId])) {
                    $short = strtoupper(substr($ktoName, 0, 2));
                    $ret_tmp = DBConnector::getInstance()->dbInsert(
                        'konto_type',
                        [
                            'id' => $ktoId,
                            'name' => $ktoName,
                            'short' => $short,
                            'iban' => $ktoIBAN,
                            'sync_from' => $syncFrom->format('Y-m-d'),
                        ]
                    );
                    $msgs[] = "<strong>Konto $ktoId:</strong> $ktoName ($short) (IBAN: $ktoIBAN) wurde neu gefunden".
                        (($ret_tmp > 0) ? ' und hinzugefügt' : ' konnte aber nicht hinzugefügt werden!');
                    $ret = $ret && ($ret_tmp > 0);
                }
                $this->lastFetchedKontos[$ktoId] = $kto;
            }
            $deletedInHibiscus = array_diff(
                array_keys($dbKtos),
                array_keys($this->lastFetchedKontos),
                [0] // 0 ist reserviert für die Handkasse
            );
            foreach ($deletedInHibiscus as $id) {
                $dbKto = $dbKtos[$id];
                $affectedRows = DBConnector::getInstance()->dbUpdate(
                    'konto_type',
                    ['id' => $id, 'sync_until' => ['IS', null]],
                    [
                        'sync_until' => date_create()
                            ->sub(DateInterval::createFromDateString('1 day'))
                            ->format('Y-m-d'),
                    ]
                );
                if ($affectedRows > 0) {
                    $msgs[] = "<strong>Konto $id:</strong> {$dbKto['name']} (IBAN: {$dbKto['iban']})".
                        'kann im FINTS nicht mehr gefunden werden. Die Synchronisation wird eingestellt.';
                }
            }

            // get Updated dbKtos
            $dbKtos = DBConnector::getInstance()->dbFetchAll(
                'konto_type',
                [DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY]
            );

            foreach ($this->lastFetchedKontos as $hibKtoId => $row) {
                $sign = date_create($dbKtos[$hibKtoId]['sync_until'])->diff(date_create(date('Y-m-d')))->invert;
                if ($sign === 1) {
                    $this->fetchableKontos[$hibKtoId] = $dbKtos[$hibKtoId];
                }
            }
        } catch (Exception $exception) {
            $ret = false;
            $msgs[] = 'Ein Fehler ist aufgetreten. Bitte benachrichtige den Systemadministrator.';
            if (DEV) {
                $msgs[] = $exception->getMessage();
            }
        }

        return [$ret, $msgs];
    }

    /**
     * @return array returns umsätze or Error Code
     */
    public function fetchAllUmsatz(): array
    {
        [$success, $msgs] = $this->updateKontoIDs();
        if (! $success) {
            return [false, $msgs, []];
        }
        // get data from RPC
        try {
            $umsatz = [];
            foreach ($this->fetchableKontos as $ktoid => $kto) {
                $letzteBankSync = date_create($this->lastFetchedKontos[$ktoid]['saldo_datum']);
                if ($letzteBankSync->diff(date_create('yesterday'))->days > 1) {
                    $msgs[] = 'FINTS hat die letzten 24h keine Synchronisation mit der Bank durchgeführt. Die angezeigten
					Umsätze können unvollständig sein.';
                }

                $umsopt = ['konto_id' => $ktoid];

                /*$url = "https://" . rawurldecode(self::$HIBISCUS_USERNAME) . ":" . rawurlencode(self::$HIBISCUS_PASSWORD) . "@" . rawurldecode(self::$HIBISCUS_URL) . "/webadmin/rest/system/status";
                echo htmlspecialchars($url);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                curl_close($ch);

                if ($response === false){
                    echo '<div class="alert alert-warning">FINTS hat keinen Status geliefert.</div>';
                }else{
                    print_r($response);

                    $response = json_decode($response, true);
                    if ($response === null || !is_array($response) || !isset($response["type"]) || !isset($response["title"]) || !isset($response["text"])){
                        echo '<div class="alert alert-warning">FINTS hat keinen parsbaren Status geliefert.</div>';
                    }else{
                        switch ($response["type"]){ # see src/de/willuhn/jameica/messaging/StatusBarMessage.java
                            case 0: # OK
                            case 2: # INFO
                                $cls = "success";
                                break;
                            case 1: # ERROR
                            default:
                                $cls = "danger";
                                $showStatus = true;
                        }
                        if ($showStatus){
                            echo '<div class="alert alert-' . $cls . '">' . htmlspecialchars("FINTS (" . $response["title"] . "): " . $response["text"]) . '</div>';
                        }
                    }
                }*/

                // letzter abgerufener Umsatz
                $lastUmsatzId = DBConnector::getInstance()->dbFetchAll(
                    'konto',
                    [DBConnector::FETCH_ASSOC],
                    ['max-id' => ['id', DBConnector::GROUP_MAX]],
                    ['konto_id' => $ktoid]
                );
                if (isset($lastUmsatzId[0]['max-id'])) {
                    $umsopt['id:min'] = 1 + $lastUmsatzId[0]['max-id'];
                }
                $sync_from = $this->fetchableKontos[$ktoid]['sync_from'];
                if (strtolower($sync_from) !== 'null' && ! is_null($sync_from) && strtotime($sync_from) > 0) {
                    $umsopt['datum:min'] = $this->fetchableKontos[$ktoid]['sync_from'];
                } else {
                    $umsopt['datum:min'] = '2017-01-01';
                }
                if (strtolower($this->fetchableKontos[$ktoid]['sync_until']) !== 'null'
                    && ! is_null($this->fetchableKontos[$ktoid]['sync_until'])) {
                    $umsopt['datum:max'] = $this->fetchableKontos[$ktoid]['sync_until'];
                }

                $client = XML_RPC2_Client::create(
                    'https://'.rawurldecode(self::$HIBISCUS_USERNAME).':'.rawurlencode(
                        self::$HIBISCUS_PASSWORD
                    ).'@'.rawurldecode(self::$HIBISCUS_BASE_URL).'/xmlrpc/hibiscus.xmlrpc.umsatz',
                    ['sslverify' => false, 'debug' => false, 'prefix' => 'hibiscus.xmlrpc.umsatz.']
                );
                $newUmsatz = $client->list($umsopt);
                array_push($umsatz, ...$newUmsatz);
                usort(
                    $umsatz,
                    static function ($a, $b) {
                        return $a['id'] <=> $b['id'];
                    }
                );
            }

            return [true, $msgs, $umsatz];
        } catch (XML_RPC2_CurlException $e) {
            return [false, $msgs, $umsatz];
        }
    }

    public function fetchFromHibiscusAnfangsbestand(): array
    {
        $year = date('Y');
        /*
        $f = ["type" => "kontenplan"];
        $f["state"] = "final";
        $f["revision"] = $year;
        $al = DBConnector::getInstance()->dbFetchAll("antrag", [], $f);
        if (count($al) != 1) die("Kontenplan nicht gefunden: " . print_r($f, true));
        $kpId = $al[0]["id"];

        // check anfangsbestand already saved
        if (DBConnector::getInstance()->dbHasAnfangsbestand("01 01", $kpId)){
            return [];
        }*/

        $client = XML_RPC2_Client::create(
            'https://'.rawurldecode(self::$HIBISCUS_USERNAME).':'.rawurlencode(
                self::$HIBISCUS_PASSWORD
            ).'@'.rawurldecode(self::$HIBISCUS_BASE_URL).'/xmlrpc/hibiscus.xmlrpc.konto',
            ['sslverify' => false, 'debug' => false, 'prefix' => 'hibiscus.xmlrpc.konto.']
        );
        $kto = $client->find();

        if (count($kto) === 0) {
            exit('Kein Bankkonto auf FINTS eingerichtet.');
        }
        if (count($kto) > 1) {
            exit('Mehr als ein Bankkonto auf FINTS eingerichtet.');
        }

        $kto = $kto[0];
        $ktoid = $kto['id'];
        $showStatus = false;

        $umsopt = ['konto_id' => $ktoid];

        /*$url = "https://" . rawurldecode(self::$HIBISCUS_USERNAME) . ":" . rawurlencode(self::$HIBISCUS_PASSWORD) . "@" . rawurldecode(self::$HIBISCUS_URL) . "/webadmin/rest/system/status";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false){
            echo '<div class="alert alert-warning">FINTS hat keinen Status geliefert.</div>';
        }else{
            echo "terst";
            echo $response;
            echo "3ews";
            $response = json_decode($response, true);
            if ($response === null || !is_array($response) || !isset($response["type"]) || !isset($response["title"]) || !isset($response["text"])){
                echo '<div class="alert alert-warning">FINTS hat keinen parsbaren Status geliefert.</div>';
            }else{
                switch ($response["type"]){ # see src/de/willuhn/jameica/messaging/StatusBarMessage.java
                    case 0: # OK
                    case 2: # INFO
                        $cls = "success";
                        break;
                    case 1: # ERROR
                    default:
                        $cls = "danger";
                        $showStatus = true;
                }
                if ($showStatus){
                    echo '<div class="alert alert-' . $cls . '">' . htmlspecialchars("FINTS (" . $response["title"] . "): " . $response["text"]) . '</div>';
                }
            }
        }*/
        /*
        // brauche umsatz vor $year-01-01 und nach $(year-1)-12-31
        $umsopt["datum:min"] = "$year-01-01";
        $umsopt["datum:max"] = "$year-12-31";

        $client = XML_RPC2_Client::create("https://" . rawurldecode(self::$HIBISCUS_USERNAME) . ":" . rawurlencode(self::$HIBISCUS_PASSWORD) . "@" . rawurldecode(self::$HIBISCUS_URL) . "/xmlrpc/hibiscus.xmlrpc.umsatz",
            ["sslverify" => false, "debug" => false, "prefix" => "hibiscus.xmlrpc.umsatz."]);
        $umsatzImJahr = $client->list($umsopt);

        $yearBefore = $year - 1;
        $umsopt["datum:min"] = "$yearBefore-01-01";
        $umsopt["datum:max"] = "$yearBefore-12-31";
        $umsatzImJahrDavor = $client->list($umsopt);

        if (count($umsatzImJahr) == 0 || count($umsatzImJahrDavor) == 0) // noch keine Umsätze
            return [];

        // lezter Umsatz im Jahr davor
        usort($umsatzImJahrDavor, function($a, $b){
            if ($a["id"] < $b["id"]) return -1;
            if ($a["id"] > $b["id"]) return 1;
            return 0;
        });

        $uLastBefore = array_pop($umsatzImJahrDavor);
        $saldo = $this->tofloatHibiscus($uLastBefore['saldo']);
        echo $saldo;
        /*
        $newForms = [];

        $datum = "$year-01-01";

        $inhalt = [];

        $inhalt[] = ["fieldname" => "zahlung.einnahmen", "contenttype" => "money", "value" => $saldo];

        $inhalt[] = ["fieldname" => "zahlung.datum", "contenttype" => "date", "value" => $datum];

        $inhalt[] = ["fieldname" => "zahlung.konto", "contenttype" => "ref", "value" => "01 01"];

        $inhalt[] = ["fieldname" => "kontenplan.otherForm", "contenttype" => "otherForm", "value" => $kpId];

        $newForms[] = $inhalt;
        */
        $newForms = [];

        return $newForms;
    }

    private function tofloatHibiscus($num)
    {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');

        if (($dotPos === false) && ($commaPos === false)) {
            $sep = false;
        } elseif ($dotPos !== false) {
            $sep = $dotPos;
        } elseif ($commaPos !== false) {
            $sep = $commaPos;
        } elseif ($commaPos > $dotPos) {
            $sep = $commaPos;
            exit('impossible');
        } else {
            $sep = $dotPos;
            exit('impossible');
        }

        if ($sep === false) {
            return (float) preg_replace("/[^0-9+\-]/", '', $num);
        }

        return (float) (preg_replace("/[^0-9+\-]/", '', substr($num, 0, $sep)).'.'.
            preg_replace("/[^0-9+\-]/", '', substr($num, $sep + 1, strlen($num))));
    }
}
