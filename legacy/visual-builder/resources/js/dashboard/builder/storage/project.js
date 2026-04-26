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

function sanitizeProjectForLoad(input) {
    const walk = (value) => {
        if (Array.isArray(value)) {
            return value.map(walk);
        }

        if (!value || typeof value !== 'object') {
            return value;
        }

        const out = {};
        Object.entries(value).forEach(([key, val]) => {
            // Broken/legacy resolver payloads can crash Grapes serialization during load.
            if (key === 'dataValues') return;
            out[key] = walk(val);
        });
        return out;
    };

    return walk(input);
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

const HEADING_TAGS = new Set(['h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
const TEXT_TAGS = new Set(['p', 'div', 'span']);
const TEXT_NODE_TYPE = 'textnode';

function textFromRawContent(value) {
    const raw = String(value || '');
    if (!raw.trim()) return '';

    const withoutScripts = raw
        .replace(/<script[\s\S]*?<\/script>/gi, ' ')
        .replace(/<style[\s\S]*?<\/style>/gi, ' ');
    const withoutTags = withoutScripts.replace(/<[^>]+>/g, ' ');
    return withoutTags.replace(/\s+/g, ' ').trim();
}

function isRawTextNode(component) {
    if (!component) return false;

    if (component?.is?.(TEXT_NODE_TYPE)) return true;
    const type = String(component.get?.('type') || '').toLowerCase();
    return type === TEXT_NODE_TYPE;
}

function isAnyTextContentNode(component) {
    if (!component) return false;
    if (isRawTextNode(component)) return true;
    if (component?.is?.('text')) return true;

    const type = String(component.get?.('type') || '').toLowerCase();
    return type === 'text';
}

function collectComponentText(component) {
    if (!component) return '';

    if (isAnyTextContentNode(component)) {
        return String(component.get('content') || '');
    }

    const children = component.components?.();
    if (!children?.length) {
        return textFromRawContent(component?.get?.('content'));
    }

    const chunks = [];
    children.each((child) => {
        const next = collectComponentText(child);
        if (next) chunks.push(next);
    });

    if (!chunks.length) {
        const direct = textFromRawContent(component?.get?.('content'));
        if (direct) chunks.push(direct);
    }

    return chunks.join(' ').replace(/\s+/g, ' ').trim();
}

function isValidHeadingChildren(component) {
    const children = component?.components?.();
    if (!children?.length) return true;
    if (children.length !== 1) return false;

    const first = children.at(0);
    if (!first) return true;
    if (isRawTextNode(first)) return true;

    const firstTag = String(first.get?.('tagName') || '').toLowerCase();
    if (firstTag !== 'a') return false;

    const anchorChildren = first.components?.();
    if (!anchorChildren?.length) return false;
    if (anchorChildren.length !== 1) return false;

    const anchorFirst = anchorChildren.at(0);
    return isRawTextNode(anchorFirst);
}

function isTextLikeComponent(component) {
    if (!component) return false;

    const type = String(component.get?.('type') || '').toLowerCase();
    if (type === 'pg-text') return true;

    const tag = String(component.get?.('tagName') || '').toLowerCase();
    if (!TEXT_TAGS.has(tag)) return false;

    const attrs = component.getAttributes?.() || {};
    const name = String(attrs['data-gjs-name'] || '').trim().toLowerCase();
    const classes = String(attrs.class || '');

    return name === 'text' || classes.includes('pg-text');
}

function isAnonymousTextWrapper(component) {
    if (!component) return false;
    const tag = String(component.get?.('tagName') || '').toLowerCase();
    if (!TEXT_TAGS.has(tag)) return false;

    const attrs = component.getAttributes?.() || {};
    const name = String(attrs['data-gjs-name'] || '').trim();
    const klass = String(attrs.class || '').trim();
    const id = String(attrs.id || '').trim();
    const href = String(attrs.href || '').trim();
    const style = String(attrs.style || '').trim();

    return !name && !klass && !id && !href && !style;
}

function isAnonymousEmptyTextWrapper(component) {
    if (!isAnonymousTextWrapper(component)) return false;
    return !collectComponentText(component).trim();
}

function isValidTextChildren(component) {
    const children = component?.components?.();
    if (!children?.length) return false;
    if (children.length !== 1) return false;

    const first = children.at(0);
    if (!first) return false;
    if (isRawTextNode(first)) return true;

    const firstTag = String(first.get?.('tagName') || '').toLowerCase();
    if (firstTag !== 'a') return false;

    const anchorChildren = first.components?.();
    if (!anchorChildren?.length || anchorChildren.length !== 1) return false;
    const anchorFirst = anchorChildren.at(0);
    return isRawTextNode(anchorFirst);
}

function normalizeLegacyQuotedText(value) {
    const raw = String(value || '').trim();
    if (!raw) return raw;
    if (
        (raw.startsWith('"') && raw.endsWith('"')) ||
        (raw.startsWith("'") && raw.endsWith("'"))
    ) {
        return raw.slice(1, -1).trim();
    }
    return raw;
}

function extractFirstTextLinkAttrs(component) {
    let found = null;

    const walk = (node) => {
        if (!node || found) return;

        const tag = String(node.get?.('tagName') || '').toLowerCase();
        if (tag === 'a') {
            const attrs = node.getAttributes?.() || {};
            const href = String(attrs.href || '').trim();
            if (href) {
                found = { href };
                if (attrs.target === '_blank') found.target = '_blank';
                if (attrs.rel) found.rel = String(attrs.rel);
                if (attrs.class) found.class = String(attrs.class);
                return;
            }
        }

        const children = node.components?.();
        if (!children?.each) return;
        children.each((child) => walk(child));
    };

    walk(component);
    return found;
}

function extractFirstHeadingLinkAttrs(component) {
    let found = null;

    const walk = (node) => {
        if (!node || found) return;

        const tag = String(node.get?.('tagName') || '').toLowerCase();
        if (tag === 'a') {
            const attrs = node.getAttributes?.() || {};
            const href = String(attrs.href || '').trim();
            if (href) {
                found = { href };
                if (attrs.target === '_blank') found.target = '_blank';
                if (attrs.rel) found.rel = String(attrs.rel);
                if (attrs.class) found.class = String(attrs.class);
                return;
            }
        }

        const children = node.components?.();
        if (!children?.each) return;
        children.each((child) => walk(child));
    };

    walk(component);
    return found;
}

function sanitizeHeadingComponent(component) {
    if (!component) return 0;

    const tag = String(component.get?.('tagName') || '').toLowerCase();
    if (!HEADING_TAGS.has(tag)) return 0;
    if (isValidHeadingChildren(component)) return 0;

    const children = component.components?.();
    if (!children) return 0;

    const text = collectComponentText(component).trim();
    if (!text) return 0;

    const linkAttrs = extractFirstHeadingLinkAttrs(component);
    if (linkAttrs?.href) {
        children.reset([
            {
                type: 'default',
                tagName: 'a',
                attributes: linkAttrs,
                components: [{ type: TEXT_NODE_TYPE, content: text }],
            },
        ]);
    } else {
        children.reset([{ type: TEXT_NODE_TYPE, content: text }]);
    }

    return 1;
}

function sanitizeTextComponent(component) {
    if (!isTextLikeComponent(component)) return 0;

    let normalized = 0;
    const children = component.components?.();
    if (!children) return 0;

    let text = normalizeLegacyQuotedText(collectComponentText(component)).trim();
    const parent = component.parent?.();
    const siblings = parent?.components?.();

    if ((!text || text === 'Write your text here') && siblings?.at && typeof siblings.indexOf === 'function') {
        const index = siblings.indexOf(component);
        if (index >= 0) {
            const candidateIndexes = [index + 1, index - 1, index + 2, index - 2];
            for (let i = 0; i < candidateIndexes.length; i += 1) {
                const candidate = siblings.at(candidateIndexes[i]);
                if (!candidate || !isAnonymousTextWrapper(candidate)) continue;
                const candidateText = normalizeLegacyQuotedText(collectComponentText(candidate)).trim();
                if (!candidateText) continue;
                text = candidateText;
                candidate.remove();
                normalized += 1;
                break;
            }
        }
    }

    if (!text) return normalized;

    const shouldRebuild = !isValidTextChildren(component) || normalizeLegacyQuotedText(text) !== text;
    if (shouldRebuild) {
        const linkAttrs = extractFirstTextLinkAttrs(component);
        if (linkAttrs?.href) {
            children.reset([
                {
                    type: 'default',
                    tagName: 'a',
                    attributes: linkAttrs,
                    components: [{ type: TEXT_NODE_TYPE, content: text }],
                },
            ]);
        } else {
            children.reset([{ type: TEXT_NODE_TYPE, content: text }]);
        }
        normalized += 1;
    }

    if (siblings?.at && typeof siblings.indexOf === 'function') {
        let hasRemovals = true;
        while (hasRemovals) {
            hasRemovals = false;
            const index = siblings.indexOf(component);
            if (index < 0) break;

            const prev = siblings.at(index - 1);
            if (isAnonymousEmptyTextWrapper(prev)) {
                prev.remove();
                normalized += 1;
                hasRemovals = true;
            }

            const next = siblings.at(index + 1);
            if (isAnonymousEmptyTextWrapper(next)) {
                next.remove();
                normalized += 1;
                hasRemovals = true;
            }
        }
    }

    return normalized;
}

function walkComponentTree(component, callback) {
    if (!component) return;
    callback(component);

    const children = component.components?.();
    if (!children?.each) return;

    const snapshot = [];
    children.each((child) => snapshot.push(child));
    snapshot.forEach((child) => walkComponentTree(child, callback));
}

function normalizeHeadingTree(component) {
    let normalized = 0;
    walkComponentTree(component, (node) => {
        normalized += sanitizeHeadingComponent(node);
    });
    return normalized;
}

function normalizeHeadingStructureBeforeSave(editor) {
    let normalized = 0;
    const wrapper = editor.getWrapper?.();
    if (wrapper) normalized += normalizeHeadingTree(wrapper);

    const pages = editor.Pages?.getAll?.();
    if (pages?.forEach) {
        pages.forEach((page) => {
            const main = page?.getMainComponent?.();
            if (main && main !== wrapper) {
                normalized += normalizeHeadingTree(main);
            }
        });
    } else if (pages?.each) {
        pages.each((page) => {
            const main = page?.getMainComponent?.();
            if (main && main !== wrapper) {
                normalized += normalizeHeadingTree(main);
            }
        });
    }

    return normalized;
}

function normalizeTextTree(component) {
    let normalized = 0;
    walkComponentTree(component, (node) => {
        normalized += sanitizeTextComponent(node);
    });
    return normalized;
}

function normalizeTextStructureBeforeSave(editor) {
    let normalized = 0;
    const wrapper = editor.getWrapper?.();
    if (wrapper) normalized += normalizeTextTree(wrapper);

    const pages = editor.Pages?.getAll?.();
    if (pages?.forEach) {
        pages.forEach((page) => {
            const main = page?.getMainComponent?.();
            if (main && main !== wrapper) {
                normalized += normalizeTextTree(main);
            }
        });
    } else if (pages?.each) {
        pages.each((page) => {
            const main = page?.getMainComponent?.();
            if (main && main !== wrapper) {
                normalized += normalizeTextTree(main);
            }
        });
    }

    return normalized;
}

export function createProjectStorage(editor, { loadUrl, saveUrl, emptyHint, locale, autosaveDelay = 3000 })
{
    let isDirty = false;
    let isSaving = false;
    let isLoading = false;
    let autosaveTimer = null;

    const clearAutosaveTimer = () => {
        if (autosaveTimer) {
            clearTimeout(autosaveTimer);
            autosaveTimer = null;
        }
    };

    const markDirty = () => {
        if (isLoading) return;

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
            isLoading = true;
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
                try {
                    editor.loadProjectData(structure);
                } catch (loadError) {
                    const message = String(loadError?.message || loadError || '');
                    const isResolverToJsonError = message.includes('toJSON');

                    if (!isResolverToJsonError) throw loadError;

                    const sanitizedStructure = sanitizeProjectForLoad(structure);
                    console.warn('[Builder] load fallback: stripping dataValues from stored project');
                    editor.loadProjectData(sanitizedStructure);
                }
            } else {
                // فقط لو فعلاً ما في شيء داخل الـ canvas
                const hasAnyComponents = !!editor.getWrapper()?.components()?.length;
                if (!hasAnyComponents) {
                    editor.setComponents(`<div class="p-10 text-slate-600">${emptyHint}</div>`);
                }
            }

            editor.getWrapper().set({ droppable: true });
            clearAutosaveTimer();
            isDirty = false;
            setStatus('Loaded', 'saved');
        } catch (e) {
            console.error('[Builder] load failed:', e);
            setStatus('Load failed', 'error');
        } finally {
            isLoading = false;
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

            const normalizedHeadings = normalizeHeadingStructureBeforeSave(editor);
            if (normalizedHeadings > 0) {
                console.warn(`[Builder] normalized ${normalizedHeadings} malformed heading component(s) before save`);
            }

            const normalizedTexts = normalizeTextStructureBeforeSave(editor);
            if (normalizedTexts > 0) {
                console.warn(`[Builder] normalized ${normalizedTexts} malformed text component(s) before save`);
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

            const rawHtml = editor.getHtml();
            const html = String(rawHtml || '').trim() ? rawHtml : '<div></div>';
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


