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
                        <button  onclick="openFilterModal()" class="btn btn-md btn-light-brand" id="filter">
                        <i class="feather-filter me-2"></i>
                        <span>Filter</span>
                    </button>
                    <div class="dropdown filter-dropdown">
                        <a class="btn btn-primary" data-bs-toggle="dropdown" data-bs-offset="0, 10" data-bs-auto-close="outside">
                            <i class="feather-plus me-2"></i>
                            <span>New</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button onclick="openAddModal()" class="dropdown-item">
                                    <i class="feather-file-plus me-3"></i>
                                    <span>Payslip Upload</span>
                                </button>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item" onclick="openMultiUploadModal()">
                                    <i class="feather-folder-plus me-3"></i>
                                    <span>Multi-Payslips Upload</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
            </div>
        </div>
    </div>

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

                                    <th>ID</th>
                                    <th>Employee Number</th>
                                    <th>Employee Name</th>
                                    <th>Area</th>
                                    <th>Department</th>
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

    <div class="fixed-bottom-multi-delete">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-auto">
                    <button id="multiDeleteBtn" class="btn btn-danger btn-lg shadow px-4" style="display: none;">
                        <i class="fas fa-trash me-2"></i> Delete Selected
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
                    width: "20px"   // 👈 Fixed pixels for checkbox
                },
                {
                    data: 'id',
                    name: 'id',
                    visible: false   // Hidden columns don't need width
                },
                {
                    data: 'employee_id',
                    name: 'employee_id',
                    width: "200px"   // 👈 Employee ID
                },
                {
                    data: 'name',
                    name: 'name',
                    width: "280px"   // 👈 Name (widest content)
                },
                {
                    data: 'area',
                    name: 'area.name',   // 👈 searchable/sortable by area name
                    width: "200px"
                },
                {
                    data: 'department',
                    name: 'department.name',   // 👈 searchable/sortable by department name
                    width: "200px"
                },
                {
                    data: 'payslip',
                    name: 'payslip',
                    defaultContent: '-',
                    width: "220px"   // 👈 Payslip filename
                },
                {
                    data: 'payslip_date',
                    width: "200px",  // 👈 Date column
                    render: function(data) {
                        return data && data !== '-' ? data : '<em>No date</em>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    width: "240px",  // 👈 Actions (3 buttons)
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
                    }
                }
            ],
            order: [[1, 'desc']],
            pageLength: 25,
            responsive: true,
            language: {
                search: "Search payslips:",
                lengthMenu: "Show _MENU_ entries",
                processing: "Loading...",
                emptyTable: "No payslips found",
                zeroRecords: "No payslips found"
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
            let $selectAll = $('#selectAll');
            if ($selectAll.length === 0) return;

            // Clear checkboxes
            $('#payslipsTable tbody .row-select').prop('checked', false);
            $selectAll.prop('checked', false);
            selectedIds = [];

            // ✅ CLEAR any existing handler, then attach only one
            $selectAll.off('change.debug').on('change.debug', function(e) {

                let isChecked = this.checked;
                $('#payslipsTable tbody .row-select').prop('checked', isChecked);

                if (isChecked) {
                    $('#payslipsTable tbody .row-select:checked').each(function() {
                        let id = $(this).val();
                        if (!selectedIds.includes(id)) selectedIds.push(id);
                    });
                } else {
                    $('#payslipsTable tbody .row-select').each(function() {
                        let id = $(this).val();
                        selectedIds = selectedIds.filter(sid => sid !== id);
                    });
                }

                updateSelectionUI();
            });

            updateSelectionUI();
        });

        // Load employees with area
        function loadPayslipEmployees() {
            let $select = $('#payslip_employee_id');
            $select.empty().append('<option value="">Select Employee</option>');

            return $.ajax({
                url: '{{ route("employees.payslip-data") }}',
                method: 'GET',
                success: function(response) {
                    $.each(response.data, function(index, employee) {
                        $select.append(`
                    <option value="${employee.employee_id}"
                            data-bio="${employee.bio_number}"
                            data-area="${employee.area_name}">
                        ${employee.employee_id} - ${employee.name} (${employee.area_name})
                    </option>
                `);
                    });
                }
            });
        }

// Close dropdown when modal opens (minimal intervention)
        $('#payslipModal').on('show.bs.modal', function() {
            // Just remove show classes, NO .hide()
            $('.filter-dropdown').removeClass('show');
            $('.filter-dropdown .dropdown-menu').removeClass('show');
        });

