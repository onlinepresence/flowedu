<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\QuoteRequest;
use App\Mail\QuoteReceipt;
use App\Services\QuoteCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class LandingController extends Controller
{
    /**
     * Display the landing page view if enabled, or redirect to login.
     */
    public function index(): View|RedirectResponse
    {
        if (! config('landing.enabled', false)) {
            if (auth()->check()) {
                return redirect()->route('post.login.redirect');
            }
            return redirect()->route('login');
        }

        return view('landing');
    }

    /**
     * Handle the AJAX request to submit a quote request.
     */
    public function quoteRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'college_name'      => ['required', 'string', 'max:255'],
            'name'              => ['required', 'string', 'max:255'],
            'role'              => ['required', 'string', 'max:255'],
            'phone'             => ['required', 'string', 'max:20'],
            'email'             => ['required', 'email', 'max:255'],
            'student_band'      => ['required', 'string', 'max:50'],
            'modules'           => ['nullable', 'array'],
            'message'           => ['nullable', 'string', 'max:2000'],
            
            // Calculator configurations
            'hosting_setup'     => ['required', 'string', 'in:self_hosted,managed,none'],
            'config_setup'      => ['required', 'in:0,1'],
            'migration'         => ['required', 'in:0,1'],
            'admin_training'    => ['required', 'integer', 'min:0', 'max:10'],
            'teacher_training'  => ['required', 'integer', 'min:0', 'max:10'],
            'onsite_training'   => ['required', 'integer', 'min:0', 'max:10'],
            'founding_client'   => ['required', 'in:0,1'],
            'send_client_receipt' => ['required', 'in:0,1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Calculate pricing details on backend matching frontend
        $pricing = QuoteCalculationService::calculate($data);

        $sendClientReceipt = ($data['send_client_receipt'] ?? '0') === '1';
        $downloadUrl = null;

        // Generate PDF
        $pdf = Pdf::loadView('pdf.proforma-invoice', [
            'contact' => $data,
            'pricing' => $pricing,
        ]);
        $pdfContent = $pdf->output();

        // Create PDF Filename
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '', str_replace(' ', '_', $data['college_name']));
        $pdfFilename = 'Proforma_Invoice_' . $safeName . '.pdf';

        // Only send client receipt email + expose download URL if they clicked "Get a Quote"
        if ($sendClientReceipt) {
            // Save selection in session for immediate download route access
            session()->put('last_quote', [
                'contact' => $data,
                'pricing' => $pricing,
            ]);

            Mail::to($data['email'])->send(new QuoteReceipt($data, $pricing, $pdfContent, $pdfFilename));

            $downloadUrl = route('quote.download-pdf');
        }

        // Always send notification email to admin
        Mail::to(config('mail.from.address', 'successinnovativehub@gmail.com'))
            ->send(new QuoteRequest($data, $pricing, $pdfContent, $pdfFilename));

        $message = $sendClientReceipt
            ? 'Thank you. We have sent a copy of your Proforma Invoice to your email.'
            : 'Thank you. We will be in touch within one business day.';

        return response()->json([
            'success'      => true,
            'message'      => $message,
            'download_url' => $downloadUrl,
        ]);
    }

    /**
     * Download the latest generated PDF from the current session.
     */
    public function downloadPdf(): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $quote = session()->get('last_quote');
        
        if (!$quote) {
            return redirect()->route('home')->with('error', 'No quote details found in session. Please fill the request form first.');
        }

        $pdf = Pdf::loadView('pdf.proforma-invoice', [
            'contact' => $quote['contact'],
            'pricing' => $quote['pricing'],
        ]);

        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '', str_replace(' ', '_', $quote['contact']['college_name']));
        $pdfFilename = 'Proforma_Invoice_' . $safeName . '.pdf';

        return $pdf->download($pdfFilename);
    }
}
