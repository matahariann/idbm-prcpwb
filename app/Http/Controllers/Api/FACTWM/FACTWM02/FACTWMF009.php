<?php

namespace App\Http\Controllers\Api\FACTWM\FACTWM02;

use App\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\FACTWM\FACTWM02\SendEmailRequest;
use App\Mail\FACTWMF009Mail;
use Illuminate\Support\Facades\Mail;

class FACTWMF009 extends Controller
{
    public function sendEmail(SendEmailRequest $request)
    {
        $validated = $request->validated();

        try {
            Mail::to($validated['email'])->send(
                new FACTWMF009Mail(
                    $validated['subject'],
                    $validated['message']
                )
            );

            return Response::success([
                'email' => $validated['email'],
                'subject' => $validated['subject'],
            ], 'Email berhasil dikirim.');
        } catch (\Throwable $th) {
            return Response::error(
                'Gagal mengirim email: ' . $th->getMessage(),
                500
            );
        }
    }
}
