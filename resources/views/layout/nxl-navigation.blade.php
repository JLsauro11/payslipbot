<style>
    .m-header {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        padding: 10px 0 !important; /* Replace your 50px margins */
    }

    .b-brand {
        display: flex !important;
        justify-content: center !important;
    }

    .rs8-logo-full {
        vertical-align: middle !important;
        max-width: 150px !important; /* Adjust to fit */
    }
</style>
<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="index.html" class="b-brand">
                <!-- ========   change your logo hear   ============ -->
                <img src="assets/images/rs8-logo-full.png" alt="" class="logo logo-lg rs8-logo-full" />
                <img src="assets/images/rs8-logo.png" alt="" class="logo logo-sm" />
            </a>
        </div>
        <div class="navbar-content">
            <ul class="nxl-navbar">
                <li class="nxl-item nxl-caption">
                    <label>Navigation</label>
                </li>
                <li class="nxl-item nxl-hasmenu">
                    <a href="{{ route('home.index') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboards</span>
                    </a>
                </li>
                <li class="nxl-item nxl-hasmenu">
                    <a href="{{ route('payslips.index') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                        <span class="nxl-mtext">Payslips</span>
                    </a>
                </li>
                <li class="nxl-item nxl-hasmenu">
                    <a href="{{ route('employees.index') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-users"></i></span>
                        <span class="nxl-mtext">Employees</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
