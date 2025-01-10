<?php

namespace framework;

use App\Exceptions\LegacyDieException;
use framework\render\HTMLPageRenderer;
use Illuminate\Support\Facades\DB;
use PDO;

class DBConnector extends Singleton
{
    public const GROUP_NOTHING = 0;

    public const GROUP_SUM = 1;

    public const GROUP_SUM_ROUND2 = 2;

    public const GROUP_COUNT = 3;

    public const GROUP_MAX = 4;

    public const GROUP_MIN = 5;

    public const FETCH_NUMERIC = 1;

    public const FETCH_ASSOC = 2;

    public const FETCH_UNIQUE_FIRST_COL_AS_KEY = 3;

    public const FETCH_ONLY_FIRST_COLUMN = 4;

    public const FETCH_UNIQUE = 5;

    public const FETCH_GROUPED = 6;

    public const SQL_DATE_FORMAT = 'Y-m-d';

    public const SQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    private PDO $pdo;

    private array $scheme;

    private array $schemeKeys;

    private array $validFields;

    private int $transactionCount = 0;

    private array $user;

    public string $dbPrefix;

    public function __construct()
    {
        HTMLPageRenderer::registerProfilingBreakpoint('init-db-connection');
        $this->initScheme();
        $this->pdo = DB::getPdo();
        $this->dbPrefix = config('database.connections.mysql.prefix');
    }

