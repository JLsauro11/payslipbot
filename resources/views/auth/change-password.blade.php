@extends('layout.auth')
@section('title', 'Change Password')

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
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            color: #6c757d;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .password-toggle.show {
            opacity: 1;
        }
        .input-group {
            position: relative;
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
                                <h2 class="fs-20 fw-bolder mb-4">Enter Verification Code</h2>
                                <p class="text-muted mb-4">Enter the 6-digit code sent to your email to reset your password.</p>

                                <form id="change-password-form" class="w-100 mt-4 pt-2">
                                    @csrf
                                    <input type="hidden" name="email" value="{{ session('reset_email') }}">
                                    <div class="mb-4">
                                        <input type="text" name="verification_code" class="form-control" placeholder="Enter 6-digit code" maxlength="6" required>
                                    </div>
                                    <div class="mb-4 position-relative">
                                        <div class="input-group">
                                            <input type="password" name="password" id="password" class="form-control" placeholder="New Password" required>
                                            <i class="feather feather-eye-off password-toggle" id="togglePassword"></i>
                                        </div>
                                    </div>
                                    <div class="mb-4 position-relative">
                                        <div class="input-group">
                                            <input type="password" name="password_confirmation" id="passwordConfirm" class="form-control" placeholder="Confirm New Password" required>
                                            <i class="feather feather-eye-off password-toggle" id="togglePasswordConfirm"></i>
                                        </div>
                                    </div>
                                    <div class="mt-5">
                                        <button type="submit" class="btn btn-lg btn-primary w-100 auth-form-btn">Reset Password</button>
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
            // Password toggle functionality for both fields
            const $passwordInput = $('#password');
            const $passwordConfirmInput = $('#passwordConfirm');
            const $togglePassword = $('#togglePassword');
            const $togglePasswordConfirm = $('#togglePasswordConfirm');

            // Show/hide toggle icons based on password input values
            function toggleIconVisibility($input, $toggle) {
                $input.on('input keyup', function() {
                    if ($input.val().length > 0) {
                        $toggle.addClass('show');
                    } else {
                        $toggle.removeClass('show');
                    }
                });
            }

            toggleIconVisibility($passwordInput, $togglePassword);
            toggleIconVisibility($passwordConfirmInput, $togglePasswordConfirm);

            // Password toggle for new password
            $togglePassword.on('click', function() {
                if ($passwordInput.attr('type') === 'password') {
                    $passwordInput.attr('type', 'text');
                    $togglePassword.removeClass('feather-eye-off').addClass('feather-eye');
                } else {
                    $passwordInput.attr('type', 'password');
                    $togglePassword.removeClass('feather-eye').addClass('feather-eye-off');
                }
                feather.replace();
            });

            // Password toggle for confirm password
            $togglePasswordConfirm.on('click', function() {
                if ($passwordConfirmInput.attr('type') === 'password') {
                    $passwordConfirmInput.attr('type', 'text');
                    $togglePasswordConfirm.removeClass('feather-eye-off').addClass('feather-eye');
                } else {
                    $passwordConfirmInput.attr('type', 'password');
                    $togglePasswordConfirm.removeClass('feather-eye').addClass('feather-eye-off');
                }
                feather.replace();
            });

            $('#change-password-form').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                var $btn = $('.auth-form-btn');
                $btn.prop('disabled', true);
                $btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Resetting...');

                $.ajax({
                    url: '{{ route("change-password.submit") }}',
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
                        if (xhr.responseJSON) {
                            errorMsg = xhr.responseJSON.message ||
                                (xhr.responseJSON.errors ?
                                    Object.values(xhr.responseJSON.errors).flat().join(', ') :
                                    errorMsg);
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: errorMsg,
                            confirmButtonColor: "#dc3545"
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $btn.html('Reset Password');
                    }
                });
            });
        });
    </script>
    @endpush
@endsection