// Reset on modal close (proper Bootstrap 5 way)
        $('#payslipModal').on('hidden.bs.modal', function() {
            // Remove any lingering classes only
            $('.filter-dropdown').removeClass('show');
            $('.filter-dropdown .dropdown-menu').removeClass('show');

            // Trigger click event to reinitialize Bootstrap dropdown
            setTimeout(function() {
                $('.filter-dropdown [data-bs-toggle="dropdown"]').trigger('click').trigger('click');
            }, 100);
            resetPayslipModal();
        });

        // ✅ Handle Cancel/X/Backdrop clicks
        $(document).on('click', '[data-bs-dismiss="modal"], .modal-backdrop', function() {
            setTimeout(() => {
                resetPayslipModal(); // ✅ Safe cleanup after animation
        }, 100);
        });

        // ✅ Full reset function
        function resetPayslipModal() {
            $('#payslipForm')[0].reset();
            $('#payslip_id').val('');
            $('#filenamePreview').hide();
            $('#employeeMatchInfo').hide();
            $('#detectedDate').empty();
            window.fileBio = null;
            window.fileArea = null;
            isProcessingFile = false;
        }

        // ✅ Update openAddModal
        window.openAddModal = function() {
            resetPayslipModal();
            $('#payslipModalTitle').text('Upload Payslip');
            loadPayslipEmployees();
            $('#payslipModal').modal('show');
        };

        // ✅ Update edit function
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).data('id');
            let url = `{{ route('payslips.show', ':id') }}`.replace(':id', id);

            $.ajax({
                url: url,
                method: 'GET',
                success: function(payslip) {
                    resetPayslipModal();
                    $('#payslip_id').val(payslip.id);

                    loadPayslipEmployees().done(function() {
                        $('#payslip_employee_id').val(payslip.employee_id).trigger('change');
                    });

                    $('#payslipModalTitle').text('Edit Payslip');
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
                formData.append('_method', 'PUT');
            }

            let $btn = $('#payslipSubmitBtn');
            let $spinner = $('#payslipSpinner');

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
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1000
                    });
                    resetPayslipModal(); // ✅ Full reset
                    table.ajax.reload();
                    $('#payslipModal').modal('hide');
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

        // ✅ Updated file change handler - Extract & show name
        $('#payslip_file').off('change').on('change', function() {
            if (isProcessingFile) return;
            isProcessingFile = true;

            const file = this.files[0];
            if (!file) {
                isProcessingFile = false;
                return;
            }

            $('#filenamePreview').hide();
            $('#employeeMatchInfo').hide();
            window.fileBio = null;
            window.fileArea = null;

            const filename = file.name.replace('.pdf', '');

            // Space check (unchanged)...
            const spaceIndex = filename.indexOf('EMP ');
            if (spaceIndex !== -1 && /\d/.test(filename[spaceIndex + 4])) {
                const empNum = filename.match(/EMP\s+(\d+)/)?.[1] || 'XXX';
                $('#detectedDate').html(`
            <div class="alert alert-warning p-2 mb-0">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <strong>Invalid Filename!</strong><br>
                'EMP${empNum}' contains space. Use 'EMP${empNum}' (no space).
            </div>
        `);
                $('#filenamePreview').show();
                this.value = '';
                isProcessingFile = false;
                return;
            }

            // ✅ Updated regex with name extraction
            const fullMatch = filename.match(/(.+?)[_^](\d{8})_(\d{8})_EMP(\d+)(.*)/i);
            if (!fullMatch) {
                $('#detectedDate').html(`
            <div class="alert alert-danger p-2 mb-0">
                <i class="fas fa-times me-1"></i>
                <strong>Invalid Format!</strong><br>
                Expected: AREA^YYYYMMDD_YYYYMMDD_EMPXXX^NAME.pdf
            </div>
        `);
                $('#filenamePreview').show();
                this.value = '';
                isProcessingFile = false;
                return;
            }

            const [, areaFromFile, startDateStr, endDateStr, empBio, namePart] = fullMatch;
            const fileNameFromFile = namePart.trim().replace(/^[_^]/, '');

            const endDate = new Date(
                parseInt(endDateStr.substring(0,4)),
                parseInt(endDateStr.substring(4,6)) - 1,
                parseInt(endDateStr.substring(6,8))
            );

            const endDay = endDate.getDate();
            const daysInMonth = new Date(endDate.getFullYear(), endDate.getMonth() + 1, 0).getDate();
            const payslipDay = endDay <= 15 ? 15 : daysInMonth;
            const payslipDate = `${String(endDate.getMonth() + 1).padStart(2, '0')}/${payslipDay}/${endDate.getFullYear()}`;

            $('#detectedDate').html(`
        <strong>📅 Payslip Date:</strong> ${payslipDate}<br>
        <strong>👤 Bio Number:</strong> EMP${empBio}<br>
        <strong>🏢 Area:</strong> ${areaFromFile}<br>
        <strong>👤 Name:</strong> ${fileNameFromFile || 'Not detected'}<br>
        <strong>📄 Period:</strong> ${startDateStr.substring(4,8)} - ${endDateStr.substring(4,8)}
    `);

            $('#filenamePreview').show();
            window.fileBio = empBio;
            window.fileArea = areaFromFile;
            window.fileName = fileNameFromFile; // ✅ Store name
            checkEmployeeMatch();

            isProcessingFile = false;
        });



