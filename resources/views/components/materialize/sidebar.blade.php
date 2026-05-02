@php
  $schoolType = $currentSchoolType ?? null;
  $isSmk = $schoolType === 'SMK';
  $admin = auth('admin')->user();
  $adminName = $admin?->name ?: 'Admin Yazid';
  $adminEmail = $admin?->email ?: 'admin@sik.local';
  $adminAvatar = $admin?->avatar_url ?? asset('assets/img/avatars/1.png');
@endphp

<style>
  /* ── Blue Gradient Sidebar ── */
  #layout-menu {
    overflow: hidden;
    background: linear-gradient(180deg, #1a3a8f 0%, #4b89fbff 40%, #2563c4 100%) !important;
    border-right: none !important;
    box-shadow: 4px 0 24px rgba(26, 58, 143, 0.2);
  }

  #layout-menu .menu-inner {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
  }

  /* All menu text white */
  #layout-menu .menu-link,
  #layout-menu .menu-link div,
  #layout-menu .menu-icon {
    color: rgba(255, 255, 255, 0.75) !important;
    transition: all 0.2s ease;
  }

  #layout-menu .menu-link:hover,
  #layout-menu .menu-link:hover div,
  #layout-menu .menu-link:hover .menu-icon {
    color: #fff !important;
    background: rgba(255, 255, 255, 0.1) !important;
  }

  /* Active menu item */
  #layout-menu .menu-item.active>.menu-link {
    background: rgba(255, 255, 255, 0.18) !important;
    border-radius: 10px;
    margin-inline: 0.5rem;
    font-weight: 600;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
  }

  #layout-menu .menu-item.active>.menu-link,
  #layout-menu .menu-item.active>.menu-link div,
  #layout-menu .menu-item.active>.menu-link .menu-icon {
    color: #fff !important;
  }

  /* Sub-menu */
  #layout-menu .menu-sub .menu-link {
    padding-left: 2.8rem !important;
  }

  #layout-menu .menu-sub .menu-item.active>.menu-link {
    background: rgba(255, 255, 255, 0.12) !important;
    margin-inline: 0.5rem;
    border-radius: 8px;
  }

  /* Toggle arrow */
  #layout-menu .menu-toggle::after {
    border-color: rgba(255, 255, 255, 0.5) !important;
  }

  /* Menu inner shadow */
  #layout-menu .menu-inner-shadow {
    background: linear-gradient(to bottom, rgba(26, 58, 143, 0.95) 0%, transparent 100%) !important;
  }

  /* Sidebar toggle button */
  #layout-menu .layout-menu-toggle {
    color: rgba(255, 255, 255, 0.7) !important;
  }

  #layout-menu .layout-menu-toggle:hover {
    color: #fff !important;
  }

  /* ── Account area ── */
  #layout-menu .sidebar-account {
    flex-shrink: 0;
    border-top: 1px solid rgba(255, 255, 255, 0.12) !important;
    background: rgba(0, 0, 0, 0.12) !important;
    backdrop-filter: none;
  }

  #layout-menu .sidebar-user-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.7rem 0.85rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.1);
    transition: padding 0.25s ease, gap 0.25s ease, border-radius 0.25s ease;
  }

  #layout-menu .sidebar-user-avatar {
    width: 38px;
    height: 38px;
    border-radius: 999px;
    object-fit: cover;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: transform 0.25s ease;
  }

  #layout-menu .sidebar-user-meta {
    min-width: 0;
    opacity: 1;
    transition: opacity 0.2s ease, transform 0.2s ease;
  }

  #layout-menu .sidebar-user-meta .fw-semibold {
    color: #fff !important;
  }

  #layout-menu .sidebar-user-meta .badge {
    width: fit-content;
    font-size: 0.68rem;
    padding-block: 0.35rem;
    background: rgba(255, 255, 255, 0.2) !important;
    color: #fff !important;
    border: 1px solid rgba(255, 255, 255, 0.25);
  }

  #layout-menu .sidebar-user-meta small {
    color: rgba(255, 255, 255, 0.6) !important;
  }

  /* Logout */
  #layout-menu .sidebar-account .menu-link,
  #layout-menu .sidebar-account .menu-link div,
  #layout-menu .sidebar-account .menu-link i {
    color: rgba(255, 255, 255, 0.7) !important;
  }

  #layout-menu .sidebar-account .menu-link:hover,
  #layout-menu .sidebar-account .menu-link:hover div,
  #layout-menu .sidebar-account .menu-link:hover i {
    color: #fff !important;
    background: rgba(255, 255, 255, 0.08) !important;
  }

  html.layout-menu-collapsed #layout-menu .sidebar-user-card {
    justify-content: center;
    padding-inline: 0.5rem;
    border-radius: 999px;
  }

  html.layout-menu-collapsed #layout-menu .sidebar-user-meta {
    opacity: 0;
    width: 0;
    overflow: hidden;
    transform: translateX(-6px);
  }

  html.layout-menu-collapsed #layout-menu .sidebar-user-avatar {
    transform: scale(0.96);
  }

  /* ── SIK-T Brand ── */
  .sik-brand-title {
    font-size: 2.8rem !important;
    font-weight: 900 !important;
    line-height: 1 !important;
    letter-spacing: 1.5px;
    background: linear-gradient(90deg, #a78bfa 0%, #f0abfc 20%, #fff 40%, #93c5fd 60%, #f0abfc 80%, #a78bfa 100%);
    background-size: 300% auto;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: sik-shimmer 2.5s linear infinite;
    filter: drop-shadow(0 0 8px rgba(167, 139, 250, 0.4));
  }

  @keyframes sik-shimmer {
    0% {
      background-position: 0% center;
    }

    100% {
      background-position: 300% center;
    }
  }

  .sik-brand-subtitle {
    font-size: 0.75rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.65);
    letter-spacing: 1.5px;
    text-transform: uppercase;
    text-align: center;
    margin-top: 3px;
  }

  .sidebar-divider {
    margin: 0 1rem;
    border: none;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.18), transparent);
  }

  html.layout-menu-collapsed .sik-brand-title,
  html.layout-menu-collapsed .sik-brand-subtitle {
    opacity: 0;
    width: 0;
    overflow: hidden;
  }

  #layout-menu .app-brand {
    border-bottom: none !important;
  }
