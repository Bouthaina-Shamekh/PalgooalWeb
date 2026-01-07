// export function registerBlocks(editor) {
//     const bm = editor.BlockManager;

//     // Global direction flag (rtl / ltr)
//     const isRtl =
//         document.documentElement.dir === 'rtl' ||
//         document.body.dir === 'rtl';

//     // Shared preview image (hero / templates)
//     const heroImage = '/assets/tamplate/images/template.webp';

//     // Hero content
//     const heroTitle = isRtl
//         ? 'أطلق موقعك الاحترافي في دقائق'
//         : 'Launch your professional website in minutes';

//     const heroSubtitle = isRtl
//         ? 'منصة متكاملة لتصميم واستضافة موقعك مع دومين جاهز وربط كامل خلال دقائق، بدون تعقيد تقني.'
//         : 'All-in-one platform to design and host your website with a ready domain in minutes — no technical hassle.';

//     const primaryText = isRtl ? 'ابدأ الآن' : 'Get Started';
//     const secondaryText = isRtl ? 'استكشف المزايا' : 'Explore features';

//     const heroDirectionClass = isRtl ? 'md:flex-row-reverse' : 'md:flex-row';

//     // Features content
//     const featuresSectionTitle = isRtl
//         ? 'خدمات رقمية متكاملة تدعم نجاحك'
//         : 'All-in-one digital services for your success';

//     const featuresSectionSubtitle = isRtl
//         ? 'منصة واحدة تجمع بين الاستضافة، القوالب الجاهزة، وربط الدومين خلال دقائق.'
//         : 'One platform that brings hosting, ready-made templates and domain connection in minutes.';

//     const featuresConfig = isRtl
//         ? [
//             {
//                 title: 'إطلاق سريع',
//                 description: 'امتلك موقعك الجاهز خلال دقائق مع إعداد تلقائي كامل.',
//             },
//             {
//                 title: 'تصاميم احترافية',
//                 description: 'قوالب مصممة بعناية لتناسب مختلف الأنشطة والمتاجر.',
//             },
//             {
//                 title: 'دعم فني مستمر',
//                 description: 'فريق مختص لمساعدتك في أي وقت خلال رحلتك الرقمية.',
//             },
//             {
//                 title: 'أداء عالي',
//                 description: 'استضافة مستقرة وسريعة لتجربة استخدام مميزة.',
//             },
//             {
//                 title: 'مرونة التخصيص',
//                 description: 'تحكم في محتوى موقعك بسهولة بدون خبرة برمجية.',
//             },
//             {
//                 title: 'تكاملات جاهزة',
//                 description: 'ربط مع بوابات الدفع وأدوات التسويق بكل سهولة.',
//             },
//         ]
//         : [
//             {
//                 title: 'Fast launch',
//                 description: 'Get your website live in minutes with full automatic setup.',
//             },
//             {
//                 title: 'Professional designs',
//                 description: 'Carefully crafted templates for different niches and stores.',
//             },
//             {
//                 title: 'Ongoing support',
//                 description: 'A dedicated team ready to help you throughout your journey.',
//             },
//             {
//                 title: 'High performance',
//                 description: 'Stable and fast hosting for a great user experience.',
//             },
//             {
//                 title: 'Flexible customization',
//                 description: 'Easily manage your content without any technical background.',
//             },
//             {
//                 title: 'Ready integrations',
//                 description: 'Connect payment gateways and marketing tools in no time.',
//             },
//         ];

