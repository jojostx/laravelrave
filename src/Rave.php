<?php

namespace KingFlamez\Rave;

use Illuminate\Support\Facades\Http;
use KingFlamez\Rave\Helpers\Banks;
use KingFlamez\Rave\Helpers\Beneficiary;
use KingFlamez\Rave\Helpers\Payments;
use KingFlamez\Rave\Helpers\Transfers;
use KingFlamez\Rave\Helpers\Verification;
use KingFlamez\Rave\Helpers\Subaccount;

/**
 * Flutterwave's Rave payment laravel package
 * @author Oluwole Adebiyi - Flamez <flamekeed@gmail.com>
 * @version 3
 **/
class Rave
{
    protected $publicKey;
    protected $secretKey;
    protected $secretHash;
    protected $encryptionKey;
    protected $baseUrl;

    function __construct()
    {
        $this->publicKey = config('flutterwave.publicKey');
        $this->secretKey = config('flutterwave.secretKey');
        $this->secretHash = config('flutterwave.secretHash');
        $this->encryptionKey = config('flutterwave.encryptionKey');
        $this->baseUrl = 'https://api.flutterwave.com/v3';
    }

    public static function initialize(string $publicKey, string $secretKey)
    {
        $rave = new static;
        $rave->secretKey = $secretKey;
        $rave->publicKey = $publicKey;

        return $rave;
    }

    public function setKeys(string $publicKey, string $secretKey)
    {
        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;

        return $this;
    }

    /**
     * Generates a unique reference
     */
    public function generateReference(String $transactionPrefix = NULL)
    {
        if ($transactionPrefix) {
            return $transactionPrefix . '_' . uniqid(time());
        }
        return 'flw_' . uniqid(time());
    }

    /**
     * Reaches out to Flutterwave to initialize a payment
     */
    public function initializePayment(array $data)
    {
        $payment = Http::withToken($this->secretKey)
            ->post(
                $this->baseUrl . '/payments',
                $data
            )->json();

        return $payment;
    }

    /**
     * Reaches out to Flutterwave to initialize a tokenized charge
     */
    public function initializeTokenizedCharge(array $data)
    {
        $payment = Http::withToken($this->secretKey)
            ->post(
                $this->baseUrl . '/tokenized-charges',
                $data
            )->json();

        return $payment;
    }

    /**
     * Reaches out to Flutterwave to initialize a tokenized charge

     */
    public function initializeBulkTokenizedCharge(array $data)
    {
        $payment = Http::withToken($this->secretKey)
            ->post(
                $this->baseUrl . '/bulk-tokenized-charges',
                $data
            )->json();

        return $payment;
    }

    /**
     * Reaches out to Flutterwave to get the collection of charges within
     * a bulk tokenized charge.
     */
    public function getBulkTokenizedCharges(string $id)
    {
        $data =  Http::withToken($this->secretKey)
            ->get($this->baseUrl . "/bulk-tokenized-charges/" . $id . '/transactions')
            ->json();

        return $data;
    }

    /**
     * Reaches out to Flutterwave to query the status of a bulk tokenized charge.
     */
    public function getBulkTokenizedChargeStatus(string $id)
    {
        $data = Http::withToken($this->secretKey)
            ->get($this->baseUrl . "/bulk-tokenized-charges/" . $id)
            ->json();

        return $data;
    }

    /**
     * Reaches out to Flutterwave to update the detail of a token.
     */
    public function updateTokenDetails(string $token, array $data)
    {
        $data =  Http::withToken($this->secretKey)
            ->put(
                $this->baseUrl . "/tokens/" . $token,
                $data
            )
            ->json();

        return $data;
    }

    /**
     * Gets a transaction ID depending on the redirect structure
     * @return string
     */
    public function getTransactionIDFromCallback()
    {
        $transactionID = request()->transaction_id;

        if (!$transactionID) {
            $transactionID = json_decode(request()->resp)->data->id;
        }

        return $transactionID;
    }

    /**
     * Reaches out to Flutterwave to validate a charge
     * @param $data
     * @return object
     */
    public function validateCharge(array $data)
    {
        $payment = Http::withToken($this->secretKey)
            ->post(
                $this->baseUrl . '/validate-charge',
                $data
            )->json();

        return $payment;
    }

    /**
     * Reaches out to Flutterwave to verify a transaction
     */
    public function verifyTransaction(string $id)
    {
        $data =  Http::withToken($this->secretKey)
            ->get($this->baseUrl . "/transactions/" . $id . '/verify')
            ->json();

        return $data;
    }

    /**
     * Confirms webhook `verifi-hash` is the same as the environment variable
     * @param $data
     * @return boolean
     */
    public function verifyWebhook()
    {
        // Process flutterwave Webhook. https://developer.flutterwave.com/reference#webhook
        if (request()->header('verif-hash')) {
            // get input and verify paystack signature
            $flutterwaveSignature = request()->header('verif-hash');

            // confirm the signature is right
            if ($flutterwaveSignature == $this->secretHash) {
                return true;
            }
        }
        return false;
    }

    /**
     * Payments
     * @return Payments
     */
    public function payments()
    {
        $payments = new Payments($this->publicKey, $this->secretKey, $this->baseUrl, $this->encryptionKey);
        return $payments;
    }

    /**
     * Banks
     * @return Banks
     */
    public function banks()
    {
        $banks = new Banks($this->publicKey, $this->secretKey, $this->baseUrl);
        return $banks;
    }

    /**
     * Transfers
     * @return Transfers
     */
    public function transfers()
    {
        $transfers = new Transfers($this->publicKey, $this->secretKey, $this->baseUrl);
        return $transfers;
    }

    /**
     * Beneficiary
     * @return Beneficiary
     */
    public function beneficiaries()
    {
        $beneficiary = new Beneficiary($this->publicKey, $this->secretKey, $this->baseUrl);
        return $beneficiary;
    }

    /**
     * Verification
     * @return Verification
     */
    public function verification()
    {
        $verification = new Verification($this->publicKey, $this->secretKey, $this->baseUrl);
        return $verification;
    }

    /**
     * Subaccounts
     * @return Subaccount
     */
    public function subaccounts()
    {
        $subaccount = new Subaccount($this->publicKey, $this->secretKey, $this->baseUrl);
        return $subaccount;
    }
}
