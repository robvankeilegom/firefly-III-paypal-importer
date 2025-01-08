<?php

namespace App;

use GuzzleHttp\Client;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;

class Firefly
{
    // Default currency
    private string $currency;

    // Guzzle client
    private Client $client;

    private string $tag;

    public function __construct()
    {
        $this->tag = date('Ymd-His', time());

        $this->client = new Client([
            'base_uri' => rtrim(config('services.firefly.uri'), '/') . '/api/v1/',
            'headers'  => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . config('services.firefly.token'),
            ],
        ]);

        // https://developer.paypal.com/api/rest/reference/currency-codes/
        $this->currency = config('app.currency');
    }

    public function push(Transaction $transaction): bool
    {
        // https://developer.paypal.com/docs/transaction-search/transaction-event-codes/
        // Only get:
        // - PayPal account-to-PayPal account payment (T00xx)
        // - Refunds
        // -- T1106 Payment reversal, initiated by PayPal.  Completion of a chargeback.
        // -- T1107 Payment refund, initiated by merchant.

        if (! $transaction->is_payment && ! $transaction->is_refund && ! $transaction->is_revenue) {
            return false;
        }

        // Set type depending on transaction
        $direction   = '';
        $type        = '';
        $destination = '';
        $source      = '';

        if ($transaction->is_payment) {
            $direction = 'expense';

            // Transaction is a payment. So the source will be the paypal account
            $source = intval(config('services.firefly.account'));
        } else {
            // Revenue or refund
            $direction = 'revenue';

            // Transaction is a deposit. So the destination will be the paypal account
            $destination = intval(config('services.firefly.account'));
        }

        $payer = $transaction->payer;

        if (is_null($payer)) {
            // All transactions should have a payer
            \Log::warning("Transaction with id {$transaction->id} doesn't have a payer. Skipping.");

            return false;
        }

        // A payer can have 2 separate firefly accounts (expense or revenue)
        $property = "firefly_{$direction}_id";

        // We haven't pushed the payer as a expense/revenue account yet,
        // create it first
        if (is_null($payer->{$property})) {
            $fireflyId = 0;

            try {
                // Create a new payer account in Firefly
                $response = $this->createAccount($payer->name, $direction, $payer->email);

                // Get the id of the newly created account.
                $fireflyId = $response->data->id;
            } catch (RequestException $e) {
                // If a request exception is thrown, it could be because the account already exists
                // This happens if the account was already created be another importer or created manually
                $response = null;

                if ($e->hasResponse()) {
                    $response = json_decode($e->getResponse()->getBody(), true);
                }

                if ('This account name is already in use.' === Arr::get($response, 'errors.name.0')) {
                    // Find the account by name
                    $fireflyId = $this->findAccountByName($payer->name, $direction);
                } else {
                    throw $e;
                }
            }

            $payer->{$property} = $fireflyId;
            $payer->save();
        }

        // We got the opposing account. Set the source or destination. Depending on the transaction.
        if ($transaction->is_payment) {
            // If the this is an expense we have to overwrite the destination.
            $destination = (int) $payer->{$property};
        } else {
            // If the this is a revenue we have to overwrite the source.
            $source = $payer->{$property};
        }

        $conversion = null;

        // Get the type of the transaction before the possible convertion to a conversion
        $type = $transaction->is_payment ? 'withdrawal' : 'deposit';

        // If the transaction is in a foreign currency, set it as the
        // conversion and get the transaction in the active currency
        if ($transaction->currency !== $this->currency) {
            $conversion = $transaction;

            $reference = $conversion->pp_id;

            // For refunds, the reference_id of the conversion id the id of the original transaction
            // Which is the reference_id on the refund.
            if ($conversion->is_refund) {
                $reference = $conversion->reference_id;
            }

            // Get the transaction from the same moment in the active currency
            $transaction = Transaction::where('reference_id', $reference)
                ->where('initiation_date', $transaction->initiation_date)
                ->where('event_code', 'T0200')
                ->where('currency', $this->currency)
                ->first();

            if (is_null($transaction)) {
                \Log::error('Can\'t find conversion for transaction ' . $conversion->id);

                return false;
            }

            // Make sure the currency we're converting from is valid.
            $exists = $this->validateCurrency($conversion->currency);

            if (false === $exists) {
                \Log::error(
                    'Couldn\'t push transaction: '
                    . $conversion->id
                    . '. Currency \''
                    . $conversion->currency
                    . '\' doesn\'t exist.'
                );

                return false;
            }
        }

        $data = [
            'error_if_duplicate_hash' => true,
            'apply_rules'             => true,
            'fire_webhooks'           => true,
            'transactions'            => [
                [
                    'type'           => $type,
                    'date'           => $transaction->initiation_date->toAtomString(),
                    'amount'         => abs($transaction->value),
                    'description'    => substr($transaction->description, 0, 1000) ?: $transaction->pp_id,
                    'order'          => 0,
                    'currency_code'  => $this->currency,
                    'source_id'      => $source,
                    'destination_id' => $destination,
                    // 'destination_name'   =>,
                    'notes'       => $transaction->description,
                    'external_id' => $transaction->pp_id,
                ],
            ],
        ];

        if (config('app.enable_tags')) {
            $data['transactions'][0]['tags'] = [$this->tag];
        }

        if (! is_null($conversion)) {
            $data['transactions'][0]['foreign_amount']        = abs($conversion->value);
            $data['transactions'][0]['foreign_currency_code'] = $conversion->currency;
        }

        if (! is_null($transaction->firefly_id)) {
            // Transaction exists, update it and return the response
            try {
                $response = $this->client->put('transactions/' . $transaction->firefly_id, [
                    'json' => $data,
                ]);

                return true;
            } catch (ClientException $e) {
                // If there's no response or the response isn't 404, throw the error anyway
                if (! $e->hasResponse() || 404 !== $e->getResponse()->getStatusCode()) {
                    throw $e;
                }

                // Got a 404 response. Don't do anything so the transaction gets POST-ed
            }
        }

        // Create a new transaction
        try {
            $response = $this->client->post('transactions', [
                'json' => $data,
            ]);
        } catch (RequestException|TransferException $e) {
            $error = '';

            if ($e->hasResponse()) {
                $response = json_decode($e->getResponse()->getBody());

                // Check if the response has an 'errors' property.
                // If so we can print out a more detailed error than the one
                // firefly provides
                if (property_exists($response, 'errors')) {
                    $error = Arr::get(current($response->errors), 0);
                }
            }

            // Swap out error for a more clear error message
            if ('The selected transactions.0.foreign_currency_code is invalid.' === $error) {
                $error = '';

                if (! is_null($conversion)) {
                    $error = "Currency {$transaction->currency} or {$conversion->currency} aren't available in Firefly. Add them under Options > Currencies.";
                } else {
                    $error = "Currency {$transaction->currency} isn't available in Firefly. Add it under Options > Currencies.";
                }

                \Log::error($error);

                return false;
            }

            // Skip duplicate transactions
            if (str_starts_with($error, 'Duplicate of transaction ')) {
                \Log::warning($error);

                // Return true since this isn't really a problem
                return true;
            }

            // TODO: error handling
            throw $e;
        } catch (\Exception $e) {
            throw $e;
            // TODO: error handling
        }

        $response = json_decode($response->getBody());

        $transaction->firefly_id = $response->data->id;

        // Save transaction here. Otherwise if the transaction was swapped for a conversion, the firefly_id would never be saved.
        $transaction->save();

        return true;
    }

    public function connectionCheck(): bool|string
    {
        try {
            $response = $this->client->get('about');

            Log::info('Firefly connection successful');
            Log::info($response->getBody());

            return true;
        } catch (ConnectException $e) {
            Log::error($e->getMessage());

            return $e->getMessage();
        } catch (ClientException $e) {
            Log::error($e->getMessage());

            return $e->getMessage();
        } catch (RequestException $e) {
            Log::error($e->getMessage());

            return $e->getMessage();
        }

        return false;
    }

    protected function createAccount(string $name, string $direction, string $email): \stdClass
    {
        $response = $this->client->post('accounts', [
            'json' => [
                'name'  => $name,
                'type'  => $direction,
                'notes' => $email,
            ],
        ]);

        return json_decode($response->getBody());
    }

    private function validateCurrency(string $currency): bool
    {
        if (empty($currency)) {
            return false;
        }

        try {
            $response = $this->client->get('currencies/' . $currency);
        } catch (ClientException $e) {
            // If there's no response or the response isn't 404, throw the error anyway
            if (! $e->hasResponse() || 404 !== $e->getResponse()->getStatusCode()) {
                throw $e;
            }

            // Got a 404 response. The currency doesn't exist.
            return false;
        }

        return true;
    }

    private function findAccountByName(string $name, string $type): string
    {
        $response = $this->client->get('search/accounts', [
            'query' => [
                'query' => $name, // The query you wish to search for.
                'type'  => $type, // Type of the account (revenue or expense)
                'field' => 'name', // The account field(s) you want to search in.
            ],
        ]);

        $response = json_decode($response->getBody());

        $count = count($response->data);

        // There's only one account, return it.
        if (1 === $count) {
            return $response->data[0]->id;
        }

        $exactMatches = 0;
        $match        = null;

        // Check if there's a single account with an exact match.
        foreach ($response->data as $account) {
            if ($account->attributes->name === $name) {
                ++$exactMatches;
                $match = $account;
            }
        }

        if (1 === $exactMatches) {
            // Yes there is, return it.
            return $match->id;
        }

        // Not sure what to do.
        throw new \RuntimeException(
            'Got ' . $count . ' results from search/accounts. Expected 1 result. q: ' . $name . ' type: ' . $type
        );
    }
}
