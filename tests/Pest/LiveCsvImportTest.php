<?php

use App\Livewire\TransactionImportWire;
use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;

test('csv import is accessible as cash officer', function () {
    Livewire::actingAs(cashManager())->test(TransactionImportWire::class)
        ->assertSuccessful();
});

test('csv import is not accessible as budget officer', function () {
    Livewire::actingAs(budgetManager())->test(TransactionImportWire::class)
        ->assertForbidden();
});

test('csv import is not accessible as normal user', function () {
    Livewire::actingAs(user())->test(TransactionImportWire::class)
        ->assertForbidden();
});

test('show last transactions', function () {
    $lastTransactions = [];
    BankAccount::all()->each(function ($account) use (&$lastTransactions) {
        $tmp = $account->bankTransactions()->orderBy('id', 'desc')->first();
        if ($tmp) {
            $lastTransactions[$account->id] = $tmp;
        }
    });
    $wire = \Livewire::actingAs(cashManager())->test(TransactionImportWire::class);
    foreach ($lastTransactions as $transaction) {
        Log::debug($transaction->date);
        $wire->set('account_id', $transaction->konto_id)
            ->assertSee(number_format($transaction->saldo, 2, ',', '.'))
            ->assertSee($transaction->date->format('d.m.Y'))
            ->assertSee($transaction->zweck);
    }
});

test('account has no transactions view', function () {
    $noTransactions = [];
    BankAccount::all()->each(function ($account) use (&$noTransactions) {
        $count = $account->bankTransactions()->count();
        if ($count === 0) {
            $noTransactions[$account->id] = $account->id;
        }
    });
    $wire = \Livewire::actingAs(cashManager())->test(TransactionImportWire::class);
    foreach ($noTransactions as $id) {
        $wire->set('account_id', $id)
            ->assertSee(__('konto.csv-no-transaction'));
    }
});

test('csv upload visibility', function () {
    $wire = \Livewire::actingAs(cashManager())->test(TransactionImportWire::class);
    $accountIds = BankAccount::all()->pluck('id')->toArray();
    foreach ($accountIds as $accountId) {
        $wire->set('account_id', $accountId)
            ->assertSee(__('konto.csv-upload-headline'));
    }
    $wire->set('account_id', '')->assertDontSee(__('konto.csv-upload-headline'));
});

const CSV_1 = [
    'header' => [
        0 => 'Bezeichnung Auftragskonto',
        1 => 'IBAN Auftragskonto',
        2 => 'BIC Auftragskonto',
        3 => 'Bankname Auftragskonto',
        4 => 'Buchungstag',
        5 => 'Valutadatum',
        6 => 'Name Zahlungsbeteiligter',
        7 => 'IBAN Zahlungsbeteiligter',
        8 => 'BIC (SWIFT-Code) Zahlungsbeteiligter',
        9 => 'Buchungstext',
        10 => 'Verwendungszweck',
        11 => 'Betrag',
        12 => 'Waehrung',
        13 => 'Saldo nach Buchung',
        14 => 'Bemerkung',
        15 => 'Kategorie',
        16 => 'Steuerrelevant',
        17 => 'Glaeubiger ID',
        18 => 'Mandatsreferenz',
    ],
    'data' => [
        1 => ['AStA - Basiskonto', 'DE12429644757213399722',
            'NKZUVJYQ0P5', 'Meine Bank', '2024-06-05', '2024-06-05', 'Person 5', 'DE63365090851878254100',
            'IHHVRZIL', 'Gutschrift', 'Entry 5', '420.99', 'EUR', '18474.22', '', 'Sonstiges', '', '', '', ],
        2 => ['AStA - Basiskonto', 'DE12429644757213399722',
            'NKZUVJYQ0P5', 'Meine Bank', '2024-06-05', '2024-06-05', 'Person 4', 'DE76169365307164900914',
            'MWFYLYEL', 'Basislastschrift', 'Entry 4', '-43.40', 'EUR', '18053.23', '', 'Sonstiges', '', '', '', ],
        3 => ['AStA - Basiskonto', 'DE12429644757213399722',
            'NKZUVJYQ0P5', 'Meine Bank', '2024-06-04', '2024-06-04', 'Person 3', 'DE67615841552532938268',
            'MVGUQVQWJZY', 'Gutschrift', 'Entry 3', '2.00', 'EUR', '18096.63', '', 'Sonstiges', '', '', '', ],
        4 => ['AStA - Basiskonto', 'DE12429644757213399722',
            'NKZUVJYQ0P5', 'Meine Bank', '2024-06-03', '2024-06-04', 'Person 2', 'DE79181333728582849451',
            'GENODEF1SDE', 'Gutschrift', 'Entry 2', '5.00', 'EUR', '18094.63', '', 'Sonstiges', '', '', '', ],
        5 => ['AStA - Basiskonto', 'DE12429644757213399722',
            'NKZUVJYQ0P5', 'Meine Bank', '2024-06-03', '2024-06-03', 'Person 1', 'DE73447318315829961821',
            'DZFPEL2K', 'Euro-Überweisung', 'Entry 1', '-13.14', 'EUR', '18089.63', '', 'Sonstiges', '', '', '', ],
    ],
];

