<?php

namespace App\Http\Controllers\Api\FACTWM\FACTWM02;

use App\Helpers\Helpers;
use App\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\FACTWM\FACTWM02\ValidateInvoiceOcrRequest;
use App\Http\Requests\FACTWM\FACTWM02\ValidateRekapJasaOcrRequest;
use App\Http\Requests\FACTWM\FACTWM02\ValidateTaxOcrRequest;
use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION as Config;
use App\Services\FACTWM\OCRService;
use Illuminate\Http\Request;

class FACTWMF007 extends Controller
{
    public function __construct(private OCRService $ocrService) {}

    public function validateInvoice(ValidateInvoiceOcrRequest $request)
    {
        $params = $this->mapParamsInvoice($request->params);
        $renderDpi = $this->resolveRenderDpi($request);
        $total = $this->extractInvoiceTotalFromParams($request->params);
        $requireMateraiOcr = $this->resolveRequireMateraiOcr($total);

        try {
            $ocrResult = $this->ocrService->validate($params, $request->invoice_file, $renderDpi);
            $valid = data_get($ocrResult, 'valid', []);
            $text = data_get($ocrResult, 'text');
            $materaiStatus = $this->resolveMateraiStatusFromValid($valid, $requireMateraiOcr);
        } catch (\Throwable $e) {
            if ($requireMateraiOcr === 'Y') {
                $materaiStatus = 'ERROR';
            } else {
                $materaiStatus = null;
            }

            throw $e;
        }

        return Response::success([
            'valid' => $valid,
            'text' => $text,
            'require_materai_ocr' => $requireMateraiOcr,
            'ocr_materai_status' => $materaiStatus,
        ]);
    }

    public function validateTax(ValidateTaxOcrRequest $request)
    {
        $params = $this->mapParamsTax($request->params);
        $renderDpi = $this->resolveRenderDpi($request);
        $ocrResult = $this->ocrService->validate($params, $request->tax_file, $renderDpi);

        return Response::success([
            'valid' => data_get($ocrResult, 'valid', []),
            'text' => data_get($ocrResult, 'text'),
        ]);
    }

    public function validateRekapJasa(ValidateRekapJasaOcrRequest $request)
    {
        $params = $this->mapParamsRekapJasa($request->params);
        $renderDpi = $this->resolveRenderDpi($request);
        $ocrResult = $this->ocrService->validate($params, $request->rekap_jasa_file, $renderDpi);

        return Response::success([
            'valid' => data_get($ocrResult, 'valid', []),
            'text' => data_get($ocrResult, 'text'),
        ]);
    }

