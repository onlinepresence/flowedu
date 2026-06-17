import * as FilePondModule from 'filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';

const FilePond = FilePondModule.default;

FilePond.registerPlugin(FilePondPluginFileValidateType);
FilePond.registerPlugin(FilePondPluginImagePreview);

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function syncLivewireHidden(selector, value) {
    console.log('[FilePond Sync] syncLivewireHidden called', { selector, value });
    let el = null;
    try {
        if (selector.startsWith('#')) {
            el = document.getElementById(selector.slice(1));
        } else {
            el = document.querySelector(selector);
        }
    } catch (e) {
        console.error('[FilePond Sync] Failed to query selector:', selector, e);
        if (selector.startsWith('#')) {
            el = document.getElementById(selector.slice(1));
        }
    }
    if (!el) {
        console.warn('[FilePond Sync] Element not found for selector:', selector);
        return;
    }
    el.value = value ?? '';
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));

    if (window.Livewire) {
        try {
            const component = window.Livewire.find(el);
            console.log('[FilePond Sync] Livewire component search results:', { el, component });
            if (component) {
                let modelName = el.dataset.model || el.getAttribute('data-model');
                if (!modelName) {
                    const modelAttr = Array.from(el.attributes).find(attr => attr.name.startsWith('wire:model'));
                    if (modelAttr) {
                        modelName = modelAttr.value;
                    }
                }
                console.log('[FilePond Sync] Model name resolved:', modelName);
                if (modelName) {
                    console.log('[FilePond Sync] Setting Livewire property', { modelName, value });
                    component.set(modelName, value ?? '', true);
                }
            } else {
                console.warn('[FilePond Sync] No Livewire component instance found for element:', el);
            }
        } catch (e) {
            console.error('[FilePond Sync] Failed to sync to Livewire component:', e);
        }
    } else {
        console.warn('[FilePond Sync] window.Livewire is not defined.');
    }
}

function bindPond(root) {
    if (root.dataset.filepondBound === '1') {
        return;
    }

    const input = root.querySelector('input[type="file"][data-filepond-input]');
    if (!input) {
        return;
    }

    const purpose = root.dataset.purpose ?? 'generic_image';
    const processUrl = root.dataset.processUrl;
    const revertUrl = root.dataset.revertUrl;
    const syncSelector = root.dataset.syncSelector;

    if (!processUrl || !revertUrl || !syncSelector) {
        return;
    }

    const accepted = root.dataset.accept;
    const pond = FilePond.create(input, {
        credits: false,
        ...(accepted
            ? { acceptedFileTypes: accepted.split(',').map((s) => s.trim()) }
            : {}),
        server: {
            process: (fieldName, file, metadata, load, error, progress, abort) => {
                const formData = new FormData();
                formData.append('filepond', file);
                formData.append('purpose', purpose);

                const request = new XMLHttpRequest();
                request.open('POST', processUrl);
                request.setRequestHeader('X-CSRF-TOKEN', csrfToken());
                request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                request.upload.onprogress = (e) => {
                    progress(e.lengthComputable, e.loaded, e.total);
                };
                request.onload = () => {
                    if (request.status >= 200 && request.status < 300) {
                        const id = request.responseText.trim();
                        load(id);
                        syncLivewireHidden(syncSelector, id);
                    } else {
                        error(request.responseText || 'Upload failed');
                    }
                };
                request.onerror = () => error('Upload failed');
                request.send(formData);

                return {
                    abort: () => {
                        request.abort();
                        abort();
                    },
                };
            },
            revert: (uniqueFileId, load, error) => {
                const request = new XMLHttpRequest();
                request.open('DELETE', revertUrl);
                request.setRequestHeader('X-CSRF-TOKEN', csrfToken());
                request.setRequestHeader('Content-Type', 'text/plain');
                request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                request.onload = () => {
                    if (request.status >= 200 && request.status < 300) {
                        syncLivewireHidden(syncSelector, '');
                        load();
                    } else {
                        error('Revert failed');
                    }
                };
                request.onerror = () => error('Revert failed');
                request.send(uniqueFileId);
            },
        },
    });

    root._collegeFilepond = pond;
    root.dataset.filepondBound = '1';
}

export function initCollegeFileponds(scope = document) {
    scope.querySelectorAll('[data-college-filepond]').forEach((root) => bindPond(root));
}

document.addEventListener('livewire:init', () => {
    initCollegeFileponds(document);
    if (typeof window.Livewire?.hook === 'function') {
        window.Livewire.hook('morph.updated', () => {
            initCollegeFileponds(document);
        });
    }
});

document.addEventListener('livewire:navigated', () => {
    initCollegeFileponds(document);
});

window.addEventListener('clear-filepond', () => {
    document.querySelectorAll('[data-college-filepond]').forEach((root) => {
        if (root._collegeFilepond) {
            try {
                root._collegeFilepond.removeFiles();
            } catch (e) {
                console.error('[FilePond Sync] Failed to clear files:', e);
            }
        }
    });
});
