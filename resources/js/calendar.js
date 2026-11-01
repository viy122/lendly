document.addEventListener('alpine:init', () => {
    Alpine.data('availabilityCalendar', (bookedRanges, reservedRanges = [], availableFrom = null, availableUntil = null, earliestDate, maximumDays = null, requestsOpen = true, rentalUnit = 'day') => ({
        maximumDays,
        requestsOpen,
        rentalUnit,
        viewDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
        minimumDate: new Date(earliestDate + 'T00:00:00'),
        selectedStart: null,
        selectedEnd: null,
        selectionError: '',
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

        canSelect(date) {
            return this.requestsOpen && !!date && date >= this.minimumDate && !this.isOutsideWindow(date) && !this.isBooked(date);
        },

        isSelected(date) {
            return !!date && !!this.selectedStart && date >= this.selectedStart && date <= (this.selectedEnd ?? this.selectedStart);
        },

        selectDate(date) {
            if (!this.canSelect(date)) return;
            this.selectionError = '';
            if (!this.selectedStart || this.selectedEnd || date < this.selectedStart) {
                this.selectedStart = date;
                this.selectedEnd = null;
            } else {
                const days = Math.round((Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()) - Date.UTC(this.selectedStart.getFullYear(), this.selectedStart.getMonth(), this.selectedStart.getDate())) / 86400000) + 1;
                if (this.maximumDays && days > this.maximumDays + (this.rentalUnit === 'hour' ? 1 : 0)) {
                    this.selectionError = this.rentalUnit === 'hour'
                        ? `Choose dates within the ${this.maximumDays * 24}-hour maximum. Exact times are checked in your request.`
                        : `This item can be rented for at most ${this.maximumDays} calendar days. Choose an earlier end date.`;
                    return;
                }
                if ([...this.ranges, ...this.reservations].some((range) => range.start <= date && range.end >= this.selectedStart)) {
                    this.selectionError = 'These dates include an unavailable day. Choose an earlier end date or a new start date.';
                    return;
                }
                this.selectedEnd = date;
            }
            this.$dispatch('rental-dates-selected', {
                startDate: this.dateString(this.selectedStart),
                endDate: this.dateString(this.selectedEnd ?? this.selectedStart),
                rentalUnit: this.rentalUnit,
            });
        },

        dateString(date) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        },

        get selectionLabel() {
            if (!this.selectedStart) return 'Select a start date, then an end date.';
            const format = { month: 'short', day: 'numeric', year: 'numeric' };
            const start = this.selectedStart.toLocaleDateString('en-US', format);
            return this.selectedEnd
                ? `${start} – ${this.selectedEnd.toLocaleDateString('en-US', format)}`
                : `${start} · Choose an end date, or request one day.`;
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