    private function initScheme(): void
    {
        $scheme = [];
        $keys = [];

        $scheme['comments'] = [
            'id' => 'INT NOT NULL',
            'target_id' => 'INT NOT NULL',
            'target' => 'VARCHAR(64)',
            'timestamp' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'creator' => 'VARCHAR(128) NOT NULL',
            'creator_alias' => 'VARCHAR(256) NOT NULL',
            'text' => 'TEXT NOT NULL',
            'type' => "tinyint(2) NOT NULL DEFAULT '0' COMMENT '0 = comment, 1 = state_change, 2 = admin only'",
        ];

        $keys['comments'] = [
            'primary' => ['id'],
        ];

        $scheme['booking'] = [
            'id' => 'INT NOT NULL',
            'titel_id' => 'int NOT NULL',
            'kostenstelle' => 'int NOT NULL',
            'zahlung_id' => 'INT NOT NULL',
            'zahlung_type' => 'INT NOT NULL',
            'beleg_id' => 'INT NOT NULL',
            'beleg_type' => 'VARCHAR(16) NOT NULL',
            'user_id' => 'int NOT NULL',
            'timestamp' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'comment' => 'varchar(2048) NOT NULL',
            'value' => 'FLOAT NOT NULL',
            'canceled' => 'INT NOT NULL DEFAULT 0',
        ];

        $keys['booking'] = [
            'primary' => ['id'],
            'foreign' => [
                [
                    'refTable' => 'konto',
                    'columns' => ['zahlung_id', 'zahlung_type'],
                    'refColumns' => ['id', 'konto_id'],
                ],
                // "zahlung_type" => ["", "id"],
                'titel_id' => ['haushaltstitel', 'id'],
                'user_id' => ['user', 'id'],
            ],
        ];

        $scheme['booking_instruction'] = [
            'id' => 'INT NOT NULL',
            'zahlung' => 'INT NOT NULL',
            'zahlung_type' => 'INT NOT NULL',
            'beleg' => 'INT NOT NULL',
            'beleg_type' => 'VARCHAR(16) NOT NULL',
            'by_user' => 'INT NOT NULL',
            'done' => 'BOOLEAN NOT NULL DEFAULT 0',
        ];
        $keys['booking_instruction'] = [
            'foreign' => [
                'by_user' => ['user', 'id'],
            ],
        ];

        $scheme['user'] = [
            'id' => 'INT NOT NULL',
            // 'fullname' => 'varchar(255) NOT NULL',
            'name' => 'varchar(255) NOT NULL', // virtual alias for fullname
            'username' => 'varchar(32) NOT NULL',
            'email' => 'varchar(128) NOT NULL',
            'iban' => "varchar(32) NOT NULL DEFAULT ''",
        ];
        $keys['user'] = [
            'primary' => ['id'],
        ];

        $scheme['haushaltstitel'] = [
            'id' => ' int NOT NULL',
            'hhpgruppen_id' => ' int NOT NULL',
            'titel_name' => ' varchar(128) NOT NULL',
            'titel_nr' => ' varchar(10) NOT NULL',
            'value' => 'float NOT NULL',
        ];
        $keys['haushaltstitel'] = [
            'primary' => ['id'],
            'foreign' => [
                'hhpgruppen_id' => ['haushaltsgruppen', 'id'],
            ],
        ];

        $scheme['haushaltsgruppen'] = [
            'id' => 'int NOT NULL',
            'hhp_id' => ' int NOT NULL',
            'gruppen_name' => ' varchar(128) NOT NULL',
            'type' => 'tinyint(1) NOT NULL',
        ];
        $keys['haushaltsgruppen'] = [
            'primary' => ['id'],
            'foreign' => [
                'hhp_id' => ['haushaltsplan', 'id'],
            ],
        ];

        $scheme['projektposten'] = [
            'id' => ' INT NOT NULL',
            'projekt_id' => ' INT NOT NULL',
            'titel_id' => ' INT NULL DEFAULT NULL',
            'einnahmen' => ' FLOAT NOT NULL',
            'ausgaben' => ' FLOAT NOT NULL',
            'name' => ' VARCHAR(128) NOT NULL',
            'bemerkung' => ' VARCHAR(256) NOT NULL',
        ];
        $keys['projektposten'] = [
            'primary' => ['id', 'projekt_id'],
            'foreign' => [
                'projekt_id' => ['projekte', 'id'],
            ],
        ];

        $scheme['konto'] = [
            'id' => 'INT NOT NULL',
            'konto_id' => 'INT NOT NULL',
            'date' => 'DATE NOT NULL',
            'valuta' => 'DATE NOT NULL',
            'type' => 'VARCHAR(128) NOT NULL',
            'empf_iban' => "VARCHAR(40) NOT NULL DEFAULT ''",
            'empf_bic' => "VARCHAR(11) DEFAULT ''",
            'empf_name' => "VARCHAR(128) NOT NULL DEFAULT ''",
            'primanota' => 'float NOT NULL DEFAULT 0',
            'value' => 'DECIMAL(10,2) NOT NULL',
            'saldo' => 'DECIMAL(10,2) NOT NULL',
            'zweck' => 'varchar(512) NOT NULL',
            'comment' => "varchar(128) NOT NULL DEFAULT ''",
            'gvcode' => 'int NOT NULL DEFAULT 0',
            'customer_ref' => 'varchar(128)',
        ];
        $keys['konto'] = [
            'primary' => ['id', 'konto_id'],
            'foreign' => [
                'konto_id' => ['konto_type', 'id'],
            ],
        ];

        $scheme['konto_type'] = [
            'id' => 'INT NOT NULL',
            'name' => 'VARCHAR(32) NOT NULL',
            'short' => 'VARCHAR(2) NOT NULL',
            'sync_from' => 'DATE NULL',
            'sync_until' => 'DATE NULL',
            'iban' => 'VARCHAR(32) NULL',
            'last_sync' => 'DATE NULL',
        ];
        $keys['konto_type'] = [
            'primary' => ['id'],
        ];

        $scheme['projekte'] = [
            'id' => 'INT NOT NULL',
            'creator_id' => 'INT NOT NULL',
            'createdat' => 'DATETIME NOT NULL',
            'lastupdated' => 'DATETIME NOT NULL',
            'version' => 'INT NOT NULL DEFAULT 1',
            'state' => 'VARCHAR(32) NOT NULL',
            'stateCreator_id' => 'INT NOT NULL',
            'name' => 'VARCHAR(128) NULL',
            'responsible' => "VARCHAR(128) NULL COMMENT 'EMAIL'",
            'org' => 'VARCHAR(64) NULL',
            'org-mail' => 'VARCHAR(128) NULL',
            'protokoll' => 'VARCHAR(256) NULL',
            'recht' => 'VARCHAR(64) NULL',
            'recht-additional' => 'VARCHAR(128) NULL',
            'date-start' => 'DATE NULL',
            'date-end' => 'DATE NULL',
            'beschreibung' => 'TEXT NULL',
        ];
        $keys['projekte'] = [
            'primary' => ['id'],
            'foreign' => [
                'creator_id' => ['user', 'id'],
                'stateCreator_id' => ['user', 'id'],
            ],
        ];

        // auslagen ---------------------
        $scheme['auslagen'] = [
            'id' => 'INT NOT NULL',
            'projekt_id' => 'INT NOT NULL',
            'name_suffix' => 'VARCHAR(255) NULL',
            'state' => 'VARCHAR(255) NOT NULL',
            'ok-belege' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'ok-hv' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'ok-kv' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'payed' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'rejected' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'zahlung-iban' => 'VARCHAR(1023) NOT NULL',
            'zahlung-name' => 'VARCHAR(127) NOT NULL',
            'zahlung-vwzk' => 'VARCHAR(127) NOT NULL',
            'address' => "VARCHAR(1023) NOT NULL DEFAULT ''",
            'last_change' => 'DATETIME NOT NULL DEFAULT NOW()',
            'last_change_by' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'etag' => 'VARCHAR(255) NOT NULL',
            'version' => 'INT NOT NULL DEFAULT 1',
            'created' => "VARCHAR(255) NOT NULL DEFAULT ''",
        ];
        $keys['auslagen'] = [
            'primary' => ['id'],
            'foreign' => [
                'projekt_id' => ['projekte', 'id'],
            ],
        ];

        $scheme['belege'] = [
            'id' => 'INT NOT NULL',
            'auslagen_id' => 'INT NOT NULL',
            'short' => 'VARCHAR(45) NULL',
            'created_on' => 'DATETIME NOT NULL DEFAULT NOW()',
            'datum' => 'DATETIME NULL DEFAULT NULL',
            'beschreibung' => 'TEXT NOT NULL',
            'file_id' => 'INT NULL DEFAULT NULL',
        ];
        $keys['belege'] = [
            'primary' => ['id'],
            'foreign' => [
                'auslagen_id' => ['auslagen', 'id'],
                // "file_id" => ["fileinfo", "id"]
            ],
        ];

        $scheme['beleg_posten'] = [
            'id' => 'INT NOT NULL',
            'beleg_id' => 'INT NOT NULL',
            'short' => 'INT NOT NULL',
            'projekt_posten_id' => 'INT NOT NULL',
            'ausgaben' => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
            'einnahmen' => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
        ];
        $keys['beleg_posten'] = [
            'primary' => ['id'],
            'unique' => [
                'uid' => ['short', 'beleg_id'],
            ],
            'foreign' => [
                'beleg_id' => ['belege', 'id'],
                // "projekt_posten_id" => ["projektposten", "id"], would need to be double key -> info missing
            ],
        ];

        // dateinen ---------------------
        $scheme['fileinfo'] = [
            'id' => 'INT NOT NULL',
            'link' => 'VARCHAR(127) NOT NULL',
            'added_on' => 'DATETIME NOT NULL DEFAULT NOW()',
            'hashname' => 'VARCHAR(255) NOT NULL',
            'filename' => 'VARCHAR(255) NOT NULL',
            'size' => 'INT NOT NULL DEFAULT 0',
            'fileextension' => "VARCHAR(45) NOT NULL DEFAULT ''",
            'mime' => 'VARCHAR(256) NULL',
            'encoding' => 'VARCHAR(45) NULL',
            'data' => 'INT NULL DEFAULT NULL',
        ];
        $keys['fileinfo'] = [
            'primary' => ['id'],
            'foreign' => [
                'data' => ['filedata', 'id'],
            ],
        ];

        $scheme['filedata'] = [
            'id' => 'INT NOT NULL',
            'data' => 'LONGBLOB NULL DEFAULT NULL',
            'diskpath' => 'VARCHAR(511) NULL DEFAULT NULL',
        ];
        $keys['filedata'] = [
            'primary' => ['id'],
        ];

        $scheme['haushaltsplan'] = [
            'id' => 'INT NOT NULL',
            'von' => 'DATE NULL',
            'bis' => 'DATE NULL',
            'state' => 'VARCHAR(64) NOT NULL',
        ];
        $keys['haushaltsplan'] = [
            'primary' => ['id'],
        ];

        $scheme['extern_meta'] = [
            'id' => 'INT NOT NULL',
            'projekt_name' => 'VARCHAR(511) NOT NULL',
            'projekt_von' => 'DATE NULL',
            'projekt_bis' => 'DATE NULL',
            'contact_mail' => 'VARCHAR(127) NULL',
            'contact_name' => 'VARCHAR(128) NULL',
            'contact_phone' => 'VARCHAR(32) NULL',
            'org_address' => 'VARCHAR(255) NULL',
            'org_name' => 'VARCHAR(127) NULL',
            'org_mail' => 'VARCHAR(127) NULL',
            'zahlung_empf' => 'VARCHAR(127) NULL',
            'zahlung_iban' => 'VARCHAR(45) NULL',
            'beschluss_nr' => 'VARCHAR(15) NOT NULL',
            'beschluss_datum' => 'DATE NOT NULL',
            'beschluss_summe' => 'DECIMAL(8,2) NOT NULL',
            'beschluss_vorkasse' => 'DECIMAL(8,2) NOT NULL',
        ];
        $keys['extern_meta'] = [
            'primary' => ['id'],
        ];

        $scheme['extern_data'] = [
            'id' => 'INT NOT NULL',
            'vorgang_id' => 'INT NOT NULL',
            'extern_id' => 'INT NOT NULL',
            'titel_id' => 'INT NOT NULL',
            'date' => 'DATETIME',
            'by_user' => 'INT NULL',
            'value' => 'DECIMAL(10,2) NULL',
            'description' => 'TEXT NULL',
            'ok-hv' => 'varchar(63) NULL',
            'ok-kv' => 'varchar(63) NULL',
            'frist' => 'DATETIME NULL',
            'flag_vorkasse' => 'TINYINT(1) DEFAULT 0',
            'flag_bewilligungsbescheid' => 'TINYINT(1) DEFAULT 0',
            'flag_pruefbescheid' => 'TINYINT(1) DEFAULT 0',
            'flag_rueckforderung' => 'TINYINT(1) DEFAULT 0',
            'flag_mahnung' => 'TINYINT(1) DEFAULT 0',
            'flag_done' => 'TINYINT(1) DEFAULT 0',
            'state_instructed' => 'varchar(63) NULL',
            'state_payed' => 'varchar(63) NULL',
            'state_booked' => 'varchar(63) NULL',
            'ref_file_id' => 'INT NULL',
            'flag_widersprochen' => 'TINYINT(1) DEFAULT 0',
            'widerspruch_date' => 'DATETIME NULL',
            'widerspruch_file_id' => 'INT NULL',
            'widerspruch_text' => 'TEXT NULL',
            'etag' => 'VARCHAR(255) NOT NULL',
        ];
        $keys['extern_data'] = [
            'primary' => ['id'],
            'foreign' => [
                'extern_id' => ['extern_meta', 'id'],
            ],
            'unique' => [
                ['vorgang_id', 'extern_id'],
            ],
        ];

        $scheme['konto_bank'] = [
            'id' => 'INT NOT NULL',
            'url' => 'VARCHAR(256) NOT NULL',
            'blz' => 'INT NOT NULL',
            'name' => 'VARCHAR(256) NOT NULL',
        ];
        $keys['konto_bank'] = [
            'primary' => ['id'],
        ];

        $scheme['konto_credentials'] = [
            'id' => 'INT NOT NULL',
            'name' => 'VARCHAR(63) NOT NULL',
            'bank_id' => 'INT NOT NULL',
            'owner_id' => 'INT NOT NULL',
            'bank_username' => 'VARCHAR(32) NOT NULL',
            'tan_mode' => 'INT NULL',
            'tan_mode_name' => 'VARCHAR(63) NULL',
            'tan_medium_name' => 'VARCHAR(63) NULL',
        ];
        $keys['konto_credentials'] = [
            'primary' => ['id'],
            'foreign' => [
                'owner_id' => ['user', 'id'],
                'bank_id' => ['konto_bank', 'id'],
            ],
        ];

        // ---------------- //

        $scheme['log'] = [
            'id' => 'int NOT NULL',
            'action' => 'VARCHAR(255) NOT NULL',
            'evtime' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'user_id' => 'INT DEFAULT NULL',
        ];
        $keys['log'] = [
            'primary' => ['id'],
        ];

        $scheme['log_property'] = [
            'id' => 'INT NOT NULL',
            'log_id' => 'INT NOT NULL',
            'name' => 'VARCHAR(127)',
            'value' => 'LONGTEXT',
        ];
        $keys['log_property'] = [
            'primary' => ['id'],
            'foreign' => [
                'log_id' => ['log', 'id'],
            ],
        ];

        $this->scheme = $scheme;
        $this->schemeKeys = $keys;

        // build valid fields out of schemes
        $validFields = [['*']];
        $blacklist = ['log', 'log_property'];
        foreach ($scheme as $tblname => $content) {
            $validFields[] = ["$tblname.*"];
            if (! is_array($content)) {
                continue;
            }
            if (in_array($tblname, $blacklist, true)) {
                continue;
            }
            $colnames = array_keys($content);
            // all all colnames of this table
            $validFields[] = $colnames;
            $func = static function (&$val, $key) use ($tblname) {
                $val = $tblname.'.'.$val;
            };
            // add all colnames with tablename.colname
            array_walk($colnames, $func);
            $validFields[] = $colnames;
        }
        $validFields = array_merge(...$validFields);
        $this->validFields = array_unique($validFields);
    }

