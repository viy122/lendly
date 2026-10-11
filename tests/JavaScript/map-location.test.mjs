import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

// Load the Alpine component with fake browser location and map services.
// No device location, map tile service, or application database is accessed.
function locationHarness(geolocation, lat = null, lng = null) {
    const components = new Map();
    const requests = [];
    const mapViews = [];
    const events = [];
    const locationMarkers = [];
    const source = readFileSync(new URL('../../resources/js/map.js', import.meta.url), 'utf8')
        .replace(/^import .*;\r?\n/gm, '');
    const context = vm.createContext({
        L: {
            Icon: { Default: { prototype: {}, mergeOptions() {} } },
            circleMarker(position) {
                locationMarkers.push(Array.from(position));
                return { addTo() { return this; }, bindTooltip() { return this; } };
            },
        },
        markerIcon2x: '', markerIcon: '', markerShadow: '',
        document: { addEventListener(event, callback) { callback(); } },
        Alpine: { data(name, factory) { components.set(name, factory); } },
        navigator: { geolocation },
        window: { dispatchEvent(event) { events.push(event.type); } },
        CustomEvent: class { constructor(type) { this.type = type; } },
    });
    vm.runInContext(source, context);
    const component = components.get('listingsMap')([], lat, lng);
    component.map = { setView(position, zoom) { mapViews.push([Array.from(position), zoom]); } };
    component.$wire = { set(...args) { requests.push(args); } };
    return { component, requests, mapViews, events, locationMarkers };
}

test('location is requested only after the renter explicitly asks', () => {
    let calls = 0;
    const { component } = locationHarness({ getCurrentPosition() { calls++; } });
    assert.equal(calls, 0);
    component.useMyLocation();
    component.useMyLocation();
    assert.equal(calls, 1, 'Repeated clicks must not start concurrent permission requests');
    assert.equal(component.locating, true);
});

test('valid zero coordinates center and mark the map and update both coordinates together', () => {
    for (const [latitude, longitude] of [[0, 120], [14, 0], [0, 0]]) {
        const state = locationHarness({
            getCurrentPosition(success, failure, options) {
                assert.equal(options.timeout, 10000);
                success({ coords: { latitude, longitude } });
            },
        });
        state.component.useMyLocation();
        assert.deepEqual(state.mapViews, [[[latitude, longitude], 14]]);
        assert.deepEqual(state.locationMarkers, [[latitude, longitude]]);
        assert.deepEqual(state.requests, [['centerLat', latitude, false], ['centerLng', longitude]]);
        assert.equal(state.component.locating, false);
        assert.equal(state.component.locationError, '');
        assert.deepEqual(state.events, ['location-found']);
    }
});

test('permission denial and location errors keep existing search results and location intact', () => {
    for (const code of [1, 2, 3]) {
        const state = locationHarness({ getCurrentPosition(success, failure) { failure({ code }); } }, 14, 120);
        state.component.useMyLocation();
        assert.equal(state.component.locating, false);
        assert.match(state.component.locationError, /keep searching by keyword and category/);
        assert.match(state.component.locationError, code === 1 ? /denied/ : /could not be found/);
        assert.deepEqual(state.requests, []);
        assert.deepEqual(state.mapViews, []);
        assert.deepEqual(Array.from(state.component.latestLocation), [14, 120]);
    }
});

test('unsupported browsers show an actionable fallback without applying distance filtering', () => {
    const state = locationHarness(undefined);
    state.component.useMyLocation();
    assert.equal(state.component.locating, false);
    assert.equal(state.component.latestLocation, null);
    assert.match(state.component.locationError, /browser cannot share your location/);
    assert.match(state.component.locationError, /browse by city or area/);
    assert.deepEqual(state.requests, []);
});

test('retry after permission denial clears the error when location succeeds', () => {
    let calls = 0;
    const state = locationHarness({
        getCurrentPosition(success, failure) {
            if (++calls === 1) failure({ code: 1 });
            else success({ coords: { latitude: 0, longitude: 0 } });
        },
    });
    state.component.useMyLocation();
    assert.match(state.component.locationError, /denied/);
    state.component.useMyLocation();
    assert.equal(state.component.locationError, '');
    assert.equal(state.component.locating, false);
    assert.deepEqual(state.locationMarkers, [[0, 0]]);
});
