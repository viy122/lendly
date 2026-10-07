import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const DEFAULT_CENTER = [14.5995, 120.9842]; // Metro Manila, used when no location is known yet

const MAP_LAYERS = [
    {
        id: 'standard',
        name: 'Standard',
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '&copy; OpenStreetMap contributors',
        preview: 'https://a.tile.openstreetmap.org/13/6850/3983.png',
    },
    {
        id: 'cyclosm',
        name: 'CyclOSM',
        url: 'https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png',
        attribution: '&copy; OpenStreetMap contributors, CyclOSM',
        preview: 'https://a.tile-cyclosm.openstreetmap.fr/cyclosm/13/6850/3983.png',
    },
    {
        id: 'transport',
        name: 'Transport Map',
        url: 'https://tile.memomaps.de/tilegen/{z}/{x}/{y}.png',
        attribution: '&copy; OpenStreetMap contributors, MemoMaps',
        preview: 'https://tile.memomaps.de/tilegen/13/6850/3983.png',
    },
    {
        id: 'humanitarian',
        name: 'Humanitarian',
        url: 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
        attribution: '&copy; OpenStreetMap contributors, Humanitarian OpenStreetMap Team',
        preview: 'https://a.tile.openstreetmap.fr/hot/13/6850/3983.png',
    },
    {
        id: 'topo',
        name: 'Topo',
        url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
        attribution: '&copy; OpenStreetMap contributors, SRTM, OpenTopoMap',
        preview: 'https://a.tile.opentopomap.org/13/6850/3983.png',
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('locationPicker', (initialLat, initialLng) => ({
        map: null,
        marker: null,

        init() {
            const center = initialLat && initialLng ? [initialLat, initialLng] : DEFAULT_CENTER;

            this.map = L.map(this.$refs.map).setView(center, initialLat ? 14 : 11);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(this.map);

            if (initialLat && initialLng) {
                this.marker = L.marker(center, { draggable: true }).addTo(this.map);
                this.marker.on('dragend', () => this.reportPosition(this.marker.getLatLng()));
            }

            this.map.on('click', (event) => {
                if (this.marker) {
                    this.marker.setLatLng(event.latlng);
                } else {
                    this.marker = L.marker(event.latlng, { draggable: true }).addTo(this.map);
                    this.marker.on('dragend', () => this.reportPosition(this.marker.getLatLng()));
                }
                this.reportPosition(event.latlng);
            });

            setTimeout(() => this.map.invalidateSize(), 200);
        },

        reportPosition(latlng) {
            this.$wire.set('latitude', Number(latlng.lat.toFixed(7)));
            this.$wire.set('longitude', Number(latlng.lng.toFixed(7)));
        },

        useMyLocation() {
            if (!navigator.geolocation) return;

            navigator.geolocation.getCurrentPosition((position) => {
                const latlng = { lat: position.coords.latitude, lng: position.coords.longitude };
                this.map.setView(latlng, 14);

                if (this.marker) {
                    this.marker.setLatLng(latlng);
                } else {
                    this.marker = L.marker(latlng, { draggable: true }).addTo(this.map);
                    this.marker.on('dragend', () => this.reportPosition(this.marker.getLatLng()));
                }

                this.reportPosition(latlng);
            });
        },
    }));

    Alpine.data('listingsMap', (markers, centerLat, centerLng) => ({
        map: null,
        baseLayer: null,
        markerLayer: null,
        locationMarker: null,
        locating: false,
        layers: MAP_LAYERS,
        activeLayer: 'transport',
        layersOpen: true,
        showListingPins: true,
        showMyLocation: true,
        latestMarkers: markers,
        latestLocation: centerLat && centerLng ? [centerLat, centerLng] : null,

        init() {
            const center = centerLat && centerLng ? [centerLat, centerLng] : DEFAULT_CENTER;

            this.map = L.map(this.$refs.map, {
                zoomControl: false,
            }).setView(center, centerLat ? 14 : 11);

            this.setBaseLayer(this.activeLayer);

            this.markerLayer = L.layerGroup().addTo(this.map);
            this.renderMarkers(markers);

            if (centerLat && centerLng) {
                this.setLocationMarker(centerLat, centerLng);
            }

            setTimeout(() => this.map.invalidateSize(), 200);

            this.$watch('$wire.markers', (value) => this.renderMarkers(value));
        },

        setBaseLayer(layerId) {
            const layer = this.layers.find((item) => item.id === layerId) ?? this.layers[0];

            if (!this.map || !layer) return;

            if (this.baseLayer) {
                this.map.removeLayer(this.baseLayer);
            }

            this.baseLayer = L.tileLayer(layer.url, {
                attribution: layer.attribution,
                maxZoom: 19,
            }).addTo(this.map);

            if (this.markerLayer) {
                this.markerLayer.bringToFront();
            }

            this.activeLayer = layer.id;
        },

        renderMarkers(items) {
            this.latestMarkers = items;
            this.markerLayer.clearLayers();

            if (!this.showListingPins) return;

            items.forEach((item) => {
                const marker = L.marker([item.lat, item.lng]).addTo(this.markerLayer);
                marker.bindPopup(
                    `<div style="min-width:160px">
                        <strong>${item.name}</strong><br>
                        PHP ${item.price}/day${item.distance ? ` &middot; ${item.distance}` : ''}<br>
                        <a href="${item.url}" style="color:#2563eb">View listing</a>
                    </div>`
                );
            });
        },

        setLocationMarker(lat, lng) {
            this.latestLocation = [lat, lng];

            if (!this.showMyLocation) return;

            if (this.locationMarker) {
                this.locationMarker.setLatLng([lat, lng]);
            } else {
                this.locationMarker = L.circleMarker([lat, lng], { radius: 6, color: '#2563eb', fillOpacity: 1 })
                    .addTo(this.map)
                    .bindTooltip('Your selected location');
            }
        },

        toggleListingPins() {
            this.renderMarkers(this.latestMarkers);
        },

        toggleMyLocation() {
            if (this.showMyLocation && this.latestLocation) {
                this.setLocationMarker(this.latestLocation[0], this.latestLocation[1]);
                return;
            }

            if (this.locationMarker) {
                this.map.removeLayer(this.locationMarker);
                this.locationMarker = null;
            }
        },

        useMyLocation() {
            if (!navigator.geolocation) return;

            this.locating = true;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;

                    this.map.setView([latitude, longitude], 14);
                    this.setLocationMarker(latitude, longitude);

                    this.$wire.set('centerLat', latitude);
                    this.$wire.set('centerLng', longitude);
                    this.locating = false;
                    window.dispatchEvent(new CustomEvent('location-found'));
                },
                () => {
                    this.locating = false;
                    window.dispatchEvent(new CustomEvent('location-found'));
                }
            );
        },
    }));

    // Plain geolocation capture with no map — used by pages (e.g. Best
    // Match) that only need lat/lng, not a visual map.
    Alpine.data('geolocationCapture', (latProp = 'lat', lngProp = 'lng') => ({
        capturing: false,

        useMyLocation() {
            if (!navigator.geolocation) return;

            this.capturing = true;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.$wire.set(latProp, position.coords.latitude);
                    this.$wire.set(lngProp, position.coords.longitude);
                    this.capturing = false;
                },
                () => {
                    this.capturing = false;
                }
            );
        },
    }));
});
