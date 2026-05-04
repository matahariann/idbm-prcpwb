<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM04;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\FACTWM01\FACTWM_MSHINFORMATION as Information;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// controller Dashboard Information
class FACTWMF016 extends Controller
{
    public function index(Request $request)
    {
        if ($request->session()->has('information_shown')) {
            return response()->json([
                'status' => 'success',
                'data' => null,
                'message' => 'Information already shown in this session'
            ]);
        }

        $roles = Auth::user()->roles->pluck('VROLENAME')->toArray()[0];
        $isAdmin = $roles === 'Admin';

        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;

        $idViewers = $isAdmin ? 0 : ($userSupplier == null ? 0 : $userSupplier->ISUPPLIER_ID);

        $user_type = $isAdmin ? 'internal' : ($userSupplier == null ? 'internal' : 'supplier');

        $today = Carbon::now();

        $data = Information::query()
            ->where('DFROM', '<=', $today)
            ->where('DTO', '>=', $today);

        if ($user_type === 'supplier') {
            $data->where(function ($q) use ($idViewers) {
                $q->where('VUSER_TYPE', 'all')
                    ->orWhere(function ($subQ) use ($idViewers) {
                        $subQ->where('VUSER_TYPE', 'supplier');

                        if ($idViewers) {
                            $intFormat = json_encode([$idViewers]);
                            $strFormat = json_encode([strval($idViewers)]);

                            $subQ->where(function ($viewerQ) use ($intFormat, $strFormat) {
                                $viewerQ->whereRaw('"VVIEWERS"::jsonb @> ?::jsonb', [$intFormat])
                                    ->orWhereRaw('"VVIEWERS"::jsonb @> ?::jsonb', [$strFormat])
                                    ->orWhereNull('VVIEWERS');
                            });
                        }
                    });
            });
        } else {
            $data->where(function ($q) {
                $q->where('VUSER_TYPE', 'all')
                    ->orWhere('VUSER_TYPE', 'internal');
            });
        }

        // Ambil semua informasi aktif hari ini, urutkan dari yang terlama
        $informations = $data->orderBy('IID', 'asc')->get();

        if ($informations->isNotEmpty()) {
            $request->session()->put('information_shown', true);
        }

        return response()->json([
            'status' => 'success',
            'data' => $informations,
        ]);
    }


    public function close(Request $request)
    {
        $auth_id = Auth::user()->IID;
        $key = "dashboard_information_{$auth_id}";
        $informationID = (int) $request->information_id;

        // ambil cache lama
        $ids = Cache::store('factwm_db')->get($key, []);

        // normalize (handle legacy JSON string)
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }

        if (!is_array($ids)) {
            $ids = [];
        }

        // tambahkan jika belum ada
        if (!in_array($informationID, $ids)) {
            $ids[] = $informationID;
        }

        // simpan cache (reset otomatis jam 23:59)
        Cache::store('factwm_db')->put(
            $key,
            $ids,
            Carbon::today()->endOfDay()
        );

        // update information total view
        $information = Information::find($informationID);
        if ($information) {
            $totalView = $information->ITOTALVIEW ?? 0;
            $information->ITOTALVIEW = $totalView + 1;
            $information->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Close Information Successfully'
        ]);
    }
}
