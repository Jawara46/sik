<footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl">
                <div
                  class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                  <div class="mb-2 mb-md-0">
                    &copy;
                    <script>
                      document.write(new Date().getFullYear());
                    </script>
                    <a href="{{ config('sik.developer_url', '#') }}" target="_blank" class="text-body fw-semibold text-decoration-none">{{ config('sik.developer', 'Yazid Digital') }}</a>
                  </div>
                  <div>
                    <span class="text-muted">{{ config('sik.app_name', 'SIK-T') }} v{{ config('sik.version', '1.0.0') }}</span>
                  </div>
                </div>
              </div>
            </footer>