<?php

namespace App;

use GuzzleHttp\Client;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;

class Firefly
{
    // Default currency
    private string $currency = 'EUR';

    // Guzzle client
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => rtrim(config('services.firefly.uri'), '/') . '/api/v1/',
            'headers'  => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . config('services.firefly.token'),
            ],
        ]);
    }

    public function sync(Collection $transactions): void
    {
        if (! $this->connectionCheck()) {
            return;
        }

        foreach ($transactions as $transaction) {
            // https://developer.paypal.com/docs/transaction-search/transaction-event-codes/
            // Only get:
            // - PayPal account-to-PayPal account payment (T00xx)
            // - Refunds
            // -- T1106 Payment reversal, initiated by PayPal.  Completion of a chargeback.
            // -- T1107 Payment refund, initiated by merchant.
            $isPayment = ('T00' === substr($transaction->event_code, 0, 3));
            $isRefund  = in_array($transaction->event_code, ['T1106', 'T1107']);

            if (! $isPayment && ! $isRefund) {
                continue;
            }

            $direction = $isPayment ? 'expense' : 'revenue';

            // Set type depending on transaction
            // Default to expense
            $type        = 'withdrawal';
            $destination = '';
            $source      = intval(config('services.firefly.account'));

            if ('revenue' === $direction) {
                // Settings for a revenue
                $type        = 'deposit';
                $destination = intval(config('services.firefly.account'));
            }

            $property = "firefly_{$direction}_id";
            $payer    = $transaction->payer;

            if (is_null($payer)) {
                \Log::warning("Transaction with id {$transaction->id} doesn't have a payer. Skipping.");

                continue;
            }

            if (! $payer->{$property}) {
                $fireflyId = 0;
                // Create a new payer account in Firefly
                try {
                    $response = $this->createAccount($payer->name, $direction, $payer->email);

                    // Get the id of the newly created account.
                    $fireflyId = $response->data->id;
                } catch (RequestException $e) {
                    if ($e->hasResponse()) {
                        $response = json_decode($e->getResponse()->getBody(), true);

                        if ('This account name is already in use.' === Arr::get($response, 'errors.name.0')) {
                            $fireflyId = $this->findAccountByName($payer->name);
                        }
                    }
                }

                $payer->{$property} = $fireflyId;
                $payer->save();
            }

            // We got the opposing account. Set the source or destination. Depending on the transaction.
            if ('expense' === $direction) {
                // If the this is an expense we have to overwrite the destination.
                $destination = $payer->{$property};
            } elseif ('revenue' === $direction) {
                // If the this is a revenue we have to overwrite the source.
                $source = $payer->{$property};
            }

            $conversion = null;

            // If the transaction is in a foreign currency, set it as the
            // conversion and get the transaction in the active currency
            if ($transaction->currency !== $this->currency) {
                $conversion = $transaction;

                // Get the transaction from the same moment in the active currency
                $transaction = Transaction::where('initiation_date', $transaction->initiation_date)
                    ->where('event_code', 'T0200')
                    ->where('currency', $this->currency)
                    ->firstOrFail();
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
                        'description'    => $transaction->description ?: $transaction->pp_id,
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

            if (! is_null($conversion)) {
                $data['transactions'][0]['foreign_amount']        = abs($conversion->value);
                $data['transactions'][0]['foreign_currency_code'] = $conversion->currency;
            }

            if (is_null($transaction->firefly_id)) {
                try {
                    $response = $this->client->post('transactions', [
                        'json' => $data,
                    ]);
                } catch (TransferException|RequestException $e) {
                    $error = '';

                    if ($e->hasResponse()) {
                        $response = json_decode($e->getResponse()->getBody());
                        $error    = Arr::get(current($response->errors), 0);
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

                        continue;
                    }

                    if (str_starts_with($error, 'Duplicate of transaction ')) {
                        // Skip duplicate transactions
                        \Log::warning($error);

                        continue;
                    }

                    // TODO: error handling
                    throw $e;
                } catch (\Exception $e) {
                    throw $e;
                    // TODO: error handling
                }
                $response                = json_decode($response->getBody());
                $transaction->firefly_id = $response->data->id;
                $transaction->save();
            } else {
                $response = $this->client->put('transactions/' . $transaction->firefly_id, [
                    'json' => $data,
                ]);
                $response = json_decode($response->getBody());
            }
        }
    }

    protected function createAccount(string $name, string $direction, string $email): stdClass
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

    private function connectionCheck(): bool
    {
        try {
            $response = $this->client->get('about');

            Log::info('Firefly connection successful');
            Log::info($response->getBody());

            return true;
        } catch (ConnectException $e) {
            Log::error($e->getMessage());
        } catch (ClientException $e) {
            Log::error($e->getMessage());
        } catch (RequestException $e) {
            Log::error($e->getMessage());
        }

        return false;
    }

    private function findAccountByName(string $name): string
    {
        $response = $this->client->get('search/accounts', [
            'query' => [
                'query' => $name, // The query you wish to search for.
                'field' => 'name', // The account field(s) you want to search in.
            ],
        ]);

        $response = json_decode($response->getBody());

        $count = count($response->data);

        if (1 !== $count) {
            throw RuntimeException('Got ' . $count . ' results from search/accounts. Expected 1 result.');
        }

        return $response->data[0]->id;
    }
}
