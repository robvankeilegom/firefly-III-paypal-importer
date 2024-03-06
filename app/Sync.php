<?php

namespace App;

use Carbon\Carbon;
use App\Models\Payer;
use App\Models\Transaction;

class Sync
{
    private PayPal $paypal;

    private Firefly $firefly;

    private bool $keepGoing;

    public function __construct(bool $keepGoing = false)
    {
        $this->keepGoing = $keepGoing;
    }

    // Loads transactions from PayPal and stores them
    public function syncPayPal(Carbon $date = null): void
    {
        $done = false;

        if (! isset($this->paypal)) {
            $this->paypal = new PayPal();
        }

        if (is_null($date)) {
            $date = Carbon::now();
        }

        // PayPal was founded in 1998
        if ($date->year < 1998) {
            return;
        }

        echo $date->month . '/' . $date->year . PHP_EOL;

        $records = $this->paypal->getTransactions($date);

        if (is_null($records)) {
            // Sync the previous month
            $this->syncPayPal($date->copy()->subMonth());
        }

        // Create a database record for each record
        foreach ($records as $record) {
            $payer = null;

            // Only load payer info when its available
            if (isset($record->payer_info->account_id)) {
                $name = '';

                // Name is not always in the same field.
                if (! empty($record->payer_info->payer_name->alternate_full_name)) {
                    $name = $record->payer_info->payer_name->alternate_full_name;
                } elseif (! empty($record->payer_info->payer_name->given_name)) {
                    $name = $record->payer_info->payer_name->given_name;
                }

                $payer = Payer::updateOrCreate([
                    'pp_id' => $record->payer_info->account_id,
                    'name'  => $name,
                ], [
                    'email'        => $record->payer_info->email_address ?? '',
                    'country_code' => $record->payer_info->country_code ?? '',
                ]);
            }

            $reference = null;

            if (isset($record->transaction_info->paypal_reference_id)) {
                $reference = $record->transaction_info->paypal_reference_id;
            }

            // Start building description.
            $description = [];

            // Add the invoice_id if available
            if (! empty($record->transaction_info->invoice_id)) {
                $description[] = $record->transaction_info->invoice_id;
            }

            if (! empty($record->cart_info->item_details)) {
                // Get all records with an item_name
                $cartItems = array_filter($record->cart_info->item_details, function ($item) {
                    return ! empty($item->item_name);
                });

                // If there are any items left, add them to the description
                if (count($cartItems) > 0) {
                    $description[] = implode(', ', array_column($cartItems, 'item_name'));
                }
            }

            // Remove duplicates. item_details and invoice_id can be the same value.
            $description = array_unique($description);

            $transaction = Transaction::where('pp_id', $record->transaction_info->transaction_id)->first();

            if ($transaction) {
                // We're only done if the --keep-going options wasn't passed.
                $done = !$this->keepGoing;
            } else {
                $transaction        = new Transaction();
                $transaction->pp_id = $record->transaction_info->transaction_id;
            }

            $transaction->reference_id    = $reference;
            $transaction->event_code      = $record->transaction_info->transaction_event_code;
            $transaction->initiation_date = $record->transaction_info->transaction_initiation_date;
            $transaction->currency        = $record->transaction_info->transaction_amount->currency_code;
            $transaction->value           = $record->transaction_info->transaction_amount->value;
            $transaction->description     = implode(' | ', $description);

            if (! is_null($payer)) {
                $transaction->payer()->associate($payer);
            } else {
                $transaction->payer_id = null;
            }
            $transaction->save();
        }

        if ($done) {
            return;
        }

        // Sync the previous month
        $this->syncPayPal($date->copy()->subMonth());
    }

    public function syncFirefly()
    {
        if (! isset($this->firefly)) {
            $this->firefly = new Firefly();
        }

        foreach (Transaction::all() as $transaction) {
            $this->firefly->push($transaction);
        }
    }
}
