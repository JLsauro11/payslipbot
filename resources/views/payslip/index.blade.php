@extends('layout.app')
@section('title', 'Payslips')
@section('content')


    @push('css')
    <style>
        .table-responsive div.dataTables_wrapper div.dataTables_filter input {
            padding: 0px 15px;
        }
        .table-responsive div.dataTables_wrapper div.dataTables_length select {
            padding: 3px 15px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 45px;
            position: absolute;
            top: 1px;
            right: 5px;
            width: 20px;
        }
        .fixed-bottom-multi-delete {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);  /* Perfect center */
            z-index: 1050;
        }


        .table-responsive {
            overflow-y: auto;
        }

        .checkbox-center-th {
            vertical-align: middle !important;
            line-height: 1 !important;
            /*display: table-cell !important;*/
        }

        .checkbox-center-th input[type="checkbox"] {
            vertical-align: middle !important;
            margin: 0 !important;
        }



    </style>

    @endpush

    <!-- [ page-header ] start -->
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Payslips</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home.index') }}">Home</a></li>
                <li class="breadcrumb-item">Payslips</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="{{ route('home.index') }}" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <div class="dropdown filter-dropdown">
                        <button  onclick="openFilterModal()" class="btn btn-md btn-light-brand" id="filter">
                            <i class="feather-filter me-2"></i>
                            <span>Filter</span>
                        </button>
                    </div>
                    <button onclick="openAddModal()" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Add Payslip</span>
                    </button>

                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
            </div>
        </div>
    </div>



    {{-- Modal --}}

    <!-- [ page-header ] end -->
    <!-- [ Main Content ] start -->
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="payslipsTable" class="table table-hover">
                                <thead>
                                <tr>
                                    <th class="dt-body-center text-center checkbox-center-th" style="width: 5%">
                                        <input type="checkbox" id="selectAll" class="select-all-checkbox">
                                    </th>

                                    <th>Employee ID</th>
                                    <th>Employee Number</th>
                                    <th>Employee Name</th>
                                    <th>Payslip</th>
                                    <th>Payslip Date</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ CENTERED BOTTOM BUTTON -->
    <div class="fixed-bottom-multi-delete">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-auto">
                    <button id="multiDeleteBtn" class="btn btn-danger btn-lg shadow px-4" style="display: none;">
                        <i class="fas fa-trash"></i> Delete Selected
                        <span class="badge bg-light text-danger ms-2" id="selectedCount">0</span>
                    </button>
                </div>
            </div>
        </div>
    </div>




@endsection

@push('js')