test('parse csv utf8 encoding', function () {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    \Livewire::actingAs(cashManager())
        ->test(TransactionImportWire::class)
        ->set('account_id', 4) // an account without transactions
        ->set('csv', $csvFile)
        ->assertSet('header', CSV_1['header'])
        ->assertSet('data', collect(CSV_1['data']));
});

test('parse csv win encoding', function () {
    $csvFile = testFile('csv-import/test-correct-semicolon-win-enc.csv');

    \Livewire::actingAs(cashManager())
        ->test(TransactionImportWire::class)
        ->set('account_id', 4) // an account without transactions
        ->set('csv', $csvFile)
        ->assertSet('header', CSV_1['header'])
        ->assertSet('data', collect(CSV_1['data']));
});

test('views showing properly', function () {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    $lw = \Livewire::actingAs(cashManager())
        ->test(TransactionImportWire::class)
        ->assertSee(__('konto.manual-headline'))
        ->assertSee(__('konto.manual-headline-sub'))
        ->assertSee(__('konto.csv-label-choose-konto'))
        ->assertDontSee(__('konto.csv-upload-headline'))
        ->set('account_id', 4) // an account with some transactions
        ->assertSee(__('konto.csv-upload-headline'))
        ->assertSee(__('konto.csv-upload-headline-sub'))
        ->assertDontSee(__('konto.transaction.headline'))
        ->set('csv', $csvFile)
        ->assertSee(__('konto.transaction.headline'))
        ->assertSee(__('konto.transaction.headline-sub'))
        ->assertSee(__('konto.manual-button-reverse-csv-order'))
        ->assertSee(__('konto.manual-button-reverse-csv-order-sub'));
    foreach ([
        'konto.label.transaction.date', 'konto.hint.transaction.date', 'konto.label.transaction.valuta', 'konto.hint.transaction.valuta',
        'konto.label.transaction.type', 'konto.hint.transaction.type', 'konto.label.transaction.empf_iban', 'konto.hint.transaction.empf_iban',
        'konto.label.transaction.empf_bic', 'konto.hint.transaction.empf_bic', 'konto.label.transaction.empf_name', 'konto.hint.transaction.empf_name',
        'konto.label.transaction.primanota', 'konto.hint.transaction.primanota', 'konto.label.transaction.value', 'konto.hint.transaction.value',
        'konto.label.transaction.saldo', 'konto.hint.transaction.saldo', 'konto.label.transaction.zweck', 'konto.hint.transaction.zweck',
        'konto.label.transaction.comment', 'konto.hint.transaction.comment', 'konto.label.transaction.customer_ref', 'konto.hint.transaction.customer_ref',
    ] as $translationKey) {
        $lw->assertSee(__($translationKey));
    }
});

test('csv upload some fields are required', function () {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    \Livewire::actingAs(cashManager())
        ->test(TransactionImportWire::class)
        ->set('account_id', 2) // an account with some transactions
        ->set('csv', $csvFile)
        ->call('save')
        ->assertHasErrors([
            'mapping.date',
            'mapping.valuta',
            'mapping.type',
            'mapping.value',
            'mapping.empf_name',
            'mapping.empf_iban',
            'mapping.zweck',
        ]);
});