</style>

<aside id="layout-menu" class="layout-menu menu-vertical menu d-flex flex-column">
  <div class="app-brand demo">
    <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
      <span class="app-brand-logo demo">
        <img src="{{ $uiSettingsUrl['app_logo'] ?? asset('assets/img/logo.png') }}" alt="SIK-T" height="42"
          onerror="this.src='{{ asset('assets/img/favicon/favicon.ico') }}'" style="transition: height 0.25s ease;">
      </span>
      <span class="menu-text ms-2 d-flex flex-column align-items-center">
        <span class="sik-brand-title">SIK-T</span>
        <span class="sik-brand-subtitle">Yazid Digital</span>
      </span>
    </a>
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="ri ri-arrow-left-s-line align-middle"></i>
    </a>
  </div>

  <hr class="sidebar-divider">

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
      <a href="{{ route('admin.dashboard') }}" class="menu-link">
        <i class="menu-icon icon-base ri ri-dashboard-line"></i>
        <div>{{ __('app.sidebar.dashboard') }}</div>
      </a>
    </li>

    <li
      class="menu-item {{ request()->routeIs('admin.school.*') || request()->routeIs('admin.students.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ri ri-building-2-line"></i>
        <div>{{ __('app.sidebar.school_center') }}</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.school.profile.*') ? 'active' : '' }}">
          <a href="{{ route('admin.school.profile.index') }}" class="menu-link">
            <div>{{ __('app.sidebar.school_profile') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
          <a href="{{ route('admin.students.index') }}" class="menu-link">
            <div>{{ __('app.sidebar.students_list') }}</div>
          </a>
        </li>
        @if ($isSmk)
          <li class="menu-item {{ request()->routeIs('admin.school.majors.*') ? 'active' : '' }}">
            <a href="{{ route('admin.school.majors.index') }}" class="menu-link">
              <div>{{ __('app.sidebar.majors') }}</div>
            </a>
          </li>
          <li class="menu-item {{ request()->routeIs('admin.school.smk-units.*') ? 'active' : '' }}">
            <a href="{{ route('admin.school.smk-units.index') }}" class="menu-link">
              <div>Master Unit (UKK)</div>
            </a>
          </li>
        @endif
        <li class="menu-item {{ request()->routeIs('admin.school.subjects.*') ? 'active' : '' }}">
          <a href="{{ route('admin.school.subjects.index') }}" class="menu-link">
            <div>{{ __('app.sidebar.subjects') }}</div>
          </a>
        </li>
      </ul>
    </li>

    <li class="menu-item {{ request()->routeIs('admin.grades.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ri ri-task-line"></i>
        <div>{{ __('app.sidebar.grade_management') }}</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.grades.academic.*') ? 'active' : '' }}">
          <a href="{{ route('admin.grades.academic.index') }}" class="menu-link">
            <div>{{ __('app.sidebar.academic_grades') }}</div>
          </a>
        </li>
        @if ($isSmk)
          <li class="menu-item {{ request()->routeIs('admin.grades.competency.*') ? 'active' : '' }}">
            <a href="{{ route('admin.grades.competency.index') }}" class="menu-link">
              <div>{{ __('app.sidebar.competency') }}</div>
            </a>
          </li>
        @endif
      </ul>
    </li>



    <li class="menu-item {{ request()->routeIs('admin.graduation.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ri ri-graduation-cap-line"></i>
        <div>{{ __('app.sidebar.graduation_services') }}</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.graduation.status.*') ? 'active' : '' }}">
          <a href="{{ route('admin.graduation.status.index') }}" class="menu-link">
            <div>{{ __('app.sidebar.graduation_status') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.graduation.templates.*') ? 'active' : '' }}">
          <a href="{{ route('admin.graduation.templates.index') }}" class="menu-link">
            <div>Template Surat</div>
          </a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.graduation.documents.*') ? 'active' : '' }}">
          <a href="{{ route('admin.graduation.documents.index') }}" class="menu-link">
            <div>{{ __('app.sidebar.print_documents') }}</div>
          </a>
        </li>
      </ul>
    </li>

    <li
      class="menu-item {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.whatsapp.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ri ri-settings-3-line"></i>
        <div>{{ __('app.sidebar.system_config') }}</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.whatsapp.*') ? 'active' : '' }}">
          <a href="{{ route('admin.whatsapp.connection.index') }}" class="menu-link">
            <div>{{ __('app.sidebar.whatsapp_center') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.settings.backup.*') ? 'active' : '' }}">
          <a href="{{ route('admin.settings.backup.index') }}" class="menu-link">
            <div>{{ __('app.sidebar.backup_database') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.settings.about.*') ? 'active' : '' }}">
          <a href="{{ route('admin.settings.about.index') }}" class="menu-link">
            <div>Tentang Aplikasi</div>
          </a>
        </li>
      </ul>
    </li>
  </ul>

  <div class="sidebar-account px-3 py-2">
    <div class="sidebar-user-card mb-2">
      <img src="{{ $adminAvatar }}" alt="{{ $adminName }}" class="sidebar-user-avatar">
      <div class="sidebar-user-meta">
        <div class="fw-semibold text-truncate">{{ $adminName }}</div>
        <div class="d-flex flex-column gap-0">
          <span class="badge bg-label-primary rounded-pill">{{ __('app.account.super_admin') }}</span>
          <small class="text-muted text-truncate d-block mt-1">{{ $adminEmail }}</small>
        </div>
      </div>
    </div>

    <form action="{{ route('admin.logout') }}" method="POST" class="m-0">
      @csrf
      <button type="submit" class="menu-link border-0 bg-transparent w-100 text-start rounded-3">
        <i class="menu-icon icon-base ri ri-logout-box-r-line"></i>
        <div>{{ __('app.account.logout') }}</div>
      </button>
    </form>
  </div>
</aside>