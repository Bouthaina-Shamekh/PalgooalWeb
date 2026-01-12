// resources/js/dashboard/builder/grapes/style-manager.js

export function registerStyleManager(editor, { isRtl = false } = {}) {
    const sm = editor.StyleManager;

    // امسح أي Sectors سابقة (لو في)
    sm.getSectors().reset([]);

    // Helpers
    const fontFamilies = [
        { id: 'inherit', name: isRtl ? 'افتراضي' : 'Default' },
        { id: 'Cairo, ui-sans-serif, system-ui', name: 'Cairo' },
        { id: 'Tajawal, ui-sans-serif, system-ui', name: 'Tajawal' },
        { id: 'Almarai, ui-sans-serif, system-ui', name: 'Almarai' },
        { id: 'Inter, ui-sans-serif, system-ui', name: 'Inter' },
        { id: 'system-ui, -apple-system, Segoe UI, Roboto, Arial', name: 'System' },
    ];

    const fontWeights = [
        { id: '300', name: isRtl ? 'خفيف' : 'Light' },
        { id: '400', name: isRtl ? 'عادي' : 'Regular' },
        { id: '500', name: isRtl ? 'متوسط' : 'Medium' },
        { id: '600', name: isRtl ? 'شبه عريض' : 'SemiBold' },
        { id: '700', name: isRtl ? 'عريض' : 'Bold' },
        { id: '800', name: isRtl ? 'ثقيل' : 'ExtraBold' },
        { id: '900', name: isRtl ? 'أسود' : 'Black' },
    ];

    const textTransforms = [
        { id: 'none', name: isRtl ? 'بدون' : 'None' },
        { id: 'uppercase', name: isRtl ? 'UPPERCASE' : 'Uppercase' },
        { id: 'lowercase', name: isRtl ? 'lowercase' : 'Lowercase' },
        { id: 'capitalize', name: isRtl ? 'Capitalize' : 'Capitalize' },
    ];

    // =========================================================
    // 1) Typography (يشبه Elementor)
    // =========================================================
    sm.addSector('pg-typography', {
        name: isRtl ? 'الخطوط' : 'Typography',
        open: true,
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
                name: isRtl ? 'محاذاة' : 'Align',
                property: 'text-align',
                type: 'radio',
                defaults: isRtl ? 'right' : 'left',
                list: [
                    { value: 'left', name: '≡' },
                    { value: 'center', name: '≣' },
                    { value: 'right', name: '≡' },
                    { value: 'justify', name: '☰' },
                ],
            },
            {
                id: 'font-family',
                name: isRtl ? 'نوع الخط' : 'Font',
                property: 'font-family',
                type: 'select',
                defaults: 'inherit',
                list: fontFamilies,
            },
            {
                id: 'font-size',
                name: isRtl ? 'حجم الخط' : 'Size',
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
                name: isRtl ? 'سُمك الخط' : 'Weight',
                property: 'font-weight',
                type: 'select',
                defaults: '800',
                list: fontWeights,
            },
            {
                id: 'text-transform',
                name: isRtl ? 'تحويل' : 'Transform',
                property: 'text-transform',
                type: 'select',
                defaults: 'none',
                list: textTransforms,
            },
            {
                id: 'line-height',
                name: isRtl ? 'ارتفاع السطر' : 'Line Height',
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
                name: isRtl ? 'تباعد الأحرف' : 'Letter Spacing',
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
                name: isRtl ? 'لون النص' : 'Text Color',
                property: 'color',
                type: 'color',
                defaults: '#240B36',
            },
        ],
    });

    // =========================================================
    // 2) Text Shadow
    // =========================================================
    sm.addSector('pg-text-shadow', {
        name: isRtl ? 'Text Shadow' : 'Text Shadow',
        open: false,
        buildProps: ['text-shadow'],
        properties: [
            {
                id: 'text-shadow',
                name: isRtl ? 'ظل النص' : 'Shadow',
                property: 'text-shadow',
                type: 'composite',
                properties: [
                    { name: isRtl ? 'X' : 'X', property: 'text-shadow-h', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: isRtl ? 'Y' : 'Y', property: 'text-shadow-v', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: isRtl ? 'Blur' : 'Blur', property: 'text-shadow-blur', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: isRtl ? 'Color' : 'Color', property: 'text-shadow-color', type: 'color', defaults: 'rgba(0,0,0,0.35)' },
                ],
                // تحويل composite إلى text-shadow حقيقي
                toStyle(values) {
                    const h = values['text-shadow-h'] ?? 0;
                    const v = values['text-shadow-v'] ?? 0;
                    const b = values['text-shadow-blur'] ?? 0;
                    const c = values['text-shadow-color'] ?? 'rgba(0,0,0,0.35)';
                    // إذا كله 0 => ألغِ
                    if (!h && !v && !b) return { 'text-shadow': 'none' };
                    return { 'text-shadow': `${h}px ${v}px ${b}px ${c}` };
                },
            },
        ],
    });

    // =========================================================
    // 3) Text Stroke (Elementor-like)
    // =========================================================
    sm.addSector('pg-text-stroke', {
        name: isRtl ? 'Text Stroke' : 'Text Stroke',
        open: false,
        buildProps: ['-webkit-text-stroke-width', '-webkit-text-stroke-color'],
        properties: [
            {
                id: 'stroke-width',
                name: isRtl ? 'السُمك' : 'Width',
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
                name: isRtl ? 'اللون' : 'Color',
                property: '-webkit-text-stroke-color',
                type: 'color',
                defaults: '#000000',
            },
        ],
    });
}