    public function convertDBValueToUserValue($value, $type): bool|string
    {
        switch ($type) {
            case 'money':
                $value = (string) $value;
                if ($value === false || $value === '') {
                    return $value;
                }

                return number_format($value, 2, ',', '');
            case 'date':
            case 'daterange':
                return htmlspecialchars(date('d.m.Y', strtotime($value)));
            default:
                return $value;
        }
    }

    public function convertUserValueToDBValue($value, $type): array|string
    {
        $length = strlen($value);

        switch ($type) {
            case 'titelnr':
                $value = trim(str_replace(' ', '', $value));
                $nv = '';
                for ($i = 0; $i < $length; $i++) {
                    if ($i % 4 === 1) {
                        $nv .= ' ';
                    }
                    $nv .= $value[$i];
                }

                return $nv;
            case 'kostennr':
                $value = trim(str_replace(' ', '', $value));
                $nv = '';
                for ($i = 0; $i < $length; $i++) {
                    if ($i % 3 == 2) {
                        $nv .= ' ';
                    }
                    $nv .= $value[$i];
                }

                return $nv;
            case 'kontennr':
                $value = trim(str_replace(' ', '', $value));
                $nv = '';
                for ($i = 0; $i < $length; $i++) {
                    if ($i % 2 === 0 && $i > 0) {
                        $nv .= ' ';
                    }
                    $nv .= $value[$i];
                }

                return $nv;
            case 'money':
                return str_replace(['.', ',', ' '], ['', '.', ''], $value);
            default:
                return $value;
        }
    }

