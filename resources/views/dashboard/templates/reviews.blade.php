<x-dashboard-layout>

  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">مراجعات القوالب</h1>

    <form method="GET" class="flex gap-2">
      <input type="text" name="q" value="{{ request('q') }}" placeholder="بحث في التعليقات/الاسم/الإيميل" class="input">
      <select name="approved" class="input">
        <option value="">الحالة</option>
        <option value="1" @selected(request('approved')==='1')>معتمد</option>
        <option value="0" @selected(request('approved')==='0')>غير معتمد</option>
      </select>
      <select name="rating" class="input">
        <option value="">التقييم</option>
        @for($i=5;$i>=1;$i--)
          <option value="{{ $i }}" @selected(request('rating')==$i)>{{ $i }}</option>
        @endfor
      </select>
      <button class="btn">تصفية</button>
    </form>
  </div>

  {{-- 1) فورم الـ bulk مستقل --}}
  <form id="bulkForm" method="POST" action="{{ route('dashboard.reviews.bulk') }}" class="mb-3 flex gap-2">
    @csrf
    <button name="action" value="approve" class="btn">اعتماد المحدد</button>
    <button name="action" value="reject"  class="btn">رفض المحدد</button>
    <button name="action" value="delete"  class="btn btn-danger" onclick="return confirm('حذف نهائي؟')">حذف المحدد</button>
  </form>

  {{-- 2) الجدول بدون أي form مغلِّف --}}
  <table class="table-auto w-full text-sm">
    <thead>
      <tr class="border-b">
        <th>
          <input type="checkbox" onclick="document.querySelectorAll('.row-check').forEach(c=>c.checked=this.checked)">
        </th>
        <th>ID</th>
        <th>القالب</th>
        <th>الكاتب</th>
        <th>التقييم</th>
        <th>الحالة</th>
        <th>التعليق</th>
        <th>تحكم</th>
      </tr>
    </thead>
    <tbody>
      @foreach($reviews as $r)
      <tr class="border-b">
        {{-- اربط كل checkbox مع bulkForm --}}
        <td><input class="row-check" type="checkbox" name="ids[]" value="{{ $r->id }}" form="bulkForm"></td>
        <td>{{ $r->id }}</td>
        <td>#{{ $r->template_id }}</td>
        <td>
          @if($r->client)
            {{ $r->client->first_name }} {{ $r->client->last_name }} (عميل)
          @elseif($r->user)
            {{ $r->user->name }} (مستخدم)
          @else
            {{ $r->author_name }} (ضيف)
          @endif
        </td>
        <td>{{ $r->rating }}/5</td>
        <td>
          @if($r->approved)
            <span class="text-green-600 font-semibold">معتمد</span>
          @else
            <span class="text-gray-500">غير معتمد</span>
          @endif
        </td>
        <td class="max-w-[480px] truncate" title="{{ $r->comment }}">{{ $r->comment }}</td>
        <td class="flex gap-2">
          @if(!$r->approved)
            {{-- الزر يستدعي فورم اعتماد خارجي --}}
            <button type="submit" class="btn" form="approve-{{ $r->id }}">اعتماد</button>
          @else
            <button type="submit" class="btn" form="reject-{{ $r->id }}">إلغاء الاعتماد</button>
          @endif
          <button type="submit" class="btn btn-danger" form="delete-{{ $r->id }}" onclick="return confirm('حذف؟')">حذف</button>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="mt-4">
    {{ $reviews->links() }}
  </div>

  {{-- 3) الفورمات الفردية خارج الجدول (ولا يوجد تعشيش) --}}
  @foreach($reviews as $r)
    <form id="approve-{{ $r->id }}" method="POST" action="{{ route('dashboard.reviews.approve',$r) }}">
      @csrf @method('PATCH')
    </form>

    <form id="reject-{{ $r->id }}" method="POST" action="{{ route('dashboard.reviews.reject',$r) }}">
      @csrf @method('PATCH')
    </form>

    <form id="delete-{{ $r->id }}" method="POST" action="{{ route('dashboard.reviews.destroy',$r) }}">
      @csrf @method('DELETE')
    </form>
  @endforeach

</x-dashboard-layout>
