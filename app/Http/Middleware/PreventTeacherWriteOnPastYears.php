<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\AcademicYear;
use Symfony\Component\HttpFoundation\Response;

class PreventTeacherWriteOnPastYears
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Always allow logout requests
        if ($request->routeIs('logout') || $request->path() === 'logout') {
            return $next($request);
        }

        // Only restrict if user is logged in, is a teacher, and is making a state-changing request
        if ($user && $user->isTeacher() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $selectedYearId = Session::get('selected_academic_year_id');
            if ($selectedYearId) {
                $selectedYear = AcademicYear::find($selectedYearId);
                // If the selected year exists and is NOT active, block the request
                if ($selectedYear && !$selectedYear->is_active) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Akses Ditolak: Guru tidak diizinkan mengubah data pada tahun ajaran yang tidak aktif.'
                        ], 403);
                    }

                    return redirect()->back()->with('error', 'Akses Ditolak: Guru tidak diizinkan mengubah data pada tahun ajaran yang tidak aktif.');
                }
            }
        }

        return $next($request);
    }
}
