import { setStatus } from '../ui/status';

export function resetPage(editor, markDirty) {
    if (!window.confirm('This will clear all current page content. Continue?')) return;

    const wrapper = editor.getWrapper?.();
    if (wrapper?.components?.()) {
        wrapper.components().reset([]);
    } else {
        editor.setComponents('<div></div>');
    }

    markDirty();
    setStatus('Page cleared', 'dirty');
}
