<x-dashboard-layout>

  {{-- تنبيهات فلاش --}}
  @if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">
      {{ session('success') }}
    </div>
  @endif
  @if($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
      {{ $errors->first() }}
    </div>
  @endif

  <!-- العنوان وشريط البحث -->
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
    <div class="flex items-center gap-3">
      <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">مراجعات القوالب</h1>
      <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
        {{ $reviews->total() }} مراجعة
      </span>
    </div>

    <form method="GET" class="flex flex-wrap items-center gap-2">
      <input type="text" name="q" value="{{ request('q') }}"
        placeholder="بحث في التعليقات/الاسم/الإيميل"
        class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-800 dark:text-gray-100">

      <select name="approved"
        class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-800 dark:text-gray-100">
        <option value="">الحالة</option>
        <option value="1" @selected(request('approved')==='1')>معتمد</option>
        <option value="0" @selected(request('approved')==='0')>غير معتمد</option>
      </select>

      <select name="rating"
        class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-800 dark:text-gray-100">
        <option value="">التقييم</option>
        @for($i=5;$i>=1;$i--)
          <option value="{{ $i }}" @selected(request('rating')==$i)>{{ $i }}</option>
        @endfor
      </select>

      <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition">
        تصفية
      </button>

      @if(request()->query())
        <a href="{{ url()->current() }}"
           class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
           title="إعادة ضبط الفلاتر">إعادة ضبط</a>
      @endif
    </form>
  </div>

  {{-- شريط الإجراءات المجمّعة (أعلى الجدول) --}}
  <form id="bulkForm" method="POST" action="{{ route('dashboard.reviews.bulk') }}"
        class="mb-4 flex flex-wrap items-center gap-3">
    @csrf
    <label for="bulk-action-top" class="text-sm text-gray-600 dark:text-gray-300">إجراء مجمّع:</label>
    <select id="bulk-action-top" name="action"
      class="min-w-40 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
      <option value="">— اختر إجراء —</option>
      <option value="approve">اعتماد</option>
      <option value="reject">رفض</option>
      <option value="delete">حذف</option>
    </select>
    <button id="bulk-apply-top" type="submit"
      class="px-4 py-2 rounded-lg bg-gray-700 text-white disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-800 transition"
      disabled>
      تطبيق
    </button>

    <span id="selectedCount" class="text-xs text-gray-500 dark:text-gray-400">0 محدد</span>
  </form>

  <!-- جدول المراجعات -->
  <div class="overflow-x-auto bg-white dark:bg-gray-900 shadow-lg rounded-lg">
    <table class="w-full text-sm text-gray-700 dark:text-gray-200">
      <thead class="bg-gray-100/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300">
        <tr>
          <th class="p-3"><input id="checkAll" type="checkbox" aria-label="تحديد الكل"></th>
          <th class="p-3 text-right">ID</th>
          <th class="p-3 text-right">المُعلّق</th>
          <th class="p-3 text-right">البريد الإلكتروني</th>
          <th class="p-3 text-right">القالب</th>
          <th class="p-3 text-right">التقييم</th>
          <th class="p-3 text-right">الحالة</th>
          <th class="p-3 text-right">التعليق</th>
          <th class="p-3 text-right">تحكم</th>
        </tr>
      </thead>
      <tbody>
        @forelse($reviews as $review)
        <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
          <td class="p-3 align-top">
            <input class="row-check" type="checkbox" name="ids[]" value="{{ $review->id }}" form="bulkForm">
          </td>

          <td class="p-3 align-top whitespace-nowrap">{{ $review->id }}</td>

          <!-- اسم المُعلّق -->
          <td class="p-3 align-top font-medium">
            @php
              $commenterName =
                  ($review->client?->first_name ? ($review->client->first_name.' '.$review->client->last_name) : null)
                  ?? ($review->user?->name ?? null)
                  ?? ($review->author_name ?? 'غير معروف');

              $commenterType =
                  $review->client ? 'عميل' :
                  ($review->user ? 'مستخدم' : 'ضيف');
            @endphp
            {{ $commenterName }}
            <span class="ms-2 text-xs text-gray-500">({{ $commenterType }})</span>
          </td>

          <!-- البريد الإلكتروني + نسخ -->
          <td class="p-3 align-top text-xs text-gray-500 whitespace-nowrap">
            <span>{{ $review->client->email ?? $review->user->email ?? $review->author_email ?? '—' }}</span>
            @php $email = $review->client->email ?? $review->user->email ?? $review->author_email; @endphp
            @if($email)
              <button type="button" class="copy-email ml-2 inline-flex items-center gap-1 px-2 py-1 rounded border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                      data-email="{{ $email }}" title="نسخ البريد">
                <svg viewBox="0 0 20 20" class="w-3.5 h-3.5" fill="currentColor" aria-hidden="true">
                  <path d="M4 4a2 2 0 012-2h5a2 2 0 012 2v3h-2V4H6v10h5v2H6a2 2 0 01-2-2V4z"></path>
                  <path d="M9 7a2 2 0 012-2h3a2 2 0 012 2v9a2 2 0 01-2 2h-3a2 2 0 01-2-2V7zm2 0v9h3V7h-3z"></path>
                </svg>
              </button>
            @endif
          </td>

          <!-- رابط القالب (slug من الترجمة مع fallback) -->
          <td class="p-3 align-top">
            @php
              $trans = $review->template?->translations
                        ?->firstWhere('locale', app()->getLocale())
                        ?? $review->template?->translations?->first();
              $slug  = $trans?->slug;
              $loc   = $trans?->locale;
            @endphp

            @if($slug)
              <a href="{{ route('template.show', ['slug' => $slug]) }}" target="_blank" rel="noopener noreferrer"
                 class="inline-flex items-center gap-2 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                <svg viewBox="0 0 20 20" class="w-3.5 h-3.5" fill="currentColor" aria-hidden="true">
                  <path d="M12.293 2.293a1 1 0 011.414 0l4 4a1 1 0 01-.293 1.707L10 15.414V18h2.586l7.121-7.121a3 3 0 000-4.243l-4-4a3 3 0 00-4.243 0L8.586 4.707 10 6.121l2.293-2.293z"></path>
                  <path d="M7 3a2 2 0 00-2 2v10a2 2 0 002 2h6a2 2 0 002-2v-3h-2v3H7V5h3V3H7z"></path>
                </svg>
                عرض القالب
                @if($loc)
                  <span class="px-1.5 py-0.5 text-[10px] rounded bg-blue-800/70">{{ $loc }}</span>
                @endif
              </a>
            @else
              <span class="text-gray-400">—</span>
            @endif
          </td>

          <!-- التقييم -->
          <td class="p-3 align-top">
            <div class="flex items-center gap-1">
              @for($i=1; $i<=5; $i++)
                <svg class="w-4 h-4 {{ $i <= (int)$review->rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.13 3.478a1 1 0 00.95.69h3.658c.969 0 1.371 1.24.588 1.81l-2.96 2.15a1 1 0 00-.364 1.118l1.13 3.478c.3.921-.755 1.688-1.54 1.118l-2.96-2.15a1 1 0 00-1.176 0l-2.96 2.15c-.785.57-1.84-.197-1.54-1.118l1.13-3.478a1 1 0 00-.364-1.118L2.72 8.905c-.783-.57-.38-1.81.588-1.81h3.658a1 1 0 00.95-.69l1.13-3.478z"/>
                </svg>
              @endfor
            </div>
          </td>

          <!-- الحالة -->
          <td class="p-3 align-top">
            @if($review->approved)
              <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">معتمد</span>
            @else
              <span class="px-2 py-1 text-xs rounded-full bg-gray-200 text-gray-700">غير معتمد</span>
            @endif
          </td>

          <!-- التعليق: إظهار المزيد -->
          @php
    $cleanComment = \Illuminate\Support\Str::of($review->comment ?? '')
                      ->trim()
                      ->trim('"')
                      ->toString();
@endphp
<td class="p-3 align-top max-w-[380px]">
    @if($cleanComment)
        <details class="group">
            <summary class="cursor-pointer list-none text-gray-700 dark:text-gray-200">
                <span class="line-clamp-1 group-open:line-clamp-none truncate" title="{{ e($cleanComment) }}">
                    {{ e($cleanComment) }}
                </span>
                <span class="text-xs text-blue-600 dark:text-blue-400 group-open:hidden">إظهار المزيد</span>
                <span class="text-xs text-blue-600 dark:text-blue-400 hidden group-open:inline">إخفاء</span>
            </summary>
        </details>
    @else
        <span class="text-gray-400">—</span>
    @endif
</td>

          <!-- التحكم -->
          <td class="p-3 align-top">
            <div class="flex flex-wrap gap-2">
              @if(!$review->approved)
                <button type="submit" form="approve-{{ $review->id }}"
                        class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-lg text-xs transition">
                  <svg viewBox="0 0 20 20" class="w-3.5 h-3.5" fill="currentColor" aria-hidden="true"><path d="M16.707 5.293a1 1 0 010 1.414l-7.5 7.5a1 1 0 01-1.414 0l-3-3 1.414-1.414L8.5 11.586l6.793-6.793a1 1 0 011.414 0z"/></svg>
                  اعتماد
                </button>
              @else
                <button type="submit" form="reject-{{ $review->id }}"
                        class="inline-flex items-center gap-1 bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-lg text-xs transition">
                  <svg viewBox="0 0 20 20" class="w-3.5 h-3.5" fill="currentColor" aria-hidden="true"><path d="M4.293 4.293l11.414 11.414-1.414 1.414L2.879 5.707l1.414-1.414z"/><path d="M15.707 4.293L4.293 15.707l1.414 1.414L17.121 5.707l-1.414-1.414z"/></svg>
                  إلغاء الاعتماد
                </button>
              @endif

              <button type="submit" form="delete-{{ $review->id }}"
                      class="inline-flex items-center gap-1 bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-xs transition"
                      onclick="return confirm('حذف؟')">
                <svg viewBox="0 0 20 20" class="w-3.5 h-3.5" fill="currentColor" aria-hidden="true"><path d="M6 7h2v9H6V7zm6 0h2v9h-2V7z"/><path d="M4 5h12v2H4V5zm3-2h6v2H7V3zm1 16a2 2 0 01-2-2V7h8v10a2 2 0 01-2 2H8z"/></svg>
                حذف
              </button>
            </div>
          </td>
        </tr>
        @empty
          <tr>
            <td colspan="9" class="p-6 text-center text-gray-500 dark:text-gray-400">لا توجد مراجعات حالياً.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- شريط الإجراءات المجمّعة (أسفل الجدول) اختياري -->
  <form id="bulkFormBottom" method="POST" action="{{ route('dashboard.reviews.bulk') }}"
        class="mt-4 flex flex-wrap items-center gap-3">
    @csrf
    <label for="bulk-action-bottom" class="text-sm text-gray-600 dark:text-gray-300">إجراء مجمّع:</label>
    <select id="bulk-action-bottom" name="action"
      class="min-w-40 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
      <option value="">— اختر إجراء —</option>
      <option value="approve">اعتماد</option>
      <option value="reject">رفض</option>
      <option value="delete">حذف</option>
    </select>
    <button id="bulk-apply-bottom" type="submit"
      class="px-4 py-2 rounded-lg bg-gray-700 text-white disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-800 transition"
      disabled>تطبيق</button>

    {{-- سيتم نسخ ids[] المختارة قبل الإرسال --}}
    {{-- <input type="hidden" id="idsClonerTarget"> --}}
  </form>

  <!-- ترقيم الصفحات -->
  <div class="mt-4">
    {{ $reviews->links('pagination::tailwind') }}
  </div>

  <!-- الفورمات الفردية -->
  @foreach($reviews as $review)
    <form id="approve-{{ $review->id }}" method="POST" action="{{ route('dashboard.reviews.approve',$review) }}">
      @csrf @method('PATCH')
    </form>
    <form id="reject-{{ $review->id }}" method="POST" action="{{ route('dashboard.reviews.reject',$review) }}">
      @csrf @method('PATCH')
    </form>
    <form id="delete-{{ $review->id }}" method="POST" action="{{ route('dashboard.reviews.destroy',$review) }}">
      @csrf @method('DELETE')
    </form>
  @endforeach

  {{-- سكربت: تحديد الكل، عدّاد المحدد، تمكين "تطبيق"، نسخ البريد، ونسخ ids للنموذج السفلي --}}
  <script>
    const master      = document.getElementById('checkAll');
    const rowChecks   = Array.from(document.querySelectorAll('.row-check'));
    const bulkFormTop = document.getElementById('bulkForm');
    const bulkFormBot = document.getElementById('bulkFormBottom');

    const applyTop    = document.getElementById('bulk-apply-top');
    const applyBot    = document.getElementById('bulk-apply-bottom');
    const selectTop   = document.getElementById('bulk-action-top');
    const selectBot   = document.getElementById('bulk-action-bottom');
    const selectedLbl = document.getElementById('selectedCount');

    function anyChecked() { return rowChecks.some(c => c.checked); }
    function selectedCount() { return rowChecks.filter(c => c.checked).length; }

    function refreshMaster() {
      const total = rowChecks.length;
      const checked = selectedCount();
      if (master) {
        master.checked = (checked === total && total > 0);
        master.indeterminate = (checked > 0 && checked < total);
      }
      if (selectedLbl) selectedLbl.textContent = checked + ' محدد';
    }

    function toggleApplyButtons() {
      applyTop.disabled = !(anyChecked() && selectTop.value);
      applyBot.disabled = !(anyChecked() && selectBot.value);
      refreshMaster();
    }

    // تحديد الكل
    if (master) {
      master.addEventListener('change', () => {
        rowChecks.forEach(c => c.checked = master.checked);
        toggleApplyButtons();
      });
    }

    // أحداث على الصفوف والسليكتات
    rowChecks.forEach(c => c.addEventListener('change', toggleApplyButtons));
    [selectTop, selectBot].forEach(s => s.addEventListener('change', toggleApplyButtons));
    toggleApplyButtons();

    // منع إرسال بدون اختيار إجراء/بدون عناصر + تأكيد الحذف
    function handleBulkSubmit(e, selectEl, formEl) {
      if (!anyChecked() || !selectEl.value) {
        e.preventDefault();
        alert('يرجى اختيار عناصر وتحديد الإجراء أولاً.');
        return;
      }
      if (selectEl.value === 'delete' && !confirm('حذف نهائي للمحدد؟')) {
        e.preventDefault();
        return;
      }

      // لو كان الإرسال من النموذج السفلي، نضيف ids[] المختارة إليه قبل الإرسال
      if (formEl === bulkFormBot) {
        formEl.querySelectorAll('input[name="ids[]"]').forEach(n => n.remove());
        rowChecks.filter(c => c.checked).forEach(c => {
          const clone = document.createElement('input');
          clone.type = 'hidden';
          clone.name = 'ids[]';
          clone.value = c.value;
          formEl.appendChild(clone);
        });
      }
    }

    bulkFormTop.addEventListener('submit', e => handleBulkSubmit(e, selectTop, bulkFormTop));
    bulkFormBot.addEventListener('submit', e => handleBulkSubmit(e, selectBot, bulkFormBot));

    // نسخ البريد
    document.addEventListener('click', async (e) => {
      if (e.target.closest('.copy-email')) {
        const btn = e.target.closest('.copy-email');
        const email = btn.getAttribute('data-email');
        try {
          await navigator.clipboard.writeText(email);
          btn.classList.add('bg-green-50','dark:bg-green-900/20');
          setTimeout(()=>btn.classList.remove('bg-green-50','dark:bg-green-900/20'), 800);
        } catch (err) {
          alert('تعذر نسخ البريد — يرجى النسخ يدويًا.');
        }
      }
    });
  </script>

</x-dashboard-layout>
