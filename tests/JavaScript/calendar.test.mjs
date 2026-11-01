import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

process.env.TZ = 'Asia/Manila';

function calendar(held = [], reserved = [], firstDate = '2026-11-01', maximumDays = null, requestsOpen = true, rentalUnit = 'day') {
    let factory;
    vm.runInNewContext(readFileSync(new URL('../../resources/js/calendar.js', import.meta.url), 'utf8'), {
        document: { addEventListener(event, callback) { callback(); } },
        Alpine: { data(name, component) { factory = component; } },
        Date,
    });
    const events = [];
    const component = factory(held, reserved, '2026-11-01', '2026-12-31', firstDate, maximumDays, requestsOpen, rentalUnit);
    component.$dispatch = (event, details) => events.push({ event, ...details });
    return { component, events };
}

const day = (month, date) => new Date(2026, month - 1, date);

test('calendar selection sends local start and end dates, including a range across months', () => {
    const { component, events } = calendar();
    component.selectDate(day(11, 30));
    assert.deepEqual(events.at(-1), { event: 'rental-dates-selected', startDate: '2026-11-30', endDate: '2026-11-30', rentalUnit: 'day' });
    component.nextMonth();
    component.selectDate(day(12, 2));
    assert.deepEqual(events.at(-1), { event: 'rental-dates-selected', startDate: '2026-11-30', endDate: '2026-12-02', rentalUnit: 'day' });
    assert.equal(component.isSelected(day(12, 1)), true);
    assert.equal(component.isSelected(day(12, 3)), false);
});

test('calendar ignores blank, too-early, held, reserved and outside-window dates', () => {
    const { component, events } = calendar(
        [{ start: '2026-11-10', end: '2026-11-12' }],
        [{ start: '2026-11-20', end: '2026-11-22' }],
        '2026-11-02',
    );
    for (const date of [null, day(10, 31), day(11, 1), day(11, 10), day(11, 12), day(11, 20), day(11, 22), day(1, 1)]) {
        assert.equal(component.canSelect(date), false);
        component.selectDate(date);
    }
    component.selectDate(new Date(2027, 0, 1));
    assert.equal(events.length, 0);
    assert.equal(component.canSelect(day(11, 2)), true);
    assert.equal(component.canSelect(day(12, 31)), true);
});

test('a date range cannot cross held or paid reservation dates and can be corrected', () => {
    for (const reservationType of ['held', 'reserved']) {
        const blocked = [{ start: '2026-11-10', end: '2026-11-12' }];
        const { component, events } = calendar(reservationType === 'held' ? blocked : [], reservationType === 'reserved' ? blocked : []);
        component.selectDate(day(11, 8));
        component.selectDate(day(11, 15));
        assert.equal(events.length, 1);
        assert.ok(component.selectionError);
        component.selectDate(day(11, 9));
        assert.equal(component.selectionError, '');
        assert.equal(events.at(-1).endDate, '2026-11-09');
    }
});

test('an earlier date or a click after a complete range starts a new selection', () => {
    const { component, events } = calendar();
    component.selectDate(day(11, 8));
    component.selectDate(day(11, 6));
    assert.equal(events.at(-1).startDate, '2026-11-06');
    component.selectDate(day(11, 7));
    component.selectDate(day(11, 15));
    assert.deepEqual(events.at(-1), { event: 'rental-dates-selected', startDate: '2026-11-15', endDate: '2026-11-15', rentalUnit: 'day' });
    assert.equal(component.isSelected(day(11, 6)), false);
});

test('calendar enforces the owner maximum inclusive daily range and permits correction', () => {
    const { component, events } = calendar([], [], '2026-11-01', 2);
    component.selectDate(day(11, 1));
    component.selectDate(day(11, 3));
    assert.match(component.selectionError, /at most 2/);
    assert.equal(events.length, 1);
    component.selectDate(day(11, 2));
    assert.equal(component.selectionError, '');
    assert.equal(events.at(-1).endDate, '2026-11-02');
});

test('an hourly calendar permits the next date for exact 24-hour validation in the request form', () => {
    const { component, events } = calendar([], [], '2026-11-01', 1, true, 'hour');
    component.selectDate(day(11, 1));
    component.selectDate(day(11, 2));
    assert.equal(events.at(-1).rentalUnit, 'hour');
    assert.equal(events.at(-1).endDate, '2026-11-02');
});

test('paused requests cannot select even an otherwise free date', () => {
    const { component, events } = calendar([], [], '2026-11-01', 7, false);
    component.selectDate(day(11, 1));
    assert.equal(component.canSelect(day(11, 2)), false);
    assert.equal(events.length, 0);
});
