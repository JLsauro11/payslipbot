@extends('layout.app')
@section('title', 'Positions')
@section('content')


    @push('css')
    <style>
        .table-responsive div.dataTables_wrapper div.dataTables_filter input {
            padding: 0px 15px;
        }
        .table-responsive div.dataTables_wrapper div.dataTables_length select {
            padding: 3px 15px;
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
                <h5 class="m-b-10">Positions</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home.index') }}">Home</a></li>
                <li class="breadcrumb-item">Positions</li>
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
                        <span>Add Position</span>
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
                            <table id="positionsTable" class="table table-hover" style="width:100%">
                                <thead>
                                <tr>
                                    <th class="dt-body-center text-center checkbox-center-th" style="width: 5%">
                                        <input type="checkbox" id="positionSelectAll" class="select-all-checkbox">
                                    </th>
                                    <th>ID</th>
                                    <th>Position</th>
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
                    <button id="position-multiDeleteBtn" class="btn btn-danger btn-lg shadow px-4" style="display: none;">
                        <i class="fas fa-trash me-2"></i> Delete Selected
                        <span class="badge bg-light text-danger ms-2" id="positionSelectedCount">0</span>
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
        let table = $('#positionsTable').DataTable({
            ajax: {
                url: '{{ route("positions.data") }}',
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
                    width: "10%"
                },
                { data: 'id', name: 'id', visible: false },
                { data: 'name', name: 'name', width: '45%' },
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
                    width: "45%"
                }
            ],
            order: [[1, 'desc']],
            pageLength: 25,
            responsive: true,
            language: {
                search: "Search positions:",
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

            // Verify checkbox is in the DOM
            let $selectAll = $('#positionSelectAll');
            if ($selectAll.length === 0) return;

            // Clear checkboxes
            $('#positionsTable tbody .row-select').prop('checked', false);
            $selectAll.prop('checked', false);
            selectedIds = [];

            // ✅ CLEAR any existing handler, then attach only one
            $selectAll.off('change.debug').on('change.debug', function(e) {

                let isChecked = this.checked;
                $('#positionsTable tbody .row-select').prop('checked', isChecked);

                if (isChecked) {
                    $('#positionsTable tbody .row-select:checked').each(function() {
                        let id = $(this).val();
                        if (!selectedIds.includes(id)) selectedIds.push(id);
                    });
                } else {
                    $('#positionsTable tbody .row-select').each(function() {
                        let id = $(this).val();
                        selectedIds = selectedIds.filter(sid => sid !== id);
                    });
                }

                updateSelectionUI();
            });

            updateSelectionUI();
        });

        // Add/Edit Modal
        window.openAddModal = function() {
            $('#positionForm')[0].reset();
            $('#id').val('');
            $('#positionModalTitle').text('Add Position');
            $('#positionModal').modal('show');
        };

        $(document).on('click', '.edit-btn', function() {
            let id = $(this).data('id');

            // Use route name with parameter replacement
            let url = `{{ route('positions.show', ':id') }}`.replace(':id', id);

            $.ajax({
                url: url,
                method: 'GET',
                success: function(position) {
                    // Fill form with employee data
                    $('#id').val(position.id);
                    $('#position_name').val(position.name);

                    $('#positionModalTitle').text('Edit Position');
                    $('#positionModal').modal('show');
                },
                error: function() {
                    Swal.fire('Error!', 'Position not found', 'error');
                }
            });
        });

        $('#positionForm').on('submit', function(e) {
            e.preventDefault();

            let positionId = $('#id').val();
            let url = positionId ? `{{ route("positions.update", ":id") }}`.replace(':id', positionId) : '{{ route("positions.store") }}';

            let formData = new FormData(this);
            if (positionId) {
                formData.append('_method', 'PUT');  // Method spoofing
            }
            let $btn = $('#positionSubmitBtn');
            let $spinner = $('#positionSpinner');

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
                        $('#positionModal').modal('hide');
                        table.ajax.reload();
                        $('#positionForm')[0].reset();
                        $('#id').val('');
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
            let positionId = $(this).data('id');
            let url = `{{ route('positions.destroy', ':position') }}`.replace(':position', positionId);
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
                        Swal.fire('Deleted!', response.message || 'Position deleted!', 'success');
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed!', 'error');
                    }
                });
            }
        });
        });

        // Select All checkbox handler (NEW)
        $('#positionsTable thead').on('change', '#positionSelectAll', function() {
            let isChecked = this.checked;
            $('#positionsTable tbody .row-select').prop('checked', isChecked);
            if (isChecked) {
                $('#positionsTable tbody .row-select:checked').each(function() {
                    let id = $(this).val();
                    if (!selectedIds.includes(id)) selectedIds.push(id);
                });
            } else {
                $('#positionsTable tbody .row-select').each(function() {
                    let id = $(this).val();
                    selectedIds = selectedIds.filter(sid => sid != id);
                });
            }
            updateSelectionUI();
        });

        $('#positionsTable tbody').on('change', '.row-select', function() {
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
            $('#positionSelectedCount').text(count);
            $('#position-multiDeleteBtn').toggle(count > 0);
            let positionSelectAll = $('#positionSelectAll')[0];
            let totalCheckboxes = $('#positionsTable tbody .row-select').length;
            positionSelectAll.checked = count > 0 && count >= totalCheckboxes;
        }


        $(document).on('click', '#position-multiDeleteBtn', function(e) {
            e.preventDefault();

            if (selectedIds.length === 0) return;

            Swal.fire({
                title: 'Are you sure?',
                text: `You won't be able to revert this! Delete ${selectedIds.length} position(s)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!'
            }).then((result) => {
                if (result.isConfirmed || result.value === true) {
                $.ajax({
                    url: '{{ route("positions.multi-delete") }}',
                    type: 'POST',  // ✅ Same as single delete
                    data: {
                        ids: selectedIds,
//                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('SUCCESS:', response);
                        Swal.fire('Deleted!', response.message || 'Positions deleted!', 'success');
                        if (typeof table !== 'undefined') {
                            table.ajax.reload();  // Refresh table
                        }
                        $('#positionSelectAll').prop('checked', false);
                        $('#positionsTable tbody .row-select').prop('checked', false);
                        selectedIds = [];  // Clear selection
                        $('#position-multiDeleteBtn').hide();
                        $('#positionSelectedCount').text('0');
                    },
                    error: function(xhr) {
                        console.log('ERROR:', xhr.status, xhr.responseText);
                        let errorMsg = 'Failed to delete positions';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            }
        });
        });

    }); // ✅ ONE closing brace
</script>



@endpush