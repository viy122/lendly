import jsQR from 'jsqr';

const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
let sdkLoad;

function loadSdk(url) {
    if (typeof window.eKYC === 'function') return Promise.resolve();
    if (sdkLoad) return sdkLoad;

    sdkLoad = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        const fail = () => {
            clearTimeout(timeout);
            script.remove();
            sdkLoad = null;
            reject(new Error('Selfie service unavailable'));
        };
        const timeout = setTimeout(fail, 30000);
        script.src = url;
        script.async = true;
        script.onload = () => {
            clearTimeout(timeout);
            if (typeof window.eKYC !== 'function') return fail();
            resolve();
        };
        script.onerror = fail;
        document.head.appendChild(script);
    });

    return sdkLoad;
}

export function identityVerification(wire, options) {
    return {
        consent: false,
        busy: false,
        reading: false,
        phase: '',
        error: '',
        notice: '',
        cameraOpen: false,
        stream: null,
        cameraTimer: null,
        cameraToken: 0,
        imageUrl: null,
        imageToken: 0,
        operation: 0,
        attemptId: null,
        beginPromise: null,
        completePromise: null,
        cancelling: false,
        cancelPromise: null,
        destroyed: false,

        init() {
            this.onLeave = () => this.destroy();
            this.onPageHide = () => void this.cancel();
            document.addEventListener('livewire:navigating', this.onLeave);
            window.addEventListener('pagehide', this.onPageHide);
            this.$watch('consent', (value) => {
                if (!value) {
                    this.stopCamera();
                    this.releaseImage();
                    if (this.busy) void this.cancel();
                }
            });
        },

        allowed() {
            if (this.destroyed || !options.available) return false;
            if (this.consent) return true;
            this.error = 'Please read and accept the consent below before continuing.';
            return false;
        },

        acceptQr(value) {
            if (typeof value !== 'string' || !value.trim() || value.length > 20000) {
                this.error = 'This QR code could not be read. Try a clearer image or scan it again.';
                return false;
            }

            wire.set('qr_value', value, false);
            this.error = '';
            this.notice = 'QR code read. Your identity is not yet verified; continue with the live selfie.';
            this.stopCamera();
            return true;
        },

        readFrame(source, width, height) {
            const scale = Math.min(1, 2048 / Math.max(width, height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(width * scale));
            canvas.height = Math.max(1, Math.round(height * scale));
            const context = canvas.getContext('2d', { willReadFrequently: true });
            context.drawImage(source, 0, 0, canvas.width, canvas.height);
            const frame = context.getImageData(0, 0, canvas.width, canvas.height);
            const result = jsQR(frame.data, frame.width, frame.height, { inversionAttempts: 'attemptBoth' });
            canvas.width = canvas.height = 0;
            return result?.data;
        },

        async scanFile(file) {
            if (!file || this.busy || !this.allowed()) return;
            this.stopCamera();
            this.releaseImage();
            this.error = '';
            this.notice = '';

            if (!/^image\/(png|jpeg|webp|gif|bmp)$/.test(file.type)) {
                this.error = 'Choose a PNG, JPG, WebP, GIF or BMP image of your QR code.';
                return;
            }
            if (file.size > 8 * 1024 * 1024) {
                this.error = 'Choose an image smaller than 8 MB.';
                return;
            }

            const token = ++this.imageToken;
            this.reading = true;
            const image = new Image();
            const url = URL.createObjectURL(file);
            this.imageUrl = url;

            try {
                await new Promise((resolve, reject) => {
                    image.onload = resolve;
                    image.onerror = reject;
                    image.src = url;
                });
                if (this.destroyed || token !== this.imageToken) return;
                const pixels = image.naturalWidth * image.naturalHeight;
                if (!pixels || pixels > 20000000) {
                    this.error = 'Choose an image up to 20 megapixels, or crop it to the QR code.';
                    return;
                }
                const value = this.readFrame(image, image.naturalWidth, image.naturalHeight);
                if (!value || !this.acceptQr(value)) {
                    this.error = 'No readable QR code found. Crop around the QR code and try again.';
                }
            } catch {
                if (!this.destroyed && token === this.imageToken) {
                    this.error = 'This image could not be read. Choose another image or use the camera.';
                }
            } finally {
                URL.revokeObjectURL(url);
                image.onload = image.onerror = null;
                image.src = '';
                if (token === this.imageToken) {
                    this.imageUrl = null;
                    this.reading = false;
                }
            }
        },

        releaseImage() {
            this.imageToken++;
            if (this.imageUrl) URL.revokeObjectURL(this.imageUrl);
            this.imageUrl = null;
            this.reading = false;
        },

        async startCamera() {
            if (this.busy || this.cameraOpen || !this.allowed()) return;
            this.releaseImage();
            this.error = '';
            this.notice = '';
            if (!navigator.mediaDevices?.getUserMedia) {
                this.error = 'Camera scanning is unavailable in this browser. Choose a QR image instead.';
                return;
            }

            this.cameraOpen = true;
            const token = ++this.cameraToken;
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false,
                });
                if (this.destroyed || token !== this.cameraToken || !this.consent) {
                    stream.getTracks().forEach((track) => track.stop());
                    return;
                }
                this.stream = stream;
                await this.$nextTick();
                if (this.destroyed || token !== this.cameraToken) return;
                this.$refs.video.srcObject = stream;
                await this.$refs.video.play();
                if (token !== this.cameraToken) return;
                this.scanCamera(token);
            } catch {
                if (this.destroyed || token !== this.cameraToken) return;
                this.stopCamera();
                this.error = 'Camera access was unavailable. Allow camera access and try again, or choose a QR image.';
            }
        },

        scanCamera(token) {
            if (this.destroyed || token !== this.cameraToken || !this.stream) return;
            const video = this.$refs.video;
            try {
                if (video.readyState >= 2 && video.videoWidth && video.videoHeight) {
                    const value = this.readFrame(video, video.videoWidth, video.videoHeight);
                    if (value && this.acceptQr(value)) return;
                }
                this.cameraTimer = setTimeout(() => this.scanCamera(token), 250);
            } catch {
                this.stopCamera();
                this.error = 'The camera image could not be read. Choose a QR image instead.';
            }
        },

        stopCamera() {
            this.cameraToken++;
            clearTimeout(this.cameraTimer);
            this.cameraTimer = null;
            this.stream?.getTracks().forEach((track) => track.stop());
            this.stream = null;
            if (this.$refs?.video) this.$refs.video.srcObject = null;
            this.cameraOpen = false;
        },

        async start() {
            if (this.busy || this.reading || !this.allowed()) return;
            this.stopCamera();
            this.busy = true;
            this.phase = 'Preparing your identity check…';
            this.error = '';
            this.notice = '';
            const operation = ++this.operation;

            try {
                this.beginPromise = wire.startVerification();
                const attemptId = await this.beginPromise;
                if (operation !== this.operation || this.destroyed) return;
                this.beginPromise = null;
                if (typeof attemptId !== 'string' || !uuid.test(attemptId)) return;
                this.attemptId = attemptId;
                await loadSdk(options.sdkUrl);
                if (operation !== this.operation || this.destroyed || !this.consent) return;
                this.phase = 'Follow the instructions in the live selfie window.';
                const response = await window.eKYC().start({ pubKey: options.publicKey });
                if (operation !== this.operation || this.destroyed || !this.consent) return;
                const sessionId = response?.result?.session_id;
                if (response?.status !== 'COMPLETED' || typeof sessionId !== 'string' || !uuid.test(sessionId)) {
                    throw new Error('Selfie incomplete');
                }
                this.phase = 'Checking your National ID, details and live selfie…';
                this.completePromise = wire.completeVerification(attemptId, sessionId);
                await this.completePromise;
            } catch {
                if (operation === this.operation && !this.destroyed) {
                    if (await this.cancel()) {
                        this.notice = '';
                        this.error = 'Your identity check could not be completed. Please try again.';
                    }
                }
            } finally {
                if (operation === this.operation) {
                    this.busy = false;
                    this.attemptId = null;
                    this.beginPromise = null;
                    this.completePromise = null;
                    this.phase = '';
                }
            }
        },

        async cancel() {
            this.stopCamera();
            this.releaseImage();
            if (this.cancelling) return this.cancelPromise;
            this.operation++;
            const beginning = this.beginPromise;
            const completing = this.completePromise;
            let attemptId = this.attemptId;
            if (!beginning && !attemptId) {
                this.busy = false;
                this.phase = '';
                return true;
            }

            this.busy = true;
            this.cancelling = true;
            this.phase = 'Cancelling your identity check…';
            this.cancelPromise = (async () => {
                try {
                    if (beginning) {
                        try { attemptId = await beginning; } catch {}
                        this.beginPromise = null;
                    }
                    if (typeof attemptId === 'string' && uuid.test(attemptId)) {
                        this.attemptId = attemptId;
                        await wire.cancelVerification(attemptId);
                    }
                    if (completing) {
                        try { await completing; } catch {}
                        this.completePromise = null;
                    }
                    this.attemptId = null;
                    this.busy = false;
                    this.phase = '';
                    this.error = '';
                    if (!this.destroyed) this.notice = 'Identity check cancelled. You can try again when you are ready.';
                    return true;
                } catch {
                    if (!this.destroyed) this.error = 'Cancellation could not be confirmed. Please try cancelling again.';
                    return false;
                } finally {
                    this.cancelling = false;
                    this.cancelPromise = null;
                }
            })();

            return this.cancelPromise;
        },

        destroy() {
            if (this.destroyed) return;
            this.destroyed = true;
            void this.cancel();
            document.removeEventListener('livewire:navigating', this.onLeave);
            window.removeEventListener('pagehide', this.onPageHide);
        },
    };
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        Alpine.data('identityVerification', identityVerification);
    });
}
