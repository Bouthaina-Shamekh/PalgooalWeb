{{-- resources/views/dashboard/partials/media-picker.blade.php --}}
<div id="media-picker-backdrop" class="fixed inset-0 z-[9998] bg-black/40 hidden"></div>

<div id="media-picker-modal" role="dialog" aria-modal="true" aria-labelledby="media-picker-title" tabindex="-1"
    lang="{{ app()->getLocale() }}" dir="{{ current_dir() }}"
    data-load-more-label="{{ t('dashboard.Media_Picker_Load_More', 'Load more media') }}"
    data-no-more-label="{{ t('dashboard.Media_Picker_No_More', 'No more media') }}"
    data-load-error="{{ t('dashboard.Media_Picker_Load_Error', 'An error occurred while loading media.') }}"
    data-unnamed-label="{{ t('dashboard.Media_Picker_Unnamed', 'Unnamed') }}"
    data-file-label="{{ t('dashboard.Media_Picker_File', 'FILE') }}"
    data-upload-unavailable="{{ t('dashboard.Media_Picker_Upload_Unavailable', 'Unable to upload the file. Reload the page and try again.') }}"
    data-upload-validation-error="{{ t('dashboard.Media_Picker_Upload_Validation_Error', 'Unable to upload the file. Check it and try again.') }}"
    data-upload-success="{{ t('dashboard.Media_Picker_Upload_Success', 'The image was uploaded successfully.') }}"
    data-upload-error="{{ t('dashboard.Media_Picker_Upload_Error', 'The image upload failed. Try again.') }}"
    data-move-earlier-label="{{ t('dashboard.Media_Picker_Move_Earlier', 'Move image earlier') }}"
    data-move-later-label="{{ t('dashboard.Media_Picker_Move_Later', 'Move image later') }}"
    data-moved-position-label="{{ t('dashboard.Media_Picker_Moved_Position', 'Image moved to position :position.') }}"
    class="fixed inset-0 z-[9999] hidden items-center justify-center px-4">
    <div class="relative w-full max-w-5xl rounded-2xl bg-white dark:bg-gray-950 shadow-2xl border border-gray-200 dark:border-gray-800 max-h-[80vh] flex flex-col overflow-hidden">
        <header class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-800">
            <h2 id="media-picker-title" class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ t('dashboard.Media_Picker_Title', 'Choose media from the library') }}</h2>
            <div class="flex items-center gap-2">
                <button type="button" id="media-picker-upload-btn" data-pending-label="{{ t('dashboard.Media_Picker_Uploading', 'Uploading images…') }}" aria-describedby="media-picker-upload-help"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <span>{{ t('dashboard.Media_Picker_Upload_New', 'Upload new image') }}</span>
                </button>
                <input type="file" id="media-picker-file-input" class="hidden" accept="image/*" multiple>
                <button type="button" id="media-picker-close" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800" aria-label="{{ t('dashboard.Close', 'Close') }}">×</button>
            </div>
        </header>

        <section class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2 text-[11px]">
                    <button type="button" data-type="" class="media-picker-filter-btn rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">{{ t('dashboard.Media_Picker_All', 'All') }}</button>
                    <button type="button" data-type="image" class="media-picker-filter-btn rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">{{ t('dashboard.Media_Picker_Images', 'Images') }}</button>
                    <button type="button" data-type="video" class="media-picker-filter-btn rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">{{ t('dashboard.Media_Picker_Videos', 'Videos') }}</button>
                    <button type="button" data-type="document" class="media-picker-filter-btn rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">{{ t('dashboard.Media_Picker_Documents', 'Documents') }}</button>
                    <button type="button" data-type="other" class="media-picker-filter-btn rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">{{ t('dashboard.Media_Picker_Other', 'Other') }}</button>
                </div>
                <div class="w-full sm:w-64">
                    <input id="media-picker-search" type="text" aria-label="{{ t('dashboard.Media_Picker_Search', 'Search by name or title') }}" placeholder="{{ t('dashboard.Media_Picker_Search_Placeholder', 'Search by name or title…') }}"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-800 placeholder:text-gray-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-400">
                </div>
            </div>
        </section>

        <main class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
            <div id="media-picker-dropzone" class="flex min-h-[100px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 text-center text-gray-500 transition hover:border-indigo-400 hover:bg-indigo-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-indigo-400 dark:hover:bg-gray-900/60">
                <p class="text-xs font-medium">{{ t('dashboard.Media_Picker_Dropzone', 'Drag files here or use the upload button above') }}</p>
                <p id="media-picker-upload-help" class="mt-1 text-[11px] text-gray-400 dark:text-gray-400">{{ t('dashboard.Media_Picker_Upload_Help', 'Supports images up to 10MB each (JPEG, PNG, WEBP, SVG…)') }}</p>
            </div>
            <div id="media-picker-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-3"></div>
            <div id="media-picker-upload-status" role="status" class="text-xs text-gray-600 dark:text-gray-300"></div>
            <div id="media-picker-status" role="status" class="sr-only"></div>
            <div id="media-picker-loading" class="mt-2 text-center text-xs text-gray-500 dark:text-gray-400 hidden">{{ t('dashboard.Media_Picker_Loading', 'Loading…') }}</div>
            <div id="media-picker-empty" class="mt-2 text-center text-xs text-gray-400 dark:text-gray-400 hidden">{{ t('dashboard.Media_Picker_Empty', 'No matching media found.') }}</div>
            <button type="button" id="media-picker-load-more" class="mt-4 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 hidden">{{ t('dashboard.Media_Picker_Load_More', 'Load more media') }}</button>
        </main>

        <footer class="px-5 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between gap-3 text-[11px]">
            <div role="status" aria-atomic="true" class="text-gray-500 dark:text-gray-400">{{ t('dashboard.Media_Picker_Selected_Items', 'Selected items:') }} <span id="media-picker-selection-count">0</span></div>
            <div class="flex items-center gap-2">
                <button type="button" id="media-picker-clear" class="hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ t('dashboard.Media_Picker_Clear_Selection', 'Clear selection') }}</button>
                <button type="button" id="media-picker-cancel" class="rounded-full border border-gray-300 px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ t('dashboard.Cancel', 'Cancel') }}</button>
                <button type="button" id="media-picker-confirm" data-clear-label="{{ t('dashboard.Media_Picker_Confirm_Removal', 'Confirm image removal') }}" class="rounded-full bg-primary px-4 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed">{{ t('dashboard.Media_Picker_Use_Selected', 'Use selected items') }}</button>
            </div>
        </footer>
    </div>
</div>
