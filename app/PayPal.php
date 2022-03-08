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
    private string $baseUri = 'https://api-m.paypal.com/v1/';

    private string $clientId;

    private string $clientSecret;

    private Client $client;

    public function __construct()
    {
        $this->clientId     = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');

        $this->getToken();
    }

    public function getTransactions(Carbon $date = null): ?array
    {
        if (is_null($date)) {
            $date = Carbon::now();
        }

        $start = $date->copy()->startOfMonth();
        $end   = $date->copy()->endOfMonth();

        try {
            // Get all transactions for the current month
            $response = $this->client->get('reporting/transactions', [
                'query' => [
                    'page'       => 1,
                    'page_size'  => 500,
                    'start_date' => $start->toAtomString(),
                    'end_date'   => $end->toAtomString(),
                    'fields'     => 'all',
                ],
            ]);
        } catch (\Exception $e) {
            $response = json_decode($e->getResponse()->getBody());

            if (! empty($response->message) && 'Data for the given start date is not available.' === $response->message) {
                // We're done here, we've loaded all transactions
                return null;
            }
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
            'headers'  => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
    }
}
