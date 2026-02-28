<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="" />
    <meta name="keyword" content="" />
    <meta name="author" content="flexilecode" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!--! The above 6 meta tags *must* come first in the head; any other head content must come *after* these tags !-->
    <!--! BEGIN: Apps Title-->
    <title>@yield('title')</title>

    <!--! END:  Apps Title-->
    <!--! BEGIN: Favicon-->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/rs8-logo.png') }}" />

    <!--! END: Favicon-->
    <!--! BEGIN: Bootstrap CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <!--! END: Bootstrap CSS-->
    <!--! BEGIN: Vendors CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/dataTables.bs5.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/tagify.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/tagify-data.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/quill.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/select2.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/select2-theme.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/daterangepicker.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/datepicker.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <!--! END: Vendors CSS-->
    <!--! BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/theme.min.css') }}" />
    <!--! END: Custom CSS-->
    <!--! HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries !-->
    <!--! WARNING: Respond.js doesn"t work if you view the page via file: !-->
    <!--[if lt IE 9]>
    <script src="https:oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https:oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style>
        .btn-primary, .btn-primary:hover, .btn.bg-soft-primary:focus, .btn.bg-soft-primary:hover {
            color: #fff !important;
            border-color: #ea4d4d !important;
            background-color: #ea4d4d !important;
        }
        .btn-secondary, .btn-secondary:hover, .btn.bg-soft-secondary:focus, .btn.bg-soft-secondary:hover {
            color: #fff !important;
            border-color: #ea4d4d !important;
            background-color: #ea4d4d !important;
        }
        .text-primary {
            color: #ea4d4d !important;
        }
        .custom-file.active, .custom-file.focus, .custom-file:active, .custom-file:focus, .custom-select.active, .custom-select.focus, .custom-select:active, .custom-select:focus, .form-control.active, .form-control.focus, .form-control:active, .form-control:focus, .form-select.active, .form-select.focus, .form-select:active, .form-select:focus, input.active, input.focus, input:active, input:focus {
            outline: 0;
            color: #283c50;
            border-color: #ea4d4d !important;
            box-shadow: none !important;
        }

        .avatar-upload-container {
            position: relative !important;
            display: inline-block !important;
            cursor: pointer !important;
        }

        .avatar-preview {
            transition: all 0.3s ease !important;
            pointer-events: none;
        }

        .avatar-upload-container:hover .avatar-preview {
            border-color: #6c757d !important;
            transform: scale(1.02);
        }

        .avatar-overlay {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: rgba(30, 30, 30, 0.9) !important;
            border-radius: 50% !important;
            opacity: 0 !important;
            transition: all 0.3s ease !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 10 !important;
            pointer-events: none;
        }

        .avatar-upload-container:hover .avatar-overlay {
            opacity: 1 !important;
        }

        .avatar-camera-icon {
            color: white !important;
            width: 32px !important;      /* Fixed size */
            height: 32px !important;     /* Fixed size */
            stroke-width: 2 !important;
            stroke: white !important;
            line-height: 1 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: rgba(255, 255, 255, 0.25) !important;
            border-radius: 50% !important;
            padding: 0 !important;       /* Remove padding */
            margin: 0 !important;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
        }

        .avatar-upload-container:hover .avatar-camera-icon {
            transform: scale(1.1);
        }

        .nxl-header .header-wrapper .user-avtar {
            width: 40px;
            height: 40px;
            margin-right: 15px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* FIXED: Larger Cropper */
        #cropModal {
            z-index: 1060 !important;
        }

        #cropper-container {
            position: relative !important;
            height: 500px !important;  /* FIXED HEIGHT */
            border-radius: 12px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            overflow: hidden !important;
        }

        .cropper-container {
            width: 100% !important;
            height: 500px !important;  /* SAME AS PARENT */
        }

        .cropper-view-box {
            border-radius: 50% !important;
            border: 4px solid #ea4d4d !important;
            box-shadow: 0 8px 25px rgba(234, 77, 77, 0.4) !important;
        }

        #cropModal .btn {
            width: 48px !important;
            height: 48px !important;
            border-radius: 50% !important;
        }

        #cropModal #cropBtn {
            width: auto !important;
            padding: 10px 24px !important;
            border-radius: 25px !important;
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
            transition: opacity 0.2s ease, color 0.2s ease;
            width: 20px;
            height: 20px;
            line-height: 20px;     /* ✅ FIXED: Matches icon height */
            display: flex;         /* ✅ NEW: Flex centering */
            align-items: center;
            justify-content: center;
        }

        .password-toggle.show {
            opacity: 1;
        }

        .input-group {
            position: relative;
            margin-top: 0.25rem;
        }

    </style>
    @stack('css')

