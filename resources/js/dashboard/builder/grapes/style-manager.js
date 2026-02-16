// resources/js/dashboard/builder/grapes/style-manager.js

export function registerStyleManager(editor, { isRtl = false } = {}) {
    const sm = editor.StyleManager;

    // Reset any previously registered sectors.
    sm.getSectors().reset([]);

    const t = (en, ar) => (isRtl ? ar : en);

    const fontFamilies = [
        { id: 'inherit', name: t('Default', 'افتراضي') },
        { id: 'Cairo, ui-sans-serif, system-ui', name: 'Cairo' },
        { id: 'Tajawal, ui-sans-serif, system-ui', name: 'Tajawal' },
        { id: 'Almarai, ui-sans-serif, system-ui', name: 'Almarai' },
        { id: 'Inter, ui-sans-serif, system-ui', name: 'Inter' },
        { id: 'system-ui, -apple-system, Segoe UI, Roboto, Arial', name: 'System' },
    ];

    const fontWeights = [
        { id: '300', name: t('Light', 'خفيف') },
        { id: '400', name: t('Regular', 'عادي') },
        { id: '500', name: t('Medium', 'متوسط') },
        { id: '600', name: t('SemiBold', 'شبه عريض') },
        { id: '700', name: t('Bold', 'عريض') },
        { id: '800', name: t('ExtraBold', 'ثقيل') },
        { id: '900', name: t('Black', 'أسود') },
    ];

    const textTransforms = [
        { id: 'none', name: t('None', 'بدون') },
        { id: 'uppercase', name: t('Uppercase', 'أحرف كبيرة') },
        { id: 'lowercase', name: t('Lowercase', 'أحرف صغيرة') },
        { id: 'capitalize', name: t('Capitalize', 'أول حرف كبير') },
    ];

    const backgroundSizes = [
        { id: 'auto', name: t('Auto', 'تلقائي') },
        { id: 'cover', name: t('Cover', 'تغطية') },
        { id: 'contain', name: t('Contain', 'احتواء') },
    ];

    const backgroundRepeats = [
        { id: 'no-repeat', name: t('No Repeat', 'بدون تكرار') },
        { id: 'repeat', name: t('Repeat', 'تكرار') },
        { id: 'repeat-x', name: t('Repeat X', 'تكرار أفقي') },
        { id: 'repeat-y', name: t('Repeat Y', 'تكرار عمودي') },
    ];

    const backgroundPositions = [
        { id: 'left top', name: t('Left Top', 'أعلى يسار') },
        { id: 'center top', name: t('Center Top', 'أعلى وسط') },
        { id: 'right top', name: t('Right Top', 'أعلى يمين') },
        { id: 'left center', name: t('Left Center', 'وسط يسار') },
        { id: 'center center', name: t('Center', 'وسط') },
        { id: 'right center', name: t('Right Center', 'وسط يمين') },
        { id: 'left bottom', name: t('Left Bottom', 'أسفل يسار') },
        { id: 'center bottom', name: t('Center Bottom', 'أسفل وسط') },
        { id: 'right bottom', name: t('Right Bottom', 'أسفل يمين') },
    ];

    const backgroundAttachments = [
        { id: 'scroll', name: t('Scroll', 'تمرير') },
        { id: 'fixed', name: t('Fixed', 'ثابت') },
        { id: 'local', name: t('Local', 'محلي') },
    ];

    // =========================================================
    // 1) Background (Color / Image)
    // =========================================================
    sm.addSector('pg-background', {
        name: t('Background', 'الخلفية'),
        open: true,
        buildProps: [
            'background-color',
            'background-image',
            'background-position',
            'background-size',
            'background-repeat',
            'background-attachment',
            'opacity',
        ],
        properties: [
            {
                id: 'background-color',
                name: t('Color', 'لون'),
                property: 'background-color',
                type: 'color',
                defaults: 'transparent',
            },
            {
                id: 'background-image',
                name: t('Image', 'صورة'),
                property: 'background-image',
                type: 'file',
                functionName: 'url',
                full: true,
                defaults: '',
            },
            {
                id: 'background-position',
                name: t('Position', 'موضع الصورة'),
                property: 'background-position',
                type: 'select',
                defaults: 'center center',
                list: backgroundPositions,
            },
            {
                id: 'background-size',
                name: t('Size', 'حجم الصورة'),
                property: 'background-size',
                type: 'select',
                defaults: 'cover',
                list: backgroundSizes,
            },
            {
                id: 'background-repeat',
                name: t('Repeat', 'تكرار الصورة'),
                property: 'background-repeat',
                type: 'select',
                defaults: 'no-repeat',
                list: backgroundRepeats,
            },
            {
                id: 'background-attachment',
                name: t('Attachment', 'ثبات الصورة'),
                property: 'background-attachment',
                type: 'select',
                defaults: 'scroll',
                list: backgroundAttachments,
            },
            {
                id: 'opacity',
                name: t('Opacity', 'الشفافية'),
                property: 'opacity',
                type: 'slider',
                min: 0,
                max: 1,
                step: 0.01,
                defaults: 1,
            },
        ],
    });

    // =========================================================
    // 2) Typography
    // =========================================================
    sm.addSector('pg-typography', {
        name: t('Typography', 'الخطوط'),
        open: false,
        buildProps: [
            'text-align',
            'font-family',
            'font-size',
            'font-weight',
            'text-transform',
            'line-height',
            'letter-spacing',
            'color',
        ],
        properties: [
            {
                id: 'text-align',
                name: t('Align', 'المحاذاة'),
                property: 'text-align',
                type: 'radio',
                defaults: isRtl ? 'right' : 'left',
                list: [
                    { value: 'left', name: 'Left' },
                    { value: 'center', name: 'Center' },
                    { value: 'right', name: 'Right' },
                    { value: 'justify', name: 'Justify' },
                ],
            },
            {
                id: 'font-family',
                name: t('Font', 'نوع الخط'),
                property: 'font-family',
                type: 'select',
                defaults: 'inherit',
                list: fontFamilies,
            },
            {
                id: 'font-size',
                name: t('Size', 'الحجم'),
                property: 'font-size',
                type: 'slider',
                units: ['px', 'rem'],
                unit: 'px',
                defaults: '32px',
                min: 8,
                max: 120,
                step: 1,
            },
            {
                id: 'font-weight',
                name: t('Weight', 'السُمك'),
                property: 'font-weight',
                type: 'select',
                defaults: '800',
                list: fontWeights,
            },
            {
                id: 'text-transform',
                name: t('Transform', 'تحويل'),
                property: 'text-transform',
                type: 'select',
                defaults: 'none',
                list: textTransforms,
            },
            {
                id: 'line-height',
                name: t('Line Height', 'ارتفاع السطر'),
                property: 'line-height',
                type: 'slider',
                units: ['', 'px'],
                unit: '',
                defaults: '1.2',
                min: 0.8,
                max: 3,
                step: 0.05,
            },
            {
                id: 'letter-spacing',
                name: t('Letter Spacing', 'تباعد الأحرف'),
                property: 'letter-spacing',
                type: 'slider',
                units: ['px', 'em'],
                unit: 'px',
                defaults: '0px',
                min: -2,
                max: 20,
                step: 0.5,
            },
            {
                id: 'color',
                name: t('Text Color', 'لون النص'),
                property: 'color',
                type: 'color',
                defaults: '#240B36',
            },
        ],
    });

    // =========================================================
    // 3) Text Shadow
    // =========================================================
    sm.addSector('pg-text-shadow', {
        name: t('Text Shadow', 'ظل النص'),
        open: false,
        buildProps: ['text-shadow'],
        properties: [
            {
                id: 'text-shadow',
                name: t('Shadow', 'الظل'),
                property: 'text-shadow',
                type: 'composite',
                properties: [
                    { name: 'X', property: 'text-shadow-h', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: 'Y', property: 'text-shadow-v', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: t('Blur', 'التمويه'), property: 'text-shadow-blur', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: t('Color', 'اللون'), property: 'text-shadow-color', type: 'color', defaults: 'rgba(0,0,0,0.35)' },
                ],
                toStyle(values) {
                    const h = values['text-shadow-h'] ?? 0;
                    const v = values['text-shadow-v'] ?? 0;
                    const b = values['text-shadow-blur'] ?? 0;
                    const c = values['text-shadow-color'] ?? 'rgba(0,0,0,0.35)';
                    if (!h && !v && !b) return { 'text-shadow': 'none' };
                    return { 'text-shadow': `${h}px ${v}px ${b}px ${c}` };
                },
            },
        ],
    });

    // =========================================================
    // 4) Text Stroke
    // =========================================================
    sm.addSector('pg-text-stroke', {
        name: t('Text Stroke', 'حدود النص'),
        open: false,
        buildProps: ['-webkit-text-stroke-width', '-webkit-text-stroke-color'],
        properties: [
            {
                id: 'stroke-width',
                name: t('Width', 'السُمك'),
                property: '-webkit-text-stroke-width',
                type: 'slider',
                units: ['px'],
                unit: 'px',
                defaults: '0px',
                min: 0,
                max: 10,
                step: 0.5,
            },
            {
                id: 'stroke-color',
                name: t('Color', 'اللون'),
                property: '-webkit-text-stroke-color',
                type: 'color',
                defaults: '#000000',
            },
        ],
    });
}