    /**
     * @return PDO $pdo
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * @return string $DB_PREFIX
     */
    public function getDbPrefix(): string
    {
        return $this->dbPrefix;
    }

    /**
     * @deprecated use Auth::user() instead
     */
    public function getUser(): array
    {
        if (isset($this->user)) {
            return $this->user;
        }

        $user = \Auth::user()?->toArray();
        $user['fullname'] = $user['name'];
        $this->user = $user;

        return $user;
    }

    /**
     * @param  string|array  $tables  table which should be used in FROM statement
     *                                if $tabels is array [t1,t2, ...]: FROM t1, t2, ...
     * @param  array  $showColumns  if empty array there will be all coulums (*) shown
     *                              if keys are not numeric, key will be used as alias
     *                              don't use same alias twice (ofc)
     *                              renaming of tables is possible
     *                              e.g.: newname => tablename.*, numerik keys(newname) will be ignored
     *                              will be: newname.col1, newname.col2 ...
     *                              if values of $showColumns are arrays, there can be aggregated functions as
     *                              second value, fist value is the columnname e.g. alias => ["colname", SUM]
     * @param  array  $where  val no array [colname => val,...]: WHERE colname = val AND ...
     *
     *                                  if val is array [colname => [operator,value],...]: WHERE colname operator value
     *                                  AND
     *                                  ...
     *
     *                                  if value is array [colname => [operator,[v1,v2,...]],...]: WHERE colname
     *                                  operator
     *                                  (v1,v2,...) AND ...
     *
     *                                  Version from before can be used as ANDBLOCK, the following syntax is also possible:
     *                                      [ ANDBLOCK1, ANDBLOCK2, ... ]: WHERE (ANDBLOCK1) OR (ANDBLOCK2) OR (...)
     * @param  array  $joins  Fields which should be joined:
     *                        ["type"="inner",table => "tblname","on" =>
     *                        [["tbl1.col","tbl2.col"],...],"operator"
     *                        => ["=",...]] Will be: FROM $table INNER JOIN tblname ON (tbl1.col =
     *                        tbl2.col AND
     *                        ... )
     *
     *                                  accepted values (<u>default</u>):
     *
     *                                   * type: <u>inner</u>, natural, left, right
     *
     *                                   * operator: <u>=</u>, <, >,<>, <=, >=
     *
     *                                  There can be multiple arrays of the above structure, there will be processed in
     *                                  original order from php
     * @param  array  $sort  Order by key (field) with val===true ? asc : desc
     * @param  array  $groupBy  Array with columns which will be grouped by
     * @param  int  $limit  limits the output of the query
     * @param  bool  $debug  gives more debug logging/output
     * @return array|bool
     */
    public function dbFetchAll(
        string|array $tables, array $fetchStyles = [self::FETCH_ASSOC], array $showColumns = [], array $where = [],
        array $joins = [], array $sort = [], array $groupBy = [], int $limit = 0, bool $debug = false
    ) {
        // check if all tables are known
        if (! is_array($tables)) {
            $tables = [$tables];
        }

        foreach ($tables as $table) {
            if (! isset($this->scheme[$table])) {
                throw new LegacyDieException(500, "Unkown table $table");
            }
        }

        // fill with everything if empty
        if (empty($showColumns)) {
            $showColumns = ['*'];
        }

        // substitute * with tablename.*
        if (in_array('*', $showColumns, true)) {
            unset($showColumns[array_search('*', $showColumns, true)]);
            foreach ($tables as $t) {
                $showColumns[] = "$t.*";
            }
            foreach ($joins as $j) {
                $showColumns[] = "{$j['table']}.*";
            }
        }

        // apply alias for table.* and set everywhere an aggregate function (default: none)
        $newShowColumns = [];
        foreach ($showColumns as $alias => $content) {
            if (is_array($content)) {
                [$col, $aggregate] = $content;
            } else {
                $col = $content;
                $aggregate = 0;
            }
            if (! is_int($alias) && ($pos = strpos($col, '.*')) !== false) {
                $tname = substr($col, 0, $pos);
                $rename = $alias;
                foreach ($this->scheme[$tname] as $colName => $dev_null) {
                    $newShowColumns[$rename.'.'.$colName] = [$tname.'.'.$colName, $aggregate];
                }
            } else {
                $newShowColumns[$alias] = [$col, $aggregate];
            }
        }

        // check join
        $validJoinOnOperators = ['=', '<', '>', '<>', '<=', '>='];
        foreach (array_keys($joins) as $nr) {
            if (! isset($joins[$nr]['table'])) {
                throw new LegacyDieException(500, "no Jointable set in '".$nr."' use !");
            } elseif (! array_key_exists($joins[$nr]['table'], $this->scheme)) {
                throw new LegacyDieException(500, 'Unknown Table '.$joins[$nr]['table']);
            } elseif (isset($joins[$nr]['type']) && ! in_array(
                strtolower($joins[$nr]['type']),
                ['inner', 'left', 'natural', 'right']
            )) {
                throw new LegacyDieException(500, 'Unknown Join type '.$joins[$nr]['type']);
            }
            if (! isset($joins[$nr]['on'])) {
                $joins[$nr]['on'] = [];
            }
            if (! is_array($joins[$nr]['on'])) {
                throw new LegacyDieException(500, "on '{$joins[$nr]['on']}' has to be an array!");
            }
            if (count($joins[$nr]['on']) === 2 && ! is_array($joins[$nr]['on'][0])) {
                $joins[$nr]['on'] = [$joins[$nr]['on']]; // if only 1 "on" set bring it into an array-form
            }
            foreach ($joins[$nr]['on'] as $pair) {
                if (! is_array($pair)) {
                    throw new LegacyDieException(500, "Join on '$pair' is not an array");
                }
                if (count($pair) !== 2) {
                    throw new LegacyDieException(500, 'unvalid joinon pair:'.implode(', ', $pair));
                }
            }
            if (isset($joins[$nr]['operator'])) {
                if (! is_array($joins[$nr]['operator'])) {
                    $joins[$nr]['operator'] = [$joins[$nr]['operator']];
                }
                foreach ($joins[$nr]['operator'] as $op) {
                    if (! in_array($op, $validJoinOnOperators, true)) {
                        throw new LegacyDieException(500, "unallowed join operator '$op' in {$nr}th join");
                    }
                }
            } else {
                $joins[$nr]['operator'] = array_fill(0, count($joins[$nr]['on']), '=');
            }
            if (count($joins[$nr]['on']) !== count($joins[$nr]['operator'])) {
                throw new LegacyDieException(500,
                    'not same amount of on-pairs('.count($joins[$nr]['on']).') and operators ('.count(
                        $joins[$nr]['operator']
                    ).')!'
                );
            }
        }

        foreach ($sort as $field => $value) {
            if (! in_array($field, $this->validFields, true)) {
                throw new LegacyDieException(500, "Unkown column $field in ORDER");
            }
        }

        foreach ($groupBy as $field) {
            if (! in_array($field, $this->validFields, true)) {
                throw new LegacyDieException(500, "Unkown column $field in GROUP");
            }
        }

        //
        // prebuild sql
        //
        $cols = [];
        foreach ($newShowColumns as $alias => [$col, $aggregateConst]) {
            if (in_array($col, $this->validFields, true)) {
                $as = (! is_int($alias)) ? " as `$alias`" : '';
                if (strpos($col, '.')) {
                    $cols[] = $this->quoteIdent($this->dbPrefix.$col, $aggregateConst).$as;
                } else {
                    $cols[] = $this->quoteIdent($col, $aggregateConst).$as;
                }
            } else {
                throw new LegacyDieException(500, "Unkown column $col in fetchAll");
            }
        }

        $joinVals = [];
        $j = [];
        // var_dump($joins);
        foreach ($joins as $nr => $join) {
            $jtype = isset($join['type']) ? (strtoupper($join['type']).' JOIN') : 'NATURAL JOIN';
            if ($jtype === 'NATURAL JOIN') {
                $j[] = PHP_EOL.'NATURAL JOIN '.$this->dbPrefix.$join['table'];
            } else {
                $jon = [];
                for ($i = 0; $i < count($join['on']); $i++) {
                    $expl = explode('.', $join['on'][$i][0]);
                    if (count($expl) > 1
                        && isset($this->scheme[$expl[0]])
                        && in_array($join['on'][$i][0], $this->validFields, true)
                    ) {
                        $first = $this->quoteIdent($this->dbPrefix.$join['on'][$i][0]);
                    } else {
                        $first = '?';
                        $joinVals[] = $join['on'][$i][0];
                    }
                    $expl = explode('.', $join['on'][$i][1]);
                    if (count($expl) > 1
                        && isset($this->scheme[$expl[0]])
                        && in_array($join['on'][$i][1], $this->validFields, true)
                    ) {
                        $second = $this->quoteIdent($this->dbPrefix.$join['on'][$i][1]);
                    } else {
                        $second = '?';
                        $joinVals[] = $join['on'][$i][1];
                    }
                    $jon[] = $first.' '.$join['operator'][$i].' '.$second;
                }
                $j[] = PHP_EOL.$jtype.' '.$this->dbPrefix.$join['table'].' ON '.implode(' AND ', $jon);
            }
        }
        [$whereSql, $whereVals] = $this->buildWhereSql($where);

        $o = [];
        foreach ($sort as $k => $v) {
            if (str_contains($k, '.')) {
                $o[] = $this->quoteIdent($this->dbPrefix.$k).' '.($v ? 'ASC' : 'DESC');
            } else {
                $o[] = $this->quoteIdent($k).' '.($v ? 'ASC' : 'DESC');
            }
        }

        $g = [];
        foreach ($groupBy as $item) {
            if (in_array($item, $this->validFields, true)) {
                if (str_contains($item, '.')) {
                    $g[] = $this->quoteIdent($this->dbPrefix.$item);
                } else {
                    $g[] = $this->quoteIdent($item);
                }
            } else {
                throw new LegacyDieException(500, "$item ist für sql nicht bekannt.");
            }
        }
        $vals = array_merge($joinVals, $whereVals);

        foreach ($tables as $key => $table) {
            $tables[$key] = $this->dbPrefix.$table;
        }

        $sql = PHP_EOL.'SELECT '.implode(','.PHP_EOL, $cols).PHP_EOL.'FROM '.implode(
            ','.PHP_EOL,
            $tables
        );
        if (count($j) > 0) {
            $sql .= ' '.implode(' ', $j).' ';
        }

        $sql .= $whereSql;

        if (count($groupBy) > 0) {
            $sql .= PHP_EOL.'GROUP BY '.implode(',', $g);
        }
        if (count($o) > 0) {
            $sql .= PHP_EOL.'ORDER BY '.implode(', ', $o);
        }

        if ($limit !== 0) {
            $sql .= PHP_EOL.'LIMIT '.$limit;
        }

        // HTMLPageRenderer::registerProfilingBreakpoint($sql);
        HTMLPageRenderer::registerProfilingBreakpoint('sql-start');
        if ($debug) {
            var_dump($sql);
            var_dump($vals);
        }
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute($vals);
        if (! $ret) {
            $errormsg = ['error' => $query->errorInfo(), 'sql' => $sql];
            if (DEV) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
                $errormsg['stacktrace'] = $trace;
            }
            throw new LegacyDieException(500, print_r($errormsg, true));
        }
        HTMLPageRenderer::registerProfilingBreakpoint('sql-done');
        if ($ret === false) {
            return false;
        }