</head>

<body>


<!--! ================================================================ !-->
<!--! [Start] Navigation Manu !-->
<!--! ================================================================ !-->
@include('layout.nxl-navigation')
<!--! ================================================================ !-->
<!--! [End]  Navigation Manu !-->
<!--! ================================================================ !-->
<!--! ================================================================ !-->
<!--! [Start] Header !-->
<!--! ================================================================ !-->
@include('layout.nxl-header')

<!--! ================================================================ !-->
<!--! [End] Header !-->
<!--! ================================================================ !-->
<!--! ================================================================ !-->
<!--! [Start] Main Content !-->
<!--! ================================================================ !-->



<main class="nxl-container">

    <div class="nxl-content">

        <!-- [ page-header ] start -->

    <!-- [ page-header ] end -->
        <!-- [ Main Content ] start -->
        @yield('content')

        <!-- [ Main Content ] end -->
    </div>


</main>

{{-- Employee Modal --}}
<div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
                <form id="employeeForm">
                @csrf
                <!-- ✅ FIX: Proper hidden ID field -->
                    <input type="hidden" id="employee_id" name="employee_id" value="">

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Employee Number</label>
                            <input type="text" class="form-control" name="employee_number" id="employee_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Employee Name</label>
                            <input type="text" class="form-control" name="name" id="name" required>
                        </div>
                        <div class="mb-3 password-field" style="display: none;">
                            <label class="form-label">Payslip Password</label>
                            <input type="text" class="form-control" name="password" id="password" placeholder="RS8-1234">
                            <div class="form-text">Leave blank to keep current password (RS8-XXXX auto-generated on create)</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" name="position" id="position">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" id="department">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" id="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="spinner"></span>
                            Save
                        </button>
                    </div>
                </form>

        </div>
    </div>
</div>

