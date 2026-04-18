document.addEventListener('DOMContentLoaded', () => {
    const main          = document.querySelector('main[data-module-id]');
    const moduleId      = main?.dataset.moduleId;
    const timerStorageKey = `practicum_timer_${moduleId}`;

    const container     = document.getElementById('monaco-editor');
    const input         = document.getElementById('code');
    const form          = document.getElementById('code-run-form');
    const continueForm  = document.getElementById('continue-form');
    const timerElement  = document.getElementById('session-timer');
    const endSessionForm = document.getElementById('end-session-form');
    const runSessionExpiresAt      = document.getElementById('run-session-expires-at');
    const continueSessionExpiresAt = document.getElementById('continue-session-expires-at');

    if (!timerElement) {
        window.sessionStorage.removeItem(timerStorageKey);
    }

    if (timerElement && endSessionForm) {
        const storageKey       = timerElement.dataset.storageKey || timerStorageKey;
        const sessionSignature = timerElement.dataset.sessionSignature || storageKey;
        const serverExpiresAt  = Number(timerElement.dataset.expiresAt || 0);
        const storedState      = JSON.parse(window.sessionStorage.getItem(storageKey) || 'null');
        const useStoredDeadline = storedState
            && storedState.signature === sessionSignature
            && Number(storedState.expiresAt || 0) > Date.now();

        let expiresAt = useStoredDeadline ? Number(storedState.expiresAt) : serverExpiresAt;
        let hasEnded  = false;

        const formatTime = (totalSeconds) => {
            const h = Math.floor(totalSeconds / 3600);
            const m = Math.floor((totalSeconds % 3600) / 60);
            const s = totalSeconds % 60;
            return h > 0
                ? [h, m, s].map(v => String(v).padStart(2, '0')).join(':')
                : [m, s].map(v => String(v).padStart(2, '0')).join(':');
        };

        const remainingSeconds  = () => Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
        const renderTimer       = () => { timerElement.textContent = formatTime(remainingSeconds()); };
        const syncDeadlineInputs = () => [runSessionExpiresAt, continueSessionExpiresAt]
            .filter(Boolean)
            .forEach(field => { field.value = String(expiresAt); });

        const persistTimerState = () => {
            window.sessionStorage.setItem(storageKey, JSON.stringify({ signature: sessionSignature, expiresAt }));
        };

        const endSession = () => {
            if (hasEnded) return;
            hasEnded = true;
            window.sessionStorage.removeItem(storageKey);
            endSessionForm.submit();
        };

        persistTimerState();
        syncDeadlineInputs();
        renderTimer();

        form?.addEventListener('submit', syncDeadlineInputs);
        continueForm?.addEventListener('submit', syncDeadlineInputs);
        endSessionForm.addEventListener('submit', () => window.sessionStorage.removeItem(storageKey));

        if (remainingSeconds() <= 0) {
            endSession();
        } else {
            window.setInterval(() => {
                persistTimerState();
                syncDeadlineInputs();
                renderTimer();
                if (remainingSeconds() <= 0) endSession();
            }, 1000);
        }
    }

    if (!container || !input || !form || typeof window.require === 'undefined') return;

    const baseUrl  = 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs';
    const language = container.dataset.language || 'plaintext';

    window.require.config({ paths: { vs: baseUrl } });

    window.MonacoEnvironment = {
        getWorkerUrl(_, label) {
            const workerMap = { json: 'json', css: 'css', html: 'html', javascript: 'typescript', typescript: 'typescript' };
            const worker = workerMap[label] || 'editor';
            return `data:text/javascript;charset=utf-8,${encodeURIComponent(`
                self.MonacoEnvironment = { baseUrl: '${baseUrl}/' };
                importScripts('${baseUrl}/base/worker/workerMain.js');
            `)}`;
        },
    };

    window.require(['vs/editor/editor.main'], () => {
        const editor = monaco.editor.create(container, {
            value: input.value,
            language,
            theme: 'vs-dark',
            automaticLayout: true,
            minimap: { enabled: false },
            scrollBeyondLastLine: false,
            tabSize: 4,
            insertSpaces: true,
            fontFamily: 'JetBrains Mono, monospace',
            fontSize: 14,
            lineHeight: 24,
            roundedSelection: false,
            lineNumbersMinChars: 3,
            quickSuggestions: true,
            suggestOnTriggerCharacters: true,
            bracketPairColorization: { enabled: true },
            guides: { bracketPairs: true, indentation: true },
            scrollbar: { verticalScrollbarSize: 10, horizontalScrollbarSize: 10 },
            padding: { top: 16, bottom: 16 },
        });

        const syncValue = () => { input.value = editor.getValue(); };
        editor.onDidChangeModelContent(syncValue);
        form.addEventListener('submit', syncValue);
        syncValue();
    });
});