        $PDOfetchType = 0;
        if (in_array(self::FETCH_NUMERIC, $fetchStyles, true) && in_array(self::FETCH_ASSOC, $fetchStyles, true)) {
            $PDOfetchType |= PDO::FETCH_BOTH;
        } elseif (in_array(self::FETCH_NUMERIC, $fetchStyles, true)) {
            $PDOfetchType |= PDO::FETCH_NUM;
        } elseif (in_array(self::FETCH_ASSOC, $fetchStyles, true)) {
            $PDOfetchType |= PDO::FETCH_ASSOC;
        }

        if (in_array(self::FETCH_ONLY_FIRST_COLUMN, $fetchStyles, true)) {
            $PDOfetchType |= PDO::FETCH_COLUMN;
        }// noelsif

        if (in_array(self::FETCH_UNIQUE_FIRST_COL_AS_KEY, $fetchStyles, true)) {
            $PDOfetchType |= PDO::FETCH_GROUP | PDO::FETCH_UNIQUE;
        } elseif (in_array(self::FETCH_UNIQUE, $fetchStyles, true)) {
            $PDOfetchType |= PDO::FETCH_UNIQUE;
        } elseif (in_array(self::FETCH_GROUPED, $fetchStyles, true)) {
            $PDOfetchType |= PDO::FETCH_GROUP;
        }

