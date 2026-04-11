@extends('layout.app')
@section('title', 'Verified Senders')
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
                <h5 class="m-b-10">Verified Senders</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home.index') }}">Home</a></li>
                <li class="breadcrumb-item">Verified Senders</li>
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
                            <table id="verifiedSendersTable" class="table table-hover" style="width:100%">
                                <thead>
                                <tr>
                                    <th class="dt-body-center text-center checkbox-center-th" style="width: 5%">
                                        <input type="checkbox" id="verifiedSenderSelectAll" class="select-all-checkbox">
                                    </th>
                                    <th>ID</th>
                                    <th>Sender ID</th>
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
                    <button id="verifiedSender-multiDeleteBtn" class="btn btn-danger btn-lg shadow px-4" style="display: none;">
                        <i class="fas fa-trash me-2"></i> Delete Selected
                        <span class="badge bg-light text-danger ms-2" id="verifiedSenderSelectedCount">0</span>
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
        let table = $('#verifiedSendersTable').DataTable({
            ajax: {
                url: '{{ route("verified_senders.data") }}',
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
                { data: 'sender_id', name: 'sender_id', width: '45%' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                        <div class="btn-group" role="group">
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
                search: "Search Verified Senders:",
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
            let $selectAll = $('#verifiedSenderSelectAll');
            if ($selectAll.length === 0) return;

            // Clear checkboxes
            $('#verifiedSendersTable tbody .row-select').prop('checked', false);
            $selectAll.prop('checked', false);
            selectedIds = [];

            // ✅ CLEAR any existing handler, then attach only one
            $selectAll.off('change.debug').on('change.debug', function(e) {

                let isChecked = this.checked;
                $('#verifiedSendersTable tbody .row-select').prop('checked', isChecked);

                if (isChecked) {
                    $('#verifiedSendersTable tbody .row-select:checked').each(function() {
                        let id = $(this).val();
                        if (!selectedIds.includes(id)) selectedIds.push(id);
                    });
                } else {
                    $('#verifiedSendersTable tbody .row-select').each(function() {
                        let id = $(this).val();
                        selectedIds = selectedIds.filter(sid => sid !== id);
                    });
                }

                updateSelectionUI();
            });

            updateSelectionUI();
        });

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            let verifiedSenderId = $(this).data('id');
            let url = `{{ route('verified_senders.destroy', ':verified_sender') }}`.replace(':verified_sender', verifiedSenderId);
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
                        Swal.fire('Deleted!', response.message || 'Verified Sender deleted!', 'success');
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed!', 'error');
                    }
                });
            }
        });
        });

        $('#verifiedSendersTable tbody').on('change', '.row-select', function() {
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
            $('#verifiedSenderSelectedCount').text(count);
            $('#verifiedSender-multiDeleteBtn').toggle(count > 0);
            let verifiedSenderSelectAll = $('#verifiedSenderSelectAll')[0];
            let totalCheckboxes = $('#verifiedSendersTable tbody .row-select').length;
            verifiedSenderSelectAll.checked = count > 0 && count >= totalCheckboxes;
        }


        $(document).on('click', '#verifiedSender-multiDeleteBtn', function(e) {
            e.preventDefault();

            if (selectedIds.length === 0) return;

            Swal.fire({
                title: 'Are you sure?',
                text: `You won't be able to revert this! Delete ${selectedIds.length} verified sender(s)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!'
            }).then((result) => {
                if (result.isConfirmed || result.value === true) {
                $.ajax({
                    url: '{{ route("verified_senders.multi-delete") }}',
                    type: 'POST',  // ✅ Same as single delete
                    data: {
                        ids: selectedIds,
//                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('SUCCESS:', response);
                        Swal.fire('Deleted!', response.message || 'Verified Senders deleted!', 'success');
                        if (typeof table !== 'undefined') {
                            table.ajax.reload();  // Refresh table
                        }
                        $('#verifiedSenderSelectAll').prop('checked', false);
                        $('#verifiedSendersTable tbody .row-select').prop('checked', false);
                        selectedIds = [];  // Clear selection
                        $('#verifiedSender-multiDeleteBtn').hide();
                        $('#verifiedSenderSelectedCount').text('0');
                    },
                    error: function(xhr) {
                        console.log('ERROR:', xhr.status, xhr.responseText);
                        let errorMsg = 'Failed to delete verified sender';
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