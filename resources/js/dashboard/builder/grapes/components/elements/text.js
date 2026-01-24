export function registerTextElement(editor) {
    const dc = editor.DomComponents;

    dc.addType('pg-text', {
        isComponent: (el) => {
            if (!el || !el.tagName) return false;
            const tag = el.tagName.toLowerCase();

            const name = (el.getAttribute?.("data-gjs-name") || "").toLowerCase();
            const isMarked = el.classList?.contains("pg-text") || name === "text";

            // ✅ p فقط
            return tag === 'p' && (isMarked || name === 'text' || name === 'Text');
        },

        model: {
            defaults: {
                tagName: "p",
                name: "Text",
                attributes: {
                    class: "pg-text text-slate-700 leading-relaxed text-right text-base font-normal",
                    "data-gjs-name": "Text",
                },

                // ✅ محتوى افتراضي (سيتم تعديله عبر TinyMCE داخل الـ canvas)
                components: [{ type: "text", content: "اكتب النص هنا…" }],

                // ✅ props للتحكم بالستايل فقط
                pgAlign: "right",
                pgSize: "base",
                pgWeight: "normal",

                traits: [
                    {
                        type: "select",
                        name: "pgAlign",
                        label: "المحاذاة",
                        changeProp: 1,
                        options: [
                            { id: "right", name: "يمين" },
                            { id: "center", name: "وسط" },
                            { id: "left", name: "يسار" },
                        ],
                    },
                    {
                        type: "select",
                        name: "pgSize",
                        label: "الحجم",
                        changeProp: 1,
                        options: [
                            { id: "sm", name: "Small" },
                            { id: "base", name: "Base" },
                            { id: "lg", name: "Large" },
                            { id: "xl", name: "XL" },
                        ],
                    },
                    {
                        type: "select",
                        name: "pgWeight",
                        label: "الوزن",
                        changeProp: 1,
                        options: [
                            { id: "normal", name: "Normal" },
                            { id: "medium", name: "Medium" },
                            { id: "bold", name: "Bold" },
                        ],
                    },
                ],
            },

            init() {
                // ✅ عند التحميل اقرأ الكلاسات الحالية (إذا كان العنصر جاي من HTML)
                hydratePropsFromClasses(this);

                const apply = () => applyTextTraits(this);

                this.on("change:pgAlign change:pgSize change:pgWeight", apply);

                // أول تطبيق
                apply();
            },
        },
    });

    function hydratePropsFromClasses(model) {
        const attrs = model.getAttributes() || {};
        const cls = String(attrs.class || "")
            .split(/\s+/)
            .filter(Boolean);

        // align
        if (cls.includes("text-left")) model.set("pgAlign", "left", { silent: true });
        else if (cls.includes("text-center")) model.set("pgAlign", "center", { silent: true });
        else model.set("pgAlign", "right", { silent: true });

        // size
        if (cls.includes("text-sm")) model.set("pgSize", "sm", { silent: true });
        else if (cls.includes("text-lg")) model.set("pgSize", "lg", { silent: true });
        else if (cls.includes("text-xl")) model.set("pgSize", "xl", { silent: true });
        else model.set("pgSize", "base", { silent: true });

        // weight
        if (cls.includes("font-bold")) model.set("pgWeight", "bold", { silent: true });
        else if (cls.includes("font-medium")) model.set("pgWeight", "medium", { silent: true });
        else model.set("pgWeight", "normal", { silent: true });
    }

    function applyTextTraits(model) {
        const align = model.get("pgAlign") ?? "right";
        const size = model.get("pgSize") ?? "base";
        const weight = model.get("pgWeight") ?? "normal";

        const attrs = model.getAttributes() || {};
        const cls = String(attrs.class || "")
            .split(/\s+/)
            .filter(Boolean);

        // ✅ تنظيف فقط الكلاسات التي نتحكم بها
        const cleaned = cls.filter((c) => {
            if (c === "text-left" || c === "text-center" || c === "text-right") return false;
            if (c === "text-sm" || c === "text-base" || c === "text-lg" || c === "text-xl") return false;
            if (c === "font-normal" || c === "font-medium" || c === "font-bold") return false;
            return true;
        });

        const sizeClass =
            size === "sm" ? "text-sm" :
                size === "lg" ? "text-lg" :
                    size === "xl" ? "text-xl" :
                        "text-base";

        const weightClass =
            weight === "bold" ? "font-bold" :
                weight === "medium" ? "font-medium" :
                    "font-normal";

        const alignClass =
            align === "left" ? "text-left" :
                align === "center" ? "text-center" :
                    "text-right";

        model.addAttributes({
            class: [...cleaned, sizeClass, weightClass, alignClass].join(" ").trim(),
        });
    }
}