{{-- Payslip Modal --}}
<div class="modal fade" id="payslipModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Upload Payslip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="payslipForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="payslip_id" id="payslip_id" value="">

                <div class="modal-body">
                    {{-- 1. Employee Selection --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Employee</label>
                        <select class="form-control" id="payslip_employee_id" name="employee_id" required>
                            <option value=""></option>
                        </select>
                    </div>

                    {{-- 2. Payslip File Upload --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Payslip File <span class="text-danger">*</span></label>
                        <input type="file"
                               class="form-control"
                               id="payslip_file"
                               name="payslip_file"
                               accept=".pdf">
                        <div class="form-text">Upload PDF only (Max 2MB)</div>
                    </div>

                    {{-- 3. Payslip Date --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Payslip Date <span class="text-danger">*</span></label>
                        <input type="text"
                                class="form-control"
                               id="payslipDate"
                               name="payslip_date"
                               placeholder="Pick payslip date "
                               required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="spinner"></span>
                        Upload Payslip
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Filter Modal --}}
<div class="modal fade" id="filterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Payslips by Date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="filterForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control " id="startDate" placeholder="Pick Start Date" required>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control " id="endDate" placeholder="Pick End Date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelFilterBtn" data-bs-dismiss="modal">
                        Clear Filters
                    </button>
                    <button type="submit" class="btn btn-primary" id="filterBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="filterSpinner"></span>
                        Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Multi Upload Modal -->
<div class="modal fade" id="multiUploadModal" tabindex="-1" aria-labelledby="multiUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="multiUploadTitle">Upload Multiple Payslips</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="multiUploadForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Select Multiple PDF Files <small class="text-muted">(Naming: EMPLOYEEID_MM_DD_YYYY.pdf)</small></label>
                        <input type="file" class="form-control" id="multi_payslip_files" name="payslip_files[]" multiple accept=".pdf" required>
                        <div class="form-text">Example: 2025050_01_15_2026.pdf</div>
                    </div>
                    <div id="multiPreview" class="mb-3"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="multiUploadForm" class="btn btn-primary" id="multiSubmitBtn">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="multiSpinner" role="status"></span>
                    Upload Payslips
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Account Settings Modal - FIXED -->
<div class="modal fade" id="accountSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Account Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="accountForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                <!-- Profile Section -->
                    <div class="mb-4">
                        <h6>Profile Information</h6>

                        <!-- Avatar (TOP) -->
                        <div class="mb-4 text-center">
                            <div class="avatar-upload-container position-relative d-inline-block">
                                <img id="previewAvatar" src="{{ Auth::user()->avatar ? asset('assets/images/avatar/' . Auth::user()->avatar) : asset('assets/images/avatar/user-icon.jpeg') }}"
                                     class="img-fluid rounded-circle avatar-preview"
                                     style="width: 120px; height: 120px; object-fit: cover; border: 5px solid #e9ecef;">
                                <input type="file" class="d-none" id="avatarInput" name="avatar" accept="image/*">
                                <div class="avatar-overlay">
                                    <i class="feather-camera avatar-camera-icon"></i>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">Click avatar to change</small>
                        </div>

                        <!-- Username & Email (SAME SECTION) -->
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="usernameInput" name="username"
                                   value="{{ Auth::user()->username }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="emailInput" name="email"
                                   value="{{ Auth::user()->email }}" required>
                        </div>
                    </div> <!-- ✅ Profile Section END -->

                    <!-- Password Section -->
                    <div class="mt-4 pt-4 border-top">
                        <h6>Change Password (Optional)</h6>
                        <div class="mb-3 position-relative">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="current_password" id="currentPassword">
                                <i class="feather feather-eye-off password-toggle" id="toggleCurrentPassword"></i>
                            </div>
                        </div>
                        <div class="mb-3 position-relative">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="newPassword" minlength="8">
                                <i class="feather feather-eye-off password-toggle" id="toggleNewPassword"></i>
                            </div>
                        </div>
                        <div class="mb-3 position-relative">
                            <label class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password_confirmation" id="confirmPassword">
                                <i class="feather feather-eye-off password-toggle" id="toggleConfirmPassword"></i>
                            </div>
                        </div>
                    </div>
            </div>
            <!-- Submit Buttons -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="accountBtn">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="accountSpinner"></span>
                    Update Account
                </button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- FIXED: Larger Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl"> <!-- CHANGED: modal-xl -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crop Avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <!-- LARGER Cropper Container -->
                <div id="cropper-container" style="width: 100%; max-width: 600px; height: 500px; margin: 0 auto;">
                    <img id="cropImage" style="max-width: 100%; height: 500px;">
                </div>
                <div class="d-flex gap-2 justify-content-center mt-4">
                    <button type="button" class="btn btn-secondary p-2" id="rotateLeft" title="Rotate Left" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                        <i class="feather-rotate-ccw" style="width: 20px; height: 20px;"></i>
                    </button>
                    <button type="button" class="btn btn-secondary p-2" id="zoomIn" title="Zoom In" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                        <i class="feather-plus" style="width: 20px; height: 20px;"></i>
                    </button>
                    <button type="button" class="btn btn-secondary p-2" id="zoomOut" title="Zoom Out" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                        <i class="feather-minus" style="width: 20px; height: 20px;"></i>
                    </button>
                    <button type="button" class="btn btn-primary px-3 py-2" id="cropBtn">
                        <i class="feather-check me-1"></i> Crop & Save
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>


<script src="{{ asset('assets/vendors/js/vendors.min.js') }}"></script>
<!-- vendors.min.js {always must need to be top} -->
<script src="{{ asset('assets/vendors/js/tagify.min.js') }}"></script>
<script src="{{ asset('assets/vendors/js/tagify-data.min.js') }}"></script>
<script src="{{ asset('assets/vendors/js/quill.min.js') }}"></script>
<script src="{{ asset('assets/vendors/js/dataTables.min.js') }}"></script>
<script src="{{ asset('assets/vendors/js/dataTables.bs5.min.js') }}"></script>
<script src="{{ asset('assets/vendors/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/vendors/js/select2-active.min.js') }}"></script>
<script src="{{ asset('assets/vendors/js/datepicker.min.js') }}"></script>
<script src="{{ asset('assets/vendors/js/daterangepicker.min.js') }}"></script>
<script src="{{ asset('assets/vendors/js/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/vendors/js/circle-progress.min.js') }}"></script>
<!--! END: Vendors JS !-->
<!--! BEGIN: Apps Init  !-->
<script src="{{ asset('assets/js/common-init.min.js') }}"></script>
<script src="{{ asset('assets/js/dashboard-init.min.js') }}"></script>
<script src="{{ asset('assets/js/proposal-create-init.min.js') }}"></script>

<!--! END: Apps Init !-->
<!--! BEGIN: Theme Customizer  !-->
<script src="{{ asset('assets/js/theme-customizer-init.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>

@stack('js')


<!--! END: Theme Customizer !-->

<script>
    $(document).ready(function() {
        let cropper = null;
        let selectedFile = null;
        let userDataCache = null;

        function loadUserDataOnce() {
            if (userDataCache) return userDataCache;

            $.get('{{ route("home.index") }}?ajax=1')
                .done(function(data) {
                    userDataCache = data;
                    updateUIWithUserData(data);
                })
                .fail(function() {
                    console.log('Failed to load user data');
                });
            return null;
        }

        function updateUIWithUserData(data) {
            const avatarSrc = data.avatar || '{{ asset("assets/images/avatar/user-icon.jpeg") }}';
            $('.user-avtar, #previewAvatar').attr('src', avatarSrc);
            $('.dropdown-header h6').text(data.username);
            $('.dropdown-header span').text(data.email);
            $('#usernameInput').val(data.username);
            $('#emailInput').val(data.email);
        }

        // Load immediately
        loadUserDataOnce();

        // ✅ FIXED: SINGLE shown.bs.modal handler
        $('#accountSettingsModal').on('shown.bs.modal', function() {
            if (userDataCache) {
                updateUIWithUserData(userDataCache);
            }
            // Re-init password toggles with longer delay
            setTimeout(initPasswordToggles, 200);
        });

        $('.nxl-user-dropdown').on('show.bs.dropdown', function() {
            if (userDataCache) {
                updateUIWithUserData(userDataCache);
            }
        });

        // Avatar handlers (unchanged)
        $(document).on('click', '.avatar-upload-container', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            $('#avatarInput').trigger('click');
        });

        $('#avatarInput').on('click', function(e) {
            e.stopPropagation();
        });

        $('#avatarInput').on('change', function(e) {
            e.stopPropagation();
            selectedFile = this.files[0];
            if (selectedFile) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#cropImage').attr('src', e.target.result);
                    $('#cropModal').modal('show');
                    initCropper();
                };
                reader.readAsDataURL(selectedFile);
            }
        });

        function initCropper() {
            const image = $('#cropImage')[0];
            if (cropper) cropper.destroy();

            setTimeout(() => {
                cropper = new Cropper(image, {
                    aspectRatio: 1,
                    viewMode: 2,
                    autoCropArea: 0.6,
                    dragMode: 'move',
                    minCropBoxWidth: 150,
                    minCropBoxHeight: 150,
                    background: false
                });

            $('#rotateLeft').off('click').on('click', () => cropper.rotate(-90));
            $('#zoomIn').off('click').on('click', () => cropper.zoom(0.1));
            $('#zoomOut').off('click').on('click', () => cropper.zoom(-0.1));
        }, 500);
        }

        $('#cropBtn').click(function() {
            if (cropper && selectedFile) {
                const canvas = cropper.getCroppedCanvas({
                    width: 200,
                    height: 200,
                    imageSmoothingQuality: 'high'
                });

                canvas.toBlob(function(blob) {
                    const croppedFile = new File([blob], selectedFile.name, {
                        type: selectedFile.type,
                        lastModified: Date.now()
                    });

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewAvatar').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(croppedFile);

                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(croppedFile);
                    $('#avatarInput')[0].files = dataTransfer.files;

                    $('#cropModal').modal('hide');
                }, selectedFile.type);
            }
        });

        $('#cropModal').on('hidden.bs.modal', function() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        });

        // Account form submission
        $('#accountForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const $btn = $('#accountBtn');
            const $spinner = $('#accountSpinner');

            $btn.prop('disabled', true);
            $spinner.removeClass('d-none');

            $.ajax({
                url: '{{ route("account.update") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        userDataCache = response.user;
                        updateUIWithUserData(response.user);
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            confirmButtonColor: '#ea4d4d'
                        });
                        $('#accountSettingsModal').modal('hide');
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: xhr.responseJSON?.message || 'Something went wrong!',
                        confirmButtonColor: "#dc3545"
                });
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $spinner.addClass('d-none');
                }
            });
        });

        // ✅ FIXED: Password toggle function (removed extra brace)
        function initPasswordToggles() {
            const passwordFields = [
                { input: '#currentPassword', toggle: '#toggleCurrentPassword' },
                { input: '#newPassword', toggle: '#toggleNewPassword' },
                { input: '#confirmPassword', toggle: '#toggleConfirmPassword' }
            ];

            passwordFields.forEach(({ input, toggle }) => {
                const $input = $(input);
            const $toggle = $(toggle);

            $input.off('input keyup');
            $toggle.off('click');

            $input.on('input keyup', function() {
                if ($input.val().length > 0) {
                    $toggle.addClass('show');
                } else {
                    $toggle.removeClass('show');
                }
            });

            $toggle.on('click', function() {
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $toggle.removeClass('feather-eye-off').addClass('feather-eye');
                } else {
                    $input.attr('type', 'password');
                    $toggle.removeClass('feather-eye').addClass('feather-eye-off');
                }
                setTimeout(() => feather.replace(), 50);
            });
        });
        }

        // ✅ Reset form ONLY on modal close (NOT on show)
        function resetAccountForm() {
            $('#currentPassword, #newPassword, #confirmPassword').val('').attr('type', 'password');
            $('.password-toggle').removeClass('show feather-eye').addClass('feather-eye-off');
            $('#avatarInput').val('');
            const originalAvatar = userDataCache?.avatar || '{{ asset("assets/images/avatar/user-icon.jpeg") }}';
            $('#previewAvatar').attr('src', originalAvatar);
        }

        // ✅ Modal close events
        $('#accountSettingsModal').on('hide.bs.modal hidden.bs.modal', function() {
            resetAccountForm();
        });

        $(document).on('click', '#accountForm .btn-secondary[data-bs-dismiss="modal"]', function() {
            setTimeout(resetAccountForm, 50);
        });

        // Initial toggle setup
        initPasswordToggles();
    });
