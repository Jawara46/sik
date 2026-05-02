<div class="row mb-4">
  <div class="col-12">
    <div class="nav-align-top">
      <ul class="nav nav-pills nav-fill" role="tablist">
        <li class="nav-item">
          <a href="{{ route('admin.whatsapp.connection.index') }}" class="nav-link {{ request()->routeIs('admin.whatsapp.connection.*') ? 'active' : '' }}" role="tab">
            <i class="ri-macbook-line me-1"></i> Koneksi Gateway
          </a>
        </li>
        <li class="nav-item">
          <a href="{{ route('admin.whatsapp.blast.index') }}" class="nav-link {{ request()->routeIs('admin.whatsapp.blast.*') ? 'active' : '' }}" role="tab">
            <i class="ri-message-3-line me-1"></i> Blast Notifikasi
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Route::is('admin.whatsapp.history.*') ? 'active' : '' }}" href="{{ route('admin.whatsapp.history.index') }}">
            <i class="ri ri-history-line me-1"></i> Riwayat
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Route::is('admin.whatsapp.auto-respond.*') ? 'active' : '' }}" href="{{ route('admin.whatsapp.auto-respond.index') }}">
            <i class="ri ri-robot-line me-1"></i> Auto-Respond
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>