// Check employee-file match when employee changes
        $('#payslip_employee_id').on('change', function() {
            checkEmployeeMatch();
        });

        // ✅ Updated match check - Include name
        function checkEmployeeMatch() {
            let $selected = $('#payslip_employee_id option:selected');
            let selectedBio = $selected.data('bio');
            let selectedArea = $selected.data('area');
            let selectedName = $selected.text().split(' - ')[1]?.split(' (')[0] || '';

            if (window.fileBio && window.fileArea && selectedBio && selectedArea) {
                let fileBioFull = 'EMP' + window.fileBio;
                let bioMatch = (selectedBio == fileBioFull || selectedBio == window.fileBio);
                let areaMatch = selectedArea.toUpperCase().trim() === window.fileArea.toUpperCase().trim();
                let nameMatch = !window.fileName || selectedName.toUpperCase().includes(window.fileName.toUpperCase().split(',')[0]);

                if (bioMatch && areaMatch && nameMatch) {
                    $('#selectedBioArea').html(`✅ <strong>${$selected.text()}</strong>`);
                    $('#fileBioArea').html(`Bio: EMP${window.fileBio}, Area: ${window.fileArea}${window.fileName ? ', Name: ' + window.fileName : ''}`);
                    $('#employeeMatchInfo').removeClass('bg-danger').addClass('bg-success').show();
                } else {
                    $('#employeeMatchInfo').removeClass('bg-success').addClass('bg-danger').show();
                    $('#selectedBioArea').html(`❌ MISMATCH<br><small>${$selected.text()}</small>`);
                    $('#fileBioArea').html(`Bio: EMP${window.fileBio}, Area: ${window.fileArea}${window.fileName ? ', Name: ' + window.fileName : ''}`);
                }
            }
        }

// ✅ Multi modal reset
        function resetMultiModal() {
            $('#multiUploadForm')[0].reset();
            $('#multiPreview').hide().empty();
        }

        window.openMultiUploadModal = function() {
            resetMultiModal();
            $('#multiUploadTitle').text('Upload Multiple Payslips');
            $('#multiUploadModal').modal('show');
        };

        let multiIsProcessingFile = false;


