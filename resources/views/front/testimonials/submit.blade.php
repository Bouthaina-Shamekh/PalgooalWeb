@php
    $pageTitle = t('frontend.testimonials.submit_title', 'شاركنا رأيك');
    $pageDescription = t('frontend.testimonials.submit_description', 'ساعدنا على تحسين خدماتنا عبر مشاركة تجربتك.');
    $pageKeywords = t('frontend.testimonials.submit_keywords', 'تقييم العملاء, مشاركة رأي, خدمة العملاء');
@endphp

<x-template.layouts.index-layouts :title="$pageTitle" :description="$pageDescription" :keywords="$pageKeywords"
    ogImage="{{ asset('assets/tamplate/images/template.webp') }}">
    <section
        class="relative overflow-hidden bg-gradient-to-br from-indigo-50 via-white to-emerald-50 py-24 px-4 sm:px-8 lg:px-24">
        <div class="absolute inset-0 pointer-events-none opacity-30">
            <div class="absolute top-10 left-10 w-32 h-32 rounded-full bg-indigo-200 blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-40 h-40 rounded-full bg-emerald-200 blur-3xl"></div>
        </div>

        <div
            class="relative max-w-4xl mx-auto bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/70">
            <div class="p-8 sm:p-12 space-y-10">
                <div class="text-center space-y-4">
                    <span
                        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold bg-indigo-50 text-indigo-600 border border-indigo-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.174c.969 0 1.371 1.24.588 1.81l-3.378 2.455a1 1 0 00-.364 1.118l1.286 3.966c.3.922-.755 1.688-1.54 1.118l-3.379-2.454a1 1 0 00-1.175 0l-3.379 2.454c-.784.57-1.838-.196-1.539-1.118l1.285-3.966a1 1 0 00-.364-1.118L2.96 9.394c-.783-.57-.38-1.81.588-1.81h4.174a1 1 0 00.95-.69l1.286-3.967z" />
                        </svg>
                        {{ t('frontend.testimonials.share_badge', 'دعوة خاصة لتقييم خدماتنا') }}
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                        {{ t('frontend.testimonials.share_heading', 'نُقدّر رأيك... شارك تجربتك معنا') }}
                    </h1>
                    <p class="text-slate-600 text-lg leading-relaxed">
                        {{ t('frontend.testimonials.share_subheading', 'يساعدنا تقييمك في تعزيز جودة خدماتنا وتقديم تجربة أفضل لك ولجميع عملائنا.') }}
                    </p>
                </div>

                @if (session('success'))
                    <div
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700 flex gap-3 items-start">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if ($errors->has('error'))
                    <div
                        class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 flex gap-3 items-start">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $errors->first('error') }}</span>
                    </div>
                @endif

                <form action="{{ route('testimonials.submit.store') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                {{ t('frontend.testimonials.form.name', 'الاسم الكامل') }}
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 transition">
                            @error('name')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                {{ t('frontend.testimonials.form.major', 'الوظيفة / المسمى') }}
                            </label>
                            <input type="text" name="major" value="{{ old('major') }}" required
                                class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 transition">
                            @error('major')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                {{ t('frontend.testimonials.form.language', 'اختر اللغة') }}
                            </label>
                            <select name="language" required
                                class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 transition">
                                <option value="">
                                    {{ t('frontend.testimonials.form.language_placeholder', 'اختر اللغة المناسبة') }}
                                </option>
                                @foreach ($languages as $language)
                                    <option value="{{ $language->code }}" @selected(old('language') === $language->code)>
                                        {{ $language->name ?? strtoupper($language->code) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('language')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                {{ t('frontend.testimonials.form.star', 'عدد النجوم (1-5)') }}
                            </label>
                            <input type="number" name="star" min="1" max="5"
                                value="{{ old('star', 5) }}" required
                                class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 transition">
                            @error('star')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            {{ t('frontend.testimonials.form.feedback', 'نص التقييم') }}
                        </label>
                        <textarea name="feedback" rows="5" required
                            class="w-full rounded-3xl border border-slate-200 bg-white/80 px-4 py-3 text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 transition">{{ old('feedback') }}</textarea>
                        @error('feedback')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            {{ t('frontend.testimonials.form.image', 'صورة رمزية (اختياري)') }}
                        </label>
                        <input type="file" name="image" accept="image/*"
                            class="w-full rounded-2xl border border-dashed border-slate-300 bg-white/70 px-4 py-3 text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 transition">
                        @error('image')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-slate-500 mt-2">
                            {{ t('frontend.testimonials.form.image_help', 'الحد الأقصى 2 ميجابايت. الصيغ المسموح بها: JPG, PNG, SVG, WebP.') }}
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="text-sm text-slate-500 space-y-1">
                            <p>
                                {{ t('frontend.testimonials.form.notice', 'بتعبئة هذا النموذج، فإنك توافق على مشاركة تقييمك في منصاتنا التسويقية.') }}
                            </p>
                            <p class="text-xs">
                                {{ t('frontend.testimonials.form.review_notice', 'لن يظهر تقييمك إلا بعد مراجعته واعتماده من فريقنا.') }}
                            </p>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3 font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:bg-primary/80 focus-visible:ring-4 focus-visible:ring-indigo-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7v10l9-5-9-5z" />
                            </svg>
                            {{ t('frontend.testimonials.form.submit', 'إرسال التقييم') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-template.layouts.index-layouts>