        return $query->fetchAll($PDOfetchType);
    }

    private function buildWhereSql($where): array
    {
        // check $where and bring in good shape
        // check if there are only numeric keys
        if (count(array_filter(array_keys($where), 'is_string')) > 0) {
            $where = [$where];
        }
        foreach ($where as $whereGroup) {
            foreach ($whereGroup as $field => $value) {
                if (! in_array($field, $this->validFields, true)) {
                    throw new LegacyDieException(500, "Unkown column $field in WHERE");
                }
            }
        }
        $w = [];
        $vals = [];
        $validWhereOperators = [
            '=',
            '<',
            '>',
            '<>',
            '<=',
            '>=',
            'like',
            'not like',
            'in',
            'between',
            'not in',
            'regexp',
            'not regexp',
            'is',
            'is not',
        ];
        foreach ($where as $whereGroup) {
            $wg = [];
            foreach ($whereGroup as $k => $v) {
                if (str_contains($k, '.')) {
                    $k = $this->dbPrefix.$k;
                }
                if (is_array($v)) {
                    if (! in_array(strtolower($v[0]), $validWhereOperators)) {
                        throw new LegacyDieException(500, "Unknown where operator $v[0]");
                    }
                    if (is_array($v[1])) {
                        switch (strtolower($v[0])) {
                            case 'not in':
                            case 'in':
                                $tmp = implode(',', array_fill(0, count($v[1]), '?'));
                                $wg[] = $this->quoteIdent($k)." $v[0] (".$tmp.')';
                                break;
                            case 'between':
                                $wg[] = $this->quoteIdent($k)." $v[0] ? AND ?";
                                if (count($v[1]) !== 2) {
                                    throw new LegacyDieException(500, 'To many values for '.$v[0]);
                                }
                                break;
                            default:
                                throw new LegacyDieException(500, 'unknown identifier '.$v[0]);
                        }
                        $vals = array_merge($vals, $v[1]);
                    } elseif ((strtolower($v[0]) === 'is' || strtolower($v[0]) === 'is not')
                        && (is_null($v[1]) || strtolower($v[1]) === 'null')) {
                        $wg[] = $this->quoteIdent($k).' '.$v[0].' null';
                    } else {
                        $wg[] = $this->quoteIdent($k).' '.$v[0].' ?';
                        $vals[] = $v[1];
                    }
                } else {
                    $wg[] = $this->quoteIdent($k).' = ?';
                    $vals[] = $v;
                }
            }
            if (count($wg) > 0) {
                $w[] = implode(' AND ', $wg);
            }
        }

        if (count($w) > 0) {
            $whereSql = PHP_EOL.'WHERE ('.implode(') OR (', $w).')';
        } else {
            $whereSql = ' ';
        }

        return [$whereSql, $vals];
    }

    private function quoteIdent(array|string $field, $aggregateConst = 0): array|string
    {
        if (is_array($field)) {
            $ret = [];
            foreach ($field as $item) {
                $ret[] = $this->quoteIdent($item, $aggregateConst);
            }

            return $ret;
        }
        switch ($aggregateConst) {
            case $this::GROUP_SUM:
                $aggregatePre = 'SUM(';
                $aggregateSuf = ')';
                break;
            case $this::GROUP_SUM_ROUND2:
                $aggregatePre = 'ROUND(SUM(';
                $aggregateSuf = '),2)';
                break;
            case $this::GROUP_COUNT:
                $aggregatePre = 'COUNT(';
                $aggregateSuf = ')';
                break;
            case $this::GROUP_MAX:
                $aggregatePre = 'MAX(';
                $aggregateSuf = ')';
                break;
            case $this::GROUP_MIN:
                $aggregatePre = 'MIN(';
                $aggregateSuf = ')';
                break;
            default:
                $aggregatePre = '';
                $aggregateSuf = '';
                break;
        }
        $ret = '`'.str_replace('`', '``', $field).'`';
        $ret = str_replace(['.', '`*`'], ['`.`', '*'], $ret);

        return $aggregatePre.$ret.$aggregateSuf;
    }

    /**
     * @param  $table  string  table in db
     * @param  $fields  array   all fields which should be filled
     * @return string last inserted id
     */
    public function dbInsert(string $table, array $fields): string
    {
        if (! isset($this->scheme[$table])) {
            throw new LegacyDieException(500, "Unkown table $table");
        }
        // if (isset($fields["id"])) unset($fields["id"]);

        $fields = array_intersect_key($fields, $this->scheme[$table]);
        $p = array_fill(0, count($fields), '?');
        $sql = 'INSERT '.$this->dbPrefix."$table (".implode(
            ',',
            array_map(
                [$this, 'quoteIdent'],
                array_keys($fields)
            )
        ).') VALUES ('.implode(',', $p).')';

        $query = $this->pdo->prepare($sql);
        $ret = $query->execute(array_values($fields));
        if ($ret === false) {
            $info = $query->errorInfo();
            if (DEV) {
                $info += ['sql' => $sql, 'values' => $fields];
            }
            throw new LegacyDieException(500, print_r($info, true));
        }

        return $this->pdo->lastInsertId();
    }

    /**
     * @param  array  $fieldSchema  array values equal keys of fields in multiFields, non valid entries will be removed
     * @param  mixed  ...$multiFields  multiple rows of $fields @see DBConnector::dbInsert()
     * @return string last inserted id (from pdo)
     */
    public function dbInsertMultiple(string $table, array $fieldSchema, array ...$multiFields): string
    {
        if (! isset($this->scheme[$table])) {
            throw new LegacyDieException(500, "Unknown table $table");
        }
        $fieldSchema = array_flip(array_intersect_key(array_flip($fieldSchema), $this->scheme[$table]));

        $sql = 'INSERT '.$this->dbPrefix."$table (".implode(
            ',',
            $this->quoteIdent($fieldSchema)
        ).') VALUES ';
        $values = [];
        foreach ($multiFields as $fields) {
            $fields = array_intersect_key($fields, array_flip($fieldSchema));
            if (count($fields) !== count($fieldSchema)) {
                throw new LegacyDieException(500, 'Ein Datenfehler ist aufgetreten - Falsche Dimension', [
                    'ist' => $fields,
                    'soll' => $fieldSchema,
                ]);
            }
            $values[] = array_values($fields);
        }

        $placeholderSingle = '('.implode(',', array_fill(0, count($fieldSchema), '?')).')';
        $placeholder = array_fill(0, count($values), $placeholderSingle);
        $sql .= implode(','.PHP_EOL, $placeholder);

        // TODO: php 7.4: [] can be removed, array merge accepts also no arguments there
        $values = array_merge([], ...$values);

        $query = $this->pdo->prepare($sql);
        $ret = $query->execute($values);
        if ($ret === false) {
            $info = $query->errorInfo();
            if (DEV === true) {
                $info += ['sql' => $sql, 'values' => $values, 'multiInput' => $multiFields];
            }
            throw new LegacyDieException(500, 'Ein Datenbank Fehler ist aufgetreten', $info);
        }

        return $this->pdo->lastInsertId();
    }

    public function logAppend($logId, $key, $value): void
    {
        $query = $this->pdo->prepare(
            'INSERT INTO '.$this->dbPrefix.'log_property (log_id, name, value) VALUES (?, ?, ?)'
        );
        if (is_array($value)) {
            $value = print_r($value, true);
        }
        $query->execute([$logId, $key, $value]) or throw new LegacyDieException(500, print_r($query->errorInfo(), true));
    }

    public function dbBegin(): bool
    {
        DB::beginTransaction();

        return true;
    }

    public function dbCommitRollbackOnFailure(): bool
    {
        try {
            DB::commit();
            $success = true;
        } catch (\Throwable $exception) {
            $success = false;
            DB::rollBack();
        }

        return $success;
    }

    public function dbCommit(): bool
    {
        try {
            DB::commit();
            $success = true;
        } catch (\Throwable $exception) {
            $success = false;
        }

        return $success;
    }

    public function dbRollBack(): bool
    {
        DB::rollBack();

        return true;
    }

    /**
     * @param  $table  string tablename
     * @param  $filter  array where clause
     * @param  $fields  array new values
     * @return int amount of changed rows
     */
    public function dbUpdate(string $table, array $filter, array $fields, bool $debugDump = false): int
    {
        if (! isset($this->scheme[$table])) {
            throw new LegacyDieException(500, "Unkown table $table");
        }

        $filter = array_intersect_key(
            $filter,
            $this->scheme[$table],
            array_flip($this->validFields)
        ); // only fetch using id and url
        // $fields = array_diff_key(array_intersect_key($fields, $this->scheme[$table]), array_flip($this->validFields)); # do not update filter fields
        $fields = array_intersect_key($fields, array_flip($this->validFields));
        if (count($filter) === 0) {
            throw new LegacyDieException(500, 'No filter fields given.');
        }
        if (count($fields) === 0) {
            throw new LegacyDieException(500, 'No fields given.');
        }
        $u = [];
        foreach ($fields as $k => $v) {
            $u[] = $this->quoteIdent($k).' = ?';
        }

        [$whereSql, $val] = $this->buildWhereSql($filter);

        $sql = 'UPDATE '.$this->dbPrefix."$table SET ".implode(', ', $u).$whereSql;
        // print_r($sql);
        $query = $this->pdo->prepare($sql);
        $values = array_merge(array_values($fields), $val);

        $ret = $query->execute($values);
        if ($debugDump) {
            dump($sql, $values);
        }
        if ($ret === false) {
            throw new LegacyDieException(500, "DB Update in $table failed", $query->errorInfo());
        }

        return $query->rowCount();
    }

    /**
     * @param  $table  string table name
     * @param  $filter  array
     * @return int amount of deleted rows, otherwise handles Error
     */
    public function dbDelete(string $table, array $filter): int
    {
        if (! isset($this->scheme[$table])) {
            throw new LegacyDieException(
                500,
                'Ein Datenbankfehler ist aufgetreten',
                "Deletion of table entries from $table not possible, table name unknown"
            );
        }

        [$whereSql, $values] = $this->buildWhereSql($filter);

        $sql = 'DELETE FROM '.$this->dbPrefix.$table.$whereSql;
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute($values);
        if ($ret === false) {
            throw new LegacyDieException(
                500,
                'Ein Datenbank Fehler ist aufgetreten',
                "Deletion of table $table not possible:".PHP_EOL.print_r($query->errorInfo(), true).PHP_EOL.$sql.print_r($values, true)
            );
        }

        return $query->rowCount();
    }

    public function dbgetHHP($id): array
    {
        $sql = '
            SELECT t.hhpgruppen_id,t.id,g.type,g.gruppen_name,t.titel_nr,t.titel_name,t.value,g.type
            FROM '.$this->dbPrefix.'haushaltstitel AS t
            INNER JOIN '.$this->dbPrefix.'haushaltsgruppen AS g ON t.hhpgruppen_id = g.id
            WHERE `hhp_id` = ?
            ORDER BY `type` ASC,`g`.`id` ASC,`titel_nr` ASC';
        $query = $this->pdo->prepare($sql);
        $query->execute([$id]) or throw new LegacyDieException(500, print_r($query->errorInfo(), true));
        $groups = [];
        $titelIdsToGroupId = [];
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $gId = array_shift($row); // hhpgruppen_id
            $tId = array_shift($row); // t.id
            $groups[$gId][$tId] = $row;
            $titelIdsToGroupId[$tId] = $gId;
        }
        if (empty($titelIdsToGroupId)) {
            return $groups;
        }
        $sql = '
            SELECT b.titel_id, b.value, b.canceled
            FROM '.$this->dbPrefix.'booking AS b
            WHERE b.titel_id IN ('.implode(',', array_fill(0, count($titelIdsToGroupId), '?')).')';
        $query = $this->pdo->prepare($sql);
        $query->execute(array_keys($titelIdsToGroupId)) or throw new LegacyDieException(500, print_r($query->errorInfo(), true));
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $tId = array_shift($row);
            $val = $row['value'];
            if ($row['canceled'] === '1') {
                $val = -$val;
            }
            if (isset($groups[$titelIdsToGroupId[$tId]][$tId]['_booked'])) {
                $groups[$titelIdsToGroupId[$tId]][$tId]['_booked'] += $val;
            } else {
                $groups[$titelIdsToGroupId[$tId]][$tId]['_booked'] = $val;
            }
        }

        [$openMoneyByTitel, $closedMoneyByTitel] = $this->getMoneyByTitle($id);
        $moneyByTitel = array_merge($openMoneyByTitel, $closedMoneyByTitel);

        foreach ($moneyByTitel as $row) {
            $value = $row['value'];
            if (isset($groups[$row['group_id']][$row['titel_id']]['_saved'])) {
                $groups[$row['group_id']][$row['titel_id']]['_saved'] += $value;
            } else {
                $groups[$row['group_id']][$row['titel_id']]['_saved'] = $value;
            }
        }

        return $groups;
    }

    public function getMoneyByTitle($hhp_id, $summed = true, $titel_id = null): array
    {
        if ($summed) {
            // summed
            $group_type = self::GROUP_SUM_ROUND2;
            $groupBy = ['titel_id'];
        } else {
            // not summed
            $group_type = self::GROUP_NOTHING;
            $groupBy = [];
        }
        $whereClosed = ['hhp_id' => $hhp_id, 'projekte.state' => 'terminated', 'auslagen.state' => ['NOT LIKE', 'revocation%']];
        $whereOpen = ['hhp_id' => $hhp_id, 'projekte.state' => ['REGEXP', 'ok-by-hv|ok-by-stura|done-hv|done-other']];
        if (isset($titel_id)) {
            $whereClosed['titel_id'] = $titel_id;
            $whereOpen['titel_id'] = $titel_id;
        }
        // ermittle alle buchungen von projekten die beendet sind
        $closedMoneyByTitel = $this->dbFetchAll(
            'auslagen',
            [self::FETCH_ASSOC],
            [
                'titel_id',
                'titel_type' => 'haushaltsgruppen.type',
                'group_id' => 'haushaltsgruppen.id',
                'ausgaben' => ['beleg_posten.ausgaben', $group_type],
                'einnahmen' => ['beleg_posten.einnahmen', $group_type],
                'auslagen' => 'auslagen.*',
                'projekte' => 'projekte.*',
            ],
            $whereClosed,
            [
                ['type' => 'inner', 'table' => 'projekte', 'on' => ['projekte.id', 'auslagen.projekt_id']],
                ['type' => 'inner', 'table' => 'belege', 'on' => ['belege.auslagen_id', 'auslagen.id']],
                ['type' => 'inner', 'table' => 'beleg_posten', 'on' => ['beleg_posten.beleg_id', 'belege.id']],
                [
                    'type' => 'inner',
                    'table' => 'projektposten',
                    'on' => [
                        ['projektposten.projekt_id', 'projekte.id'],
                        ['projektposten.id', 'beleg_posten.projekt_posten_id'],
                    ],
                ],
                [
                    'type' => 'inner',
                    'table' => 'haushaltstitel',
                    'on' => ['projektposten.titel_id', 'haushaltstitel.id'],
                ],
                [
                    'type' => 'inner',
                    'table' => 'haushaltsgruppen',
                    'on' => ['haushaltstitel.hhpgruppen_id', 'haushaltsgruppen.id'],
                ],
                [
                    'type' => 'inner',
                    'table' => 'haushaltsplan',
                    'on' => ['haushaltsplan.id', 'haushaltsgruppen.hhp_id'],
                ],
            ],
            [],
            $groupBy
        );

        $openMoneyByTitel = $this->dbFetchAll(
            'projekte',
            [self::FETCH_ASSOC],
            [
                'titel_id',
                'titel_type' => 'haushaltsgruppen.type',
                'group_id' => 'haushaltsgruppen.id',
                'projektposten.name',
                'ausgaben' => ['projektposten.ausgaben', $group_type],
                'einnahmen' => ['projektposten.einnahmen', $group_type],
                'projekte' => 'projekte.*',
            ],
            $whereOpen,
            [
                ['type' => 'inner', 'table' => 'projektposten', 'on' => ['projektposten.projekt_id', 'projekte.id']],
                [
                    'type' => 'inner',
                    'table' => 'haushaltstitel',
                    'on' => ['projektposten.titel_id', 'haushaltstitel.id'],
                ],
                [
                    'type' => 'inner',
                    'table' => 'haushaltsgruppen',
                    'on' => ['haushaltstitel.hhpgruppen_id', 'haushaltsgruppen.id'],
                ],
                [
                    'type' => 'inner',
                    'table' => 'haushaltsplan',
                    'on' => ['haushaltsplan.id', 'haushaltsgruppen.hhp_id'],
                ],
            ],
            ['projekte.id' => true, 'ausgaben' => false, 'einnahmen' => false],
            $groupBy
        );
        $counter = count($closedMoneyByTitel);
        // merge for adding value
        $moneyByTitel = array_merge($closedMoneyByTitel, $openMoneyByTitel);
        foreach ($moneyByTitel as $key => $row) {
            $value = 0;
            if ((int) $row['einnahmen'] !== 0) {
                $value += (float) $row['einnahmen'];
            }
            if ((int) $row['ausgaben'] !== 0) {
                $value -= (float) $row['ausgaben'];
            }
            if ((int) $row['titel_type'] !== 0) {
                $value = -$value;
            }
            $moneyByTitel[$key]['value'] = $value;
        }

        // split again (close,open)
        return [array_slice($moneyByTitel, 0, $counter), array_slice($moneyByTitel, $counter)];
    }

    private function buildColDef($fields): string
    {
        $r = [];
        foreach ($fields as $key => $val) {
            $r[] = $this->quoteIdent($key)." $val";
        }

        return implode(','.PHP_EOL, $r);
    }

    public function hasTableColumns(string $tableName, string ...$columns): bool
    {
        foreach ($columns as $column) {
            if (! isset($this->scheme[$tableName][$column])) {
                return false;
            }
        }

        return true;
    }
}
