@extends('layout.app')
@section('title', 'Employees')
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
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #444;
            line-height: 20px;
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
        .password-toggle {
            cursor: pointer;
            color: #6c757d;
            opacity: 0.7;
            transition: all 0.2s ease;
        }
        .password-toggle:hover {
            color: #ea4d4d;
            opacity: 1;
        }
        .password-text.hidden {
            font-family: monospace !important;
        }


    </style>

    @endpush

    <!-- [ page-header ] start -->
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Employees</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home.index') }}">Home</a></li>
                <li class="breadcrumb-item">Employees</li>
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
                    <button onclick="openAddModal()" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Add Employee</span>
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
                            <table id="employeesTable" class="table table-hover" style="width:100%">
                                <thead>
                                <tr>
                                    <th class="dt-body-center text-center checkbox-center-th" style="width: 5%">
                                        <input type="checkbox" id="employeeSelectAll" class="select-all-checkbox">
                                    </th>
                                    <th>ID</th>
                                    <th>Employee Number</th>
                                    <th>Bio Number</th>
                                    <th>Area</th>
                                    <th>Employee Name</th>
                                    <th>Payslip Password</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Status</th>
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

    <div class="fixed-bottom-multi-delete">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-auto">
                    <button id="employee-multiDeleteBtn" class="btn btn-danger btn-lg shadow px-4" style="display: none;">
                        <i class="fas fa-trash me-2"></i> Delete Selected
                        <span class="badge bg-light text-danger ms-2" id="employeeSelectedCount">0</span>
                    </button>
                </div>
            </div>
        </div>
    </div>



@endsection

@push('js')

