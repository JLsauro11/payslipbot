@extends('layout.auth')
@section('title', 'Forgot Password')

@section('content')
    @push('css')
    <style>
        .bg-primary {
            background-color: #ea4d4d !important;
        }
        .btn-primary, .btn-primary:hover {
            color: #fff !important;
            border-color: #ea4d4d !important;
            background-color: #ea4d4d !important;
        }
        .text-primary {
            color: #ea4d4d !important;
        }
    </style>
    @endpush

    <main class="auth-creative-wrapper">
        <div class="auth-creative-inner">
            <div class="creative-card-wrapper">
                <div class="card my-4 overflow-hidden" style="z-index: 1">
                    <div class="row flex-1 g-0">
                        <div class="col-lg-6 h-100 my-auto order-1 order-lg-0">
                            <div class="wd-50 bg-white p-2 rounded-circle shadow-lg position-absolute translate-middle top-50 start-50 d-none d-lg-block">
                                <img src="assets/images/rs8-logo.png" alt="" class="img-fluid">
                            </div>
                            <div class="creative-card-body card-body p-sm-5">
                                <h2 class="fs-20 fw-bolder mb-4">Forgot Password</h2>
                                <p class="text-muted mb-4">Enter your email address and we'll send you a 6-digit verification code.</p>

                                <form id="forgot-form" class="w-100 mt-4 pt-2">
                                    @csrf
                                    <div class="mb-4">
                                        <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                                    </div>
                                    <div class="mt-5">
                                        <button type="submit" class="btn btn-lg btn-primary w-100 auth-form-btn">Send Code</button>
                                    </div>
                                    <div class="text-center mt-4">
                                        <a href="{{ route('login') }}" class="fs-14 text-primary">← Back to Login</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-lg-6 bg-primary order-0 order-lg-1">
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <img src="assets/images/auth/auth-bg2.png" alt="" class="img-fluid">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @push('js')
    <script>
        $(function () {
            $('#forgot-form').on('submit', function(e) {
                e.preventDefault();

                // ✅ Fix FormData - get email value directly
                let email = $('input[name="email"]').val().trim();
                let formData = new FormData();
                formData.append('email', email);
                formData.append('_token', $('input[name="_token"]').val());

                var $btn = $('.auth-form-btn');
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Sending...');

                $.ajax({
                    url: '{{ route("forgot-password.submit") }}',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        if (response.status) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                confirmButtonColor: '#ea4d4d'
                            }).then(() => {
                                window.location.href = response.redirect;
                        });
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Something went wrong!';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: errorMsg,
                            confirmButtonColor: "#dc3545"
                    });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('Send Code');
                    }
                });
            });
        });
    </script>

    @endpush
@endsection
