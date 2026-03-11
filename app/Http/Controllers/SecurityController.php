<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityController extends Controller
{
    /**
     * Handle CSP Violation Reports.
     */
    public function handleCspReport(Request $request)
    {
        // Harden: Validate payload size (max 8KB)
        if (strlen($request->getContent()) > 8192) {
            return response()->json(['error' => 'Payload too large'], 413);
        }

        $payload = $request->json()->all();

        if (empty($payload)) {
            // Sometimes CSP reports come as raw POST body content type application/csp-report
            $content = $request->getContent();
            $payload = json_decode($content, true);
        }

        Log::channel('daily')->warning('CSP Violation Reported:', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'report' => $payload
        ]);

        return response()->json(['status' => 'received'], 204);
    }
}
