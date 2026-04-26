export function registerLegacyComponentAliases(editor) {
    const dc = editor.DomComponents;

    if (!dc.getType('Heading') && dc.getType('pg-heading')) {
        dc.addType('Heading', {
            extend: 'pg-heading',
        });
    }

    if (!dc.getType('hero-section')) {
        dc.addType('hero-section', {
            model: {
                defaults: {
                    tagName: 'section',
                    name: 'Hero Section',
                    droppable: true,
                    draggable: true,
                },
            },
        });
    }
}
