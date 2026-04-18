<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Mail\TutorApplicationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TutorApplicationController extends Controller
{
    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'subject' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'experience' => 'required|numeric',
            'medium' => 'required|string|max:50',
            'availability' => 'required|string',
            'bio' => 'required|string',
            'cvFile' => 'nullable|file|mimes:pdf,doc,docx|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fill all required fields correctly.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();

            // Handle File Upload
            if ($request->hasFile('cvFile')) {
                $file = $request->file('cvFile');
                
                // Sanitize filename to prevent link breakage
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $safeName = Str::slug($originalName) . '.' . $extension;
                
                $filename = time() . '_' . $safeName;
                $path = $file->storeAs('cvs', $filename, 'public');
                $data['cvLink'] = url(Storage::url($path));
            }

            // Get receiving email from settings
            $recipient = SiteSetting::get('footer_tutor_receive_email', 'admin@titjaffna.lk');

            Mail::to($recipient)->send(new TutorApplicationMail($data));

            return response()->json([
                'success' => true,
                'message' => 'Your application has been submitted successfully! We will contact you soon.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send application. Please try again later or contact us directly.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
