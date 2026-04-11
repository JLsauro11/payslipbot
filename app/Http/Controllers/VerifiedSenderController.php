<?php

namespace App\Http\Controllers;
use App\Models\VerifiedSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VerifiedSenderController extends Controller
{
    public function index() {
        return view('verified_sender.index');
    }

    public function data()
    {
        $verified_senders = VerifiedSender::select('id', 'sender_id')->get();
        return response()->json(['data' => $verified_senders]);
    }

    public function destroy(VerifiedSender $verifiedSender)
    {
        $verifiedSender->delete();  // ✅ Laravel auto-finds by ID
        return response()->json([
            'status' => true,
            'message' => "Verified Sender deleted successfully!"
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'No Verified Sender selected'
            ], 400);
        }

        $deletedCount = VerifiedSender::whereIn('id', $ids)->delete();

        return response()->json([
            'status' => true,
            'message' => "{$deletedCount} Verified sender(s) deleted successfully!"
        ]);
    }
}
