<!-- resources/views/driver/application-form.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Driver Application</h3>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('driver.application.submit') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="alert alert-info">
                            <h5>Become a Delivery Driver</h5>
                            <p>
                                Thank you for your interest in becoming a delivery driver with us.
                                Please complete the application form below. Your application will be reviewed by our team.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h5>Personal Information</h5>
                            <hr>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="profile_photo" class="form-label">Profile Photo <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="profile_photo" id="profile_photo" required>
                                <small class="form-text text-muted">Please upload a clear photo of yourself (max 5MB)</small>
                            </div>

                            <div class="mb-3">
                                <label for="id_document" class="form-label">ID Document <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="id_document" id="id_document" required>
                                <small class="form-text text-muted">Please upload a photo of your ID card/passport (max 5MB)</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5>Vehicle Information</h5>
                            <hr>

                            <div class="mb-3">
                                <label for="vehicle_type" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                    <option value="">-- Select Vehicle Type --</option>
                                    <option value="car" {{ old('vehicle_type') == 'car' ? 'selected' : '' }}>Car</option>
                                    <option value="motorcycle" {{ old('vehicle_type') == 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                                    <option value="bicycle" {{ old('vehicle_type') == 'bicycle' ? 'selected' : '' }}>Bicycle</option>
                                    <option value="walking" {{ old('vehicle_type') == 'walking' ? 'selected' : '' }}>Walking/On Foot</option>
                                </select>
                            </div>

                            <div class="mb-3 vehicle-details d-none">
                                <label for="vehicle_license_plate" class="form-label">License Plate Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="vehicle_license_plate" name="vehicle_license_plate" value="{{ old('vehicle_license_plate') }}">
                                <small class="form-text text-muted">Required for car or motorcycle</small>
                            </div>

                            <div class="mb-3 vehicle-details d-none">
                                <label for="driving_license" class="form-label">Driving License <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="driving_license" name="driving_license">
                                <small class="form-text text-muted">Required for car or motorcycle (max 5MB)</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5>Application Details</h5>
                            <hr>

                            <div class="mb-3">
                                <label for="application_reason" class="form-label">Why do you want to be a driver? <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="application_reason" name="application_reason" rows="5" required>{{ old('application_reason') }}</textarea>
                                <small class="form-text text-muted">Explain why you want to be a driver and any relevant experience you have (50-500 characters)</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5>Agreement</h5>
                            <hr>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms_agreement" name="terms_agreement" required>
                                <label class="form-check-label" for="terms_agreement">
                                    I agree to the Terms and Conditions for drivers
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="background_check" name="background_check" required>
                                <label class="form-check-label" for="background_check">
                                    I understand that my application is subject to a background check
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Submit Application</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Show/hide vehicle details based on selection
    document.getElementById('vehicle_type').addEventListener('change', function() {
        const vehicleType = this.value;
        const vehicleDetails = document.querySelectorAll('.vehicle-details');

        if (vehicleType === 'car' || vehicleType === 'motorcycle') {
            vehicleDetails.forEach(el => {
                el.classList.remove('d-none');
                el.querySelector('input').setAttribute('required', 'required');
            });
        } else {
            vehicleDetails.forEach(el => {
                el.classList.add('d-none');
                el.querySelector('input').removeAttribute('required');
            });
        }
    });

    // Check initial value for vehicle type
    document.addEventListener('DOMContentLoaded', function() {
        const vehicleType = document.getElementById('vehicle_type').value;
        if (vehicleType === 'car' || vehicleType === 'motorcycle') {
            document.querySelectorAll('.vehicle-details').forEach(el => {
                el.classList.remove('d-none');
                el.querySelector('input').setAttribute('required', 'required');
            });
        }
    });
</script>
</body>
</html>
