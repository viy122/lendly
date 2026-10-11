import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

// Replace only browser/Leaflet boundaries; run the actual Alpine component code.
function mapComponents() {
    const components = {};
    const pins = [];
    const navigated = [];
    const wireValues = {};
    const layer = () => ({
        handlers: {},
        icon: { handlers: {}, attributes: {}, setAttribute(name, value) { this.attributes[name] = value; }, addEventListener(name, handler) { this.handlers[name] = handler; } },
        addTo() { return this; },
        getElement() { return this.icon; },
        on(event, handler) { this.handlers[event] = handler; return this; },
        bindPopup(content) { this.popup = content; return this; },
        bindTooltip(content) { this.tooltip = content; return this; },
        openTooltip() { this.tooltipOpen = true; },
        closeTooltip() { this.tooltipOpen = false; },
        setLatLng(position) { this.position = position; return this; },
        getLatLng() { return this.position; },
        clearLayers() {},
    });
    const L = {
        Icon: { Default: { prototype: {}, mergeOptions() {} } },
        map() {
            return Object.assign(layer(), {
                setView(center, zoom) { this.center = Array.from(center); this.zoom = zoom; return this; },
                invalidateSize() {},
            });
        },
        tileLayer: layer,
        layerGroup: layer,
        marker(position, options) {
            const marker = Object.assign(layer(), { position, options });
            pins.push(marker);
            return marker;
        },
        circleMarker(position) { return Object.assign(layer(), { position }); },
    };
    const document = {
        addEventListener(event, callback) { callback(); },
        createElement(tagName) {
            return {
                tagName, style: {}, children: [], textContent: '',
                append(...children) { this.children.push(...children); },
                addEventListener() {},
                set innerHTML(value) { throw new Error('Listing preview must not parse HTML'); },
            };
        },
    };
    const source = readFileSync(new URL('../resources/js/map.js', import.meta.url), 'utf8').replace(/^import .*;\r?\n/gm, '');
    const pinsSource = readFileSync(new URL('../resources/js/listing-map-pin.js', import.meta.url), 'utf8').replace(/^export /gm, '');
    vm.runInNewContext(`${pinsSource}\n${source}`, {
        L, document, markerIcon2x: '', markerIcon: '', markerShadow: '',
        Alpine: { data(name, factory) { components[name] = factory; } },
        setTimeout() {}, navigator: {},
        window: { location: { assign(url) { navigated.push(url); } } },
    });
    function component(name, ...args) {
        return Object.assign(components[name](...args), {
            $refs: { map: {} }, $watch() {},
            $wire: { set(name, value) { wireValues[name] = value; } },
        });
    }
    return { component, pins, navigated, wireValues };
}

for (const [lat, lng] of [[0, 120], [14, 0], [0, 0]]) {
    test(`owner picker initializes at valid coordinates ${lat}, ${lng}`, () => {
        const { component, pins } = mapComponents();
        const picker = component('locationPicker', lat, lng);
        picker.init();
        assert.deepEqual(picker.map.center, [lat, lng]);
        assert.equal(picker.map.zoom, 14);
        assert.equal(pins.length, 1);
        assert.deepEqual(Array.from(pins[0].position), [lat, lng]);
    });

    test(`listing map initializes and marks valid coordinates ${lat}, ${lng}`, () => {
        const { component } = mapComponents();
        const map = component('listingsMap', [], lat, lng);
        map.init();
        assert.deepEqual(map.map.center, [lat, lng]);
        assert.equal(map.map.zoom, 14);
        assert.deepEqual(Array.from(map.locationMarker.position), [lat, lng]);
    });
}

test('missing coordinates use the default center and do not add a location pin', () => {
    for (const [lat, lng] of [[null, null], [0, null], [null, 0]]) {
        const { component, pins } = mapComponents();
        const picker = component('locationPicker', lat, lng);
        picker.init();
        assert.deepEqual(picker.map.center, [14.5995, 120.9842]);
        assert.equal(picker.map.zoom, 11);
        assert.equal(pins.length, 0);
        const map = component('listingsMap', [], lat, lng);
        map.init();
        assert.deepEqual(map.map.center, [14.5995, 120.9842]);
        assert.equal(map.locationMarker, null);
    }
});

test('picker map click and marker drag report zero coordinates without losing precision', () => {
    const { component, wireValues } = mapComponents();
    const picker = component('locationPicker', null, null);
    picker.init();
    picker.map.handlers.click({ latlng: { lat: 0, lng: 120.123456789 } });
    assert.deepEqual(wireValues, { latitude: 0, longitude: 120.1234568 });
    picker.marker.position = { lat: 14.123456789, lng: 0 };
    picker.marker.handlers.dragend();
    assert.deepEqual(wireValues, { latitude: 14.1234568, longitude: 0 });
});

test('pin preview contains a photo and literal name and daily price without parsing HTML', () => {
    const { component, pins } = mapComponents();
    const name = '<img src=x onerror=alert(1)>';
    const map = component('listingsMap', [{
        lat: 0, lng: 0, name, image: '/storage/listings/camera.jpg', price: '1,234.50', distance: '0.0 km away', url: '/listings/7',
    }], null, null);
    map.init();
    const preview = pins[0].tooltip;
    assert.equal(typeof preview, 'object');
    const image = preview.children.find((child) => child.tagName === 'img');
    assert.equal(image.src, '/storage/listings/camera.jpg');
    assert.equal(image.alt, name);
    assert.equal(preview.children.find((child) => child.tagName === 'strong').textContent, name);
    assert.equal(preview.children.find((child) => child.className === 'listing-map-preview__price').textContent, '₱1,234.50 per day');
    assert.equal(preview.children.find((child) => child.className === 'listing-map-preview__distance').textContent, '0.0 km away');
    assert.equal(pins[0].options.title, `${name} · ₱1,234.50 per day`);
    assert.equal(pins[0].icon.attributes.role, 'link');
    assert.equal(preview.children.find((child) => child.tagName === 'a').href, '/listings/7');
});

test('selecting either pin navigates directly to that listing detail', () => {
    const { component, pins, navigated } = mapComponents();
    const map = component('listingsMap', [
        { lat: 0, lng: 0, name: 'Camera', image: null, price: '100.00', distance: null, url: '/listings/7' },
        { lat: 1, lng: 1, name: 'Drill', image: null, price: '200.00', distance: null, url: '/listings/9' },
    ], null, null);
    map.init();
    assert.equal(typeof pins[1].handlers.click, 'function');
    pins[1].handlers.click();
    pins[0].handlers.click();
    assert.deepEqual(navigated, ['/listings/9', '/listings/7']);
    assert.equal(pins[0].tooltip.children.some((child) => child.tagName === 'img'), false);
});

test('Enter and Space select the focused listing pin while other keys keep map navigation', () => {
    const { component, pins, navigated } = mapComponents();
    const map = component('listingsMap', [
        { lat: 0, lng: 0, name: 'Camera', image: null, price: '100.00', distance: null, url: '/listings/7' },
    ], null, null);
    map.init();
    assert.equal(typeof pins[0].icon.handlers.keydown, 'function');
    const prevented = [];
    for (const key of ['ArrowRight', 'Enter', ' ']) {
        pins[0].icon.handlers.keydown({ key, preventDefault() { prevented.push(key); }, stopPropagation() {} });
    }
    assert.deepEqual(navigated, ['/listings/7', '/listings/7']);
    assert.deepEqual(prevented, ['Enter', ' ']);
});
