document.addEventListener('alpine:init', () => {
    Alpine.data('availabilityCalendar', (bookedRanges, reservedRanges = [], availableFrom = null, availableUntil = null) => ({
        viewDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
        ranges: bookedRanges.map((r) => ({
            start: new Date(r.start + 'T00:00:00'),
            end: new Date(r.end + 'T00:00:00'),
        })),

        reservations: reservedRanges.map((r) => ({
            start: new Date(r.start + 'T00:00:00'),
            end: new Date(r.end + 'T00:00:00'),
        })),

        availableFrom: availableFrom ? new Date(availableFrom + 'T00:00:00') : null,
        availableUntil: availableUntil ? new Date(availableUntil + 'T00:00:00') : null,

        isOutsideWindow(date) {
            return !!date && ((this.availableFrom && date < this.availableFrom)
                || (this.availableUntil && date > this.availableUntil));
        },

        get monthLabel() {
            return this.viewDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },

        get weeks() {
            const year = this.viewDate.getFullYear();
            const month = this.viewDate.getMonth();
            const startOffset = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            const cells = [];
            for (let i = 0; i < startOffset; i++) cells.push(null);
            for (let d = 1; d <= daysInMonth; d++) cells.push(new Date(year, month, d));
            while (cells.length % 7 !== 0) cells.push(null);

            const weeks = [];
            for (let i = 0; i < cells.length; i += 7) weeks.push(cells.slice(i, i + 7));
            return weeks;
        },

        isBooked(date) {
            return !!date && (this.ranges.some((r) => date >= r.start && date <= r.end) || this.isReserved(date));
        },

        isReserved(date) {
            return !!date && this.reservations.some((r) => date >= r.start && date <= r.end);
        },

        isPast(date) {
            if (!date) return false;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            return date < today;
        },

        prevMonth() {
            this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() - 1, 1);
        },

        nextMonth() {
            this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, 1);
        },
    }));
});
