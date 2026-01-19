import { registerWysiwygTrait } from './traits/wysiwyg';
import { registerHeadingElement } from './elements/heading';
import { registerTextElement } from './elements/text';
import { registerRowElement } from './layout/row';
import { registerServicesSection } from '../sections/services';

export function registerAllComponents(editor) {
    registerWysiwygTrait(editor);
    registerHeadingElement(editor);
    registerTextElement(editor);
    registerRowElement(editor);
    registerServicesSection(editor);
}

