export function createListingPreview(item) {
    const preview = document.createElement('div');
    preview.className = 'listing-map-preview';

    const fallback = document.createElement('div');
    fallback.className = 'listing-map-preview__fallback';
    fallback.textContent = 'No item photo';

    if (item.image) {
        const image = document.createElement('img');
        image.className = 'listing-map-preview__image';
        image.src = item.image;
        image.alt = item.name;
        image.decoding = 'async';
        image.addEventListener('error', () => image.replaceWith(fallback), { once: true });
        preview.append(image);
    } else {
        preview.append(fallback);
    }

    const name = document.createElement('strong');
    name.className = 'listing-map-preview__name';
    name.textContent = item.name;
    preview.append(name);

    const price = document.createElement('p');
    price.className = 'listing-map-preview__price';
    price.textContent = `₱${item.price} per day`;
    preview.append(price);

    if (item.distance) {
        const distance = document.createElement('p');
        distance.className = 'listing-map-preview__distance';
        distance.textContent = item.distance;
        preview.append(distance);
    }

    const link = document.createElement('a');
    link.className = 'listing-map-preview__link';
    link.href = item.url;
    link.textContent = 'Open item details';
    preview.append(link);

    return preview;
}

export function bindListingPin(marker, item, navigate = (url) => window.location.assign(url)) {
    marker.bindTooltip(createListingPreview(item), {
        direction: 'top',
        offset: [0, -32],
        className: 'listing-map-tooltip',
        interactive: true,
        opacity: 1,
    });
    marker.on('click', () => navigate(item.url));

    const icon = marker.getElement();
    icon.setAttribute('role', 'link');
    icon.setAttribute('aria-label', `Open ${item.name}, ₱${item.price} per day${item.distance ? `, ${item.distance}` : ''}`);
    icon.addEventListener('focus', () => marker.openTooltip());
    icon.addEventListener('blur', () => marker.closeTooltip());
    icon.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        event.stopPropagation();
        navigate(item.url);
    });

    return marker;
}