//     const featuresItemsHtml = featuresConfig
//         .map(
//             (item, index) => `
// <div class="group rounded-2xl bg-white/90 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-700
//            p-5 sm:p-6 shadow-[0_10px_30px_rgba(15,23,42,0.06)]
//            hover:shadow-[0_18px_40px_rgba(15,23,42,0.14)]
//            transition-all duration-200"
//      data-gjs-name="Feature Item"
//      data-feature-index="${index}">
//   <div class="flex flex-col items-center sm:items-start gap-4">
//     <div class="w-12 h-12 flex items-center justify-center rounded-xl
//                 bg-primary/10 text-primary
//                 group-hover:bg-primary group-hover:text-white
//                 transition-colors duration-200 shrink-0">
//       <!-- Placeholder icon circle (you can later replace with SVG via editor) -->
//       <span class="w-2 h-2 rounded-full bg-current shadow-[0_0_0_3px_rgba(255,255,255,0.35)]"></span>
//     </div>
//     <span class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white text-center sm:text-start"
//           data-field="feature-title">
//       ${item.title}
//     </span>
//   </div>
//   <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed text-center sm:text-start"
//      data-field="feature-description">
//     ${item.description}
//   </p>
// </div>`.trim()
//         )
//         .join('\n');

//     const featuresSectionHtml = `
// <section data-section-type="features"
//          data-gjs-name="Features Section"
//          class="py-20 sm:py-24 lg:py-28 px-4 sm:px-6 lg:px-8 bg-background" dir="auto">
//   <div class="container-xx">
//     <!-- Section heading -->
//     <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-14 lg:mb-16">
//       <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-primary tracking-tight mb-4"
//           data-field="title">
//         ${featuresSectionTitle}
//       </h2>
//       <p class="text-tertiary text-sm sm:text-base leading-relaxed"
//          data-field="subtitle">
//         ${featuresSectionSubtitle}
//       </p>
//     </div>

//     <!-- Main grid: illustration + features cards -->
//     <div class="grid gap-12 lg:gap-16 lg:grid-cols-5 items-center">
//       <!-- Illustration (optional static preview image) -->
//       <div class="lg:col-span-2 flex justify-center" data-gjs-name="Illustration">
//         <img
//           src="/assets/tamplate/images/Fu.svg"
//           alt="Platform features"
//           class="max-w-[260px] sm:max-w-sm lg:max-w-[420px] w-full h-auto object-contain mx-auto
//                  animate-fade-in-up transition-transform duration-500 ease-out hover:scale-105"
//           loading="lazy"
//         />
//       </div>

//       <!-- Features list -->
//       <div class="lg:col-span-3">
//         <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8"
//              data-gjs-name="Features Grid">
//           ${featuresItemsHtml}
//         </div>
//       </div>
//     </div>
//   </div>
// </section>`.trim();

//     const iconHero = `
// <svg viewBox="0 0 24 24" fill="none"
//      stroke="currentColor" stroke-width="1.6"
//      stroke-linecap="round" stroke-linejoin="round">
//   <rect x="3.5" y="5" width="17" height="14" rx="2.5"></rect>
//   <path d="M8 9h8M7 13h4M7 16h3"></path>
// </svg>`.trim();

//     const iconFeatures = `
// <svg viewBox="0 0 24 24" fill="none"
//      stroke="currentColor" stroke-width="1.6"
//      stroke-linecap="round" stroke-linejoin="round">
//   <rect x="3" y="4" width="18" height="16" rx="2.5"></rect>
//   <path d="M8 9h8M8 13h5M8 17h3"></path>
// </svg>`.trim();

//     const iconText = `
// <svg viewBox="0 0 24 24" fill="none"
//      stroke="currentColor" stroke-width="1.6"
//      stroke-linecap="round" stroke-linejoin="round">
//   <path d="M5 7h14M5 12h10M5 17h7"></path>
// </svg>`.trim();

//     const iconButton = `
// <svg viewBox="0 0 24 24" fill="none"
//      stroke="currentColor" stroke-width="1.6"
//      stroke-linecap="round" stroke-linejoin="round">
//   <rect x="4" y="9" width="16" height="6" rx="3"></rect>
//   <path d="M9 12h6"></path>
// </svg>`.trim();

//     const makeLabel = (iconSvg, title) => `
// <div class="pg-block-card">
//   <div class="pg-block-icon">
//     ${iconSvg}
//   </div>
//   <div class="pg-block-title">
//     ${title}
//   </div>
// </div>
// `.trim();

