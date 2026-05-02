@php
$admin = auth('admin')->user();
$adminName = $admin?->name ?: 'Administrator';
$adminEmail = $admin?->email ?: 'admin@sik.local';
$adminAvatar = $admin?->avatar_url ?? asset('assets/img/avatars/1.png');
$currentLocale = app()->getLocale();
$supportedLocales = (array) config('sik.supported_locales', ['id', 'en']);
@endphp

<style>
  .app-topbar-search {
    display: inline-flex;
    align-items: center;
    gap: 0.85rem;
    min-width: min(420px, 100%);
    padding: 0.25rem 0;
    color: var(--bs-heading-color);
    background: transparent;
    border: 0;
  }

  .app-topbar-search .search-placeholder {
    color: var(--bs-secondary-color);
    font-weight: 500;
    white-space: nowrap;
  }

  .app-topbar-search .search-placeholder span {
    opacity: 0.8;
  }

  .app-topbar-icon {
    width: 2.75rem;
    height: 2.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .app-topbar-avatar {
    position: relative;
    width: 48px;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .app-topbar-avatar img {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 999px;
  }

  .app-topbar-avatar-status {
    position: absolute;
    right: 1px;
    bottom: 2px;
    width: 11px;
    height: 11px;
    border-radius: 999px;
    background: #71dd37;
    border: 2px solid var(--bs-paper-bg);
  }

  .app-search-modal .modal-dialog {
    max-width: 720px;
  }

  .app-search-results {
    min-height: 260px;
    max-height: 56vh;
    overflow-y: auto;
  }

  .app-search-results-group + .app-search-results-group {
    margin-top: 1.25rem;
  }

  .app-search-results-group-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--bs-secondary-color);
    margin-bottom: 0.75rem;
  }

  .app-search-result-item {
    display: flex;
    align-items: flex-start;
    gap: 0.9rem;
    padding: 0.9rem 1rem;
    border-radius: 1rem;
    text-decoration: none;
    color: inherit;
    transition: background-color 0.2s ease, transform 0.2s ease;
  }

  .app-search-result-item:hover {
    background: rgba(var(--bs-primary-rgb), 0.06);
    transform: translateY(-1px);
  }

  .app-search-result-icon {
    width: 2.5rem;
    height: 2.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: rgba(var(--bs-primary-rgb), 0.08);
    color: var(--bs-primary);
    flex-shrink: 0;
  }

  .app-search-empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 220px;
    text-align: center;
    color: var(--bs-secondary-color);
    padding: 1.5rem;
  }

  @media (max-width: 767.98px) {
    .app-topbar-search {
      min-width: 0;
    }

    .app-topbar-search .search-placeholder {
      font-size: 0.95rem;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }
</style>

<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme">
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
      <i class="ri ri-menu-line ri-22px"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center justify-content-between w-100 gap-4" id="navbar-collapse">
    <div class="navbar-nav align-items-center flex-grow-1">
      <div class="nav-item navbar-search-wrapper mb-0">
        <button
          type="button"
          class="nav-item nav-link px-0 app-topbar-search"
          data-app-search-trigger="true"
          aria-label="{{ __('app.topbar.search_button') }}">
          <i class="ri ri-search-line ri-24px"></i>
          <span class="d-inline-block search-placeholder">{{ __('app.topbar.search_trigger') }}</span>
        </button>
      </div>
    </div>

    <ul class="navbar-nav flex-row align-items-center ms-auto gap-1">
      <li class="nav-item dropdown me-sm-1 me-xl-0">
        <a
          class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill app-topbar-icon"
          href="javascript:void(0);"
          id="nav-language"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          title="{{ __('app.topbar.language') }}">
          <i class="ri ri-translate-2 ri-22px"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-language">
          @foreach ($supportedLocales as $locale)
            <li>
              <button
                type="button"
                class="dropdown-item d-flex align-items-center justify-content-between locale-switcher {{ $currentLocale === $locale ? 'active' : '' }}"
                data-locale="{{ $locale }}">
                <span>{{ __('app.locales.' . $locale) }}</span>
                @if ($currentLocale === $locale)
                  <i class="ri ri-check-line"></i>
                @endif
              </button>
            </li>
          @endforeach
        </ul>
      </li>

      <li class="nav-item dropdown me-sm-1 me-xl-0">
        <a
          class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill app-topbar-icon"
          id="nav-theme"
          href="javascript:void(0);"
          data-bs-toggle="dropdown"
          aria-expanded="false">
          <i class="ri ri-sun-line ri-22px theme-icon-active"></i>
          <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
          <li>
            <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light" aria-pressed="false">
              <span><i class="ri ri-sun-line ri-22px me-3" data-icon="sun-line"></i>Light</span>
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark" aria-pressed="false">
              <span><i class="ri ri-moon-clear-line ri-22px me-3" data-icon="moon-clear-line"></i>Dark</span>
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system" aria-pressed="false">
              <span><i class="ri ri-computer-line ri-22px me-3" data-icon="computer-line"></i>System</span>
            </button>
          </li>
        </ul>
      </li>

      <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-sm-1 me-xl-1">
        <a
          class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill app-topbar-icon"
          href="javascript:void(0);"
          data-bs-toggle="dropdown"
          data-bs-auto-close="outside"
          aria-expanded="false"
          id="notification-trigger">
          <i class="ri ri-notification-2-line ri-22px"></i>
          <span id="notification-count-badge" class="position-absolute top-0 start-50 translate-middle-y badge badge-dot bg-danger mt-2 border d-none"></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end py-0">
          <li class="dropdown-menu-header border-bottom py-50">
            <div class="dropdown-header d-flex align-items-center py-2">
              <h6 class="mb-0 me-auto">{{ __('app.topbar.notifications') }}</h6>
              <span id="notification-count-label" class="badge rounded-pill bg-label-primary fs-xsmall d-none">0 Baru</span>
              <a href="javascript:void(0)" id="mark-all-read" class="dropdown-notifications-all text-body" data-bs-toggle="tooltip" data-bs-placement="top" title="Tandai semua dibaca">
                <i class="ri ri-mail-open-line fs-4"></i>
              </a>
            </div>
          </li>
          <li class="dropdown-notifications-list scrollable-container">
            <ul class="list-group list-group-flush" id="notification-list">
              <!-- Dynamic Notifications Here -->
              <li class="list-group-item text-center py-4 text-muted" id="notification-empty">
                <i class="ri ri-notification-off-line d-block fs-2 mb-2"></i>
                <small>Tidak ada notifikasi baru</small>
              </li>
            </ul>
          </li>
          <li class="border-top" id="notification-footer">
            <div class="d-grid p-3">
              <a class="btn btn-sm btn-primary" href="{{ route('admin.whatsapp.connection.index') }}">{{ __('app.topbar.open_whatsapp_center') }}</a>
            </div>
          </li>
        </ul>
      </li>

      <script>
        document.addEventListener('DOMContentLoaded', function() {
          const notificationList = document.getElementById('notification-list');
          const notificationBadge = document.getElementById('notification-count-badge');
          const notificationLabel = document.getElementById('notification-count-label');
          const notificationEmpty = document.getElementById('notification-empty');
          const markAllReadBtn = document.getElementById('mark-all-read');

          function fetchNotifications() {
            fetch('{{ route("admin.notifications.index") }}')
              .then(response => response.json())
              .then(data => {
                updateNotificationUI(data);
              })
              .catch(error => console.error('Error fetching notifications:', error));
          }

          function updateNotificationUI(data) {
            const { notifications, unread_count } = data;

            // Update badges
            if (unread_count > 0) {
              notificationBadge.classList.remove('d-none');
              notificationLabel.classList.remove('d-none');
              notificationLabel.textContent = `${unread_count} Baru`;
            } else {
              notificationBadge.classList.add('d-none');
              notificationLabel.classList.add('d-none');
            }

            if (notifications.length === 0) {
              notificationEmpty.classList.remove('d-none');
              // Remove old items but keep empty state
              const items = notificationList.querySelectorAll('.notification-item');
              items.forEach(item => item.remove());
              return;
            }

            notificationEmpty.classList.add('d-none');

            // Simple clear and re-render (optimization later if needed)
            const oldItems = notificationList.querySelectorAll('.notification-item');
            oldItems.forEach(item => item.remove());

            notifications.forEach(notif => {
              const li = document.createElement('li');
              li.className = `list-group-item list-group-item-action dropdown-notifications-item notification-item`;
              li.innerHTML = `
                <div class="d-flex align-items-start">
                  <div class="flex-shrink-0 me-3">
                    <div class="avatar">
                      <span class="avatar-initial rounded-circle bg-label-${notif.type}"><i class="ri ${notif.icon}"></i></span>
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="small mb-1">${notif.title}</h6>
                    <small class="mb-1 d-block text-body">${notif.message}</small>
                    <small class="text-body-secondary">${notif.time}</small>
                  </div>
                  <div class="flex-shrink-0 dropdown-notifications-actions">
                    <a href="javascript:void(0)" class="mark-as-read text-muted" data-id="${notif.id}">
                      <i class="ri ri-circle-fill fs-tiny text-primary"></i>
                    </a>
                  </div>
                </div>
              `;
              
              // Add click event for mark as read
              li.querySelector('.mark-as-read').addEventListener('click', function(e) {
                e.stopPropagation();
                markAsRead(notif.id, li);
              });

              notificationList.appendChild(li);
            });
          }

          function markAsRead(id, element) {
            fetch(`{{ url('admin/notifications') }}/${id}/read`, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
              }
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                element.style.opacity = '0';
                element.style.transform = 'translateX(20px)';
                element.style.transition = 'all 0.3s ease';
                setTimeout(() => {
                  element.remove();
                  fetchNotifications(); // Refresh count
                }, 300);
              }
            });
          }

          markAllReadBtn.addEventListener('click', function() {
            fetch('{{ route("admin.notifications.read-all") }}', {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
              }
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                const items = notificationList.querySelectorAll('.notification-item');
                items.forEach(item => {
                  item.style.opacity = '0';
                  item.style.transform = 'translateX(20px)';
                  item.style.transition = 'all 0.3s ease';
                });
                setTimeout(() => {
                  fetchNotifications();
                }, 300);
              }
            });
          });

          // Poll every 30 seconds
          fetchNotifications();
          setInterval(fetchNotifications, 30000);
        });
      </script>

      <li class="nav-item dropdown">
        <a
          class="nav-link dropdown-toggle hide-arrow d-flex align-items-center"
          href="javascript:void(0);"
          id="adminProfileDropdown"
          data-bs-toggle="dropdown"
          aria-expanded="false">
          <span class="app-topbar-avatar">
            <img src="{{ $adminAvatar }}" alt="{{ $adminName }}">
            <span class="app-topbar-avatar-status"></span>
          </span>
        </a>
        <div class="dropdown-menu dropdown-menu-end mt-2 py-2" aria-labelledby="adminProfileDropdown" style="min-width: 260px;">
          <div class="px-4 py-2 border-bottom">
            <div class="fw-semibold">{{ $adminName }}</div>
            <small class="text-muted">{{ $adminEmail }}</small>
          </div>
          <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.profile.index', ['tab' => 'profile']) }}">
            <i class="ri ri-user-3-line"></i>
            <span>{{ __('app.account.profile') }}</span>
          </a>
          <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.profile.index', ['tab' => 'security']) }}">
            <i class="ri ri-lock-password-line"></i>
            <span>{{ __('app.account.security') }}</span>
          </a>
          <div class="dropdown-divider my-1"></div>
          <form action="{{ route('admin.logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
              <i class="ri ri-logout-box-r-line"></i>
              <span>{{ __('app.account.logout') }}</span>
            </button>
          </form>
        </div>
      </li>
    </ul>
  </div>
</nav>

<div
  class="modal fade app-search-modal"
  id="appSearchModal"
  tabindex="-1"
  aria-hidden="true"
  data-search-url="{{ route('admin.search') }}"
  data-empty-text="{{ __('app.search.status.empty') }}"
  data-loading-text="{{ __('app.search.status.loading') }}"
  data-start-typing-text="{{ __('app.search.status.start_typing') }}"
  data-error-text="{{ __('app.search.status.error') }}">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-bottom">
        <div class="w-100 d-flex align-items-center gap-3">
          <i class="ri ri-search-line ri-24px text-muted"></i>
          <input
            id="appSearchInput"
            type="text"
            class="form-control border-0 shadow-none px-0"
            placeholder="{{ __('app.topbar.search_input_placeholder') }}"
            autocomplete="off">
          <span class="badge bg-label-secondary">{{ __('app.topbar.search_shortcut') }}</span>
        </div>
        <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="appSearchResults" class="app-search-results">
          <div class="app-search-empty-state">{{ __('app.search.status.start_typing') }}</div>
        </div>
      </div>
    </div>
  </div>
</div>
