<!-- resources/views/driver/application-pending.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Pending</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h3 class="mb-0">Application Pending</h3>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-hourglass-half fa-4x text-warning"></i>
                    </div>

                    <h4 class="mb-3">Your driver application is currently under review</h4>

                    <p class="lead">
                        Thank you for applying to be a driver with us. Our team is reviewing your application
                        and we'll notify you once a decision has been made.
                    </p>

                    <div class="alert alert-info mt-4">
                        <strong>Application Date:</strong> {{ $application->created_at->format('F d, Y') }}
                    </div>

                    <p class="mt-4">
                        If you have any questions about your application, please contact our support team.
                    </p>

                    <a href="{{ route('home') }}" class="btn btn-primary mt-3">
                        Return to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@vite(['resources/js/app.js'])
</body>
</html>
