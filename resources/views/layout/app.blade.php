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
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/favicon.ico') }}" />
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
                        <i class="fas fa-upload me-1"></i> Upload Payslip
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
<!--! END: Theme Customizer !-->
<script src="{{ asset('assets/vendors/js/bootstrap.min.js') }}"></script>

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




@stack('js')


</body>

</html>