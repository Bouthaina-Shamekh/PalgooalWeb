import { registerMediaTrait } from './traits/media.js';
import { registerGapControlTrait } from './traits/gap-control';
import { registerIconSelectTrait } from './traits/icon-select';
import { registerRangeTrait } from './traits/range';
import { registerSwitchTrait } from './traits/switch';
import { registerWysiwygTrait } from './traits/wysiwyg';
import { registerHeadingElement } from './elements/heading';
import { registerTextElement } from './elements/text';
import { registerContainerElement } from './layout/container';
import { registerGridElement } from './layout/grid';
import { registerRowElement } from './layout/row';
import { registerServicesSection } from '../sections/services';
import { registerSliderSection } from './sections/slider';


export function registerAllComponents(editor) {
    registerMediaTrait(editor);
    registerGapControlTrait(editor);
    registerIconSelectTrait(editor);
    registerRangeTrait(editor);
    registerSwitchTrait(editor);
    registerWysiwygTrait(editor);
    registerHeadingElement(editor);
    registerTextElement(editor);
    registerContainerElement(editor);
    registerGridElement(editor);
    registerRowElement(editor);
    registerServicesSection(editor);
    registerSliderSection(editor);
}
