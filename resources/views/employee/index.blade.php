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
                                    <th>Employee Number</th>
                                    <th>Employee Name</th>
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



@endsection

@push('js')

<script>
    $(document).ready(function() {

        // Initialize DataTable
        let table = $('#employeesTable').DataTable({
            ajax: {
                url: '{{ route("employees.data") }}',
                dataSrc: 'data'
            },
            columns: [
                { data: 'employee_id', name: 'employee_id' },
                { data: 'name', name: 'name' },
                { data: 'position', name: 'position', defaultContent: '-' },
                { data: 'department', name: 'department', defaultContent: '-' },
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
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true,
            language: {
                search: "Search employees:",
                lengthMenu: "Show _MENU_ entries"
            }
        });

        // Add/Edit Modal
        window.openAddModal = function() {
            $('#employeeForm')[0].reset();
            $('#employee_id').val('');
            $('#modalTitle').text('Add Employee');
            $('#employeeModal').modal('show');
        };

        $(document).on('click', '.edit-btn', function() {
            let id = $(this).data('id');

            // Use route name with parameter replacement
            let url = `{{ route('employees.show', ':id') }}`.replace(':id', id);

            $.ajax({
                url: url,
                method: 'GET',
                success: function(employee) {
                    // Fill form with employee data
                    $('#employee_id').val(employee.id);
                    $('#employee_number').val(employee.employee_id);
                    $('#name').val(employee.name);
                    $('#position').val(employee.position || '');
                    $('#department').val(employee.department || '');
                    $('#status').val(employee.status);

                    $('#modalTitle').text('Edit Employee');
                    $('#employeeModal').modal('show');
                },
                error: function() {
                    Swal.fire('Error!', 'Employee not found', 'error');
                }
            });
        });


        $('#employeeForm').on('submit', function(e) {
            e.preventDefault();

            let employeeId = $('#employee_id').val();
            let url = employeeId ? `{{ route("employees.update", ":id") }}`.replace(':id', employeeId) : '{{ route("employees.store") }}';

            let formData = new FormData(this);
            if (employeeId) {
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
                        $('#employeeModal').modal('hide');
                        table.ajax.reload();
                        $('#employeeForm')[0].reset();
                        $('#employee_id').val(''); // Clear hidden ID
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

            let employeeId = $(this).data('id');
            let url = `{{ route('employees.destroy', ':id') }}`.replace(':id', employeeId);

            console.log('Delete ID:', employeeId);
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
                if (result.isConfirmed || result.value === true) {
                $.ajax({
                    url: url,
                    type: 'POST',  // ✅ Laravel DELETE uses POST with _method
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('SUCCESS:', response);
                        Swal.fire('Deleted!', response.message || 'Employee deleted!', 'success');
                        if (typeof table !== 'undefined') {
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        console.log('ERROR:', xhr.status, xhr.responseText);
                        let errorMsg = 'Failed to delete Employee';
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