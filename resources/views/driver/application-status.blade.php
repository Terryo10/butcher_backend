<!-- resources/views/driver/application-status.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header {{ $application->status === 'approved' ? 'bg-success' : ($application->status === 'rejected' ? 'bg-danger' : 'bg-warning') }} text-white">
                    <h3 class="mb-0">Application Status</h3>
                </div>
                <div class="card-body text-center py-5">
                    @if($application->status === 'pending')
                        <div class="mb-4">
                            <i class="fas fa-hourglass-half fa-4x text-warning"></i>
                        </div>

                        <h4 class="mb-3">Your application is still under review</h4>

                        <p class="lead">
                            Thank you for your patience. Our team is reviewing your application
                            and we'll notify you once a decision has been made.
                        </p>

                        <div class="alert alert-info mt-4">
                            <strong>Application Date:</strong> {{ $application->created_at->format('F d, Y') }}
                        </div>
                    @elseif($application->status === 'approved')
                        <div class="mb-4">
                            <i class="fas fa-check-circle fa-4x text-success"></i>
                        </div>

                        <h4 class="mb-3">Congratulations! Your application has been approved</h4>

                        <p class="lead">
                            Welcome to our driver team! You can now log in to the driver portal
                            and start accepting delivery assignments.
                        </p>

                        <div class="alert alert-success mt-4">
                            <strong>Approved On:</strong> {{ $application->reviewed_at ? $application->reviewed_at->format('F d, Y') : 'N/A' }}
                        </div>

                        <a href="{{ url('/driver') }}" class="btn btn-success btn-lg mt-3">
                            Go to Driver Portal
                        </a>
                    @else
                        <div class="mb-4">
                            <i class="fas fa-times-circle fa-4x text-danger"></i>
                        </div>

                        <h4 class="mb-3">Your application has been declined</h4>

                        <p class="lead">
                            We're sorry, but we are unable to approve your driver application at this time.
                        </p>

                        @if($application->admin_notes)
                            <div class="alert alert-danger mt-4">
                                <strong>Reason:</strong> {{ $application->admin_notes }}
                            </div>
                        @endif

                        <div class="mt-4">
                            <p>If you believe there has been a mistake or if you want to apply again with updated information,
                                please contact our support team.</p>
                        </div>
                    @endif

                    <a href="{{ url('/') }}" class="btn btn-primary mt-3">
                        Return to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