//     const heroContent = `
// <section data-section-type="hero"
//          data-gjs-name="Hero"
//          class="relative bg-gradient-to-tr from-primary to-primary shadow-2xl overflow-hidden -mt-20">
//   <img src="${heroImage}"
//        alt="Palgoals templates preview"
//        fetchpriority="high"
//        class="absolute inset 0 z-0 opacity-80 w-full h-full object-cover object-center ltr:scale-x-[-1] rtl:scale-x-100 transition-transform duration-500 ease-in-out"
//        aria-hidden="true"
//        decoding="async"
//        loading="eager" />

//   <div class="relative z-10 px-4 sm:px-8 lg:px-24 py-20 sm:py-28 lg:py-32 flex flex-col-reverse ${heroDirectionClass} items-center justify-between gap-12 min-h-[600px] lg:min-h-[700px]">
//     <div class="max-w-xl rtl:text-right ltr:text-left text-center md:text-start"
//          data-gjs-name="Hero Content">
//       <h1 class="text-3xl/20 sm:text-4xl/20 lg:text-5xl/20 font-extrabold text-white leading-tight drop-shadow-lg mb-6"
//           data-field="title">
//         ${heroTitle}
//       </h1>

//       <p class="text-white/90 text-base sm:text-lg font-light mb-8"
//          data-field="subtitle">
//         ${heroSubtitle}
//       </p>

//       <div class="flex flex-row flex-wrap gap-3 justify-center md:justify-start"
//            data-gjs-name="Hero Buttons">
//         <a href="#"
//            data-field="primary-button"
//            class="bg-secondary hover:bg-primary text-white font-bold px-6 py-3 rounded-lg shadow transition text-sm sm:text-base">
//           ${primaryText}
//         </a>

//         <a href="#"
//            data-field="secondary-button"
//            class="bg-white/10 text-white font-bold px-6 py-3 rounded-lg shadow transition hover:bg-white/20 text-sm sm:text-base border border-white/30">
//           ${secondaryText}
//         </a>
//       </div>
//     </div>
//   </div>

//   <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl z-0"></div>
// </section>`.trim();

//     bm.add('pg-hero', {
//         id: 'pg-hero',
//         label: makeLabel(iconHero, isRtl ? 'سكشن هيرو' : 'Hero Section'),
//         category: {
//             id: 'pg-hero-sections',
//             label: isRtl ? 'سكاشن الهيرو' : 'Hero Sections',
//             open: true,
//         },
//         content: heroContent,
//     });

//     bm.add('pg-features', {
//         id: 'pg-features',
//         label: makeLabel(iconFeatures, isRtl ? 'مميزات' : 'Features'),
//         category: {
//             id: 'pg-content-sections',
//             label: isRtl ? 'سكاشن المحتوى' : 'Content Sections',
//             open: true,
//         },
//         content: featuresSectionHtml,
//     });

//     bm.add('pg-text', {
//         id: 'pg-text',
//         label: makeLabel(iconText, isRtl ? 'نص' : 'Text'),
//         category: {
//             id: 'pg-basic-elements',
//             label: isRtl ? 'عناصر أساسية' : 'Basic Elements',
//             open: false,
//         },
//         content: `
// <p class="text-slate-700" data-gjs-name="Text Block">
//   ${isRtl ? 'اكتب النص هنا…' : 'Write your text here…'}
// </p>`.trim(),
//     });

//     bm.add('pg-button', {
//         id: 'pg-button',
//         label: makeLabel(iconButton, isRtl ? 'زر' : 'Button'),
//         category: {
//             id: 'pg-basic-elements',
//             label: isRtl ? 'عناصر أساسية' : 'Basic Elements',
//             open: false,
//         },
//         content: `
// <a href="#"
//    data-gjs-name="Button"
//    class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-sky-600 text-white font-semibold">
//    ${isRtl ? 'زر' : 'Button'}
// </a>`.trim(),
//     });
// }

