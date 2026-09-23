<?php

namespace App;

use Carbon\Carbon;
use GuzzleHttp\Client;

use function GuzzleHttp\json_decode;

class PayPal
{
    /*
     * Sandbox: https://api-m.sandbox.paypal.com/v1/
     * Live: https://api-m.paypal.com/v1/
     */
    private string $baseUri;

    private string $clientId;

    private string $clientSecret;

    private Client $client;

    public function __construct()
    {
        $this->baseUri = config('services.paypal.uri');
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');

        $this->getToken();
    }

    public function getTransactions(?Carbon $date = null): ?array
    {
        if (is_null($date)) {
            $date = Carbon::now();
        }

        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        // PayPal's Transaction Search only covers the previous three years.
        // syncPayPal() walks backwards a month at a time and relies on this
        // method returning null to know when to stop, so stop at the documented
        // horizon rather than discovering it through a failed request.
        if ($start->lt(Carbon::now()->subYears(3)->addDays(2))) {
            return null;
        }

        try {
            // Get all transactions for the current month
            $response = $this->client->get('reporting/transactions', [
                'query' => [
                    'page' => 1,
                    'page_size' => 500,
                    'start_date' => $start->toAtomString(),
                    'end_date' => $end->toAtomString(),
                    'fields' => 'all',
                ],
            ]);
        } catch (\Exception $e) {
            $body = $e->getResponse() !== null ? (string) $e->getResponse()->getBody() : '';
            $err = json_decode($body);

            // Returning null tells the caller to stop walking backwards, so it
            // must only mean "there is no more data".
            if (! empty($err->message) && $err->message === 'Data for the given start date is not available.') {
                return null;
            }

            // Once the start date passes the three-year mark PayPal answers
            // INVALID_REQUEST rather than the message above. That was not
            // handled, so execution fell through to the return below with
            // $response holding a decoded stdClass and died with
            // "Call to undefined method stdClass::getBody()".
            if (! empty($err->name) && $err->name === 'INVALID_REQUEST') {
                return null;
            }

            // A rate limit, an expired token or a PayPal outage is not the same
            // as running out of history. Silently treating those as "done"
            // would truncate the import and still look like a clean run, so
            // fail loudly and say which month failed.
            throw new \RuntimeException(sprintf(
                'PayPal returned an unexpected error for %s: %s',
                $start->format('Y-m'),
                $body !== '' ? $body : $e->getMessage()
            ));
        }

        return json_decode($response->getBody())->transaction_details;
    }

    private function getToken()
    {
        $client = new Client([
            'base_uri' => $this->baseUri,
        ]);

        $response = $client->post(
            'oauth2/token',
            [
                'auth' => [
                    $this->clientId, $this->clientSecret,
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
            ]
        );

        $response = json_decode($response->getBody());

        $token = $response->access_token;

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);
    }
}
