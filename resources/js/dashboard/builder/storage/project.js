import { fetchJson } from '../helpers/http';
import { setStatus } from '../ui/status';

function normalizeResolverListenersMap(watcher) {
    if (!watcher || typeof watcher !== 'object') return false;

    const source = watcher.resolverListeners;
    if (!source || typeof source !== 'object') return false;

    const keys = Object.keys(source);
    if (!keys.length) return false;

    const next = Object.create(null);
    let changed = false;

    keys.forEach((key) => {
        const listener = source[key];
        const resolver = listener?.resolver;

        if (resolver && typeof resolver.toJSON === 'function') {
            next[key] = listener;
        } else {
            changed = true;
        }
    });

    if (!changed) return false;

    watcher.resolverListeners = next;
    return true;
}

function resetResolverListenersMap(watcher) {
    if (!watcher || typeof watcher !== 'object') return false;
    watcher.resolverListeners = Object.create(null);
    return true;
}

function sanitizeComponentResolverWatchers(component) {
    if (!component) return 0;

    let cleaned = 0;
    const watchers = component.dataResolverWatchers;

    if (watchers && typeof watchers === 'object') {
        ['propertyWatcher', 'styleWatcher', 'attributeWatcher'].forEach((watcherKey) => {
            if (normalizeResolverListenersMap(watchers[watcherKey])) {
                cleaned += 1;
            }
        });
    }

    const children = component.components?.();
    if (children?.each) {
        children.each((child) => {
            cleaned += sanitizeComponentResolverWatchers(child);
        });
    }

    return cleaned;
}

function resetComponentResolverWatchers(component) {
    if (!component) return 0;

    let cleaned = 0;
    const watchers = component.dataResolverWatchers;

    if (watchers && typeof watchers === 'object') {
        ['propertyWatcher', 'styleWatcher', 'attributeWatcher'].forEach((watcherKey) => {
            if (resetResolverListenersMap(watchers[watcherKey])) {
                cleaned += 1;
            }
        });
    }

    const children = component.components?.();
    if (children?.each) {
        children.each((child) => {
            cleaned += resetComponentResolverWatchers(child);
        });
    }

    return cleaned;
}

function sanitizeEditorBeforeSave(editor) {
    let cleaned = 0;
    const wrapper = editor.getWrapper?.();
    if (wrapper) cleaned += sanitizeComponentResolverWatchers(wrapper);

    const pages = editor.Pages?.getAll?.();
    if (pages?.forEach) {
        pages.forEach((page) => {
            const main = page?.getMainComponent?.();
            if (main && main !== wrapper) {
                cleaned += sanitizeComponentResolverWatchers(main);
            }
        });
    } else if (pages?.each) {
        pages.each((page) => {
            const main = page?.getMainComponent?.();
            if (main && main !== wrapper) {
                cleaned += sanitizeComponentResolverWatchers(main);
            }
        });
    }

    return cleaned;
}

function resetEditorResolverWatchers(editor) {
    let cleaned = 0;
    const wrapper = editor.getWrapper?.();
    if (wrapper) cleaned += resetComponentResolverWatchers(wrapper);

    const pages = editor.Pages?.getAll?.();
    if (pages?.forEach) {
        pages.forEach((page) => {
            const main = page?.getMainComponent?.();
            if (main && main !== wrapper) {
                cleaned += resetComponentResolverWatchers(main);
            }
        });
    } else if (pages?.each) {
        pages.each((page) => {
            const main = page?.getMainComponent?.();
            if (main && main !== wrapper) {
                cleaned += resetComponentResolverWatchers(main);
            }
        });
    }

    return cleaned;
}

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

            const cleanedWatchers = sanitizeEditorBeforeSave(editor);
            if (cleanedWatchers > 0) {
                console.warn(`[Builder] sanitized ${cleanedWatchers} resolver watcher buckets before save`);
            }

            let structure;
            try {
                structure = editor.getProjectData();
            } catch (error) {
                const message = String(error?.message || error || '');
                const isResolverToJsonError = message.includes('toJSON');

                if (!isResolverToJsonError) throw error;

                const resetCount = resetEditorResolverWatchers(editor);
                console.warn(`[Builder] reset ${resetCount} resolver watcher buckets and retry save after toJSON failure`);
                structure = editor.getProjectData();
            }

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

