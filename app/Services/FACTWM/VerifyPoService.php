<?php

namespace App\Services\FACTWM;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION as Config;
use Illuminate\Support\Facades\Http;

class VerifyPoService
{
    public function getPPhList(Request $request)
    {
        $pph = strtolower(trim((string) $request->input('pph')));       // exact
        $search = strtolower(trim((string) $request->input('search'))); // like

        $grouped = [];

        $token = config('services.ifs.token');
        $base_url_api = Config::where('VVARIABLE', 'base_url_api')->value('VVALUE');
        $endpoint = Config::where('VVARIABLE', 'endpoint_objek_pajak')->value('VVALUE');
        $config_verify_api = Config::where('VVARIABLE', 'verify_api')->first();
        $verify_api = filter_var(
            $config_verify_api->VVALUE ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$base_url_api || !$endpoint) {
            return [
                'success' => false,
                'status'  => 500,
                'message' => 'Config API tidak lengkap'
            ];
        }

        $params = array_filter([
            'nama' => $search !== '' ? $search : null,
            'q' => $pph !== '' ? $pph : null,
        ], fn($value) => ! is_null($value) && $value !== '');

        // $params = [
        //     'q' => $pph // contoh: PPh23
        // ];

        $url = $base_url_api . $endpoint;
        $dummy = json_decode(File::get(base_path('dummy/pph-list.json')), true);

        if ($verify_api) {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->get($url, $params);

            if ($response->successful()) {
                $json = $response->json();

                return [
                    'success' => true,
                    'message' => $json['message'] ?? null,
                    'data'    => $json['data'] ?? null,
                ];
            }

            return [];
        } else {
            $grouped = array_values(array_filter($dummy, function ($item) use ($pph, $search) {

                $pasal = strtolower(trim((string) ($item['vpph_pasal'] ?? '')));
                $text  = strtolower(trim((string) ($item['text'] ?? '')));

                // pph wajib exact match
                if ($pph === '' || $pasal !== $pph) {
                    return false;
                }

                // kalau search kosong, cukup by pph
                if ($search === '') {
                    return true;
                }

                // kalau search ada, pph harus match + text mengandung search
                return str_contains($text, $search);
            }));

            return [
                'success' => true,
                'message' => 'List objek PPH',
                'data'    => $grouped,
            ];
        }
    }
}
