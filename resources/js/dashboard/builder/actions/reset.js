import { setStatus } from '../ui/status';

export function resetPage(editor, markDirty) {
    if (!window.confirm('سيتم مسح كل محتوى الصفحة الحالية، هل أنت متأكد؟')) return;
    editor.DomComponents.clear();
    editor.setComponents('');
    markDirty();
    setStatus('Page cleared', 'dirty');
}