    private function mapParamsInvoice($params)
    {
        $mapParams = explode(',', $params);
        $invoice = $mapParams[0] ?? null;
        $tglInvoice = $mapParams[1] ?? null;
        $amount = (int) str_replace('.', '', $mapParams[2] ?? 0);
        $ppn = (int) str_replace('.', '', $mapParams[3] ?? 0);
        $pkpSupplier = $mapParams[5] ?? false;
        $total = (int) str_replace('.', '', $mapParams[7] ?? 0);
        $inputPPh = $mapParams[8] ?? null;
        $minimumValidasiMaterai = $this->getMinimumValidasiMaterai();

        $listFormatTglInvoice = Helpers::listDateFormat($tglInvoice);
        $keywords = [
            [
                'key' => 'invoice_number',
                'value' => $invoice,
                'checked' => false,
            ],
        ];

        foreach ($listFormatTglInvoice as $date) {
            $keywords[] = [
                'key' => 'invoice_date',
                'value' => $date,
                'checked' => false,
            ];
        }

        $keywords = array_merge($keywords, [
            [
                'key' => 'net-amount',
                'id' => 'net-amount-status',
                'value' => number_format((float) $amount, 0, ',', '.'),
                'checked' => false,
            ],
            [
                'key' => 'net-amount',
                'id' => 'net-amount-status',
                'value' => number_format((float) $amount, 0, '.', ','),
                'checked' => false,
            ],
            [
                'key' => 'net-amount',
                'id' => 'net-amount-status',
                'value' => strval($amount),
                'checked' => false,
            ],
        ]);

        if ($pkpSupplier) {
            $keywords = array_merge($keywords, [
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => number_format((float) $ppn, 0, ',', '.'),
                    'checked' => false,
                ],
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => number_format((float) $ppn, 0, '.', ','),
                    'checked' => false,
                ],
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => strval($ppn),
                    'checked' => false,
                ],
            ]);
        }

        if (! empty($inputPPh)) {
            $pph = (int) str_replace('.', '', $inputPPh);

            $keywords = array_merge($keywords, [
                [
                    'key' => 'nilai',
                    'id' => 'nilai-status',
                    'value' => number_format((float) $pph, 0, ',', '.'),
                    'checked' => false,
                ],
                [
                    'key' => 'nilai',
                    'id' => 'nilai-status',
                    'value' => number_format((float) $pph, 0, '.', ','),
                    'checked' => false,
                ],
                [
                    'key' => 'nilai',
                    'id' => 'nilai-status',
                    'value' => strval($pph),
                    'checked' => false,
                ],
            ]);
        }

        if ($total >= $minimumValidasiMaterai) {
            $keywords[] = [
                'key' => 'materai',
                'value' => 'METERAI',
                'checked' => false,
            ];
        }

        return $keywords;
    }

    private function mapParamsRekapJasa($params)
    {
        $mapParams = explode(',', $params);
        $nilai = (int) str_replace('.', '', $mapParams[0] ?? 0);

        return [
            [
                'key' => 'nilai',
                'id' => 'nilai-status',
                'value' => number_format((float) $nilai, 0, ',', '.'),
                'checked' => false,
            ],
            [
                'key' => 'nilai',
                'id' => 'nilai-status',
                'value' => number_format((float) $nilai, 0, '.', ','),
                'checked' => false,
            ],
            [
                'key' => 'nilai',
                'id' => 'nilai-status',
                'value' => strval($nilai),
                'checked' => false,
            ],
        ];
    }

    private function mapParamsTax($params)
    {
        $mapParams = explode(',', $params);
        $tax = $mapParams[0] ?? null;
        $tglTax = $mapParams[1] ?? null;
        $ppn = (int) str_replace('.', '', $mapParams[3] ?? 0);
        $npwpSupplier = $mapParams[4] ?? null;
        $npwpIdbm = $mapParams[5] ?? null;
        $pkpSupplier = $mapParams[6] ?? false;
        $dpp = (int) str_replace('.', '', $mapParams[7] ?? 0);

        $listFormatTglInvoice = Helpers::listDateFormat($tglTax);
        $keywords = [
            [
                'key' => 'tax_invoice_number',
                'value' => $tax,
                'checked' => false,
            ],
            [
                'key' => 'dpp-nilai-lain',
                'id' => 'dpp-nilai-lain-status',
                'value' => number_format((float) $dpp, 0, ',', '.'),
                'checked' => false,
            ],
            [
                'key' => 'dpp-nilai-lain',
                'id' => 'dpp-nilai-lain-status',
                'value' => number_format((float) $dpp, 0, '.', ','),
                'checked' => false,
            ],
            [
                'key' => 'dpp-nilai-lain',
                'id' => 'dpp-nilai-lain-status',
                'value' => strval($dpp),
                'checked' => false,
            ],
        ];

        foreach ($listFormatTglInvoice as $date) {
            $keywords[] = [
                'key' => 'tax_invoice_date',
                'value' => $date,
                'checked' => false,
            ];
        }

        $keywords = array_merge($keywords, [
            [
                'key' => 'npwp_supplier',
                'value' => $npwpSupplier,
                'checked' => false,
            ],
            [
                'key' => 'npwp_idbm',
                'id' => 'npwp-idbm-status',
                'value' => $npwpIdbm,
                'checked' => false,
            ],
        ]);

        if ($pkpSupplier) {
            $keywords = array_merge($keywords, [
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => number_format((float) $ppn, 0, ',', '.'),
                    'checked' => false,
                ],
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => number_format((float) $ppn, 0, '.', ','),
                    'checked' => false,
                ],
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => strval($ppn),
                    'checked' => false,
                ],
            ]);
        }

        return $keywords;
    }

    private function extractInvoiceTotalFromParams(string $params): int
    {
        $mapParams = explode(',', $params);

        return (int) str_replace('.', '', $mapParams[7] ?? 0);
    }

    private function getMinimumValidasiMaterai(): int
    {
        $config = Config::where('VVARIABLE', 'minimum_validasi_materai')->first();

        return (int) ($config->VVALUE ?? 0);
    }

    private function resolveRequireMateraiOcr(int $total): string
    {
        return $total >= $this->getMinimumValidasiMaterai() ? 'Y' : 'N';
    }

    private function resolveMateraiStatusFromValid(array $valid, string $requireMateraiOcr): ?string
    {
        if ($requireMateraiOcr !== 'Y') {
            return null;
        }

        $materaiItem = collect($valid)->first(function ($item) {
            return strtolower((string) data_get($item, 'key')) === 'materai';
        });

        return data_get($materaiItem, 'checked') ? 'VERIFIED' : 'INVALID';
    }

    private function resolveRenderDpi(Request $request): int
    {
        return $request->integer('render_dpi') ?: 140;
    }
}