test('csv upload with wrong date check (order and start)', function () {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    $lw = \Livewire::actingAs(cashManager())
        ->test(TransactionImportWire::class)
        ->set('account_id', 2) // an account with some transactions
        ->set('csv', $csvFile)
        ->set('mapping.date', 4)
        ->set('mapping.valuta', 5);
    // dump("setter done");
    $lw->assertHasErrors(['mapping.date', 'mapping.valuta']);
    // dump('first test done');
    $lw->call('reverseCsvOrder')
        ->assertHasNoErrors(['mapping.date', 'mapping.valuta']);
    $lw->call('reverseCsvOrder')
        ->assertHasErrors(['mapping.date', 'mapping.valuta']);
});

test('csv upload with wrong saldo check (order and start)', function () {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    \Livewire::actingAs(cashManager())
        ->test(TransactionImportWire::class)
        ->set('account_id', 2) // an account with some transactions
        ->set('csv', $csvFile)
        ->set('mapping.date', 4)
        ->set('mapping.valuta', 5)
        // ->set('mapping.empf_name', 6)
        // ->set('mapping.empf_iban', 7)
        // ->set('mapping.type', 9)
        // ->set('mapping.zweck', 10)
        // ->set('mapping.value', 12)
        // ->set('mapping.saldo', 13)
        // ->call('reverseCsvOrder')
        ->assertHasErrors(['mapping.date', 'mapping.valuta']);
});

test('only csv upload accepted', function () {
    $image = \Illuminate\Http\Testing\File::image('test-image.png');
    $image_csv = \Illuminate\Http\Testing\File::image('test-image.csv');
    $pdf = testFile('empty.pdf');
    $pdf_csv = testFile('empty.pdf', 'empty.csv');

    \Livewire::actingAs(cashManager())
        ->test(TransactionImportWire::class)
        ->set('account_id', 2) // an account with some transactions
        ->set('csv', $image)
        ->assertHasErrors(['csv'])
        ->set('csv', $image_csv)
        ->assertHasErrors(['csv'])
        ->set('csv', $pdf)
        ->assertHasErrors(['csv'])
        ->set('csv', $pdf_csv)
        ->assertHasErrors(['csv']);
});

test('if csv import is saved', function () {
    $account_id = 4;
    $transactionAmount = BankTransaction::where('konto_id', '=', $account_id)->count();
    expect($transactionAmount)->toBe(0);

    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    \Livewire::actingAs(cashManager())
        ->test(TransactionImportWire::class)
        ->set('account_id', $account_id) // an account without transactions
        ->set('csv', $csvFile)
        ->set('mapping.date', 4)
        ->set('mapping.valuta', 5)
        ->set('mapping.empf_name', 6)
        ->set('mapping.empf_iban', 7)
        ->set('mapping.type', 9)
        ->set('mapping.zweck', 10)
        ->set('mapping.value', 11)
        ->set('mapping.saldo', 13)
        ->call('reverseCsvOrder')
        ->call('save')
        ->assertHasNoErrors();

    $transaction = BankTransaction::where('konto_id', '=', 4)->first();
    expect([
        $transaction?->value,
        $transaction?->saldo,
        $transaction?->empf_name,
        $transaction?->empf_iban,
        $transaction?->zweck,
    ])->toBe([
        '-13.14', '18089.63', 'Person 1', 'DE73447318315829961821', 'Entry 1',
    ]);
});

test('if mapping was saved and loaded', function () {
    $account_id = 4;
    $transactionAmount = BankTransaction::where('konto_id', '=', $account_id)->count();
    expect($transactionAmount)->toBe(5);

    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    \Livewire::actingAs(cashManager())
        ->test(TransactionImportWire::class)
        ->set('account_id', $account_id) // an account without transactions
        ->assertSet('mapping.date', 4)
        ->assertSet('mapping.valuta', 5)
        ->assertSet('mapping.empf_name', 6)
        ->assertSet('mapping.empf_iban', 7)
        ->assertSet('mapping.type', 9)
        ->assertSet('mapping.zweck', 10)
        ->assertSet('mapping.value', 11)
        ->assertSet('mapping.saldo', 13);
});

test('csv upload with correct saldo check', function () {});
