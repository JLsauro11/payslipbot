@extends('layout.auth')
@section('title', 'Login')

@section('content')

    <main class="auth-creative-wrapper">
        <div class="auth-creative-inner">
            <div class="creative-card-wrapper">
                <div class="card my-4 overflow-hidden" style="z-index: 1">
                    <div class="row flex-1 g-0">
                        <div class="col-lg-6 h-100 my-auto order-1 order-lg-0">
                            <div class="wd-50 bg-white p-2 rounded-circle shadow-lg position-absolute translate-middle top-50 start-50 d-none d-lg-block">
                                <img src="assets/images/logo-abbr.png" alt="" class="img-fluid">
                            </div>
                            <div class="creative-card-body card-body p-sm-5">
                                <h2 class="fs-20 fw-bolder mb-4">Login to your account</h2>
                                <form id="login-form" enctype="multipart/form-data" class="w-100 mt-4 pt-2">
                                    @csrf
                                    <div class="mb-4">
                                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                                    </div>
                                    <div class="mb-3">
                                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">

                                        <div>
                                            <a href="auth-reset-creative.html" class="fs-11 text-primary">Forget password?</a>
                                        </div>
                                    </div>
                                    <div class="mt-5">
                                        <button type="submit" class="btn btn-lg btn-primary w-100 auth-form-btn">Login</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-lg-6 bg-primary order-0 order-lg-1">
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <img src="assets/images/auth/auth-user.png" alt="" class="img-fluid">
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
            $('#login-form').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                var $btn = $('.auth-form-btn');
                $btn.prop('disabled', true);
                $btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Signing In...');

                $.ajax({
                    url: '{{ route("login") }}',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log(response);

                        if (response.success) {
                            window.location.href = response.redirect_url;
                        }
                    },

                    error: function(xhr) {
                        Swal.fire("Login Failed!", xhr.responseJSON.message, {
                            icon: "error",
                            confirmButtonColor: "#dc3545"
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $btn.html('Login');
                    }
                });
            });
        });
    </script>

@endpush
@endsection