// resources/js/dashboard/builder/grapes/blocks.js
// Named exports

const ensureClass = (cls) => (cls ? String(cls).trim() : '');
const splitClasses = (cls) => ensureClass(cls).split(/\s+/).filter(Boolean);

function removeByPrefixes(classList, prefixes = []) {
    return classList.filter((c) => !prefixes.some((p) => c.startsWith(p)));
}

function applyRowLayoutClasses(model) {
    // اقرأ القيم من Props (Traits)
    const mode = model.get('pgMode') || 'grid';
    const cols = model.get('pgCols') || '3';
    const gap = model.get('pgGap') || '6';
    const items = model.get('pgItems') || 'stretch';
    const justify = model.get('pgJustify') || 'start';
    const wrap = model.get('pgWrap') || 'wrap';

    // الكلاسات الأساسية
    const base = ['pg-layout'];

    // امسح أي كلاسات layout قديمة (حتى لا تتراكم)
    const old = (model.getAttributes()?.class || '').split(/\s+/).filter(Boolean);
    const cleaned = old.filter((c) => {
        // إزالة grid/flex related
        if (c === 'grid' || c === 'flex') return false;
        if (c.startsWith('grid-cols-')) return false;
        if (c.startsWith('gap-')) return false;
        if (c.startsWith('items-')) return false;
        if (c.startsWith('justify-')) return false;
        if (c === 'flex-wrap' || c === 'flex-nowrap') return false;
        return true;
    });

    // ابني كلاسات جديدة حسب الوضع
    if (mode === 'flex') {
        base.push('flex');
        base.push(wrap === 'nowrap' ? 'flex-nowrap' : 'flex-wrap');
        base.push(`gap-${gap}`);
        base.push(`items-${items}`);
        base.push(`justify-${justify}`);
    } else {
        base.push('grid');
        base.push(`grid-cols-${cols}`);
        base.push(`gap-${gap}`);
        // في grid: items- و justify- ينطبقوا أيضًا
        base.push(`items-${items}`);
        base.push(`justify-${justify}`);
    }

    // طبّق الكلاسات على attributes
    model.addAttributes({
        class: [...cleaned, ...base].join(' ').trim(),
    });
}


