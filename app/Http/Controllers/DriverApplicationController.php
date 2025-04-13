<?php

namespace App\Http\Controllers;

use App\Models\DriverApplication;
use App\Models\User;
use App\Models\DeliveryNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverApplicationController extends Controller
{
    /**
     * Show the driver application form.
     */
    public function showApplicationForm()
    {
        return view('driver.application-form');
    }

    /**
     * Submit a driver application.
     */
    public function submitApplication(Request $request)
    {
        // Validate the application data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'application_reason' => 'required|string|min:50|max:500',
            'vehicle_type' => 'required|in:car,motorcycle,bicycle,walking',
            'vehicle_license_plate' => 'required_if:vehicle_type,car,motorcycle|nullable|string|max:20',
            'id_document' => 'required|file|image|max:5120', // 5MB
            'driving_license' => 'required_if:vehicle_type,car,motorcycle|nullable|file|image|max:5120',
            'profile_photo' => 'required|file|image|max:5120',
            'terms_agreement' => 'required',
            'background_check' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Find or create user
        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['name' => $request->name]
        );

        // Store file uploads
        $idDocumentPath = $request->file('id_document')->store('driver-documents/id', 'private');
        $profilePhotoPath = $request->file('profile_photo')->store('driver-documents/photo', 'private');

        // Store driving license if provided
        $drivingLicensePath = null;
        if ($request->hasFile('driving_license')) {
            $drivingLicensePath = $request->file('driving_license')->store('driver-documents/license', 'private');
        }

        // Create the application
        $application = DriverApplication::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'application_reason' => $request->application_reason,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_license_plate' => $request->vehicle_license_plate,
            'id_document' => $idDocumentPath,
            'driving_license' => $drivingLicensePath,
            'profile_photo' => $profilePhotoPath,
        ]);

        // Create a notification for admins
        DeliveryNotification::create([
            'type' => 'new_driver_application',
            'title' => 'New Driver Application',
            'body' => $user->name . ' has applied to become a driver.',
            'data' => [
                'application_id' => $application->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
            ],
            'channel' => 'database',
        ]);

        // Store application ID in session for redirects
        session(['driver_application_id' => $application->id]);

        return redirect()->route('driver.application.status')
            ->with('success', 'Your application has been submitted successfully. We will review it shortly.');
    }

    /**
     * Check application status.
     */
    public function checkApplicationStatus(Request $request)
    {
        // Get application ID from session
        $applicationId = session('driver_application_id');

        if (!$applicationId) {
            return redirect()->route('driver.application')
                ->with('info', 'You have not applied to be a driver yet.');
        }

        $application = DriverApplication::findOrFail($applicationId);

        return view('driver.application-status', ['application' => $application]);
    }

    /**
     * Show pending application page.
     */
    public function pendingApplication(Request $request)
    {
        // Get application ID from session
        $applicationId = session('driver_application_id');

        if (!$applicationId) {
            return redirect()->route('driver.application')
                ->with('info', 'You have not applied to be a driver yet.');
        }

        $application = DriverApplication::findOrFail($applicationId);

        return view('driver.application-pending', ['application' => $application]);
    }
}
