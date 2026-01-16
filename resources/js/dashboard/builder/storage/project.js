import { fetchJson } from '../helpers/http';
import { setStatus } from '../ui/status';

export function createProjectStorage(editor, { loadUrl, saveUrl, emptyHint, locale, autosaveDelay = 3000 })
{
    let isDirty = false;
    let isSaving = false;
    let autosaveTimer = null;

    const clearAutosaveTimer = () => {
        if (autosaveTimer) {
            clearTimeout(autosaveTimer);
            autosaveTimer = null;
        }
    };

    const markDirty = () => {
        if (!isDirty) {
            isDirty = true;
            setStatus('Unsaved', 'dirty');
        }

        clearAutosaveTimer();

        autosaveTimer = window.setTimeout(() => {
            if (!isSaving && isDirty) saveProject(true);
        }, autosaveDelay);
    };

    async function loadProject() {
        try {
            setStatus('Loading…', 'saving');

            const data = await fetchJson(loadUrl, { method: 'GET' });
            const structure = data?.structure;

            const isValidProject =
                structure &&
                typeof structure === 'object' &&
                (
                    Array.isArray(structure.pages) ||
                    Array.isArray(structure.styles) ||
                    Array.isArray(structure.assets) ||
                    structure.components
                );

            if (isValidProject) {
                editor.loadProjectData(structure);
            } else {
                // فقط لو فعلاً ما في شيء داخل الـ canvas
                const hasAnyComponents = !!editor.getWrapper()?.components()?.length;
                if (!hasAnyComponents) {
                    editor.setComponents(`<div class="p-10 text-slate-600">${emptyHint}</div>`);
                }
            }

            editor.getWrapper().set({ droppable: true });
            isDirty = false;
            setStatus('Loaded', 'saved');
        } catch (e) {
            console.error('[Builder] load failed:', e);
            setStatus('Load failed', 'error');
        }
    }

    async function saveProject(isAuto = false) {
        if (isSaving) return;

        try {
            isSaving = true;

            // امنع أي autosave متأخر من التداخل
            clearAutosaveTimer();

            setStatus(isAuto ? 'Auto saving…' : 'Saving…', 'saving');

            const structure = editor.getProjectData();
            const html = editor.getHtml();
            const css = editor.getCss();

            await fetchJson(saveUrl, {
                method: 'POST',
                body: { structure, html, css, locale },
            });

            isDirty = false;
            setStatus(isAuto ? 'Auto saved' : 'Saved', 'saved');
        } catch (e) {
            console.error('[Builder] save failed:', e);
            setStatus('Save failed', 'error');
        } finally {
            isSaving = false;
        }
    }

    return { loadProject, saveProject, markDirty };
}
