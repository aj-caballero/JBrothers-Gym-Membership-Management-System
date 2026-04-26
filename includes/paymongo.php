<?php
// includes/paymongo.php
// ============================================================
//  PayMongo API Wrapper
//  Supports: Checkout Sessions (cards, GCash, Maya, etc.)
//  Docs: https://developers.paymongo.com
// ============================================================

class PayMongoAPI
{
    private string $secretKey;
    private string $baseUrl = 'https://api.paymongo.com/v1';

    public function __construct(string $secretKey)
    {
        if (empty($secretKey)) {
            throw new \InvalidArgumentException('PayMongo secret key cannot be empty.');
        }
        $this->secretKey = $secretKey;
    }

    // ----------------------------------------------------------
    //  Core HTTP helper
    // ----------------------------------------------------------
    private function request(string $method, string $endpoint, array $data = []): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension is required for PayMongo integration.');
        }

        $url = $this->baseUrl . $endpoint;
        $ch  = curl_init($url);

        $headers = [
            'Authorization: Basic ' . base64_encode($this->secretKey . ':'),
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_SSL_VERIFYPEER => true,
        ];

        if (!empty($data)) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $opts);

        $raw   = curl_exec($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("cURL error: $error");
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException("Invalid JSON response from PayMongo (HTTP $code).");
        }

        if ($code >= 400) {
            $detail = $decoded['errors'][0]['detail'] ?? ($decoded['message'] ?? 'Unknown PayMongo error');
            throw new \RuntimeException("PayMongo API error ($code): $detail");
        }

        return $decoded;
    }

    // ----------------------------------------------------------
    //  Checkout Sessions  (recommended – handles 3DS, GCash, etc.)
    // ----------------------------------------------------------

    /**
     * Create a hosted checkout session.
     *
     * @param array $lineItems  e.g. [['name'=>'Monthly Pass','amount'=>100000,'currency'=>'PHP','quantity'=>1]]
     * @param array $billing    e.g. ['name'=>'Juan Dela Cruz','email'=>'juan@example.com','phone'=>'09171234567']
     * @param string $successUrl  Full URL to redirect on success
     * @param string $cancelUrl   Full URL to redirect on cancel/failure
     * @param string $referenceNumber  Your internal payment reference
     * @param array $methods    Allowed methods: 'card','gcash','paymaya','dob','dob_ubp','billease','qrph'
     * @return array  Full API response (use ['data']['attributes']['checkout_url'] to redirect)
     */
    public function createCheckoutSession(
        array  $lineItems,
        array  $billing,
        string $successUrl,
        string $cancelUrl,
        string $referenceNumber,
        array  $methods = ['card', 'gcash', 'paymaya']
    ): array {
        $payload = [
            'data' => [
                'attributes' => [
                    'billing'              => $billing,
                    'line_items'           => $lineItems,
                    'payment_method_types' => $methods,
                    'success_url'          => $successUrl,
                    'cancel_url'           => $cancelUrl,
                    'reference_number'     => $referenceNumber,
                    'send_email_receipt'   => false,
                    'show_description'     => true,
                    'show_line_items'      => true,
                ],
            ],
        ];
        return $this->request('POST', '/checkout_sessions', $payload);
    }

    /**
     * Retrieve a checkout session (to verify payment status on return).
     */
    public function getCheckoutSession(string $sessionId): array
    {
        return $this->request('GET', '/checkout_sessions/' . $sessionId);
    }

    // ----------------------------------------------------------
    //  Payment Intents  (for advanced/custom flows)
    // ----------------------------------------------------------

    public function createPaymentIntent(int $amountCentavos, string $currency = 'PHP', string $description = ''): array
    {
        return $this->request('POST', '/payment_intents', [
            'data' => [
                'attributes' => [
                    'amount'                 => $amountCentavos,
                    'currency'               => $currency,
                    'capture_type'           => 'automatic',
                    'description'            => $description,
                    'payment_method_allowed' => ['card', 'gcash', 'paymaya'],
                ],
            ],
        ]);
    }

    public function getPaymentIntent(string $intentId): array
    {
        return $this->request('GET', '/payment_intents/' . $intentId);
    }

    // ----------------------------------------------------------
    //  Payment Links  (simple shareable link)
    // ----------------------------------------------------------

    public function createPaymentLink(int $amountCentavos, string $description, string $remarks = ''): array
    {
        return $this->request('POST', '/links', [
            'data' => [
                'attributes' => [
                    'amount'      => $amountCentavos,
                    'description' => $description,
                    'remarks'     => $remarks,
                ],
            ],
        ]);
    }

    // ----------------------------------------------------------
    //  Webhooks  (register your endpoint)
    // ----------------------------------------------------------

    public function createWebhook(string $url, array $events = ['checkout_session.payment.paid']): array
    {
        return $this->request('POST', '/webhooks', [
            'data' => [
                'attributes' => [
                    'url'    => $url,
                    'events' => $events,
                ],
            ],
        ]);
    }

    public function listWebhooks(): array
    {
        return $this->request('GET', '/webhooks');
    }

    // ----------------------------------------------------------
    //  Helper: peso → centavos
    // ----------------------------------------------------------

    public static function pesoToCentavos(float $peso): int
    {
        return (int) round($peso * 100);
    }
}

// ----------------------------------------------------------
//  Factory helper – throws a clear error if keys are missing
// ----------------------------------------------------------
function getPayMongoClient(): PayMongoAPI
{
    if (!defined('PAYMONGO_CONFIGURED')) {
        require_once __DIR__ . '/../config/paymongo.php';
    }

    if (!PAYMONGO_CONFIGURED) {
        throw new \RuntimeException(
            'PayMongo API keys are not configured. ' .
            'Open config/paymongo.php and replace the placeholder values with your real keys from dashboard.paymongo.com.'
        );
    }

    return new PayMongoAPI(PAYMONGO_SECRET_KEY);
}