<script>
    $(document).ready(function() {

        var baseURL = window.baseUrl = '{{ url("") }}';

        // Declare variables at top
        let table;
        let currentFilters = { start_date: '', end_date: '' };
        let selectedIds = [];

        // Filter Modal
        window.openFilterModal = function() {
            $('#filterForm')[0].reset();
            $('#filterModal #startDate').val(currentFilters.start_date);
            $('#filterModal #endDate').val(currentFilters.end_date);
            $('#filterModal').modal('show');
        };

        // Filter Submit - FULL CORRECTED VERSION
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();

            let startDate = $('#filterModal #startDate').val();
            let endDate = $('#filterModal #endDate').val();

            // ✅ FIXED DATE VALIDATION (MM/DD/YYYY format)
            if (startDate && endDate) {
                let startParts = startDate.split('/');
                let endParts = endDate.split('/');

                let start = new Date(startParts[2], startParts[0]-1, startParts[1]);  // Year, Month-1, Day
                let end = new Date(endParts[2], endParts[0]-1, endParts[1]);          // Year, Month-1, Day

                if (start > end || isNaN(start) || isNaN(end)) {
                    Swal.fire('Invalid Dates!', 'Start date cannot be after end date', 'error');
                    return false;
                }
            }

            let $btn = $('#filterBtn');
            let $spinner = $('#filterSpinner');

            $btn.prop('disabled', true);
            $spinner.removeClass('d-none');

            currentFilters.start_date = startDate;
            currentFilters.end_date = endDate;

            table.ajax.reload(null, false);
            $('#filterModal').modal('hide');

            setTimeout(() => {
                $btn.prop('disabled', false);
            $spinner.addClass('d-none');  // ✅ SEMICOLON FIXED
        }, 500);
        });




        // Clear Filters button
        $('#cancelFilterBtn').on('click', function() {
            currentFilters = { start_date: '', end_date: '' };
            $('#filterForm')[0].reset();
            table.ajax.reload(null, false);
            $('#filterModal').modal('hide');
        });

        // Select2
        $('#payslip_employee_id').select2({
            placeholder: '-- Select Employee --',
            width: '100%',
            dropdownParent: $('#payslipModal')
        });

        // DataTable
        table = $('#payslipsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("payslips.data") }}',
                data: function(d) {
                    d.start_date = currentFilters.start_date;
                    d.end_date = currentFilters.end_date;
                },
                dataSrc: 'data'
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'dt-body-center',
                    render: function (data, type, row) {
                        return '<input type="checkbox" class="row-select" value="' + row.id + '">';
                    },
                    width: "5%"
                },
                { data: 'id', name: 'id', visible: false },
                { data: 'employee_id', name: 'employee_id' },
                { data: 'name', name: 'name' },
                { data: 'payslip', name: 'payslip', defaultContent: '-' },
                {
                    data: 'payslip_date',
                    render: function(data) {
                        return data && data !== '-' ? data : '<em>No date</em>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                        <div class="btn-group" role="group">
                            <a href="{{ asset('payslips') }}/${data.payslip}" target="_blank" class="btn btn-sm btn-info me-1">
            <i class="fas fa-eye"></i>
        </a>
         <button class="btn btn-sm btn-warning edit-btn me-1" data-id="${row.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    },
                    width: "15%"
                }
            ],
            order: [[2, 'desc']],
            pageLength: 25,
            responsive: true,
            language: {
                search: "Search payslips:",
                lengthMenu: "Show _MENU_ entries",
                processing: "Loading...",
                emptyTable: "No payslips found",
                zeroRecords: "No payslips found for selected date range"
            }
        });

        // ✅ FIXED with your required empty option
        function loadPayslipEmployees() {
            let $select = $('#payslip_employee_id');
            $select.empty().append('<option value="">Select Employee</option>');  // Your requirement ✅

            return $.ajax({
                url: '{{ route("employees.data") }}',
                method: 'GET',
                success: function(response) {
                    $.each(response.data, function(index, employee) {
                        $select.append(`
                    <option value="${employee.employee_id}">
                        ${employee.employee_id} - ${employee.name}
                    </option>
                `);
                    });
                    $select.trigger('change');
                }
            });
        }


        // Add/Edit Modal
        window.openAddModal = function() {
            $('#payslipForm')[0].reset();
            $('#payslip_id').val('');
            $('#modalTitle').text('Add Payslip');
            loadPayslipEmployees();
            $('#payslipModal').modal('show');
        };

        // Edit remains the same - works perfectly
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).data('id');
            let url = `{{ route('payslips.show', ':id') }}`.replace(':id', id);

            $.ajax({
                url: url,
                method: 'GET',
                success: function(payslip) {
                    $('#payslipForm')[0].reset();
                    $('#payslip_id').val(payslip.id);
                    $('#startDate').val(payslip.payslip_date);

                    loadPayslipEmployees().done(function() {
                        $('#payslip_employee_id').val(payslip.employee_id).trigger('change');
                    });

                    $('#modalTitle').text('Edit Payslip');
                    $('#payslipModal').modal('show');
                },
                error: function() {
                    Swal.fire('Error!', 'Payslip not found', 'error');
                }
            });
        });


        $('#payslipForm').on('submit', function(e) {
            e.preventDefault();

            let payslipId = $('#payslip_id').val();
            let url = payslipId ? `{{ route("payslips.update", ":id") }}`.replace(':id', payslipId) : '{{ route("payslips.store") }}';

            let formData = new FormData(this);
            if (payslipId) {
                formData.append('_method', 'PUT');  // Method spoofing
            }
            let $btn = $('#submitBtn');
            let $spinner = $('#spinner');

            $btn.prop('disabled', true);
            $spinner.removeClass('d-none');

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 1500
                        });
                        $('#payslipModal').modal('hide');
                        table.ajax.reload();
                        $('#payslipForm')[0].reset();
                        $('#payslip_id').val(''); // Clear hidden ID
                    }
                },
                error: function(xhr) {
                    let response = xhr.responseJSON;
                    if (response && response.validation) {
                        let errors = Object.values(response.errors).flat().join('<br>');
                        Swal.fire('Validation Error!', errors, 'error');
                    } else {
                        Swal.fire('Error!', response?.message || 'Something went wrong!', 'error');
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $spinner.addClass('d-none');
                }
            });
        });

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();

            let payslipId = $(this).data('id');
            let url = `{{ route('payslips.destroy', ':id') }}`.replace(':id', payslipId);

            console.log('Delete ID:', payslipId);
            console.log('Delete URL:', url);

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed || result.value === true) {  // ✅ Covers both cases
                $.ajax({
                    url: url,
                    type: 'POST',  // ✅ Laravel DELETE uses POST with _method
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('SUCCESS:', response);
                        Swal.fire('Deleted!', response.message || 'Payslip deleted!', 'success');
                        if (typeof table !== 'undefined') {
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        console.log('ERROR:', xhr.status, xhr.responseText);
                        let errorMsg = 'Failed to delete payslip';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            }
        });
        });



