import { fetchJson } from '../helpers/http';
import { isNonEmptyObject } from '../helpers/dom';
import { setStatus } from '../ui/status';

export function createProjectStorage(editor, { loadUrl, saveUrl, emptyHint, autosaveDelay = 3000 }) {
    let isDirty = false;
    let isSaving = false;
    let autosaveTimer = null;

    const markDirty = () => {
        if (!isDirty) {
            isDirty = true;
            setStatus('Unsaved', 'dirty');
        }
        if (autosaveTimer) clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(() => {
            if (!isSaving && isDirty) saveProject(true);
        }, autosaveDelay);
    };

    async function loadProject() {
        try {
            setStatus('Loading…', 'saving');
            const data = await fetchJson(loadUrl, { method: 'GET' });
            const structure = data?.structure;

            if (isNonEmptyObject(structure) && (structure.pages || structure.assets || structure.styles || structure.components)) {
                editor.loadProjectData(structure);
            } else {
                editor.setComponents(`<div class="p-10 text-slate-600">${emptyHint}</div>`);
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
            setStatus(isAuto ? 'Auto saving…' : 'Saving…', 'saving');

            const structure = editor.getProjectData();
            const html = editor.getHtml();
            const css = editor.getCss();

            await fetchJson(saveUrl, {
                method: 'POST',
                body: { structure, html, css },
            });

            isDirty = false;
            setStatus(isAuto ? 'Auto saved' : 'Saved', 'saved');
        } catch (e) {
            console.error('[Builder] save failed:', e);
            setStatus('Save failed', 'error');
        } finally {
            isSaving = false;
            if (autosaveTimer) {
                clearTimeout(autosaveTimer);
                autosaveTimer = null;
            }
        }
    }

    return { loadProject, saveProject, markDirty };
}
