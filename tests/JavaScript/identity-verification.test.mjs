import assert from 'node:assert/strict';
import test from 'node:test';
import { identityVerification } from '../../resources/js/identity-verification.js';

const attempt = '65d7678b-6613-4699-9d1d-a802315b6618';
const session = 'c5c07831-d930-479d-a5a3-401c3fdc6117';

function harness(result, options = {}) {
    const calls = [];
    const listeners = new Map();
    globalThis.window = {
        eKYC: () => ({ start: async (args) => { calls.push(['sdk', args]); return result; } }),
        addEventListener(name, callback) { listeners.set(name, callback); },
        removeEventListener(name) { listeners.delete(name); },
    };
    globalThis.document = {
        addEventListener(name, callback) { listeners.set(name, callback); },
        removeEventListener(name) { listeners.delete(name); },
    };
    const wire = {
        async startVerification() { calls.push(['begin']); return attempt; },
        async completeVerification(...args) { calls.push(['finish', ...args]); },
        async cancelVerification(attemptId) { calls.push(['cancel', attemptId]); },
        set(...args) { calls.push(['set', ...args]); },
    };
    const component = identityVerification(wire, {
        available: true, publicKey: 'public-test', sdkUrl: 'https://example.gov.ph/sdk.js', ...options,
    });
    component.$watch = () => {};
    component.$refs = { video: { srcObject: null, async play() {} } };
    component.$nextTick = async () => {};
    return { component, calls, listeners, wire };
}

test('a completed SDK session forwards only the bound attempt and UUID to the server', async () => {
    const { component, calls } = harness({
        status: 'COMPLETED', result: { session_id: session, photo: 'private-photo', photo_url: 'private-url' },
    });
    component.consent = true;
    await component.start();
    assert.deepEqual(calls, [
        ['begin'], ['sdk', { pubKey: 'public-test' }], ['finish', attempt, session],
    ]);
    assert.equal(component.busy, false);
    assert.equal(component.error, '');
    assert.equal(component.verified, undefined, 'Only server rendering may show verified status');
});

test('cancelled, incomplete and malformed SDK sessions cannot request server completion', async () => {
    for (const result of [
        { status: 'CANCELLED', result: { session_id: session } },
        { status: 'COMPLETED', result: {} },
        { status: 'COMPLETED', result: { session_id: 'not-a-uuid' } },
        { status: 'COMPLETED', result: { session_id: { toString: () => session } } },
        null,
    ]) {
        const { component, calls } = harness(result);
        component.consent = true;
        await component.start();
        assert.equal(calls.some(([method]) => method === 'finish'), false);
        assert.deepEqual(calls.at(-1), ['cancel', attempt]);
        assert.match(component.error, /try again/i);
    }
});

test('disabled verification and missing consent never request the SDK or a camera', async () => {
    for (const options of [{ available: false }, { available: true }]) {
        const { component, calls } = harness(null, options);
        component.init();
        await component.start();
        await component.startCamera();
        assert.deepEqual(calls, []);
        assert.equal(component.cameraOpen, false);
        component.destroy();
    }
});

test('server validation failure stops before opening the selfie SDK', async () => {
    const { component, calls, wire } = harness(null);
    wire.startVerification = async () => null;
    component.consent = true;
    await component.start();
    assert.deepEqual(calls, []);
    assert.equal(component.busy, false);
});

test('cancellation ignores a late SDK completion', async () => {
    let resolveSdk;
    const { component, calls } = harness(null);
    window.eKYC = () => ({ start: () => new Promise((resolve) => { resolveSdk = resolve; }) });
    component.consent = true;
    const pending = component.start();
    while (!resolveSdk) await new Promise((resolve) => setTimeout(resolve, 0));
    await component.cancel();
    resolveSdk({ status: 'COMPLETED', result: { session_id: session } });
    await pending;
    assert.equal(calls.some(([method]) => method === 'finish'), false);
    assert.deepEqual(calls.at(-1), ['cancel', attempt]);
    assert.equal(component.busy, false);
});

test('cancelling a pending begin blocks restart until that exact attempt is cancelled', async () => {
    const secondAttempt = '7f06975e-42bd-48d6-9e13-897691f3ed34';
    const { component, calls, wire } = harness({ status: 'COMPLETED', result: { session_id: session } });
    let resolveBegin;
    let resolveCancel;
    wire.startVerification = () => {
        calls.push(['begin']);
        return new Promise((resolve) => { resolveBegin = resolve; });
    };
    wire.cancelVerification = (attemptId) => {
        calls.push(['cancel', attemptId]);
        return new Promise((resolve) => { resolveCancel = resolve; });
    };
    component.consent = true;
    const first = component.start();
    const cancelling = component.cancel();
    assert.equal(component.busy, true);
    await component.start();
    assert.deepEqual(calls, [['begin']], 'Restart cannot race the original begin request');
    resolveBegin(attempt);
    while (!resolveCancel) await new Promise((resolve) => setTimeout(resolve, 0));
    assert.deepEqual(calls, [['begin'], ['cancel', attempt]]);
    await component.start();
    assert.equal(component.busy, true, 'Restart also waits for the cancellation response');
    assert.equal(calls.filter(([method]) => method === 'begin').length, 1);
    resolveCancel();
    await Promise.all([first, cancelling]);
    assert.equal(component.busy, false);

    wire.startVerification = async () => { calls.push(['begin']); return secondAttempt; };
    await component.start();
    assert.deepEqual(calls.at(-1), ['finish', secondAttempt, session]);
    assert.deepEqual(calls.filter(([method]) => method === 'cancel'), [['cancel', attempt]]);
});

