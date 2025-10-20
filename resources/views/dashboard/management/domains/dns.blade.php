{{-- resources/views/dashboard/management/domains/dns.blade.php --}}
@php use Illuminate\Support\Str; @endphp
<x-dashboard-layout>
  <div class="container mx-auto py-6 max-w-5xl space-y-6">
    <div>
      <a href="{{ route('dashboard.domains.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition">
        <i class="ti ti-arrow-left me-1"></i>{{ __('Back to domains') }}
      </a>
    </div>

    <div>
      <h1 class="text-2xl font-bold mb-2">{{ __('Change DNS for :domain', ['domain' => $domain->domain_name]) }}</h1>
      <p class="text-sm text-gray-500">
        {{ __('Update the nameservers or leave a note for the operations team to process this request.') }}
      </p>
    </div>

    @if (session('success'))
      <div class="bg-green-100 text-green-800 p-4 rounded">{{ session('success') }}</div>
    @endif

    @if (!empty($remoteDns))
      <div class="bg-slate-50 border border-slate-200 p-4 rounded">
        <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
          <div class="text-sm font-medium text-slate-700">
            {{ __('Registrar DNS snapshot') }}
          </div>
          @if (!empty($remoteDns['fetched_at']))
            <div class="text-xs text-slate-500">
              {{ __('Fetched at :date', [
                  'date' => $remoteDns['fetched_at']->timezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i'),
              ]) }}
            </div>
          @endif
        </div>

        @if (!empty($remoteDns['error']))
          <div class="mt-2 text-sm text-red-600">
            {{ $remoteDns['error'] }}
          </div>
        @else
          @php
            $statusKey = $remoteDns['status'] ?? null;
            $statusMap = [
              'default' => __('Provider default (Use our name servers)'),
              'custom' => __('Custom nameservers enabled'),
              'park' => __('Parked at provider'),
            ];
            $statusText = $statusKey ? ($statusMap[$statusKey] ?? Str::title($statusKey)) : __('Unknown');
          @endphp
          <div class="mt-2 text-sm text-slate-600">
            {{ __('Mode: :status', ['status' => $statusText]) }}
          </div>
          <div class="mt-2">
            @if (!empty($remoteDns['nameservers']))
              <ul class="text-sm text-slate-700 space-y-1 list-disc ps-5">
                @foreach ($remoteDns['nameservers'] as $ns)
                  <li>{{ $ns }}</li>
                @endforeach
              </ul>
            @else
              <div class="text-sm text-slate-500">
                {{ __('Registrar did not return explicit nameserver records.') }}
              </div>
            @endif
          </div>
        @endif
      </div>
    @endif

    @if ($domain->dns_last_synced_at)
      <div class="bg-blue-50 text-blue-800 border border-blue-200 p-4 rounded">
        <div class="text-sm">
          {{ __('Last sync with registrar on :date', ['date' => $domain->dns_last_synced_at->timezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i')]) }}
        </div>
        @if ($domain->dns_last_note)
          <div class="text-xs mt-1 text-blue-700">
            {{ __('Last note: :note', ['note' => $domain->dns_last_note]) }}
          </div>
        @endif
      </div>
    @endif

    @if ($errors->any())
      <div class="bg-red-100 text-red-800 p-4 rounded" role="alert" aria-live="polite">
        <ul class="ps-5 list-disc space-y-1">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('dashboard.domains.dns.update', $domain->id) }}" method="POST" class="bg-white rounded-lg shadow p-6 space-y-6" novalidate>
      @csrf
      @method('PUT')

      <section>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
          <div>
            <h2 class="text-lg font-semibold">{{ __('Nameservers') }}</h2>
            <p class="text-sm text-gray-500">
              {{ __('Provide at least two nameservers. Additional entries are optional (up to 12).') }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <select id="ns-preset" class="form-control min-w-44">
              <option value="">{{ __('Choose preset (optional)') }}</option>
              <option value="cloudflare">Cloudflare</option>
              <option value="palgoals">Palgoals (Default)</option>
            </select>

            <button type="button" id="add-nameserver"
              class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
              <i class="ti ti-plus me-1"></i>{{ __('Add nameserver') }}
            </button>
          </div>
        </div>

        @php
          $requiredCount = $minNameservers ?? 2;
          $maxSlots = $maxNameservers ?? 12;
          $oldNs = array_values(old('nameservers', $nameservers ?? []));
          $displaySlots = min($maxSlots, max($requiredCount, count($oldNs)));
          $oldNs = array_pad($oldNs, $displaySlots, '');
        @endphp
        <div id="nameserver-fields" class="grid grid-cols-1 gap-4 md:grid-cols-2" data-min="{{ $requiredCount }}" data-max="{{ $maxSlots }}" data-fixed-count="{{ $requiredCount }}" aria-describedby="ns-help">
          @foreach ($oldNs as $index => $nameserver)
            @php $isFixed = $index < $requiredCount; @endphp
            <div class="space-y-1 ns-item" data-fixed="{{ $isFixed ? 'true' : 'false' }}">
              <label for="nameserver_{{ $index }}" class="flex items-center gap-2 text-sm font-medium text-gray-700">
                <span class="ns-label-text">{{ __('Nameserver :number', ['number' => $index + 1]) }}</span>
                <span class="badge-placeholder inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-600 {{ $index < $requiredCount ? '' : 'hidden' }}">
                  {{ __('Required') }}
                </span>
              </label>
              <div class="flex gap-2">
                <input
                  type="text"
                  name="nameservers[{{ $index }}]"
                  id="nameserver_{{ $index }}"
                  value="{{ $nameserver }}"
                  class="form-control flex-1"
                  inputmode="url"
                  dir="ltr"
                  placeholder="ns{{ $index + 1 }}.example.com"
                  pattern="^([a-z0-9-]+\.)+[a-z]{2,}$"
                  aria-invalid="false"
                />
                <button
                  type="button"
                  class="btn btn-secondary !px-2 remove-ns {{ $isFixed ? 'opacity-0 pointer-events-none' : '' }}"
                  data-fixed="{{ $isFixed ? 'true' : 'false' }}"
                  title="{{ __('Remove') }}"
                >
                  <i class="ti ti-x"></i>
                </button>
              </div>
              <p class="text-xs text-gray-500">{{ __('Example: ns1.example.com') }}</p>
            </div>
          @endforeach
        </div>

        <div id="ns-help" class="flex items-center justify-between mt-2">
          <p class="text-xs text-gray-500">
            {{ __('DNS updates usually take effect after the registry processes them. You can add up to :max nameservers.', ['max' => $maxSlots]) }}
          </p>
          <p id="ns-counter" class="text-xs text-gray-500"></p>
        </div>

        <div id="ns-errors" class="hidden mt-3 bg-red-50 text-red-700 border border-red-200 rounded p-3 text-sm" role="alert" aria-live="polite"></div>
      </section>

      <section class="space-y-2">
        <label for="dns-notes" class="text-sm font-medium text-gray-700">{{ __('Internal note (optional)') }}</label>
        <textarea id="dns-notes" name="notes" rows="4" class="form-control"
          placeholder="{{ __('Share any additional instructions for the team handling this change.') }}">{{ old('notes', $domain->dns_last_note) }}</textarea>
      </section>

      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="text-sm text-gray-500">
          {{ __('DNS updates may require manual confirmation with the registrar until automation is completed.') }}
        </div>
        <div class="flex gap-2">
          <a href="{{ route('dashboard.domains.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
          <button type="submit" class="btn btn-primary">{{ __('Save DNS settings') }}</button>
        </div>
      </div>
    </form>
  </div>

  {{-- Template for dynamically added nameserver rows --}}
  <template id="ns-template">
    <div class="space-y-1 ns-item" data-fixed="false">
      <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
        <span class="ns-label-text"></span>
        <span class="badge-placeholder inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-600 hidden"></span>
      </label>
      <div class="flex gap-2">
        <input type="text" class="form-control flex-1" inputmode="url" dir="ltr" placeholder="" pattern="^([a-z0-9-]+\.)+[a-z]{2,}$" aria-invalid="false" />
        <button type="button" class="btn btn-secondary !px-2 remove-ns" data-fixed="false" title="{{ __('Remove') }}">
          <i class="ti ti-x"></i>
        </button>
      </div>
      <p class="text-xs text-gray-500">{{ __('Example: ns1.example.com') }}</p>
    </div>
  </template>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const fieldsContainer = document.getElementById('nameserver-fields');
      const addButton       = document.getElementById('add-nameserver');
      const presetSelect    = document.getElementById('ns-preset');
      const tmpl            = document.getElementById('ns-template');
      const errorsBox       = document.getElementById('ns-errors');
      const counterEl       = document.getElementById('ns-counter');

      const MIN = parseInt(fieldsContainer.dataset.min || '2', 10);
      const MAX = parseInt(fieldsContainer.dataset.max || '12', 10);
      const BASE = parseInt(fieldsContainer.dataset.fixedCount || '0', 10);
      const LABEL_TEXT = `{{ __('Nameserver') }}`;
      const REQUIRED_BADGE = `{{ __('Required') }}`;
      const MIN_MSG = `{{ __('Please provide at least :n nameservers.', ['n' => $requiredCount]) }}`;
      const MAX_MSG = `{{ __('You can add up to :n nameservers.', ['n' => $maxSlots]) }}`;
      const FIX_MSG = `{{ __('Please fix the highlighted fields.') }}`;

      /** Helpers */
      const updateNames = () => {
        const items = fieldsContainer.querySelectorAll('.ns-item');
        items.forEach((item, i) => {
          const label = item.querySelector('label');
          const input = item.querySelector('input');
          const textSpan = label?.querySelector('.ns-label-text');
          const badge = label?.querySelector('.badge-placeholder');
          const isFixed = i < BASE;

          item.dataset.fixed = isFixed ? 'true' : 'false';
          label?.setAttribute('for', `nameserver_${i}`);
          if (textSpan) {
            textSpan.textContent = `${LABEL_TEXT} ${i + 1}`;
          }
          if (badge) {
            if (i < BASE) {
              badge.textContent = REQUIRED_BADGE;
              badge.classList.remove('hidden');
            } else {
              badge.classList.add('hidden');
              badge.textContent = REQUIRED_BADGE;
            }
          }
          input.id = `nameserver_${i}`;
          input.name = `nameservers[${i}]`;
          input.placeholder = `ns${i + 1}.example.com`;

          const removeBtn = item.querySelector('.remove-ns');
          if (removeBtn) {
            removeBtn.dataset.fixed = isFixed ? 'true' : 'false';
            removeBtn.classList.toggle('opacity-0', isFixed);
            removeBtn.classList.toggle('pointer-events-none', isFixed);
          }
        });
        counterEl.textContent = `${items.length}/${MAX}`;
      };

      const showError = (msg) => {
        if (!msg) { errorsBox.classList.add('hidden'); errorsBox.textContent = ''; return; }
        errorsBox.textContent = msg;
        errorsBox.classList.remove('hidden');
      };

      const validateAll = () => {
        let ok = true;
        const items = fieldsContainer.querySelectorAll('.ns-item');
        if (items.length < MIN) { ok = false; showError(MIN_MSG); }
        else if (items.length > MAX) { ok = false; showError(MAX_MSG); }
        else {
          showError('');
        }

        // تحقق من الصيغة البسيطة
        const re = /^([a-z0-9-]+\.)+[a-z]{2,}$/i;
        items.forEach(item => {
          const input = item.querySelector('input');
          const valid = input.value.trim() === '' ? true : re.test(input.value.trim());
          input.setAttribute('aria-invalid', valid ? 'false' : 'true');
          input.classList.toggle('ring-2', !valid);
          input.classList.toggle('ring-red-400', !valid);
          if (!valid) ok = false;
        });
        return ok;
      };

      const addField = (value = '', { focus = true } = {}) => {
        const count = fieldsContainer.querySelectorAll('.ns-item').length;
        if (count >= MAX) { showError(MAX_MSG); return; }

        const node = tmpl.content.firstElementChild.cloneNode(true);
        const input = node.querySelector('input');
        const removeBtn = node.querySelector('.remove-ns');
        input.value = value || '';
        removeBtn.dataset.fixed = 'false';
        removeBtn.addEventListener('click', () => {
          node.remove();
          updateNames();
          validateAll();
        });

        fieldsContainer.appendChild(node);
        updateNames();
        validateAll();
        if (focus) {
          input.focus();
        }
      };

      // أزرار موجودة مسبقًا
      fieldsContainer.querySelectorAll('.remove-ns').forEach(btn => {
        btn.addEventListener('click', (e) => {
          if (btn.dataset.fixed === 'true') return;
          const items = fieldsContainer.querySelectorAll('.ns-item');
          e.currentTarget.closest('.ns-item').remove();
          updateNames();
          validateAll();
        });
      });

      addButton?.addEventListener('click', () => addField('', { focus: true }));

      // Presets
      presetSelect?.addEventListener('change', () => {
        const v = presetSelect.value;
        if (!v) return;

        const presets = {
          cloudflare: ['ns1.cloudflare.com', 'ns2.cloudflare.com'],
          palgoals: ['ns11.palgoals.com', 'ns12.palgoals.com'], // عدّل القيم حسب واقعك
        };

        const list = presets[v];
        if (!list) return;

        const items = Array.from(fieldsContainer.querySelectorAll('.ns-item'));
        const baseItems = items.slice(0, BASE);

        baseItems.forEach((item, idx) => {
          const input = item.querySelector('input');
          input.value = list[idx] ?? '';
        });

        items.slice(BASE).forEach(item => item.remove());

        updateNames();
        validateAll();

        if (list.length > BASE) {
          for (let i = BASE; i < list.length && i < MAX; i++) {
            addField(list[i], { focus: false });
          }
        }

        presetSelect.value = '';
      });

      // تحقق فوري عند الكتابة
      fieldsContainer.addEventListener('input', (e) => {
        if (e.target.matches('input[type="text"]')) validateAll();
      });

      // تهيئة
      updateNames();
      validateAll();

      // تأكيد قبل الإرسال لو فيه خطأ
      const form = fieldsContainer.closest('form');
      form.addEventListener('submit', (e) => {
        if (!validateAll()) {
          e.preventDefault();
          showError(FIX_MSG);
          fieldsContainer.querySelector('[aria-invalid="true"]')?.focus();
        }
      });
    });
  </script>
</x-dashboard-layout>
