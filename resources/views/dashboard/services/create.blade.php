<x-dashboard-layout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
        <!-- Breadcrumb Section -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="container mx-auto px-4 py-4 max-w-7xl">
                <nav class="flex items-center space-x-2 rtl:space-x-reverse text-sm">
                    <a href="{{ route('dashboard.home') }}"
                        class="flex items-center text-blue-600 hover:text-blue-800 transition-colors duration-200">
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        </svg>
                        {{ t('dashboard.Home', 'الرئيسية') }}
                    </a>
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <a href="{{ route('dashboard.services.index') }}"
                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                        {{ t('dashboard.services', 'الخدمات') }}
                    </a>
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-500">إضافة خدمة جديدة</span>
                </nav>
            </div>
        </div>

        <div class="container mx-auto px-4 py-8 max-w-6xl">
            <!-- Header Section -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border-l-4 border-green-500">
                <div class="flex items-center space-x-4 rtl:space-x-reverse">
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">إضافة خدمة جديدة</h1>
                        <p class="text-gray-600 mt-1">إضافة خدمة جديدة إلى النظام مع جميع التفاصيل المطلوبة</p>
                    </div>
                </div>
            </div>

            <!-- Error Messages -->
            @if ($errors->any())
                <div
                    class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-400 p-6 rounded-xl mb-8 shadow-sm">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="mr-3">
                            <h3 class="text-red-800 font-semibold mb-2">يرجى تصحيح الأخطاء التالية:</h3>
                            <ul class="list-disc list-inside space-y-1 text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Main Form -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
                <div class="px-8 py-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <h2 class="text-2xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 ml-2 text-gray-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        معلومات الخدمة
                    </h2>
                    <p class="text-gray-600 mt-1">املأ جميع الحقول المطلوبة لإضافة خدمة جديدة</p>
                </div>

                <form action="{{ route('dashboard.services.store') }}" method="POST" enctype="multipart/form-data"
                    class="p-8">
                    @csrf
                    <div class="grid grid-cols-12 gap-x-6">
                        @include('dashboard.services._form')
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* تحسين شكل النموذج */
        .form-control {
            @apply w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-white;
        }

        .form-control:focus {
            @apply shadow-lg border-blue-500 ring-2 ring-blue-200;
        }

        /* تحسين الأزرار */
        .btn {
            @apply px-6 py-3 rounded-xl font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2;
        }

        .btn-primary {
            @apply bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 focus:ring-blue-300;
        }

        .btn-secondary {
            @apply bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 focus:ring-gray-300;
        }

        /* تحسين التسميات */
        label {
            @apply text-sm font-semibold text-gray-700 mb-2 block;
        }

        /* تحسين رسائل الخطأ */
        .text-red-600 {
            @apply text-red-500 text-sm mt-1 block;
        }

        /* تحسين الحدود والظلال */
        .border {
            @apply border-gray-200 rounded-xl shadow-sm;
        }

        /* تحسين المودال */
        .modal-content {
            @apply rounded-2xl border-0 shadow-2xl;
        }

        .modal-header {
            @apply bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200 rounded-t-2xl;
        }

        .modal-body {
            @apply p-6;
        }

        .modal-footer {
            @apply bg-gray-50 border-t border-gray-200 rounded-b-2xl;
        }
    </style>
</x-dashboard-layout>