<script>
    $(document).ready(function() {

        var selectedIds = [];

// Initialize DataTable
        let table = $('#employeesTable').DataTable({
            ajax: {
                url: '{{ route("employees.data") }}',
                dataSrc: ''  // ✅ Returns array directly
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
                {
                    data: 'bio_number',
                    name: 'bio_number',
                    defaultContent: '-'
                },
                {
                    data: 'area',
                    name: 'area',
                    defaultContent: '-'
                },
                {
                    data: 'display_name',  // ✅ CHANGED: 'name' → 'display_name'
                    name: 'display_name',  // ✅ Updated name too
                    defaultContent: '-'
                },
                {
                    data: 'password',
                    name: 'password',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        if (!data) return '-';
                        return `
                    <div class="position-relative password-container">
                        <span class="password-text hidden fw-monospace" data-password="${data}" style="font-family: monospace; min-width: 100px; display: block;">●●●●●●●●</span>
                        <i class="feather feather-eye-off password-toggle"
                           style="cursor: pointer; position: absolute; right: 8px; top: 50%; transform: translateY(-50%); z-index: 10; color: #6c757d; opacity: 0.7; font-size: 16px;"></i>
                    </div>
                `;
                    },
                    width: "160px"
                },
                {
                    data: 'position',
                    name: 'position',
                    defaultContent: '-'
                },
                {
                    data: 'department',
                    name: 'department',
                    defaultContent: '-'
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function(data) {
                        let badgeClass = data === 'Active' ? 'bg-success' : 'bg-danger';
                        return `<span class="badge ${badgeClass}">${data}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                    <div class="btn-group" role="group">
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
            order: [[1, 'desc']],
            pageLength: 25,
            responsive: true,
            language: {
                search: "Search employees:",
                lengthMenu: "Show _MENU_ entries"
            },
            // 👇 Sticky header + footer
            fixedHeader: {
                header: true,
                footer: true
            },

            // Optional: if you want internal Y‑scroll (not full page)
            scrollY: 400,
            scrollCollapse: true,
            scrollX: true
        });

        table.on('draw', function() {
            console.log('✅ draw → employeeSelectAll exists?', $('#employeeSelectAll').length);

            // Verify checkbox is in the DOM
            let $selectAll = $('#employeeSelectAll');
            if ($selectAll.length === 0) return;

            // Clear checkboxes
            $('#employeesTable tbody .row-select').prop('checked', false);
            $selectAll.prop('checked', false);
            selectedIds = [];

            // ✅ CLEAR any existing handler, then attach only one
            $selectAll.off('change.debug').on('change.debug', function(e) {
                console.log('✅ SelectAll change.debug:', this.checked, e.target);

                let isChecked = this.checked;
                $('#employeesTable tbody .row-select').prop('checked', isChecked);

                if (isChecked) {
                    $('#employeesTable tbody .row-select:checked').each(function() {
                        let id = $(this).val();
                        if (!selectedIds.includes(id)) selectedIds.push(id);
                    });
                } else {
                    $('#employeesTable tbody .row-select').each(function() {
                        let id = $(this).val();
                        selectedIds = selectedIds.filter(sid => sid !== id);
                    });
                }

                updateSelectionUI();
            });

            updateSelectionUI();
        });

        $(document).on('click', '.password-toggle', function() {
            let $container = $(this).closest('.password-container');
            let $text = $container.find('.password-text');
            let password = $text.data('password');

            if ($text.hasClass('hidden')) {
                // Show password
                $text.text(password).removeClass('hidden');
                $(this).removeClass('feather-eye-off').addClass('feather-eye');
            } else {
                // Hide password
                $text.text('●●●●●●●●').addClass('hidden');
                $(this).removeClass('feather-eye').addClass('feather-eye-off');
            }
        });

// ✅ Add Modal (unchanged)
        window.openAddModal = function() {
            $('#employeeForm')[0].reset();
            $('#id').val('');
            $('.password-field').hide();
            $('#employeeModalTitle').text('Add Employee');

            $('#employeeModal').modal('show');
            loadAreas();
            loadDepartments();
            loadPositions();
        };

        // ✅ FIXED: Edit button handler (matches controller response)
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).data('id');
            let url = `{{ route('employees.show', ':id') }}`.replace(':id', id);

            $.ajax({
                url: url,
                method: 'GET',
                success: function(employee) {
                    console.log('Employee:', employee);

                    // ✅ Fixed field mapping
                    $('#id').val(employee.id);
                    $('#employee_number').val(employee.employee_number || employee.employee_id);
                    $('#bio_number').val(employee.bio_number || '');
                    $('#first_name').val(employee.first_name || '');
                    $('#last_name').val(employee.last_name || '');
                    $('#middle_initial').val(employee.middle_initial || '');
                    $('#suffix').val(employee.suffix || '');
                    $('#status').val(employee.status || 'Active');

                    $('.password-field').show();
                    $('#password').val('');

                    $('#employeeModalTitle').text('Edit Employee');
                    $('#employeeModal').modal('show');

                    $.when(loadAreas(), loadDepartments(), loadPositions()).done(function() {
                        $('#area_id').val(employee.area_id || '').trigger('change');
                        $('#position_id').val(employee.position_id || '').trigger('change');
                        $('#department_id').val(employee.department_id || '').trigger('change');
                    });
                },
                error: function(xhr) {
                    console.error('Error loading employee:', xhr);
                    Swal.fire('Error!', 'Failed to load employee data.', 'error');
                }
            });
        });

        // ✅ Form Submit (minor cleanup)
        $('#employeeForm').on('submit', function(e) {
            e.preventDefault();

            let employeeId = $('#id').val();
            let url = employeeId ? `{{ route("employees.update", ":id") }}`.replace(':id', employeeId) : '{{ route("employees.store") }}';

            let formData = new FormData(this);
            if (employeeId) {
                formData.append('_method', 'PUT');
            }

            let $btn = $('#employeeSubmitBtn');
            let $spinner = $('#employeeSpinner');

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
                            showConfirmButton: false,
                            timer: 1000
                        });
                        $('#employeeModal').modal('hide');
                        table.ajax.reload();
                        $('#employeeForm')[0].reset();
                    }
                },
                error: function(xhr) {
                    let response = xhr.responseJSON;
                    if (response && response.validation) {
                        let errors = Object.values(response.errors).flat().join('<br>');
                        Swal.fire('Error!', errors, 'error');
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
            let employeeId = $(this).data('id');
            let url = `{{ route('employees.destroy', ':employee') }}`.replace(':employee', employeeId);
            Swal.fire({
                title: 'Are you sure?', text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed || result.value === true) {
                $.ajax({
                    url: url,
                    method: 'DELETE',  // ✅ Native DELETE
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire('Deleted!', response.message || 'Employee deleted!', 'success');
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed!', 'error');
                    }
                });
            }
        });
        });

        $('#employeesTable tbody').on('change', '.row-select', function() {
            let id = $(this).val();
            if (this.checked && !selectedIds.includes(id)) {
                selectedIds.push(id);
            } else {
                selectedIds = selectedIds.filter(sid => sid != id);
            }
            updateSelectionUI();
        });

        function updateSelectionUI() {
            let count = selectedIds.length;
            $('#employeeSelectedCount').text(count);
            $('#employee-multiDeleteBtn').toggle(count > 0);
            let employeeSelectAll = $('#employeeSelectAll')[0];
            let totalCheckboxes = $('#employeesTable tbody .row-select').length;
            employeeSelectAll.checked = count > 0 && count >= totalCheckboxes;
        }


        $(document).on('click', '#employee-multiDeleteBtn', function(e) {
            e.preventDefault();

            if (selectedIds.length === 0) return;

            Swal.fire({
                title: 'Are you sure?',
                text: `You won't be able to revert this! Delete ${selectedIds.length} employee(s)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!'
            }).then((result) => {
                if (result.isConfirmed || result.value === true) {
                $.ajax({
                    url: '{{ route("employees.multi-delete") }}',
                    type: 'POST',  // ✅ Same as single delete
                    data: {
                        ids: selectedIds,
//                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('SUCCESS:', response);
                        Swal.fire('Deleted!', response.message || 'Employees deleted!', 'success');
                        if (typeof table !== 'undefined') {
                            table.ajax.reload();  // Refresh table
                        }
                        $('#employeeSelectAll').prop('checked', false);
                        $('#employeesTable tbody .row-select').prop('checked', false);
                        selectedIds = [];  // Clear selection
                        $('#employee-multiDeleteBtn').hide();
                        $('#employeeSelectedCount').text('0');
                    },
                    error: function(xhr) {
                        console.log('ERROR:', xhr.status, xhr.responseText);
                        let errorMsg = 'Failed to delete employees';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            }
        });
        });

        function loadAreas() {
            let $select = $('#area_id');

            // ✅ Destroy if exists
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.empty().append('<option value="">Select Area</option>');

            return $.ajax({
                url: '{{ route("areas.data") }}',
                method: 'GET',
                success: function(response) {
                    $.each(response.data, function(index, area) {
                        $select.append(`<option value="${area.id}">${area.name}</option>`);
                    });

                    // ✅ Initialize Select2 AFTER data loads
                    $select.select2({
                        placeholder: '-- Select Area --',
                        width: '100%',
                        dropdownParent: $('#employeeModal .modal-body')
                    });
                }
            });
        }

        function loadDepartments() {
            let $select = $('#department_id');

            // ✅ Destroy if exists
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.empty().append('<option value="">Select Department</option>');

            return $.ajax({
                url: '{{ route("departments.data") }}',
                method: 'GET',
                success: function(response) {
                    $.each(response.data, function(index, department) {
                        $select.append(`<option value="${department.id}">${department.name}</option>`);
                    });

                    // ✅ Initialize Select2 AFTER data loads
                    $select.select2({
                        placeholder: '-- Select Department --',
                        width: '100%',
                        dropdownParent: $('#employeeModal .modal-body')
                    });
                }
            });
        }

        function loadPositions() {
            let $select = $('#position_id');

            // ✅ Destroy if exists
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.empty().append('<option value="">Select Position</option>');

            return $.ajax({
                url: '{{ route("positions.data") }}',
                method: 'GET',
                success: function(response) {
                    $.each(response.data, function(index, position) {
                        $select.append(`<option value="${position.id}">${position.name}</option>`);
                    });

                    // ✅ Initialize Select2 AFTER data loads
                    $select.select2({
                        placeholder: '-- Select Position --',
                        width: '100%',
                        dropdownParent: $('#employeeModal .modal-body')
                    });
                }
            });
        }
    });
</script>



@endpush