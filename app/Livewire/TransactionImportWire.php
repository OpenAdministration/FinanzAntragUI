<?php

namespace App\Livewire;

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\User;
use App\Rules\CsvTransactionImport\BalanceColumnRule;
use App\Rules\CsvTransactionImport\DateColumnRule;
use App\Rules\CsvTransactionImport\IbanColumnRule;
use App\Rules\CsvTransactionImport\MoneyColumnRule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Regex\Regex;

class TransactionImportWire extends Component
{
    use WithFileUploads;

    public $csv;

    public $separator;

    public $csvFileEncoding;

    #[Url]
    public $account_id;

    public $latestTransaction;

    /** @var Collection */
    public $mapping;

    public $db_col_types;

    /** @var Collection */
    public $data;

    public $header;

    // CSV entries in order = -1, in reverse order = 1
    public $csvOrder = -1;

    public function mount()
    {
        $this->authorize('cashOfficer', User::class);

        $this->mapping = $this->createMapping();
        $this->data = collect();
        $this->account_id = '';

        $foo = new BankTransaction;
        foreach ($this->mapping as $db_column => $csv_colum) {
            $this->db_col_types[$db_column] = DB::getSchemaBuilder()->getColumnType($foo->getTable(), $db_column);
        }
    }

    public function rules(): array
    {
        // mapping has only the csv column numbers as values, so we need to work around a bit,
        // we only check if a matching column was given, the values of this columns only in special cases
        return [
            'csv' => 'required|file|mimes:csv,txt|extensions:csv',
            'mapping.date' => [
                'required',
                'int',
                new DateColumnRule($this->data->pluck($this->mapping->get('date'))),
            ],
            'mapping.valuta' => [
                'required',
                'int',
                new DateColumnRule($this->data->pluck($this->mapping->get('valuta'))),
            ],
            'mapping.type' => 'required|int',
            'mapping.value' => [
                'required', 'int',
                new MoneyColumnRule($this->data->pluck($this->mapping->get('value'))),
            ],
            'mapping.saldo' => [
                'int',
                new MoneyColumnRule($this->data->pluck($this->mapping->get('saldo'))),
                // new MoneyRule($this->data->pluck($this->mapping->get('value'))),
                new BalanceColumnRule(
                    $this->data->pluck($this->mapping->get('value')),
                    $this->data->pluck($this->mapping->get('saldo')),
                    $this->latestTransaction?->saldo
                ),
            ],
            'mapping.empf_name' => 'required|int',
            'mapping.empf_bic' => 'sometimes|int',
            'mapping.empf_iban' => [
                'required', 'int',
                new IbanColumnRule($this->data->pluck($this->mapping->get('empf_iban'))),
            ],
            'mapping.zweck' => 'required|int',
        ];
    }

    /**
     * Fills up the mapping array with missing column keys (and empty values)
     */
    private function createMapping(array $merger = []): Collection
    {
        $foo = new BankTransaction;
        $emptyMapping = collect(array_flip(array_keys($foo->getLabels())));

        return $emptyMapping->map(function ($value, $key) use ($merger) {
            return $merger[$key] ?? '';
        });
    }

