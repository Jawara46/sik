<!-- Core JS -->


    <!-- build:js assets/vendor/js/theme.js  -->

    <script src="{{ asset("assets/vendor/libs/jquery/jquery.js") }}"></script>

    <script src="{{ asset("assets/vendor/libs/popper/popper.js") }}"></script>
    <script src="{{ asset("assets/vendor/js/bootstrap.js") }}"></script>
    <script src="{{ asset("assets/vendor/libs/node-waves/node-waves.js") }}"></script>

    <script src="{{ asset("assets/vendor/libs/@algolia/autocomplete-js.js") }}"></script>

    <script src="{{ asset("assets/vendor/libs/pickr/pickr.js") }}"></script>

    <script src="{{ asset("assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js") }}"></script>

    <script src="{{ asset("assets/vendor/libs/hammer/hammer.js") }}"></script>

    <script src="{{ asset("assets/vendor/libs/i18n/i18n.js") }}"></script>

    <script src="{{ asset("assets/vendor/js/menu.js") }}"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset("assets/vendor/libs/apex-charts/apexcharts.js") }}"></script>
    <script src="{{ asset("assets/vendor/libs/swiper/swiper.js") }}"></script>

    <!-- Main JS -->

    <script src="{{ asset("assets/js/main.js") }}"></script>

    <!-- Page JS -->
    <script src="{{ asset("assets/js/dashboards-analytics.js") }}"></script>

    <script>
      (function () {
        function resolveMenuOptions() {
          const templateKey = 'templateCustomizer-' + window.templateName + '--ShowDropdownOnHover';
          const storedValue = localStorage.getItem(templateKey);

          return {
            orientation: document.getElementById('layout-menu')?.classList.contains('menu-horizontal') ? 'horizontal' : 'vertical',
            closeChildren: document.getElementById('layout-menu')?.classList.contains('menu-horizontal') ? true : false,
            accordion: false,
            showDropdownOnHover: storedValue !== null
              ? storedValue === 'true'
              : window.templateCustomizer !== undefined
                ? window.templateCustomizer.settings.defaultShowDropdownOnHover
                : true
          };
        }

        function reinitializeSidebarMenu() {
          const layoutMenu = document.getElementById('layout-menu');
          if (!layoutMenu || typeof window.Menu === 'undefined') {
            return;
          }

          const existingMenu = layoutMenu.menuInstance || window.Helpers?.mainMenu || null;
          if (existingMenu && typeof existingMenu.destroy === 'function') {
            existingMenu.destroy();
          }

          const menu = new window.Menu(layoutMenu, resolveMenuOptions());
          if (window.Helpers) {
            window.Helpers.mainMenu = menu;
            window.Helpers.scrollToActive(false);
          }
          menu.update();
        }

        function ensureMaterialToast() {
          if (window.M && typeof window.M.toast === 'function') {
            return;
          }

          const existingContainer = document.getElementById('material-toast-container');
          const container = existingContainer || document.createElement('div');
          container.id = 'material-toast-container';
          container.className = 'toast-container position-fixed top-0 end-0 p-3';
          container.style.zIndex = '1095';

          if (!existingContainer) {
            document.body.appendChild(container);
          }

          const toneClasses = {
            success: 'text-bg-success',
            danger: 'text-bg-danger',
            error: 'text-bg-danger',
            warning: 'text-bg-warning',
            info: 'text-bg-info'
          };

          window.M = window.M || {};
          window.M.toast = function (options) {
            const settings = options || {};
            const toast = document.createElement('div');
            const tone = toneClasses[settings.classes] || 'text-bg-dark';
            const wrapper = document.createElement('div');
            const body = document.createElement('div');
            const closeButton = document.createElement('button');

            toast.className = 'toast align-items-center border-0 ' + tone;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            wrapper.className = 'd-flex';
            body.className = 'toast-body';
            body.textContent = String(settings.html || '');
            closeButton.type = 'button';
            closeButton.className = 'btn-close btn-close-white me-2 m-auto';
            closeButton.setAttribute('data-bs-dismiss', 'toast');
            closeButton.setAttribute('aria-label', 'Close');
            wrapper.appendChild(body);
            wrapper.appendChild(closeButton);
            toast.appendChild(wrapper);

            container.appendChild(toast);
            const instance = new bootstrap.Toast(toast, {
              autohide: true,
              delay: Number(settings.displayLength || 3500)
            });

            toast.addEventListener('hidden.bs.toast', function () {
              toast.remove();
            });

            instance.show();
          };
        }

        function initializeProfileDropdown() {
          document
            .querySelectorAll('[data-bs-toggle="dropdown"]')
            .forEach(function (dropdownTrigger) {
              bootstrap.Dropdown.getOrCreateInstance(dropdownTrigger);
            });
        }

        function initializeLocaleSwitcher() {
          document.querySelectorAll('.locale-switcher').forEach(function (button) {
            button.addEventListener('click', async function () {
              const locale = button.dataset.locale;
              const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

              if (!locale || !csrf) {
                return;
              }

              try {
                const response = await fetch(@json(route('locale.update')), {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                  },
                  body: JSON.stringify({ locale: locale })
                });

                if (!response.ok) {
                  throw new Error('Locale switch failed');
                }

                window.location.reload();
              } catch (error) {
                window.M.toast({ html: @json(__('app.topbar.language_switch_failed')), classes: 'danger' });
              }
            });
          });
        }

        function initializeAppSearch() {
          const modalElement = document.getElementById('appSearchModal');
          const trigger = document.querySelector('[data-app-search-trigger="true"]');
          const input = document.getElementById('appSearchInput');
          const results = document.getElementById('appSearchResults');

          if (!modalElement || !trigger || !input || !results || typeof bootstrap === 'undefined') {
            return;
          }

          const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
          const searchUrl = modalElement.dataset.searchUrl;
          const emptyText = modalElement.dataset.emptyText || 'No results.';
          const loadingText = modalElement.dataset.loadingText || 'Loading...';
          const startTypingText = modalElement.dataset.startTypingText || 'Start typing...';
          const errorText = modalElement.dataset.errorText || 'Search error.';
          let debounceTimer = null;

          function escapeHtml(value) {
            return String(value)
              .replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#039;');
          }

          function renderEmpty(message) {
            results.innerHTML = '<div class="app-search-empty-state">' + escapeHtml(message) + '</div>';
          }

          function renderGroups(groups) {
            if (!Array.isArray(groups) || groups.length === 0) {
              renderEmpty(emptyText);
              return;
            }

            results.innerHTML = groups.map(function (group) {
              const itemsHtml = (group.items || []).map(function (item) {
                return [
                  '<a href="' + escapeHtml(item.url || '#') + '" class="app-search-result-item">',
                  '  <span class="app-search-result-icon"><i class="' + escapeHtml(item.icon || 'ri-search-line') + ' ri-lg"></i></span>',
                  '  <span class="flex-grow-1 min-w-0">',
                  '    <span class="d-flex align-items-center justify-content-between gap-3">',
                  '      <span class="fw-semibold text-truncate">' + escapeHtml(item.title || '') + '</span>',
                  '      <span class="badge bg-label-primary rounded-pill flex-shrink-0">' + escapeHtml(item.badge || '') + '</span>',
                  '    </span>',
                  '    <span class="text-muted d-block small text-truncate mt-1">' + escapeHtml(item.subtitle || '') + '</span>',
                  '  </span>',
                  '</a>'
                ].join('');
              }).join('');

              return [
                '<div class="app-search-results-group">',
                '  <div class="app-search-results-group-title">' + escapeHtml(group.label || '') + '</div>',
                '  <div class="d-grid gap-2">' + itemsHtml + '</div>',
                '</div>'
              ].join('');
            }).join('');
          }

          async function fetchResults(query) {
            const normalizedQuery = query.trim();

            if (normalizedQuery.length < 2) {
              try {
                const response = await fetch(searchUrl, {
                  headers: { 'Accept': 'application/json' }
                });
                const payload = await response.json();
                renderGroups(payload.groups || []);
              } catch (error) {
                renderEmpty(errorText);
              }
              return;
            }

            renderEmpty(loadingText);

            try {
              const url = new URL(searchUrl, window.location.origin);
              url.searchParams.set('q', normalizedQuery);

              const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' }
              });

              if (!response.ok) {
                throw new Error('Search request failed');
              }

              const payload = await response.json();
              renderGroups(payload.groups || []);
            } catch (error) {
              renderEmpty(errorText);
            }
          }

          trigger.addEventListener('click', function () {
            modal.show();
          });

          document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
              event.preventDefault();
              modal.show();
            }
          });

          modalElement.addEventListener('shown.bs.modal', function () {
            input.focus();
            fetchResults('');
          });

          modalElement.addEventListener('hidden.bs.modal', function () {
            input.value = '';
            renderEmpty(startTypingText);
          });

          input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(function () {
              fetchResults(input.value);
            }, 220);
          });
        }

        function initializePasswordToggles() {
          document.querySelectorAll('[data-password-toggle]').forEach(function (toggleButton) {
            if (toggleButton.dataset.passwordToggleInitialized === 'true') {
              return;
            }

            const targetSelector = toggleButton.getAttribute('data-target');
            const targetInput = targetSelector ? document.querySelector(targetSelector) : null;
            const icon = toggleButton.querySelector('i');
            const showLabel = toggleButton.getAttribute('data-label-show') || 'Show password';
            const hideLabel = toggleButton.getAttribute('data-label-hide') || 'Hide password';

            if (!targetInput) {
              return;
            }

            function syncState() {
              const isVisible = targetInput.getAttribute('type') === 'text';

              toggleButton.setAttribute('aria-label', isVisible ? hideLabel : showLabel);
              toggleButton.setAttribute('title', isVisible ? hideLabel : showLabel);

              if (icon) {
                icon.classList.toggle('ri-eye-line', !isVisible);
                icon.classList.toggle('ri-eye-off-line', isVisible);
              }
            }

            toggleButton.addEventListener('click', function () {
              const currentType = targetInput.getAttribute('type') === 'text' ? 'text' : 'password';
              targetInput.setAttribute('type', currentType === 'password' ? 'text' : 'password');
              syncState();
              targetInput.focus({ preventScroll: true });
            });

            toggleButton.dataset.passwordToggleInitialized = 'true';
            syncState();
          });
        }

        function initializeUiEnhancements() {
          ensureMaterialToast();
          reinitializeSidebarMenu();
          initializeProfileDropdown();
          initializeLocaleSwitcher();
          initializeAppSearch();
          initializePasswordToggles();

          @if (session('status'))
            window.M.toast({ html: @json(session('status')), classes: 'success' });
          @endif

          @if ($errors->any())
            @foreach ($errors->all() as $message)
              window.M.toast({ html: @json($message), classes: 'danger', displayLength: 5500 });
            @endforeach
          @endif
        }

        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', initializeUiEnhancements, { once: true });
        } else {
          initializeUiEnhancements();
        }
      })();
    </script>
