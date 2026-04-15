<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Support\Facades\Auth;

class StampCorrectionController extends Controller
{
    /**
     * 申請一覧
     */
    public function list(Request $request)
    {
        $isAdmin = Auth::user()->role === 'admin';
        $status = $request->query('status', 'pending');
        
        $query = AttendanceCorrectionRequest::where('status', $status)
            ->with(['user', 'attendance'])
            ->latest();

        if (!$isAdmin) {
            $query->where('user_id', Auth::id());
        }

        $requests = $query->paginate(10);

        $view = $isAdmin ? 'admin.correction_request.list' : 'correction_request.list';
        return view($view, compact('requests', 'status'));
    }
}
