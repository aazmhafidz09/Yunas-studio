<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <!-- LOGO -->
    <div class="navbar-brand-box">
        <a href="{{Auth::user()->isUser() ? route('user.foto.index') : (Auth::user()->isKasir() ? url('transaksi') : url('index'))}}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="" height="12">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="" height="50">
            </span>
        </a>

        <a href="{{url('index')}}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="" height="12">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="" height="50">
            </span>
        </a>
    </div>

    <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect vertical-menu-btn">
        <i class="fa fa-fw fa-bars"></i>
    </button>

    <div data-simplebar class="sidebar-menu-scroll">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">@lang('translation.Menu')</li>

                @can('viewAny', App\Models\Expense::class)
                    <li>
                        <a href="{{url('index')}}">
                            <i class="uil-home-alt"></i>
                            <span>@lang('translation.Dashboard')</span>
                        </a>
                    </li>
                @endcan

                <li>
                    <a href="{{url('transaksi')}}" class="waves-effect">
                        <i class="uil-invoice"></i>
                        <span>Transactions</span>
                    </a>
                </li>

                {{-- Galeri Foto hanya untuk akun user (pelanggan) --}}
                @if(Auth::user()->isUser())
                    <li>
                        <a href="{{ route('user.foto.index') }}" class="waves-effect {{ request()->routeIs('user.foto.*') ? 'active' : '' }}">
                            <i class="uil-images"></i>
                            <span>Foto</span>
                        </a>
                    </li>
                @endif

                @if(Auth::user()->isAdmin() || Auth::user()->isKasir())
                    @php $pendingSelections = \App\Models\Transaksi::where('edit_selection_status', 'submitted')->count(); @endphp
                    <li>
                        <a href="{{ route('photo-selections.index') }}" class="waves-effect {{ request()->routeIs('photo-selections.*') ? 'active' : '' }}">
                            <i class="uil-check-square"></i>
                            @if($pendingSelections > 0)
                                <span class="badge rounded-pill bg-warning float-end">{{ $pendingSelections }}</span>
                            @endif
                            <span>Pilihan Foto Edit</span>
                        </a>
                    </li>
                @endif

                @can('viewAny', App\Models\Expense::class)
                    <li>
                        <a href="{{url('expenses')}}" class="waves-effect">
                            <i class="uil-money-withdrawal"></i>
                            <span>Expenses</span>
                        </a>
                    </li>
                @endcan

                @if(!Auth::user()->isUser())
                 <li class="menu-title">@lang('translation.Management')</li>
                @endif

                @can('viewAny', App\Models\User::class)
                    <li>
                        <a href="{{url('users')}}">
                            <i class="uil-users-alt"></i>
                            <span>@lang('translation.User')</span>
                        </a>
                    </li>
                @endcan

                @can('viewAny', App\Models\Additional::class)
                    <li>
                        <a href="{{url('additionals')}}">
                            <i class="uil-pricetag-alt"></i>
                            <span>Additional</span>
                        </a>
                    </li>
                @endcan

                @can('viewAny', App\Models\Product::class)
                    <li>
                        <a href="{{ route('packets.index') }}" >
                            <i class="uil-store"></i>
                            <span>Products</span>
                        </a>
                    </li>
                @endcan
                <li>
                    <a href="{{ route('view-reschedule') }}" class="waves-effect">
                        <i class="uil-chart"></i>
                        <span>Reschedule</span>
                    </a>
                </li>
                
                <li>
                    <a href="{{url('gateway')}}" class="waves-effect">
                        <i class="uil-invoice"></i>
                        <span>Payment Gateway</span>
                    </a>
                </li>
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
