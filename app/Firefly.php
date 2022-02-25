<?php

namespace App;

use GuzzleHttp\Client;
use App\Models\Transaction;

class Firefly
{
    private string $baseUri = 'http://firefly.box/api/v1/';

    private string $currency = 'EUR';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'headers'  => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . config('services.firefly.token'),
            ],
        ]);
    }

    public function sync($transactions)
    {
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

            if (! $payer->{$property}) {
                // Create a new payer account in Firefly
                try {
                    $response = $this->client->post('accounts', [
                        'json' => [
                            'name'  => $payer->name,
                            'type'  => $direction,
                            'notes' => $payer->email,
                        ],
                    ]);
                } catch (\Exception $e) {
                    $response = json_decode($e->getResponse()->getBody());

                    if ('This account name is already in use.' === $response->errors->name[0]) {
                        // TODO. User already exists. Retrieve it somehow.
                        throw new \RuntimeException('TODO');
                    }
                }
                $response = json_decode($response->getBody());

                $payer->{$property} = $response->data->id;
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
                } catch (\Exception $e) {
                    $response = json_decode($e->getResponse()->getBody());
                    dd($response, $data);
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
}
