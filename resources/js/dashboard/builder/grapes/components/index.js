import { registerMediaTrait } from './traits/media.js';
import { registerWysiwygTrait } from './traits/wysiwyg';
import { registerHeadingElement } from './elements/heading';
import { registerTextElement } from './elements/text';
import { registerRowElement } from './layout/row';
import { registerServicesSection } from '../sections/services';
import { registerSliderSection } from './sections/slider';


export function registerAllComponents(editor) {
    registerMediaTrait(editor);
    registerWysiwygTrait(editor);
    registerHeadingElement(editor);
    registerTextElement(editor);
    registerRowElement(editor);
    registerServicesSection(editor);
    registerSliderSection(editor);
}