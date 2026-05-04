<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM04;

use App\DataTables\Original\FACTWM04\FACTWMF015Datatable;
use App\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Models\FACTWM01\FACTWM_MSHNEWS as News;
use App\Services\FACTWM\NewsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FACTWMF015 extends Controller
{
    public function __construct(protected NewsService $newsService) {}

    public function index(FACTWMF015Datatable $datatable)
    {
        $roles = count(Auth::user()->roles) > 0 ? Auth::user()->roles->pluck('VROLENAME')->toArray()[0] : 'Gues';

        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;

        $isAdmin = $roles === 'Admin' || $userSupplier === null;
        $news = $this->newsService->getPublishedNews($userSupplier?->ISUPPLIER_ID ?? null, $isAdmin);

        $latestNews = $news->first();
        $olderNews = $news->slice(1, 2)->values();

        $idViewers = $isAdmin ? 0 : ($userSupplier == null ? 0 : $userSupplier->ISUPPLIER_ID);

        return $datatable->render('modules.FACTWM.FACTWM04.FACTWMF015.FACTWMF015', [
            'latestNews' => $latestNews,
            'olderNews' => $olderNews,
            'idViewers' => $idViewers
        ]);
    }

    public function showNewsByViewer($idViewers, $slug)
    {
        $news = News::where('VSUBJECT', $slug)->where('BSTATUS', true)->firstOrFail();
        if (in_array($idViewers, $news->AVIEWERS) || $idViewers == 0) {
            // Increment the total view count
            $news->ITOTALVIEW = $news->ITOTALVIEW ? $news->ITOTALVIEW + 1 : 1;
            $news->save();
            // return Response::success(data: $news, message: 'News retrieved successfully');
            return view('modules.FACTWM.FACTWM04.FACTWMF015.partials._news-detail', compact('news'));
        } else {
            abort(404);
        }
    }

    public function filterNews(Request $request)
    {
        try {
            $dateRange = $request->query('date');
            $searchQuery = $request->query('search');

            $roles = Auth::user()->roles->pluck('VROLENAME')->toArray()[0];

            $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;

            $isAdmin = $roles === 'Admin' || $userSupplier === null;
            $allNews = $this->newsService->getPublishedNews($userSupplier?->ISUPPLIER_ID ?? null, $isAdmin);

            // Filter by date range
            if ($dateRange) {
                $parts = explode(' to ', $dateRange);

                // Single date
                if (count($parts) === 1) {
                    $allNews = $allNews->filter(function ($news) use ($parts) {
                        return Carbon::parse($news->DPUBLISHED_AT)->toDateString() === $parts[0];
                    });
                }
                // Range date
                elseif (count($parts) === 2) {
                    [$startDate, $endDate] = $parts;
                    $allNews = $allNews->filter(
                        fn($news) =>
                        $news->DPUBLISHED_AT >= $startDate && $news->DPUBLISHED_AT <= $endDate
                    );
                }
            }

            // Filter by search query
            if ($searchQuery) {
                $allNews = $allNews->filter(function ($news) use ($searchQuery) {
                    return stripos($news->VTITLE, $searchQuery) !== false || stripos($news->VCONTENT, $searchQuery) !== false;
                });
            }

            // Re-index the collection
            $allNews = $allNews->map(function ($news) {
                $news->VIMAGE_PATH = $news->VIMAGE_PATH
                    ? asset('storage/news/images/' . $news->VIMAGE_PATH)
                    : null;

                return $news;
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $allNews,
            ]);
        } catch (\Throwable $e) {

            // Log error untuk debugging
            Log::error('Filter News Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while filtering news.',
                'error' => $e->getMessage(), // optional → hapus jika tidak ingin expose error
            ], 500);
        }
    }
}
