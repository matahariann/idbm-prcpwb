<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM01;

use App\DataTables\Original\FACTWM01\FACTWMF001DataTable;
use App\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\FACTWM\FACTWM01\ConfigurationRequest;
use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION as Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PSpell\Config;

class FACTWMF001 extends Controller
{
    public function index(FACTWMF001DataTable $dataTable)
    {
        return $dataTable->render('modules.FACTWM.FACTWM01.FACTWMF001.FACTWMF001');
    }

    public function store(ConfigurationRequest $request)
    {
        $data = $request->validated();

        $configuration = DB::transaction(function () use ($data) {
            $configuration = Configuration::create([
                'VVARIABLE' => $data['variable'],
                'VVALUE' => $data['value']
            ]);

            return $configuration;
        });

        return Response::success(message: "Config {$configuration->VVARIABLE} created successfully");
    }

    public function show(Configuration $configuration)
    {
        return Response::success(data: $configuration);
    }

    public function showByVariable(Request $request)
    {
        $variable = $request->query('variable');

        if (!$variable) {
            return response()->json([
                'success' => false,
                'message' => 'Variable parameter is required'
            ], 400);
        }

        try {
            $config = Configuration::where('VVARIABLE', $variable)->first();

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'content' => $config->VVALUE
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(ConfigurationRequest $request, Configuration $configuration)
    {
        $data = $request->validated();

        $configuration = DB::transaction(function () use ($data, $configuration) {
            $configuration->update([
                'VVARIABLE' => $data['variable'],
                'VVALUE' => $data['value']
            ]);

            return $configuration;
        });

        return Response::success(message: "Config {$configuration->VVARIABLE} updated successfully");
    }

    public function destroy(Configuration $configuration)
    {
        try {
            $configuration->delete();

            return Response::success(message: "Configuration {$configuration->VVARIABLE} deleted successfully");
        } catch (\Throwable $th) {
            return Response::error(message: 'Configuration data cannot be deleted, it may be related to other data');
        }
    }
}
