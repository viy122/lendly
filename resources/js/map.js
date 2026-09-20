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
        markerLayer: null,
        locationMarker: null,
        locating: false,

        init() {
            const center = centerLat && centerLng ? [centerLat, centerLng] : DEFAULT_CENTER;

            this.map = L.map(this.$refs.map).setView(center, centerLat ? 14 : 11);

            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                attribution: '&copy; <a href="https://www.esri.com">Esri</a> &mdash; Source: Esri, HERE, Garmin, USGS, Intermap, INCREMENT P, NRCan, Esri Japan, METI, Esri China (Hong Kong), Esri Korea, Esri (Thailand), NGCC, © OpenStreetMap contributors, and the GIS User Community',
                maxZoom: 19,
            }).addTo(this.map);

            this.markerLayer = L.layerGroup().addTo(this.map);
            this.renderMarkers(markers);

            if (centerLat && centerLng) {
                this.setLocationMarker(centerLat, centerLng);
            }

            setTimeout(() => this.map.invalidateSize(), 200);

            this.$watch('$wire.markers', (value) => this.renderMarkers(value));
        },

        renderMarkers(items) {
            this.markerLayer.clearLayers();

            items.forEach((item) => {
                const marker = L.marker([item.lat, item.lng]).addTo(this.markerLayer);
                marker.bindPopup(
                    `<div style="min-width:160px">
                        <strong>${item.name}</strong><br>
                        ₱${item.price}/day &middot; ${item.distance ?? ''}<br>
                        <a href="${item.url}" style="color:#2563eb">View listing</a>
                    </div>`
                );
            });
        },

        setLocationMarker(lat, lng) {
            if (this.locationMarker) {
                this.locationMarker.setLatLng([lat, lng]);
            } else {
                this.locationMarker = L.circleMarker([lat, lng], { radius: 6, color: '#2563eb', fillOpacity: 1 })
                    .addTo(this.map)
                    .bindTooltip('Your selected location');
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