// ✅ Multi-file validation + preview with 100-file limit
        $('#multi_payslip_files').off('change').on('change', function() {
            if (multiIsProcessingFile) return;
            multiIsProcessingFile = true;

            const files = Array.from(this.files);
            const preview = $('#multiPreview');
            preview.empty().hide();

            // ✅ NEW: Check 100-file limit
            if (files.length > 100) {
                Swal.fire({
                    icon: 'warning',
                    title: 'File Limit Exceeded!',
                    text: `Maximum 100 files allowed. You selected ${files.length} files.`,
                    confirmButtonText: 'OK'
                });
                this.value = ''; // Clear selection
                multiIsProcessingFile = false;
                return;
            }

            if (files.length === 0) {
                multiIsProcessingFile = false;
                return;
            }

            let validFiles = [];
            let errors = [];

            files.forEach((file, index) => {
                const filename = file.name.replace('.pdf', '');

            // ✅ Space check
            const spaceIndex = filename.indexOf('EMP ');
            if (spaceIndex !== -1 && /\d/.test(filename[spaceIndex + 4])) {
                const empNum = filename.match(/EMP\s+(\d+)/)?.[1] || 'XXX';
                errors.push(`❌ ${file.name}: 'EMP${empNum}' contains space`);
                return;
            }

            // ✅ Format check
            const dateEmpMatch = filename.match(/(\d{8})_(\d{8})_EMP(\d+)/);
            if (!dateEmpMatch) {
                errors.push(`❌ ${file.name}: Invalid format. Expected BUSINESS_UNIT^YYYYMMDD_YYYYMMDD_EMPXXX^NAME.pdf`);
                return;
            }

            const [, startDateStr, endDateStr, empBio] = dateEmpMatch;
            const areaFromFile = filename.split('^')[0] || 'Unknown';

            const endDate = new Date(
                parseInt(endDateStr.substring(0,4)),
                parseInt(endDateStr.substring(4,6)) - 1,
                parseInt(endDateStr.substring(6,8))
            );

            const endDay = endDate.getDate();
            const daysInMonth = new Date(endDate.getFullYear(), endDate.getMonth() + 1, 0).getDate();
            const payslipDay = endDay <= 15 ? 15 : daysInMonth;
            const payslipDate = `${String(endDate.getMonth() + 1).padStart(2, '0')}/${payslipDay}/${endDate.getFullYear()}`;

            validFiles.push({
                file: file.name,
                area: areaFromFile,
                bio: `EMP${empBio}`,
                date: payslipDate,
                period: `${startDateStr.substring(4,8)} - ${endDateStr.substring(4,8)}`
            });
        });

            // ✅ Enhanced preview with file count
            let previewHtml = `<div class="alert alert-info">
        📊 <strong>${files.length} files selected</strong> (Max: 100)
    </div>`;

            if (validFiles.length > 0) {
                previewHtml += `<div class="alert alert-success mb-3">✅ ${validFiles.length} valid file(s)</div>`;
                validFiles.forEach(item => {
                    previewHtml += `
                <div class="d-flex justify-content-between align-items-center p-2 border rounded mb-2 bg-light">
                    <div>
                        <strong>${item.file}</strong><br>
                        <small class="text-muted">${item.area} | ${item.bio} | ${item.date}</small>
                    </div>
                    <span class="badge bg-success">✓ Valid</span>
                </div>
            `;
            });
            }

            if (errors.length > 0) {
                previewHtml += `
            <div class="alert alert-danger">
                <strong>❌ ${errors.length} invalid file(s):</strong><br>
                ${errors.join('<br>')}
            </div>
        `;
            }

            preview.html(previewHtml);
            preview.show();
            multiIsProcessingFile = false;
        });


// ✅ Multi-file submit with DETAILED error display
        $('#multiUploadForm').on('submit', function(e) {
            e.preventDefault();

            let formData = new FormData(this);
            let $btn = $('#multiSubmitBtn');
            let $spinner = $('#multiSpinner');

            $btn.prop('disabled', true);
            $spinner.removeClass('d-none');

            $.ajax({
                url: '{{ route("payslips.multi-store") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    let swalHtml = response.message;

                    // ✅ SHOW DETAILED ERRORS if they exist
                    if (response.details && response.details.errors && response.details.errors.length > 0) {
                        swalHtml += '<div class="mt-3"><strong>Detailed Errors:</strong><br>';
                        response.details.errors.forEach(error => {
                            swalHtml += `<div class="mt-1">• ${error}</div>`;
                    });
                        swalHtml += '</div>';
                    }

                    Swal.fire({
                        icon: response.details && response.details.success > 0 ? 'success' : 'warning',
                        title: 'Upload Complete!',
                        html: swalHtml,
                        customClass: {
                            popup: 'text-left max-w-lg'
                        },
                        width: '500px'
                    });

                    resetMultiModal();
                    table.ajax.reload();
                    $('#multiUploadModal').modal('hide');
                },
                error: function(xhr) {
                    let response = xhr.responseJSON;
                    Swal.fire('Error!', response?.message || 'Upload failed!', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $spinner.addClass('d-none');
                }
            });
        });

// ✅ Modal cleanup
        $('#multiUploadModal').on('hidden.bs.modal', function() {
            resetMultiModal();
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
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!!',
                            html: response.message || 'Payslip deleted!',
                            showConfirmButton: false,
                            timer: 1000
                        });
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
                    url: '{{ route("payslips.multi-delete") }}',
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