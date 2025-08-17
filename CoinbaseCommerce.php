<?php

namespace Paymenter\Extensions\Gateways\CoinbaseCommerce;

use App\Classes\Extension\Gateway;
use App\Helpers\ExtensionHelper;
use App\Models\Gateway as ModelsGateway;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoinbaseCommerce extends Gateway
{
    public function boot()
    {
        require __DIR__ . '/routes.php';
        // Register webhook route
    }

    public function getMetadata()
    {
        return [
            'display_name' => 'Coinbase Commerce',
            'description'  => 'Accept cryptocurrency payments through Coinbase Commerce. Supports Bitcoin, Ethereum, USDC, and other major cryptocurrencies.',
            'version'      => '1.0.0',
            'author'       => 'Dankata Pich',
            'website'      => 'https://dankata.eu.org',
        ];
    }

    /**
     * Get all the configuration for the extension
     * 
     * @param array $values
     * @return array
     */
    public function getConfig($values = [])
    {
        return [
            [
                'name' => 'api_key',
                'label' => 'API Key',
                'type' => 'text',
                'description' => 'Your Coinbase Commerce API key from https://beta.commerce.coinbase.com/settings/security',
                'required' => true,
            ],
            [
                'name' => 'webhook_secret',
                'label' => 'Webhook Secret',
                'type' => 'text',
                'description' => 'Your webhook shared secret from https://beta.commerce.coinbase.com/settings/notifications',
                'required' => true,
            ],
            [
                'name' => 'test_mode',
                'label' => 'Test Mode',
                'type' => 'checkbox',
                'description' => 'Enable test mode for development',
                'required' => false,
            ],
            [
                'name' => 'charge_reuse_hours',
                'label' => 'Charge Reuse Window (Hours)',
                'type' => 'number',
                'description' => 'How many hours to wait before creating a new charge for the same invoice (prevents duplicate charges)',
                'required' => false,
                'default' => 1,
            ],
        ];
    }
    
    /**
     * Return a view or a url to redirect to
     * 
     * @param Invoice $invoice
     * @param float $total
     * @return string
     */
    public function pay(Invoice $invoice, $total)
    {
        try {
            // Check if there's a recent charge for this invoice (within the configured time window)
            $reuseHours = $this->config('charge_reuse_hours') ?? 1;
            $recentCharge = $invoice->transactions()
                ->where('transaction_id', 'like', '%')
                ->where('created_at', '>=', now()->subHours($reuseHours))
                ->first();

            if ($recentCharge) {
                // Get the charge details from Coinbase Commerce to check if it's still valid
                try {
                    $response = Http::withHeaders([
                        'X-CC-Api-Key' => $this->config('api_key'),
                        'Content-Type' => 'application/json',
                    ])->get('https://api.commerce.coinbase.com/charges/' . $recentCharge->transaction_id);

                    if ($response->successful()) {
                        $charge = $response->json();
                        $chargeData = $charge['data'];
                        
                        // Check if the charge is still valid (not expired, not completed, and amount matches)
                        $chargeAmount = $chargeData['pricing']['local']['amount'] ?? 0;
                        $currentTotal = number_format($total, 2, '.', '');
                        
                        // According to API docs, check the timeline for the latest status
                        $latestStatus = $this->getChargeStatus($chargeData);
                        
                        // Check if charge is valid for reuse (not completed and amount matches)
                        if ($latestStatus !== 'COMPLETED' && 
                            $chargeAmount == $currentTotal) {
                            
                            Log::info('Coinbase Commerce: Reusing existing charge', [
                                'invoice_id' => $invoice->id,
                                'charge_id' => $chargeData['id'],
                                'latest_status' => $latestStatus,
                                'charge_amount' => $chargeAmount,
                                'current_total' => $currentTotal,
                                'reuse_window_hours' => $reuseHours
                            ]);
                            
                            // Return the existing hosted payment URL
                            return $chargeData['hosted_url'];
                        } else {
                            Log::info('Coinbase Commerce: Existing charge not suitable for reuse', [
                                'invoice_id' => $invoice->id,
                                'charge_id' => $chargeData['id'],
                                'latest_status' => $latestStatus,
                                'charge_amount' => $chargeAmount,
                                'current_total' => $currentTotal,
                                'reason' => $latestStatus === 'COMPLETED' ? 'completed' : 'amount_mismatch'
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Coinbase Commerce: Failed to check existing charge, creating new one', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Create a new charge on Coinbase Commerce
            $chargeData = [
                'name' => 'Invoice #' . $invoice->id,
                'description' => 'Payment for invoice #' . $invoice->id,
                'pricing_type' => 'fixed_price',
                'local_price' => [
                    'amount' => number_format($total, 2, '.', ''),
                    'currency' => $invoice->currency_code ?? 'USD'
                ],
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'user_id' => $invoice->user_id,
                    'total' => $total
                ],
                'redirect_url' => route('invoices.show', $invoice),
                'cancel_url' => route('invoices.show', $invoice),
            ];

            $response = Http::withHeaders([
                'X-CC-Api-Key' => $this->config('api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.commerce.coinbase.com/charges', $chargeData);

            if ($response->successful()) {
                $charge = $response->json();
                
                // Create a temporary transaction record to link the charge ID to the invoice
                // This allows us to find the invoice when webhook arrives before payment processing
                $invoice->transactions()->create([
                    'gateway_id' => null,
                    'amount' => 0,
                    'fee' => null,
                    'transaction_id' => $charge['data']['id']
                ]);

                // Return the hosted payment URL - Paymenter will redirect to it
                return $charge['data']['hosted_url'];
            } else {
                Log::error('Coinbase Commerce Charge Creation Failed', [
                    'invoice_id' => $invoice->id,
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                
                throw new \Exception('Failed to create payment charge: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Coinbase Commerce Payment Error', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Payment processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle webhook notifications from Coinbase Commerce
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function webhook(Request $request)
    {
        try {
            $rawPayload = $request->getContent();
            $signature = $request->header('X-CC-Webhook-Signature');
            $webhookSecret = $this->config('webhook_secret');

            // Verify webhook signature
            if (!$this->verifyWebhookSignature($rawPayload, $signature, $webhookSecret)) {
                Log::error('Coinbase Commerce Webhook: Invalid signature', [
                    'signature' => $signature,
                    'payload_length' => strlen($rawPayload)
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $data = json_decode($rawPayload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Coinbase Commerce Webhook: Invalid JSON', [
                    'error' => json_last_error_msg(),
                    'payload' => $rawPayload
                ]);
                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            Log::info('Coinbase Commerce Webhook Received', [
                'event_type' => $data['event']['type'] ?? 'unknown',
                'charge_id' => $data['event']['data']['id'] ?? 'unknown'
            ]);

            // Process the webhook event
            $this->processWebhookEvent($data);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Coinbase Commerce Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->getContent()
            ]);
            
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Verify the webhook signature
     * 
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @return bool
     */
    private function verifyWebhookSignature($payload, $signature, $secret)
    {
        if (empty($signature) || empty($secret)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process webhook events
     * 
     * @param array $data
     * @return void
     */
    private function processWebhookEvent($data)
    {
        $eventType = $data['event']['type'] ?? '';
        $chargeData = $data['event']['data'] ?? [];
        $chargeId = $chargeData['id'] ?? '';

        // Find invoice by charge ID using the transaction_id in InvoiceTransaction
        $invoice = Invoice::whereHas('transactions', function ($query) use ($chargeId) {
            $query->where('transaction_id', $chargeId);
        })->first();

        if (!$invoice) {
            Log::warning('Coinbase Commerce Webhook: Invoice not found', [
                'charge_id' => $chargeId,
                'event_type' => $eventType
            ]);
            return;
        }

        Log::info('Coinbase Commerce Webhook: Processing event', [
            'event_type' => $eventType,
            'charge_id' => $chargeId,
            'invoice_id' => $invoice->id
        ]);

        switch ($eventType) {
            case 'charge:confirmed':
                $this->handleChargeConfirmed($invoice, $chargeData);
                break;
                
            case 'charge:pending':
                $this->handleChargePending($invoice, $chargeData);
                break;
                
            case 'charge:failed':
                $this->handleChargeFailed($invoice, $chargeData);
                break;
                
            case 'charge:created':
                $this->handleChargeCreated($invoice, $chargeData);
                break;
                
            default:
                Log::info('Coinbase Commerce Webhook: Unhandled event type', [
                    'event_type' => $eventType,
                    'charge_id' => $chargeId
                ]);
                break;
        }
    }

    /**
     * Handle confirmed charge
     * 
     * @param Invoice $invoice
     * @param array $chargeData
     * @return void
     */
    private function handleChargeConfirmed($invoice, $chargeData)
    {
        // Check if payment already exists (real payment, not temporary)
        $existingPayment = $invoice->transactions()
            ->where('transaction_id', $chargeData['id'])
            ->where('amount', '>', 0)
            ->first();

        if ($existingPayment) {
            Log::info('Coinbase Commerce: Payment already processed', [
                'invoice_id' => $invoice->id,
                'charge_id' => $chargeData['id']
            ]);
            return;
        }

        // Get the amount from the charge
        $amount = $chargeData['pricing']['local']['amount'] ?? 0;
        $currency = $chargeData['pricing']['local']['currency'] ?? 'USD';

        // Find the temporary transaction and update it with real payment data
        $tempTransaction = $invoice->transactions()
            ->where('transaction_id', $chargeData['id'])
            ->where('amount', 0)
            ->first();

        if ($tempTransaction) {
            // Update the temporary transaction with real payment data
            $tempTransaction->update([
                'amount' => $amount,
                'gateway_id' => ModelsGateway::where('extension', 'CoinbaseCommerce')->first()?->id
            ]);
        } else {
            // Create a new transaction if no temporary one exists
            ExtensionHelper::addPayment(
                $invoice->id,
                'Coinbase Commerce',
                $amount,
                null, // No fee information available
                $chargeData['id']
            );
        }

        Log::info('Coinbase Commerce: Payment confirmed and processed', [
            'invoice_id' => $invoice->id,
            'charge_id' => $chargeData['id'],
            'amount' => $amount,
            'currency' => $currency
        ]);
    }

    /**
     * Handle pending charge
     * 
     * @param Invoice $invoice
     * @param array $chargeData
     * @return void
     */
    private function handleChargePending($invoice, $chargeData)
    {
        Log::info('Coinbase Commerce: Charge pending', [
            'invoice_id' => $invoice->id,
            'charge_id' => $chargeData['id']
        ]);

        // Update invoice status if needed
        if ($invoice->status === 'pending') {
            $invoice->update(['status' => 'pending']);
        }
    }

    /**
     * Handle failed charge
     * 
     * @param Invoice $invoice
     * @param array $chargeData
     * @return void
     */
    private function handleChargeFailed($invoice, $chargeData)
    {
        Log::warning('Coinbase Commerce: Charge failed', [
            'invoice_id' => $invoice->id,
            'charge_id' => $chargeData['id'],
            'failure_reason' => $chargeData['failure_reason'] ?? 'unknown'
        ]);

        // Update invoice status if needed
        if ($invoice->status === 'pending') {
            $invoice->update(['status' => 'pending']);
        }
    }

    /**
     * Handle created charge
     * 
     * @param Invoice $invoice
     * @param array $chargeData
     * @return void
     */
    private function handleChargeCreated($invoice, $chargeData)
    {
        Log::info('Coinbase Commerce: Charge created', [
            'invoice_id' => $invoice->id,
            'charge_id' => $chargeData['id']
        ]);
    }

    /**
     * Get the current status of a charge from the timeline
     * According to API docs, status is in the timeline array
     * 
     * @param array $chargeData
     * @return string|null
     */
    private function getChargeStatus($chargeData)
    {
        if (!isset($chargeData['timeline']) || !is_array($chargeData['timeline'])) {
            return null;
        }

        // Get the latest timeline entry (last in the array)
        $latestTimeline = end($chargeData['timeline']);
        if (!$latestTimeline || !isset($latestTimeline['status'])) {
            return null;
        }

        return $latestTimeline['status'];
    }
}