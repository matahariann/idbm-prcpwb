<?php

namespace App\Services\FACTWM;

use App\Jobs\FACTWM\SendInvoiceJob;
use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION as Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class InvoiceService
{
    public function sendInvoice(array $payload): array
    {
        $billingStatement = $this->extractBillingStatement($payload);

        if (empty($payload)) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Payload invoice tidak boleh kosong',
            ];
        }

        try {
            SendInvoiceJob::dispatch($payload, $billingStatement);

            return [
                'success' => true,
                'status' => 202,
                'message' => 'Invoice queued successfully',
                'data' => [
                    'status' => 'WAITING',
                    'Billing_Statement_No' => $billingStatement,
                    'queued' => true,
                ],
            ];
        } catch (\Throwable $e) {
            Log::critical('Failed to dispatch SendInvoiceJob', [
                'billing_statement' => $billingStatement,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Gagal memasukkan invoice ke antrean',
                'detail' => $e->getMessage(),
            ];
        }
    }

    public function sendInvoiceNow(array $payload): array
    {
        $token = config('services.ifs.token');
        $base_url_api = Config::where('VVARIABLE', 'base_url_api')->value('VVALUE');
        $endpoint = Config::where('VVARIABLE', 'endpoint_create_manual_si')->value('VVALUE');

        if (!$base_url_api || !$endpoint) {
            return [
                'success' => false,
                'status'  => 500,
                'message' => 'Config API tidak lengkap'
            ];
        }

        $url = $base_url_api . $endpoint;
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(300)
                ->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status'  => $response->status(),
                    'data'    => $response->json()
                ];
            }

            // 🔐 HANDLE 401
            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'status'  => 401,
                    'message' => 'Unauthorized / token tidak valid atau sudah expired',
                    'detail'  => $response->json() ?? $response->body()
                ];
            }

            // ❌ VALIDATION
            if ($response->status() === 422) {
                return [
                    'success' => false,
                    'status'  => 422,
                    'message' => 'Validasi gagal',
                    'errors'  => $response->json()
                ];
            }

            // ❗ ERROR LAIN
            Log::error('API Error', [
                'url'      => $url,
                'payload'  => $payload,
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'status'  => $response->status(),
                'message' => 'Request API gagal',
                'detail'  => $response->json() ?? $response->body()
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // 🔌 Timeout / connection error
            Log::critical('API Connection Error', [
                'url'   => $url,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status'  => 503,
                'message' => 'API tidak dapat dihubungi',
                'detail'  => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            Log::critical('API Exception', [
                'url'   => $url,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status'  => 500,
                'message' => 'Terjadi kesalahan sistem',
                'detail'  => $e->getMessage()
            ];
        }
    }

    public function extractBillingStatement(array $payload): ?string
    {
        $firstItem = $payload[0] ?? null;

        if (is_array($firstItem)) {
            return $firstItem['Billing_Stat_No'] ?? $firstItem['Billing_Statement_No'] ?? null;
        }

        return $payload['Billing_Stat_No'] ?? $payload['Billing_Statement_No'] ?? null;
    }
}
