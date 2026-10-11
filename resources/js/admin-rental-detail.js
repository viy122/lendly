document.addEventListener('alpine:init', () => {
    window.Alpine.data('adminRentalDetail', () => ({
        activeTab: 'overview',
        historyOpen: false,
        tabs: ['overview', 'payments', 'agreement', 'evidence', 'actions'],

        selectTab(tab, focus = false) {
            this.activeTab = tab;
            if (focus) {
                this.$nextTick(() => this.$refs.tabs.querySelector(`#rental-tab-${tab}`).focus());
            }
        },

        moveTab(offset) {
            const index = this.tabs.indexOf(this.activeTab);
            this.selectTab(this.tabs[(index + offset + this.tabs.length) % this.tabs.length], true);
        },
    }));
});