// Select All checkbox handler (NEW)
        $('#payslipsTable thead').on('change', '#selectAll', function () {
            let isChecked = this.checked;

            // Toggle ALL visible checkboxes on current page
            $('#payslipsTable tbody .row-select').prop('checked', isChecked);

            if (isChecked) {
                // Add all visible row IDs
                $('#payslipsTable tbody .row-select:checked').each(function() {
                    let id = $(this).val();
                    if (!selectedIds.includes(id)) {
                        selectedIds.push(id);
                    }
                });
            } else {
                // Remove all visible row IDs
                $('#payslipsTable tbody .row-select').each(function() {
                    let id = $(this).val();
                    selectedIds = selectedIds.filter(sid => sid != id);
                });
            }

            updateSelectionUI();
        });

// Individual checkbox change handler (ENHANCED)
        $('#payslipsTable tbody').on('change', '.row-select', function () {
            let id = $(this).val();

            if (this.checked) {
                if (!selectedIds.includes(id)) {
                    selectedIds.push(id);
                }
            } else {
                selectedIds = selectedIds.filter(sid => sid != id);
            }

            updateSelectionUI();
        });

// NEW: Update UI function (handles button + select all state)
        function updateSelectionUI() {
            let count = selectedIds.length;
            $('#selectedCount').text(count);
            $('#multiDeleteBtn').toggle(count > 0);

            // SIMPLIFIED: No indeterminate state
            let selectAll = $('#selectAll')[0];
            let totalCheckboxes = $('#payslipsTable tbody .row-select').length;

            if (count === 0) {
                selectAll.checked = false;
            } else if (count >= totalCheckboxes) {
                selectAll.checked = true;
            } else {
                selectAll.checked = false;  // Never indeterminate
            }
        }



        $(document).on('click', '#multiDeleteBtn', function(e) {
            e.preventDefault();

            if (selectedIds.length === 0) return;

            Swal.fire({
                title: 'Are you sure?',
                text: `You won't be able to revert this! Delete ${selectedIds.length} payslip(s)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!'
            }).then((result) => {
                if (result.isConfirmed || result.value === true) {
                $.ajax({
                    url: baseURL + '/payslips/delete-selected',
                    type: 'POST',  // ✅ Same as single delete
                    data: {
                        ids: selectedIds,
//                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('SUCCESS:', response);
                        Swal.fire('Deleted!', response.message || 'Payslips deleted!', 'success');
                        if (typeof table !== 'undefined') {
                            table.ajax.reload();  // Refresh table
                        }
                        $('#selectAll').prop('checked', false);
                        $('#payslipsTable tbody .row-select').prop('checked', false);
                        selectedIds = [];  // Clear selection
                        $('#multiDeleteBtn').hide();
                        $('#selectedCount').text('0');
                    },
                    error: function(xhr) {
                        console.log('ERROR:', xhr.status, xhr.responseText);
                        let errorMsg = 'Failed to delete payslips';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            }
        });
        });


    });
</script>



@endpush