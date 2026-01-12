// resources/js/dashboard/builder/grapes/elements/heading.js

const ensureClass = (cls) => (cls ? String(cls).trim() : '');
const splitClasses = (cls) => ensureClass(cls).split(/\s+/).filter(Boolean);

function removeByPrefixes(classList, prefixes = []) {
    return classList.filter((c) => !prefixes.some((p) => c.startsWith(p)));
}

function addPrefixedClasses(raw, prefix) {
    const v = ensureClass(raw);
    if (!v) return [];
    return v.split(/\s+/).filter(Boolean).map((c) => (prefix ? `${prefix}${c}` : c));
}

// يطبّق إعدادات Advanced على class بدون ما يلمس كلاساتك الأساسية
function applyHeadingAdvancedClasses(model) {
    const attrs = model.getAttributes?.() || {};
    const original = splitClasses(attrs.class || '');

    // قيم Advanced
    const advMargin = ensureClass(model.get('pgAdvMargin'));
    const advPadding = ensureClass(model.get('pgAdvPadding'));
    const advZ = ensureClass(model.get('pgAdvZ'));
    const advCustom = ensureClass(model.get('pgAdvCustom'));

    const mMobile = ensureClass(model.get('pgAdvMobile'));
    const mTablet = ensureClass(model.get('pgAdvTablet'));
    const mDesk = ensureClass(model.get('pgAdvDesktop'));

    const hideMobile = !!model.get('pgHideMobile');
    const hideTablet = !!model.get('pgHideTablet');
    const hideDesktop = !!model.get('pgHideDesktop');

    // 1) نظّف فقط “كلاسات Advanced السابقة” حتى ما تتراكم
    let cleaned = original;

    // شيل margin/padding/z/index prefixes العامة (ممكن توسعها لاحقًا)
    cleaned = removeByPrefixes(cleaned, [
        'm-', 'mx-', 'my-', 'mt-', 'mr-', 'mb-', 'ml-',
        'p-', 'px-', 'py-', 'pt-', 'pr-', 'pb-', 'pl-',
        'z-',
        'hidden', 'block',
        'sm:', 'md:', 'lg:', 'xl:', '2xl:', 'max-',
    ]);

    // 2) ابنِ additions
    const additions = [];

    // Margin/Padding (تكتبها Tailwind مباشرة مثل: "mt-6 mb-2" أو "px-4 py-2")
    if (advMargin) additions.push(...advMargin.split(/\s+/));
    if (advPadding) additions.push(...advPadding.split(/\s+/));

    // Z-index (select: z-0..z-50)
    if (advZ) additions.push(advZ);

    // Responsive Classes (بدون prefix، وإحنا نضيف prefix)
    // Mobile = بدون prefix
    additions.push(...addPrefixedClasses(mMobile, ''));
    // Tablet = md:
    additions.push(...addPrefixedClasses(mTablet, 'md:'));
    // Desktop = lg:
    additions.push(...addPrefixedClasses(mDesk, 'lg:'));

    // Hide on device (تقريب عملي وواضح)
    // Hide Mobile: يخفي بالموبايل فقط => hidden + يظهر من sm وفوق
    if (hideMobile) additions.push('hidden', 'sm:block');

    // Hide Tablet: (تقريب) نخفي أقل من lg => hidden + يظهر من lg وفوق
    // هذا يخفي mobile+tablet، لو بدك “tablet فقط” بدقة بنعملها لاحقًا بتوليفة max-*
    if (hideTablet) additions.push('hidden', 'lg:block');

    // Hide Desktop: نخفي من lg وفوق
    if (hideDesktop) additions.push('lg:hidden');

    // Custom classes (raw)
    if (advCustom) additions.push(...advCustom.split(/\s+/));

    // 3) طبّق
    model.addAttributes({
        class: [...cleaned, ...additions].join(' ').trim(),
    });
}

export function registerHeadingElement(editor) {
    editor.DomComponents.addType('pg-heading', {
        model: {
            defaults: {
                tagName: 'h2',
                name: 'Heading',
                attributes: {
                    class: 'text-3xl sm:text-4xl font-extrabold text-primary tracking-tight',
                    'data-gjs-name': 'Heading',
                },

                // ===== Advanced state =====
                pgAdvMargin: '',
                pgAdvPadding: '',
                pgAdvZ: '',
                pgAdvCustom: '',

                pgAdvMobile: '',
                pgAdvTablet: '',
                pgAdvDesktop: '',

                pgHideMobile: false,
                pgHideTablet: false,
                pgHideDesktop: false,

                traits: [
                    // =======================
                    // (موجود عندك: محتوى/تنسيق)
                    // =======================
                    // انت عندك محتوى وتنظيم سابق - خليه كما هو

                    // =======================
                    // Advanced (متقدم)
                    // =======================
                    { type: 'text', name: 'pgAdvMargin', label: 'Margin (Tailwind)', placeholder: 'مثال: mt-6 mb-2 mx-auto', changeProp: 1 },
                    { type: 'text', name: 'pgAdvPadding', label: 'Padding (Tailwind)', placeholder: 'مثال: px-4 py-2', changeProp: 1 },

                    {
                        type: 'select',
                        name: 'pgAdvZ',
                        label: 'Z-index',
                        options: [
                            { id: '', name: '—' },
                            { id: 'z-0', name: 'z-0' },
                            { id: 'z-10', name: 'z-10' },
                            { id: 'z-20', name: 'z-20' },
                            { id: 'z-30', name: 'z-30' },
                            { id: 'z-40', name: 'z-40' },
                            { id: 'z-50', name: 'z-50' },
                        ],
                        changeProp: 1,
                    },

                    { type: 'text', name: 'pgAdvMobile', label: 'Responsive: Mobile', placeholder: 'مثال: text-xl', changeProp: 1 },
                    { type: 'text', name: 'pgAdvTablet', label: 'Responsive: Tablet (md:)', placeholder: 'مثال: text-3xl', changeProp: 1 },
                    { type: 'text', name: 'pgAdvDesktop', label: 'Responsive: Desktop (lg:)', placeholder: 'مثال: text-5xl', changeProp: 1 },

                    { type: 'checkbox', name: 'pgHideMobile', label: 'Hide on Mobile', changeProp: 1 },
                    { type: 'checkbox', name: 'pgHideTablet', label: 'Hide on Tablet', changeProp: 1 },
                    { type: 'checkbox', name: 'pgHideDesktop', label: 'Hide on Desktop', changeProp: 1 },

                    { type: 'text', name: 'pgAdvCustom', label: 'Custom classes', placeholder: 'مثال: my-custom-class', changeProp: 1 },
                ],
            },

            init() {
                // أي تغيير بخصائص Advanced ينعكس على class مباشرة
                this.on(
                    'change:pgAdvMargin change:pgAdvPadding change:pgAdvZ change:pgAdvCustom ' +
                    'change:pgAdvMobile change:pgAdvTablet change:pgAdvDesktop ' +
                    'change:pgHideMobile change:pgHideTablet change:pgHideDesktop',
                    () => applyHeadingAdvancedClasses(this)
                );

                applyHeadingAdvancedClasses(this);
            },
        },
    });
}