export function registerBlocks(editor) {
    const bm = editor.BlockManager;

    /**
     * ------------------------------------------------------------------
     * Component: PG Row (Grid/Flex)
     * ------------------------------------------------------------------
     */
    editor.DomComponents.addType('pg-row', {
        model: {
            defaults: {
                tagName: 'div',
                name: 'Row',
                attributes: { class: 'pg-layout grid grid-cols-3 gap-6' },


                // Starter content: 3 lightweight columns
                components: [
                    { type: 'default', tagName: 'div', attributes: { class: 'pg-layout min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' }, components: [{ type: 'text', content: 'Column 1' }] },
                    { type: 'default', tagName: 'div', attributes: { class: 'pg-layout min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' }, components: [{ type: 'text', content: 'Column 2' }] },
                    { type: 'default', tagName: 'div', attributes: { class: 'pg-layout min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' }, components: [{ type: 'text', content: 'Column 3' }] },
                ],

                // Internal state (not HTML attributes)
                pgMode: 'grid',
                pgCols: '3',
                pgGap: '6',
                pgItems: 'stretch',
                pgJustify: 'start',
                pgWrap: 'wrap',

                traits: [
                    { type: 'select', name: 'pgMode', label: 'Layout', options: [{ id: 'grid', name: 'Grid' }, { id: 'flex', name: 'Flex' }], changeProp: 1 },
                    { type: 'select', name: 'pgCols', label: 'Columns (Grid)', options: ['1', '2', '3', '4', '5', '6'].map(v => ({ id: v, name: v })), changeProp: 1 },
                    { type: 'select', name: 'pgGap', label: 'Gap', options: ['0', '2', '3', '4', '6', '8', '10', '12'].map(v => ({ id: v, name: v })), changeProp: 1 },
                    {
                        type: 'select', name: 'pgItems', label: 'Items',
                        options: [
                            { id: 'start', name: 'Start' }, { id: 'center', name: 'Center' },
                            { id: 'end', name: 'End' }, { id: 'stretch', name: 'Stretch' },
                        ],
                        changeProp: 1,
                    },
                    {
                        type: 'select', name: 'pgJustify', label: 'Justify',
                        options: [
                            { id: 'start', name: 'Start' }, { id: 'center', name: 'Center' },
                            { id: 'end', name: 'End' }, { id: 'between', name: 'Between' },
                            { id: 'around', name: 'Around' }, { id: 'evenly', name: 'Evenly' },
                        ],
                        changeProp: 1,
                    },
                    { type: 'select', name: 'pgWrap', label: 'Wrap (Flex)', options: [{ id: 'wrap', name: 'Wrap' }, { id: 'nowrap', name: 'No Wrap' }], changeProp: 1 },
                ],
            },

            init() {
                this.on('change:pgMode change:pgCols change:pgGap change:pgItems change:pgJustify change:pgWrap', () => {
                    applyRowLayoutClasses(this);
                });
                applyRowLayoutClasses(this);
            },

        },
    });

    /**
     * ------------------------------------------------------------------
     * Blocks: Layout
     * ------------------------------------------------------------------
     */
    bm.add('pg-container', {
        label: 'Container',
        category: 'Layout',
        content: `
      <section class="pg-layout max-w-7xl mx-auto px-4 sm:px-8 lg:px-24 py-12" data-gjs-name="Container">
        <div class="pg-layout text-slate-600">Container content…</div>
      </section>
    `,
    });

    bm.add('pg-row', {
        label: 'Row (Grid/Flex)',
        category: 'Layout',
        content: { type: 'pg-row' },
    });

    bm.add('pg-card', {
        label: 'Card',
        category: 'Layout',
        content: `
      <div class="pg-layout rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-gjs-name="Card">
        <h3 class="pg-layout text-lg font-extrabold text-primary">Card title</h3>
        <p class="pg-layout mt-2 text-slate-600">Card content…</p>
      </div>
    `,
    });

    bm.add('pg-spacer', {
        label: 'Spacer',
        category: 'Layout',
        content: `<div class="pg-layout h-10" data-gjs-name="Spacer"></div>`,
    });

    bm.add('pg-divider', {
        label: 'Divider',
        category: 'Layout',
        content: `<hr class="pg-layout my-8 border-slate-200" data-gjs-name="Divider" />`,
    });

    /**
     * ------------------------------------------------------------------
     * Blocks: Basic
     * ------------------------------------------------------------------
     */
    bm.add('pg-heading', {
        label: 'Heading',
        category: 'Basic',
        content: `<h2 class="pg-layout text-3xl sm:text-4xl font-extrabold text-primary tracking-tight" data-gjs-name="Heading">عنوان</h2>`,
    });

    bm.add('pg-text', {
        label: 'Text',
        category: 'Basic',
        content: `<p class="pg-layout text-slate-700 leading-relaxed" data-gjs-name="Text">اكتب النص هنا…</p>`,
    });

    bm.add('pg-button', {
        label: 'Button',
        category: 'Basic',
        content: `<a class="pg-layout inline-flex items-center justify-center rounded-xl px-6 py-3 font-bold bg-primary text-white hover:bg-primary/90 transition" href="#" data-gjs-name="Button">زر</a>`,
    });

    bm.add('pg-image', {
        label: 'Image',
        category: 'Basic',
        content: `<img class="pg-layout w-full max-w-full rounded-2xl border border-slate-200" src="https://via.placeholder.com/1200x600" alt="Image" data-gjs-name="Image" />`,
    });
}
