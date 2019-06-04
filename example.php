<?php

require_once __DIR__ . '/vendor/autoload.php';

use \RetailCrm\ApiClient;
use \RetailCrm\Exception\CurlException;
use \Dotenv\Dotenv;

$dotenv = Dotenv::create(__DIR__);
$dotenv->load();
$urlCrm = getenv('URL_CRM');
$apiKey = getenv('API_KEY');

$client = new ApiClient(
    $urlCrm,
    $apiKey,
    ApiClient::V5
);

try {
    $response = $client->request->customersList([], null, 100);
} catch (CurlException $e) {
    echo "Connection error: " . $e->getMessage();
}

if ($response->isSuccessful()) {
    $totalPageCount = $response->pagination['totalPageCount'];
    $customerList = [];
    for ($page = 1; $page <= $totalPageCount; $page++) {
        $responseCustomersList = $client->request->customersList([], $page, 100);
        foreach ($responseCustomersList->customers as $customer) {
            if (!empty($customer['presumableSex'])) { //'site'
                $customerList[] = ['id' => $customer['id'], 'sex' => $customer['presumableSex'], 'site' => $customer['site']];
            }
        }
    }

    file_put_contents(__DIR__ . '/customerList.log', json_encode([
        'date' => date('Y-m-d H:i:s'),
        'customerList' => $customerList
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);

    for ($page = 1; $page <= $totalPageCount; $page++) {
        foreach ($customerList as $customer) {
            $responseСustomerEdit = $client->request->customersEdit(['id' => $customer['id'], 'sex' => $customer['sex']], 'id', $customer['site']);
            file_put_contents(__DIR__ . '/response.log', json_encode([
                'date' => date('Y-m-d H:i:s'),
                'customerId' => $customer['id'],
                'response' => [
                    $responseСustomerEdit->getStatusCode(),
                    $responseСustomerEdit->isSuccessful(),
                    isset($responseСustomerEdit['errorMsg']) ? $responseСustomerEdit['errorMsg'] : 'not errors'
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
        }
    }
} else {
    echo sprintf(
        "Error: [HTTP-code %s] %s",
        $response->getStatusCode(),
        $response->getErrorMsg()
    );
    if (isset($response['errors'])) {
        print_r($response['errors']);
    }
}