test('failed cancellation keeps restart blocked until cancellation can be confirmed', async () => {
    const { component, calls, wire } = harness({ status: 'CANCELLED' });
    wire.cancelVerification = async (attemptId) => {
        calls.push(['cancel', attemptId]);
        throw new Error('Offline');
    };
    component.consent = true;
    await component.start();
    assert.equal(component.busy, true);
    assert.match(component.error, /cancell/i);
    await component.start();
    assert.equal(calls.filter(([method]) => method === 'begin').length, 1);
    wire.cancelVerification = async (attemptId) => { calls.push(['cancel', attemptId]); };
    await component.cancel();
    assert.equal(component.busy, false);
    assert.deepEqual(calls.at(-1), ['cancel', attempt]);
});

test('restart also waits for a cancelled attempt’s pending completion response', async () => {
    const { component, calls, wire } = harness({ status: 'COMPLETED', result: { session_id: session } });
    let resolveComplete;
    wire.completeVerification = (...args) => {
        calls.push(['finish', ...args]);
        return new Promise((resolve) => { resolveComplete = resolve; });
    };
    component.consent = true;
    const pending = component.start();
    while (!resolveComplete) await new Promise((resolve) => setTimeout(resolve, 0));
    const cancelling = component.cancel();
    await new Promise((resolve) => setTimeout(resolve, 0));
    assert.equal(component.busy, true, 'A late completion response must settle before another begin');
    await component.start();
    assert.deepEqual(calls.filter(([method]) => method === 'begin'), [['begin']]);
    assert.deepEqual(calls.at(-1), ['cancel', attempt]);
    resolveComplete();
    await Promise.all([pending, cancelling]);
    assert.equal(component.busy, false);
});

test('a rejected begin allows retry without attempting cancellation with an unknown ID', async () => {
    const { component, calls, wire } = harness({ status: 'COMPLETED', result: { session_id: session } });
    wire.startVerification = async () => { throw new Error('Unavailable'); };
    component.consent = true;
    await component.start();
    assert.equal(component.busy, false);
    assert.deepEqual(calls, []);
    wire.startVerification = async () => attempt;
    await component.start();
    assert.deepEqual(calls.at(-1), ['finish', attempt, session]);
});

test('a QR read preserves raw content, clears camera resources and waits for server authenticity checks', () => {
    const { component, calls } = harness(null);
    let stopped = 0;
    component.stream = { getTracks: () => [{ stop() { stopped++; } }] };
    component.cameraOpen = true;
    component.acceptQr('{"subject":{"name":"SYNTHETIC"}}\n');
    assert.deepEqual(calls, [['set', 'qr_value', '{"subject":{"name":"SYNTHETIC"}}\n', false]]);
    assert.equal(stopped, 1);
    assert.equal(component.cameraOpen, false);
    assert.match(component.notice, /not.*verified/i);
    assert.equal(component.acceptQr('x'.repeat(20001)), false);
    assert.equal(calls.length, 1);
});

test('leaving the page stops camera tracks and removes lifecycle listeners', () => {
    const { component, listeners } = harness(null);
    let stopped = 0;
    component.init();
    component.stream = { getTracks: () => [{ stop() { stopped++; } }] };
    component.$refs.video.srcObject = component.stream;
    listeners.get('livewire:navigating')();
    assert.equal(stopped, 1);
    assert.equal(component.$refs.video.srcObject, null);
    assert.equal(listeners.size, 0);
});

test('browser back cache cleanup still allows a new check when the page returns', () => {
    const { component, listeners } = harness(null);
    let stopped = 0;
    component.init();
    component.consent = true;
    component.stream = { getTracks: () => [{ stop() { stopped++; } }] };
    listeners.get('pagehide')({ persisted: true });
    assert.equal(stopped, 1);
    assert.equal(component.allowed(), true);
    component.destroy();
});

test('a camera permission response arriving after cancellation has every track stopped', async () => {
    let resolveCamera;
    let stopped = 0;
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: {
        mediaDevices: { getUserMedia: () => new Promise((resolve) => { resolveCamera = resolve; }) },
    } });
    const { component } = harness(null);
    component.consent = true;
    const pending = component.startCamera();
    component.stopCamera();
    resolveCamera({ getTracks: () => [{ stop() { stopped++; } }] });
    await pending;
    assert.equal(stopped, 1);
    assert.equal(component.stream, null);
    assert.equal(component.cameraOpen, false);
});

test('oversized and non-image files are rejected before creating an image URL', async () => {
    const { component } = harness(null);
    component.consent = true;
    for (const file of [{ type: 'text/plain', size: 10 }, { type: 'image/png', size: 8 * 1024 * 1024 + 1 }]) {
        await component.scanFile(file);
        assert.match(component.error, /image|8 MB/i);
        assert.equal(component.imageUrl, null);
    }
});

test('large decoded images are rejected and the temporary image URL is released', async () => {
    const { component, calls } = harness(null);
    const released = [];
    const originalImage = globalThis.Image;
    const originalCreate = URL.createObjectURL;
    const originalRevoke = URL.revokeObjectURL;
    globalThis.Image = class {
        naturalWidth = 6000;
        naturalHeight = 4000;
        set src(value) { if (value) queueMicrotask(() => this.onload?.()); }
    };
    URL.createObjectURL = () => 'blob:synthetic-qr';
    URL.revokeObjectURL = (url) => released.push(url);
    try {
        component.consent = true;
        await component.scanFile({ type: 'image/png', size: 1024 });
        assert.match(component.error, /20 megapixels/);
        assert.deepEqual(calls, []);
        assert.deepEqual(released, ['blob:synthetic-qr']);
        assert.equal(component.reading, false);
        assert.equal(component.imageUrl, null);
    } finally {
        globalThis.Image = originalImage;
        URL.createObjectURL = originalCreate;
        URL.revokeObjectURL = originalRevoke;
    }
});
