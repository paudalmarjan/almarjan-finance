<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use App\Models\AcademicYear;
use Symfony\Component\HttpFoundation\Response;

class SetAcademicYearContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for console commands, unless running in a testing environment
        if (app()->runningInConsole() && !app()->environment('testing')) {
            return $next($request);
        }

        try {
            // 1. Get all academic years for the dropdown
            $allAcademicYears = AcademicYear::orderBy('name', 'desc')->get();

            // 2. Determine selected academic year id
            if ($request->has('change_academic_year_id')) {
                $yearId = $request->input('change_academic_year_id');
                if (AcademicYear::where('id', $yearId)->exists()) {
                    Session::put('selected_academic_year_id', (int) $yearId);
                }
                // Redirect back without the parameter to keep URLs clean
                return redirect()->to($request->url());
            }

            // 3. Fallback to active year or first year in database
            if (!Session::has('selected_academic_year_id')) {
                $activeYear = AcademicYear::where('is_active', true)->first() 
                    ?? AcademicYear::first();

                if ($activeYear) {
                    Session::put('selected_academic_year_id', $activeYear->id);
                }
            }

            // 4. Load selected academic year details
            $selectedYearId = Session::get('selected_academic_year_id');
            $selectedAcademicYear = $selectedYearId 
                ? AcademicYear::find($selectedYearId) 
                : null;
        } catch (\Exception $e) {
            $allAcademicYears = collect();
            $selectedAcademicYear = null;
        }

        // 5. Share variables globally with all Blade views
        View::share('allAcademicYears', $allAcademicYears);
        View::share('selectedAcademicYear', $selectedAcademicYear);

        return $next($request);
    }
}