    public function parseCSV(): void
    {
        // temp save uploaded file
        $this->csv->store();
        $content = $this->csv->get();

        // check for windows excel file encoding, transform to utf-8
        $enc = mb_detect_encoding($content, ['Windows-1252', 'UTF-8']);
        if ($enc !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        // explode content in lines
        $content = str($content);
        $lines = $content->explode(PHP_EOL);

        // guess csv separator
        $amountComma = $content->substrCount(',');
        $amountSemicolon = $content->substrCount(';');
        $this->separator = $amountSemicolon > $amountComma ? ';' : ',';

        // extract header and data, explode data with csv separator guesses above
        $this->header = str_getcsv($lines->first(), $this->separator);
        $this->data = $lines->except(0)
            ->filter(function ($line) {
                return ! (empty($line) || Regex::match('/^(,*|;*)\r?\n?$/', $line)->hasMatch());
            })->map(function ($line) {
                return str_getcsv($line, $this->separator);
            })->map(function ($lineArray) {
                // normalize data
                foreach ($lineArray as $key => $cell) {
                    // tests
                    $moneyTest = Regex::match('/^(\-?)([0-9]+)([,\.]([0-9]{1,2}))?$/', $cell);
                    $dateTest = Regex::match('/^([0-3]?[0-9])\.([01]?[0-9])\.((20)?[0-9]{2})$/', $cell);
                    // conversions
                    if ($moneyTest->hasMatch()) {
                        $g = $moneyTest->groups();
                        $lineArray[$key] = $g[1] // sign
                            .Str::padRight($g[2] ?? '', 1, '0') //  money before delimiter (at least 1 digit)
                            .'.' // delimiter (3rd group, with the rest together)
                            .Str::padRight($g[4] ?? '', 2, '0'); // cents after delimiter (at least 2 digits)
                    } elseif ($dateTest->hasMatch()) {
                        $g = $dateTest->groups();
                        $lineArray[$key] = Str::padLeft($g[3], 4, '20') // year
                            .'-'.Str::padLeft($g[2], 2, '0')
                            .'-'.Str::padLeft($g[1], 2, '0');
                    }
                }

                return $lineArray;
            });

        if ($this->isCsvOrderReversed()) {
            $this->data->reverse();
        }

        // check if mapping has some presets, if then do an initial validation, no preset, no validation
        $hasPreset = $this->mapping->reject(function ($value) {
            return $value === '';
        })->count() > 0;
        if ($hasPreset) {
            $this->validate();
        }
    }

    public function updatedCsv(): void
    {
        // dump($this->csv->getMimeType());
        $this->validateOnly('csv');
        if (in_array($this->csv->getMimeType(), ['text/csv', 'text/plain'])) {
            $this->parseCSV();
        }
    }

    public function save()
    {
        $this->validate();
        // mapping als vorlage speichern
        $account = BankAccount::findOrFail($this->account_id);
        $account->csv_import_settings = [
            'csv_import_mapping' => $this->mapping,
            'csv_order' => $this->csvOrder,
        ];
        $account->save();
        $last_id = BankTransaction::where('konto_id', $this->account_id)
            ->orderBy('id', 'desc')->limit(1)->first('id')->id ?? 1;

        // In case no saldo is given in CSV, we need to calculate it row by row
        // first Saldo should be sourced from last entry in DB, if there is no entry, we assume 0
        $currentBalance = $this->latestTransaction->saldo ?? 0; // alternative to 0: throw error

        // create BankTransaction with values from $data, according to the keys assigned in $mapping
        DB::beginTransaction();
        foreach ($this->data as $row) {
            $transaction = new BankTransaction;
            $transaction->id = ++$last_id;
            $transaction->konto_id = $this->account_id;

            foreach ($this->mapping as $db_col_name => $csv_col_id) {
                if (! empty($this->mapping[$db_col_name])) {
                    $transaction->$db_col_name = $this->formatDataDb($row[$this->mapping[$db_col_name]], $db_col_name);
                } elseif ($db_col_name === 'saldo') {
                    $currentValue = str($row[$this->mapping['value']])->replace(',', '.');
                    $currentBalance = bcadd($currentBalance, $currentValue, 2);
                    $transaction->$db_col_name = $this->formatDataDb($currentBalance, $db_col_name);
                }
            }

            $transaction->save();
        }
        try {
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $this->addError('csv', 'Nope');

            return;
        }

        $newBalance = BankTransaction::where('konto_id', $this->account_id)
            ->orderBy('id', 'desc')->limit(1)->first()->saldo;

        return redirect()->route('konto.import.manual')
            ->with(['message' => __('konto.csv-import-success-msg', ['new-saldo' => $newBalance, 'transaction-amount' => $this->data->count()])]);
    }

    public function render(): Application|Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\View\View
    {
        $accounts = BankAccount::all();
        $labels = (new BankTransaction)->getLabels();

        return view('livewire.bank.csv-import', [
            'accounts' => $accounts,
            'firstNewTransaction' => $this->data->first(),
            'lastNewTransaction' => $this->data->last(),
            'labels' => $labels,
        ]);
    }

    public function updatedAccountId(): void
    {
        $account = BankAccount::find($this->account_id);
        if ($account) {
            $this->latestTransaction = BankTransaction::where('konto_id', $this->account_id)
                ->orderBy('id', 'desc')->limit(1)->first();
            $this->mapping = $this->createMapping($account->csv_import_settings['csv_import_mapping'] ?? []);
            $this->csvOrder = (int) ($account->csv_import_settings['csv_order'] ?? -1);
        } else {
            $this->latestTransaction = null;
            $this->mapping = null;
            $this->csvOrder = null;
        }
        $this->resetValidation();
    }

    /**
     * is called when mapping got updated
     */
    public function updatedMapping(): void
    {
        $this->validate();
        // date, value, saldo
    }

    public function formatDataDb(string|int $value, string $db_col_name): int|string
    {
        $type = $this->db_col_types[$db_col_name];

        // if($type === 'decimal') dd([$value, (float) $value,$db_col_name]);
        return match ($type) {
            'integer' => (int) $value,
            'date' => guessCarbon($value, 'Y-m-d'),
            'decimal' => $value, // no casting needed, string is expected
            default => $value,
        };
    }

    public function formatDataView(string|int $value, string $db_col_name): int|string
    {
        $type = $this->db_col_types[$db_col_name];
        if ($db_col_name === 'empf_iban') {
            $type = 'iban';
        }
        if ($db_col_name === 'empf_bic') {
            $type = 'bic';
        }

        return match ($type) {
            'date' => guessCarbon($value, 'd.m.Y'),
            'decimal' => number_format((float) $value, 2, ',', '.').' €',
            'iban' => iban_to_human_format($value),
            default => $value
        };
    }

    /**
     * Change the order of CSV entries in current upload
     */
    public function reverseCsvOrder(): void
    {
        $this->data = $this->data->reverse();
        $this->csvOrder *= -1;
        $this->resetValidation();
        $this->validate();
    }

    public function isCsvOrderReversed(): bool
    {
        return $this->csvOrder === -1;
    }
}