</script>


<script>
    $(document).ready(function() {
        loadUserProfile();

        function loadUserProfile() {
            $.ajax({
                url: '{{ route("home.index") }}',
                method: 'GET',
                data: { ajax: true },
                dataType: 'json',
                success: function(data) {
                    // Update both avatars
                    const avatarSrc = data.avatar ?
                        '{{ asset("") }}' + data.avatar : 'assets/images/avatar/user-icon.jpeg';

                    $('img.user-avtar').attr('src', avatarSrc);

                    // Update dropdown header
                    $('.nxl-user-dropdown .dropdown-header').html(`
                    <div class="d-flex align-items-center">
                        <img src="${avatarSrc}" alt="user-image" class="img-fluid user-avtar" />
                        <div>
                            <h6 class="text-dark mb-0">${data.username}</h6>
                            <span class="fs-12 fw-medium text-muted">${data.email}</span>
                        </div>
                    </div>
                `);
                },
                error: function() {
                    console.error('Failed to load user profile');
                }
            });
        }
    });
</script>


<script>
    $(document).ready(function() {
        var i = 1;
        $("#add_row").click(function() {
            b = i - 1;
            $("#addr" + i)
                .html($("#addr" + b).html())
                .find("td:first-child")
                .html(i + 1);
            $("#tab_logic").append('<tr id="addr' + (i + 1) + '"></tr>');
            i++;
        });
        $("#delete_row").click(function() {
            if (i > 1) {
                $("#addr" + (i - 1)).html("");
                i--;
            }
            calc();
        });
        $("#tab_logic tbody").on("keyup change", function() {
            calc();
        });
        $("#tax").on("keyup change", function() {
            calc_total();
        });
    });

    function calc() {
        $("#tab_logic tbody tr").each(function(i, element) {
            var html = $(this).html();
            if (html != "") {
                var qty = $(this).find(".qty").val();
                var price = $(this).find(".price").val();
                $(this)
                    .find(".total")
                    .val(qty * price);
                calc_total();
            }
        });
    }

    function calc_total() {
        total = 0;
        $(".total").each(function() {
            total += parseInt($(this).val());
        });
        $("#sub_total").val(total.toFixed(2));
        tax_sum = (total / 100) * $("#tax").val();
        $("#tax_amount").val(tax_sum.toFixed(2));
        $("#total_amount").val((tax_sum + total).toFixed(2));
    }
</script>


</body>

</html>