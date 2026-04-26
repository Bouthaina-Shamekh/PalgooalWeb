import{_ as gt}from"./grapes.min-BaMFK-eV.js";const m=(g,u=document)=>u.querySelector(g),C=(g,u=document)=>Array.from(u.querySelectorAll(g));function w(g,u="idle"){const h=m("#builder-save-status");if(!h)return;const v=h.querySelector("[data-status-text]"),f=h.querySelector("[data-status-time]"),p=h.querySelector("[data-status-dot]");if(v&&(v.textContent=g),f){const S=new Date,k=String(S.getHours()).padStart(2,"0"),b=String(S.getMinutes()).padStart(2,"0");f.textContent=`${k}:${b}`}p&&(p.classList.remove("bg-amber-400","bg-emerald-500","bg-red-500","bg-sky-500","animate-pulse"),u==="dirty"?p.classList.add("bg-amber-400","animate-pulse"):u==="saving"?p.classList.add("bg-sky-500","animate-pulse"):u==="saved"?p.classList.add("bg-emerald-500"):u==="error"?p.classList.add("bg-red-500"):p.classList.add("bg-amber-400"))}async function V(g,{method:u="GET",body:h=null,headers:v={}}={}){var E;const f=((E=m('meta[name="csrf-token"]'))==null?void 0:E.content)||"",p=await fetch(g,{method:u,credentials:"include",redirect:"manual",headers:{Accept:"application/json","X-Requested-With":"XMLHttpRequest",...u!=="GET"?{"Content-Type":"application/json"}:{},...f?{"X-CSRF-TOKEN":f}:{},...v},...h?{body:JSON.stringify(h)}:{}});if(p.status>=300&&p.status<400)throw new Error(`Redirect detected (${p.status}). Check auth/CSRF/middleware for: ${g}`);const S=p.headers.get("content-type")||"",k=S.includes("application/json"),b=k?await p.json():await p.text();if(u!=="GET"&&!k){const x=String(b||"").slice(0,200).replace(/\s+/g," ");throw new Error(`Expected JSON but got "${S}". Response preview: ${x}`)}if(!p.ok){const x=b&&b.message?b.message:`Request failed (${p.status})`;throw new Error(x)}return b}function ut(g){return g&&typeof g=="object"&&!Array.isArray(g)&&Object.keys(g).length>0}const M=document.getElementById("page-builder-root");var J;if(M){let Z=function(){const a=C(".pg-sidebar-tab-btn"),n=C(".pg-sidebar-tab-content");if(!a.length||!n.length)return;const s=i=>{a.forEach(e=>{const t=e.dataset.tab===i;e.dataset.active=t?"true":"false"}),n.forEach(e=>{const t=e.dataset.tabContent===i;e.dataset.active=t?"true":"false",e.classList.toggle("hidden",!t)})};a.forEach(i=>{i.addEventListener("click",e=>{e.preventDefault();const t=i.dataset.tab||"widgets";s(t)})}),s("widgets")},O=function(a){const n=document.getElementById("gjs-blocks");if(!n)return;const s=Array.from(n.querySelectorAll(".gjs-block"));if(!s.length)return;const i=document.createElement("div");i.className="pg-blocks-grid",s.forEach(e=>{var t;e.style.width="100%",e.style.height="auto",e.style.margin="0",e.classList.add("pg-widget-tile"),(t=e.querySelector(".gjs-block-media, .gjs-block__media"))==null||t.remove(),i.appendChild(e)}),n.innerHTML="",n.appendChild(i)},tt=function(a){const n=document.getElementById("pg-widgets-search"),s=document.getElementById("gjs-blocks");if(!n||!s)return;const i=l=>(l||"").toString().trim().toLowerCase(),e=()=>{const l=!!s.querySelector(".gjs-block"),d=!!s.querySelector(".pg-widget-tile");l&&!d&&O()},t=()=>{e();const l=i(n.value),d=Array.from(s.querySelectorAll(".pg-widget-tile"));if(!d.length)return;let B=!1;d.forEach(L=>{var q,P;const U=((q=L.querySelector(".pg-block-title"))==null?void 0:q.textContent)||((P=L.querySelector(".gjs-block-label"))==null?void 0:P.textContent)||L.textContent||"",D=!l||i(U).includes(l);L.classList.toggle("is-hidden",!D),D&&(B=!0)});let y=s.querySelector(".pg-widgets-empty");B?y==null||y.remove():y||(y=document.createElement("div"),y.className="pg-widgets-empty",y.textContent="لا توجد نتائج مطابقة",s.appendChild(y))};n.addEventListener("input",t),n.addEventListener("search",t);try{a.on("load",t),a.on("block:add",t)}catch{}let o=null;const r=new MutationObserver(()=>{o&&clearTimeout(o),o=setTimeout(()=>t(),50)});r.observe(s,{childList:!0,subtree:!0}),window.addEventListener("beforeunload",()=>r.disconnect()),t(),setTimeout(t,250),setTimeout(t,700)},et=function(){const a=document.getElementById("pg-widgets-toggle"),n=document.getElementById("pg-widgets-wrap");if(!a||!n)return;const s="pg_widgets_collapsed",i=t=>{n.classList.toggle("is-collapsed",t),a.textContent=t?"إظهار":"إخفاء";try{localStorage.setItem(s,t?"1":"0")}catch{}};let e=!1;try{e=localStorage.getItem(s)==="1"}catch{}i(e),a.addEventListener("click",()=>{e=!n.classList.contains("is-collapsed"),i(e)})},at=function(a){const n=document.querySelector(".pg-props-tabs-wrap");if(!n)return;const s=Array.from(n.querySelectorAll(".pg-props-tab-btn")),i=Array.from(n.querySelectorAll(".pg-props-tab-content")),e=document.getElementById("pg-props-selected"),t=r=>{s.forEach(l=>{const d=l.dataset.propTab===r;l.dataset.active=d?"true":"false"}),i.forEach(l=>{const d=l.dataset.propContent===r;l.dataset.active=d?"true":"false"})};s.forEach(r=>{r.addEventListener("click",l=>{l.preventDefault(),t(r.dataset.propTab||"layers")})}),t("layers");const o=()=>{var d;const r=a.getSelected();if(!r){e&&(e.textContent="No selection");return}const l=r.get("custom-name")||((d=r.getName)==null?void 0:d.call(r))||r.get("tagName")||r.get("type")||"Component";e&&(e.textContent=l),t("traits")};a.on("component:selected",o),a.on("component:deselected",()=>{e&&(e.textContent="No selection"),t("layers")}),o()},st=function(){const a=C(".builder-tab[data-tab-target]"),n=C(".builder-tab-content[data-tab-content]"),s=C("[data-tab-helper]");if(!a.length||!n.length)return;const i=e=>{a.forEach(t=>{const o=t.dataset.tabTarget===e;t.classList.toggle("active",o),t.dataset.selected=o?"true":"false",t.setAttribute("aria-selected",o?"true":"false")}),n.forEach(t=>{const o=t.dataset.tabContent===e;t.classList.toggle("active",o),t.classList.toggle("hidden",!o),t.setAttribute("aria-hidden",o?"false":"true")}),s.forEach(t=>{t.classList.toggle("hidden",t.dataset.tabHelper!==e)})};a.forEach(e=>e.addEventListener("click",()=>i(e.dataset.tabTarget))),i("palette")},nt=function(a){if(!F.length&&!E&&!x)return;const n={desktop:"Desktop",tablet:"Tablet",mobile:"Mobile"},s={desktop:"Desktop",tablet:"Tablet",mobile:"Mobile"};function i(t){F.forEach(o=>{const r=o.dataset.preview===t;o.classList.toggle("bg-white",r),o.classList.toggle("text-slate-900",r),o.classList.toggle("shadow-sm",r),o.classList.toggle("bg-transparent",!r),o.classList.toggle("text-slate-500",!r)}),G&&s[t]&&(G.textContent=s[t])}function e(t){const o=n[t]||"Desktop";a.setDevice(o),i(t)}if(F.length&&(F.forEach(t=>{t.addEventListener("click",o=>{o.preventDefault();const r=t.dataset.preview;e(r)})}),e("desktop")),E&&x){const t=()=>x.classList.remove("open"),o=()=>x.classList.toggle("open");E.addEventListener("click",r=>{r.preventDefault(),o()}),document.addEventListener("click",r=>{x.contains(r.target)||E.contains(r.target)||t()})}},ot=function(a){const n=a.BlockManager,s=document.documentElement.dir==="rtl"||document.body.dir==="rtl",i="/assets/tamplate/images/template.webp",e=s?"أطلق موقعك الاحترافي في دقائق":"Launch your professional website in minutes",t=s?"منصة متكاملة لتصميم واستضافة موقعك مع دومين جاهز وربط كامل خلال دقائق، بدون تعقيد تقني.":"All-in-one platform to design and host your website with a ready domain in minutes — no technical hassle.",o=s?"ابدأ الآن":"Get Started",r=s?"استكشف المزايا":"Explore features",l=s?"md:flex-row-reverse":"md:flex-row",d=s?"خدمات رقمية متكاملة تدعم نجاحك":"All-in-one digital services for your success",B=s?"منصة واحدة تجمع بين الاستضافة، القوالب الجاهزة، وربط الدومين خلال دقائق.":"One platform that brings hosting, ready-made templates and domain connection in minutes.",L=(s?[{title:"إطلاق سريع",description:"امتلك موقعك الجاهز خلال دقائق مع إعداد تلقائي كامل."},{title:"تصاميم احترافية",description:"قوالب مصممة بعناية لتناسب مختلف الأنشطة والمتاجر."},{title:"دعم فني مستمر",description:"فريق مختص لمساعدتك في أي وقت خلال رحلتك الرقمية."},{title:"أداء عالي",description:"استضافة مستقرة وسريعة لتجربة استخدام مميزة."},{title:"مرونة التخصيص",description:"تحكم في محتوى موقعك بسهولة بدون خبرة برمجية."},{title:"تكاملات جاهزة",description:"ربط مع بوابات الدفع وأدوات التسويق بكل سهولة."}]:[{title:"Fast launch",description:"Get your website live in minutes with full automatic setup."},{title:"Professional designs",description:"Carefully crafted templates for different niches and stores."},{title:"Ongoing support",description:"A dedicated team ready to help you throughout your journey."},{title:"High performance",description:"Stable and fast hosting for a great user experience."},{title:"Flexible customization",description:"Easily manage your content without any technical background."},{title:"Ready integrations",description:"Connect payment gateways and marketing tools in no time."}]).map((N,z)=>`
<div class="group rounded-2xl bg-white/90 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-700
           p-5 sm:p-6 shadow-[0_10px_30px_rgba(15,23,42,0.06)]
           hover:shadow-[0_18px_40px_rgba(15,23,42,0.14)]
           transition-all duration-200"
     data-gjs-name="Feature Item"
     data-feature-index="${z}">
  <div class="flex flex-col items-center sm:items-start gap-4">
    <div class="w-12 h-12 flex items-center justify-center rounded-xl
                bg-primary/10 text-primary
                group-hover:bg-primary group-hover:text-white
                transition-colors duration-200 shrink-0">
      <!-- Placeholder icon circle (you can later replace with SVG via editor) -->
      <span class="w-2 h-2 rounded-full bg-current shadow-[0_0_0_3px_rgba(255,255,255,0.35)]"></span>
    </div>
    <span class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white text-center sm:text-start"
          data-field="feature-title">
      ${N.title}
    </span>
  </div>
  <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed text-center sm:text-start"
     data-field="feature-description">
    ${N.description}
  </p>
</div>`.trim()).join(`
`),U=`
<section data-section-type="features"
         data-gjs-name="Features Section"
         class="py-20 sm:py-24 lg:py-28 px-4 sm:px-6 lg:px-8 bg-background" dir="auto">
  <div class="container-xx">
    <!-- Section heading -->
    <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-14 lg:mb-16">
      <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-primary tracking-tight mb-4"
          data-field="title">
        ${d}
      </h2>
      <p class="text-tertiary text-sm sm:text-base leading-relaxed"
         data-field="subtitle">
        ${B}
      </p>
    </div>

    <!-- Main grid: illustration + features cards -->
    <div class="grid gap-12 lg:gap-16 lg:grid-cols-5 items-center">
      <!-- Illustration (optional static preview image) -->
      <div class="lg:col-span-2 flex justify-center" data-gjs-name="Illustration">
        <img
          src="/assets/tamplate/images/Fu.svg"
          alt="Platform features"
          class="max-w-[260px] sm:max-w-sm lg:max-w-[420px] w-full h-auto object-contain mx-auto
                 animate-fade-in-up transition-transform duration-500 ease-out hover:scale-105"
          loading="lazy"
        />
      </div>

      <!-- Features list -->
      <div class="lg:col-span-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8"
             data-gjs-name="Features Grid">
          ${L}
        </div>
      </div>
    </div>
  </div>
</section>`.trim(),D=`
<svg viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round">
  <rect x="3.5" y="5" width="17" height="14" rx="2.5"></rect>
  <path d="M8 9h8M7 13h4M7 16h3"></path>
</svg>`.trim(),q=`
<svg viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round">
  <rect x="3" y="4" width="18" height="16" rx="2.5"></rect>
  <path d="M8 9h8M8 13h5M8 17h3"></path>
</svg>`.trim(),P=`
<svg viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round">
  <path d="M5 7h14M5 12h10M5 17h7"></path>
</svg>`.trim(),dt=`
<svg viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round">
  <rect x="4" y="9" width="16" height="6" rx="3"></rect>
  <path d="M9 12h6"></path>
</svg>`.trim(),_=(N,z)=>`
<div class="pg-block-card">
  <div class="pg-block-icon">
    ${N}
  </div>
  <div class="pg-block-title">
    ${z}
  </div>
</div>
`.trim(),pt=`
<section data-section-type="hero"
         data-gjs-name="Hero"
         class="relative bg-gradient-to-tr from-primary to-primary shadow-2xl overflow-hidden -mt-20">
  <img src="${i}"
       alt="Palgoals templates preview"
       fetchpriority="high"
       class="absolute inset 0 z-0 opacity-80 w-full h-full object-cover object-center ltr:scale-x-[-1] rtl:scale-x-100 transition-transform duration-500 ease-in-out"
       aria-hidden="true"
       decoding="async"
       loading="eager" />

  <div class="relative z-10 px-4 sm:px-8 lg:px-24 py-20 sm:py-28 lg:py-32 flex flex-col-reverse ${l} items-center justify-between gap-12 min-h-[600px] lg:min-h-[700px]">
    <div class="max-w-xl rtl:text-right ltr:text-left text-center md:text-start"
         data-gjs-name="Hero Content">
      <h1 class="text-3xl/20 sm:text-4xl/20 lg:text-5xl/20 font-extrabold text-white leading-tight drop-shadow-lg mb-6"
          data-field="title">
        ${e}
      </h1>

      <p class="text-white/90 text-base sm:text-lg font-light mb-8"
         data-field="subtitle">
        ${t}
      </p>

      <div class="flex flex-row flex-wrap gap-3 justify-center md:justify-start"
           data-gjs-name="Hero Buttons">
        <a href="#"
           data-field="primary-button"
           class="bg-secondary hover:bg-primary text-white font-bold px-6 py-3 rounded-lg shadow transition text-sm sm:text-base">
          ${o}
        </a>

        <a href="#"
           data-field="secondary-button"
           class="bg-white/10 text-white font-bold px-6 py-3 rounded-lg shadow transition hover:bg-white/20 text-sm sm:text-base border border-white/30">
          ${r}
        </a>
      </div>
    </div>
  </div>

  <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl z-0"></div>
</section>`.trim();n.add("pg-hero",{id:"pg-hero",label:_(D,s?"سكشن هيرو":"Hero Section"),category:{id:"pg-hero-sections",label:s?"سكاشن الهيرو":"Hero Sections",open:!0},content:pt}),n.add("pg-features",{id:"pg-features",label:_(q,s?"مميزات":"Features"),category:{id:"pg-content-sections",label:s?"سكاشن المحتوى":"Content Sections",open:!0},content:U}),n.add("pg-text",{id:"pg-text",label:_(P,s?"نص":"Text"),category:{id:"pg-basic-elements",label:s?"عناصر أساسية":"Basic Elements",open:!1},content:`
<p class="text-slate-700" data-gjs-name="Text Block">
  ${s?"اكتب النص هنا…":"Write your text here…"}
</p>`.trim()}),n.add("pg-button",{id:"pg-button",label:_(dt,s?"زر":"Button"),category:{id:"pg-basic-elements",label:s?"عناصر أساسية":"Basic Elements",open:!1},content:`
<a href="#"
   data-gjs-name="Button"
   class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-sky-600 text-white font-semibold">
   ${s?"زر":"Button"}
</a>`.trim()})},rt=function(a){const n=a.DomComponents,s=a.BlockManager,i=a.TraitManager,e=document.documentElement.dir==="rtl"||document.body.dir==="rtl";i.addType("pg-add-feature",{createInput(){const t=document.createElement("div");return t.className="pg-features-controls flex flex-col gap-1.5",t.innerHTML=`
                <button type="button"
                    class="pg-add-feature-btn gjs-btn-prim w-full text-[11px] py-1.5 rounded-md !bg-primary !text-white hover:opacity-90">
                    ${e?"➕ إضافة ميزة جديدة":"➕ Add feature"}
                </button>
                <small class="text-[10px] text-slate-500">
                    ${e?"يمكنك أيضًا نسخ بطاقات المميزات يدويًا من داخل السكشن.":"You can also duplicate feature cards directly in the canvas."}
                </small>
            `,t.querySelector(".pg-add-feature-btn").addEventListener("click",r=>this.onAddFeature(r)),t},onAddFeature(t){t!=null&&t.preventDefault&&(t.preventDefault(),t.stopPropagation());const o=this.target||a.getSelected();if(!o)return;const r=o.find('[data-pg-features-grid="1"]')[0]||o,l=r.components();let d;l.length?(d=l.at(l.length-1).clone(),r.append(d)):d=r.append({tagName:"article",attributes:{class:"pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100"},components:[{tagName:"div",attributes:{class:"flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4"},components:[{type:"text",content:"★"}]},{tagName:"h3",attributes:{class:"text-lg font-semibold text-slate-900 mb-2"},components:[{type:"text",content:e?"عنوان الميزة":"Feature title"}]},{tagName:"p",attributes:{class:"text-sm text-slate-600 leading-relaxed"},components:[{type:"text",content:e?"وصف مختصر للميزة يوضح فائدتها للمستخدم.":"Short description that explains the benefit."}]}]})[0],d&&(a.select(d),a.trigger("change:canvasOffset"))}}),n.addType("pg-features-section",{isComponent(t){return!t||!t.getAttribute?!1:t.getAttribute("data-gjs-type")==="pg-features-section"||t.getAttribute("data-pg-section")==="features"},model:{defaults:{tagName:"section",attributes:{"data-pg-section":"features"},classes:["py-24","px-4","sm:px-8","lg:px-20","bg-[#F9F6FB]"],traits:[{type:"text",label:e?"العنوان الرئيسي":"Main title",name:"data-pg-title"},{type:"textarea",label:e?"الوصف (Subtitle)":"Subtitle",name:"data-pg-subtitle",rows:3},{type:"pg-add-feature",label:e?"إدارة المميزات":"Features",name:"pg-add-feature"}]},init(){this.on("change:attributes:data-pg-title",this.updateTitleFromAttr),this.on("change:attributes:data-pg-subtitle",this.updateSubtitleFromAttr)},updateTitleFromAttr(){const t=this.getAttributes()["data-pg-title"]||"",o=this.find("h2")[0];o&&t&&o.components(t)},updateSubtitleFromAttr(){const t=this.getAttributes()["data-pg-subtitle"]||"",o=this.find("p")[0];o&&o.components(t||"")}}}),s.add("pg-features-section",{id:"pg-features-section",label:e?"سكشن المميزات":"Features Section",category:e?"سكاشن المحتوى":"Sections",attributes:{class:"gjs-fonts gjs-f-b1"},content:`
      <section class="py-24 px-4 sm:px-8 lg:px-20 bg-[#F9F6FB]" data-gjs-type="pg-features-section">
        <div class="max-w-6xl mx-auto">
          <!-- Head -->
          <div class="text-center mb-14">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-primary mb-3 tracking-tight">
              ${e?"خدمات رقمية متكاملة تدعم نجاحك":"All-in-one digital services for your success"}
            </h2>
            <p class="text-tertiary text-base sm:text-lg max-w-2xl mx-auto">
              ${e?"خدمات قيمة متكاملة تساعدك على إطلاق مشروعك بثقة، واستضافة سريعة، وقوالب احترافية.":"Valuable services that help you launch your project with confidence."}
            </p>
          </div>

          <!-- Features Grid -->
          <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3" data-pg-features-grid="1">

            <!-- Feature item 1 -->
            <article class="pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100">
              <div class="flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4">
                <span class="text-lg font-bold">★</span>
              </div>
              <h3 class="text-lg font-semibold text-slate-900 mb-2">
                ${e?"إطلاق سريع":"Fast launch"}
              </h3>
              <p class="text-sm text-slate-600 leading-relaxed">
                ${e?"امتلك موقعك الجاهز خلال دقائق مع إعداد تلقائي كامل.":"Get your website live in minutes with full automatic setup."}
              </p>
            </article>

            <!-- Feature item 2 -->
            <article class="pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100">
              <div class="flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4">
                <span class="text-lg font-bold">★</span>
              </div>
              <h3 class="text-lg font-semibold text-slate-900 mb-2">
                ${e?"تصاميم احترافية":"Professional designs"}
              </h3>
              <p class="text-sm text-slate-600 leading-relaxed">
                ${e?"قوالب مصممة بعناية لتناسب مختلف الأنشطة والمتاجر.":"Carefully crafted templates for different niches."}
              </p>
            </article>

            <!-- Feature item 3 -->
            <article class="pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100">
              <div class="flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4">
                <span class="text-lg font-bold">★</span>
              </div>
              <h3 class="text-lg font-semibold text-slate-900 mb-2">
                ${e?"دعم فني مستمر":"Ongoing support"}
              </h3>
              <p class="text-sm text-slate-600 leading-relaxed">
                ${e?"فريق مختص لمساعدتك في أي وقت خلال رحلتك الرقمية.":"A dedicated team ready to help you anytime."}
              </p>
            </article>

          </div>
        </div>
      </section>
    `})},ct=function(){window.confirm("سيتم مسح كل محتوى الصفحة الحالية، هل أنت متأكد؟")&&(c.DomComponents.clear(),c.setComponents(""),A(),w("Page cleared","dirty"))};(J=document.querySelector('meta[name="csrf-token"]'))!=null&&J.content;const g=M.dataset.loadUrl,u=M.dataset.saveUrl;M.dataset.previewUrl,M.dataset.builderUrl;const h=m("#builder-reset"),v=m("#builder-lang-toggle"),f=m("#builder-lang-menu"),p=document.documentElement.getAttribute("dir")||"ltr",k=p==="rtl"?"ابدأ بسحب بلوك من اليمين…":"Start by dragging a block from the right…",b=m("#builder-empty-state"),H=m("#pg-save-btn"),E=m("#preview-toggle-btn"),x=m("#preview-menu"),G=m("[data-preview-label]"),F=C(".builder-preview-btn"),K=m("#gjs-blocks"),X=m("#gjs-layers"),Y=m("#gjs-traits"),Q=m("#gjs-styles");Z(),st();const W=[],I=document.getElementById("palgoals-app-css");I&&I.href&&W.push(I.href);const c=gt.init({container:"#gjs",height:"100%",width:"auto",fromElement:!1,noticeOnUnload:!0,storageManager:!1,deviceManager:{devices:[{id:"Desktop",name:"Desktop",width:""},{id:"Tablet",name:"Tablet",width:"768px",widthMedia:"992px"},{id:"Mobile",name:"Mobile",width:"375px",widthMedia:"480px"}]},panels:{defaults:[]},blockManager:K?{appendTo:"#gjs-blocks"}:{},layerManager:X?{appendTo:"#gjs-layers"}:{},traitManager:Y?{appendTo:"#gjs-traits"}:{},styleManager:Q?{appendTo:"#gjs-styles",sectors:[{name:"Typography",open:!0,buildProps:["font-family","font-size","font-weight","color","line-height","text-align"]},{name:"Spacing",open:!1,buildProps:["margin","padding"]},{name:"Size",open:!1,buildProps:["width","height","max-width","min-height"]},{name:"Borders",open:!1,buildProps:["border","border-radius","box-shadow"]},{name:"Background",open:!1,buildProps:["background-color","background","opacity"]}]}:{},selectorManager:{componentFirst:!0},canvas:{styles:W}});c.on("load",()=>{var l;b&&(b.style.display="none");const a=c.Canvas.getDocument();if(!a)return;const n=a.documentElement,s=c.Canvas.getBody(),i=a.head||a.querySelector("head");n.setAttribute("dir",p),Object.assign(s.style,{background:"transparent",fontFamily:'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif',color:"#0f172a",margin:"0",padding:"0"});const e=c.getWrapper(),t=(l=e==null?void 0:e.getEl)==null?void 0:l.call(e);if(e&&e.set({droppable:!0}),t&&Object.assign(t.style,{width:"100%",maxWidth:"100%",margin:"0",boxSizing:"border-box"}),i){const d=a.createElement("link");d.rel="stylesheet",d.href="/assets/tamplate/css/app.css",i.appendChild(d)}const o=a.createElement("style"),r=(k||"").replace(/"/g,'\\"');o.innerHTML=`
      [data-pg-selected] {
        outline: 2px dashed #2563eb;
        outline-offset: 4px;
        position: relative;
      }

      html[dir="rtl"] [data-pg-selected]::before,
      html[dir="ltr"] [data-pg-selected]::before {
        content: attr(data-pg-selected);
        position: absolute;
        top: -14px;
        background: #2563eb;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        pointer-events: none;
      }

      html[dir="rtl"] [data-pg-selected]::before {
        right: 0;
        left: auto;
      }

      html[dir="ltr"] [data-pg-selected]::before {
        left: 0;
        right: auto;
      }

      html,
      body {
        height: 100%;
      }

      html[dir="rtl"] body {
        margin: 0 !important;
        padding: 0 !important;
        text-align: right;
      }

      html[dir="ltr"] body {
        margin: 0 !important;
        padding: 0 !important;
        text-align: left;
      }

      .gjs-wrapper {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        min-height: 100vh;
        padding: 0;
        box-sizing: border-box;
      }

      .gjs-wrapper > :first-child {
        margin-top: 0 !important;
      }

      .gjs-wrapper:empty::before {
        content: "${r}";
        display: block;
        text-align: center;
        color: #64748b;
        font-weight: 600;
        padding-top: 60px;
      }
    `,i&&i.appendChild(o),et(),O(),tt(c),c.on("block:add",()=>O()),at(c)}),c.on("component:selected",a=>{if(!a||a.get("type")!=="pg-features-section")return;const n=c.TraitManager.getTraitsViewer().el;if(!n)return;const s=n.querySelector("[data-pg-feature-add]");!s||s.dataset.pgBound==="1"||(s.dataset.pgBound="1",s.addEventListener("click",i=>{i.preventDefault();const e=c.getSelected();if(!e)return;const t=e.find('[data-pg-features-grid="1"]')[0]||e,o=t.components();let r=o.length?o.at(o.length-1):null;if(!r)r=t.append({tagName:"article",attributes:{class:"pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100"},components:[{tagName:"div",attributes:{class:"flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4"},components:[{type:"text",content:"★"}]},{tagName:"h3",attributes:{class:"text-lg font-semibold text-slate-900 mb-2"},components:[{type:"text",content:"عنوان الميزة"}]},{tagName:"p",attributes:{class:"text-sm text-slate-600 leading-relaxed"},components:[{type:"text",content:"وصف مختصر للميزة يوضح فائدتها للمستخدم."}]}]});else{const l=r.clone();t.append(l)}c.trigger("change:canvasOffset")}))}),c.on("component:deselected",a=>{var s;const n=(s=a==null?void 0:a.view)==null?void 0:s.el;n&&n.removeAttribute("data-pg-selected")}),ot(c),nt(c),rt(c);let T=!1,$=!1;const it=3e3;let j=null;const A=()=>{T||(T=!0,w("Unsaved","dirty")),j&&clearTimeout(j),j=window.setTimeout(()=>{!$&&T&&R(!0)},it)};async function lt(){try{w("Loading…","saving");const a=await V(g,{method:"GET"}),n=a==null?void 0:a.structure;ut(n)&&(n.pages||n.assets||n.styles||n.components)?c.loadProjectData(n):c.setComponents(`<div class="p-10 text-slate-600">${k}</div>`),c.getWrapper().set({droppable:!0}),T=!1,w("Loaded","saved")}catch(a){console.error("[Builder] load failed:",a),w("Load failed","error")}}async function R(a=!1){if(!$)try{$=!0,w(a?"Auto saving…":"Saving…","saving");const n=c.getProjectData(),s=c.getHtml(),i=c.getCss();await V(u,{method:"POST",body:{structure:n,html:s,css:i}}),T=!1,w(a?"Auto saved":"Saved","saved")}catch(n){console.error("[Builder] save failed:",n),w("Save failed","error")}finally{$=!1,j&&(clearTimeout(j),j=null)}}c.on("component:add",A),c.on("component:update",A),c.on("component:remove",A),c.on("component:styleUpdate",A),H&&H.addEventListener("click",a=>{a.preventDefault(),R(!1)}),h&&h.addEventListener("click",a=>{a.preventDefault(),ct()}),v&&f&&(v.addEventListener("click",a=>{a.stopPropagation(),f.classList.toggle("hidden")}),document.addEventListener("click",a=>{f.classList.contains("hidden")||f.contains(a.target)||v.contains(a.target)||f.classList.add("hidden")})),document.addEventListener("keydown",a=>{(navigator.platform.toUpperCase().includes("MAC")?a.metaKey:a.ctrlKey)&&a.key.toLowerCase()==="s"&&(a.preventDefault(),R(!1))}),lt()}
