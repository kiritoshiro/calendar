#!/usr/bin/env node

'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '..');
const customCss = fs.readFileSync(path.join(root, 'renginiu-kalendorius/assets/css/adventistai-calendar.css'), 'utf8');
const screenshotPath = process.env.MEC_LAYOUT_SCREENSHOT || path.join(root, 'calendar-navigation-preview.png');

const months = [
    ['January', 7], ['February', 6], ['March', 6], ['April', 8],
    ['May', 7], ['June', 6], ['July', 8], ['August', 5],
    ['September', 7], ['October', 6], ['November', 4], ['December', 4],
];

const monthMarkup = months.map(([name, count], index) => `
    <a class="mec-ymtabs-item${index === 9 ? ' mec-ymtabs-item-active' : ''}" href="#">
        <span>${name}</span><span class="mec-ymtabs-item-count">${count}</span>
    </a>`).join('');

const baseCss = `
    * { box-sizing: border-box; }
    body { margin: 0; padding: 40px 32px; font-family: Arial, sans-serif; background: #fff; }
    .mec-ymtabs { --ymtabs-accent: #32658f; overflow: hidden; border: 1px solid #e7e7e7; border-radius: 11px; }
    .mec-ymtabs-yearbar { min-height: 40px; }
    button { border: 0; background: transparent; cursor: pointer; }
    .mec-ymtabs-months { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .mec-ymtabs-item { display: flex; align-items: center; justify-content: center; gap: 6px; height: 46px; color: #18233a; text-decoration: none; border-right: 1px solid #e4e8ec; border-bottom: 1px solid #32658f; }
    .mec-ymtabs-item-active { color: #fff; background: #32658f; font-weight: 700; }
    .mec-ymtabs-item-count { display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; color: #fff; background: #32658f; font-size: 11px; }
    .mec-ymtabs-item-active .mec-ymtabs-item-count { color: #32658f; background: #fff; }
`;

// The magenda (Weekly View mini calendar) as weekly_view.php prints it, for
// November 2026: today the 3rd, event counts per day, Dec 1-5 as padding.
const magendaCss = fs.readFileSync(path.join(root, 'renginiu-kalendorius/assets/css/magenda.min.css'), 'utf8');
// 19-21: three days in a row with three or more events.
const magendaCounts = { 3: 1, 6: 1, 7: 1, 10: 2, 13: 3, 14: 1, 19: 3, 20: 5, 21: 4, 27: 1, 32: 1, 34: 1 };
const magendaMarkup = () => {
    let days = '';
    for (let i = 1; i <= 35; i++) {
        const pad = i > 30;
        const day = pad ? i - 30 : i;
        const count = magendaCounts[i] || 0;
        const cls = 'mec-magenda-day' + (pad ? ' mec-magenda-day-pad' : '') + (count ? ' mec-magenda-day-has-events' : '')
            + (i === 3 ? ' mec-magenda-day-today' : '') + (i % 7 === 0 ? ' mec-magenda-day-saturday' : '');
        days += `<button type="button" class="${cls}" data-date="${pad ? '2026-12' : '2026-11'}-${String(day).padStart(2, '0')}"`
            + (count ? ` aria-label="${day}, ${count} renginiai"` : '') + '>'
            + `<span class="mec-magenda-day-num">${day}</span>`
            + (count ? `<span class="mec-magenda-day-dots" data-n="${Math.min(count, 3)}" aria-hidden="true"></span>` : '')
            + '</button>';
    }
    return `<div class="mec-wrap colorskin-custom"><div class="mec-magenda">
        <h2 class="mec-magenda-sr-title">Renginių kalendorius</h2>
        <div class="mec-magenda-header">
            <button type="button" class="mec-magenda-nav mec-magenda-prev" aria-label="Previous month">&#8249;</button>
            <button type="button" class="mec-magenda-label mec-magenda-month-label" data-open="month">Rugpjūtis</button>
            <button type="button" class="mec-magenda-label mec-magenda-year-label" data-open="year">2026</button>
            <button type="button" class="mec-magenda-nav mec-magenda-next" aria-label="Next month">&#8250;</button>
        </div>
        <div class="mec-magenda-weekdays">${['S', 'P', 'A', 'T', 'K', 'Pn', 'Š'].map((d, i) => `<span class="${i === 6 ? 'mec-magenda-weekday-saturday' : ''}">${d}</span>`).join('')}</div>
        <div class="mec-magenda-grid">${days}</div>
        <div class="mec-magenda-agenda"><ul class="mec-weekly-view-dates-events"><li class="mec-weekly-view-date-events">
            <article class="mec-event-article"><div class="mec-weekly-contents-wrapper"><div class="mec-event-image"></div><div class="mec-weekly-contents">
                <div class="mec-event-time mec-color"><i class="mec-sl-clock-o"></i> Šiandien, 19:00 - 20:00</div>
                <h3 class="mec-event-title"><a href="#">Maldos židinys</a><span class="event-color" style="background: #00a0d2"></span></h3>
                <div class="mec-event-detail"><div class="mec-event-loc-place"></div></div>
            </div></div></article>
        </li></ul></div>
    </div></div>`;
};

// A theme that styles every button as a dark uppercase pill, as ALPS does.
const hostileThemeCss = `
    * { box-sizing: border-box; }
    body { margin: 0; padding: 16px; font-family: Arial, sans-serif; background: #fff; }
    :root { --mec-color-skin: #2d5c88; }
    button { padding: 10px 24px; border: 2px solid #111; border-radius: 999px; background: #111; color: #fff; font-weight: 700; text-transform: uppercase; box-shadow: 0 2px 4px #000; }
    #frame { width: var(--frame-width); }
`;

async function checkMagenda(browser) {
    const page = await browser.newPage({ viewport: { width: 1340, height: 700 }, deviceScaleFactor: 1 });
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"><style>${hostileThemeCss}\n${magendaCss}</style></head><body>
        <div id="frame">${magendaMarkup()}</div></body></html>`);

    const measure = () => page.evaluate(() => {
        const rect = el => el.getBoundingClientRect();
        const root = document.querySelector('.mec-magenda');
        const days = [...document.querySelectorAll('.mec-magenda-day')];
        const tops = [...new Set(days.map(d => Math.round(rect(d).top)))];
        const day = selector => document.querySelector(`.mec-magenda-day[data-date="${selector}"]`);
        const dotGroup = button => {
            const dots = button.querySelector('.mec-magenda-day-dots');
            const style = getComputedStyle(dots);
            const r = rect(dots);
            const shadows = style.boxShadow === 'none' ? [] : style.boxShadow.split(/,(?![^(]*\))/).map(s => parseFloat(s.replace(/rgba?\([^)]*\)/, '').trim()));
            const right = Math.max(r.right, ...shadows.map(x => r.right + x));
            return { count: 1 + shadows.length, width: r.width, left: r.left, right, centre: (r.left + right) / 2, colour: style.backgroundColor, top: r.top };
        };
        const numRect = rect(day('2026-11-13').querySelector('.mec-magenda-day-num'));
        const prev = rect(document.querySelector('.mec-magenda-prev'));
        const next = rect(document.querySelector('.mec-magenda-next'));
        const month = rect(document.querySelector('.mec-magenda-month-label'));
        const year = rect(document.querySelector('.mec-magenda-year-label'));
        const todayNum = getComputedStyle(day('2026-11-03').querySelector('.mec-magenda-day-num'));
        const plain = getComputedStyle(day('2026-11-04'));
        return {
            width: rect(root).width,
            overflow: root.scrollWidth > root.clientWidth + 0.5,
            rows: tops.length,
            rowPitch: tops[1] - tops[0],
            plainDay: { background: plain.backgroundColor, border: plain.borderTopWidth, shadow: plain.boxShadow, radius: plain.borderTopLeftRadius, transform: plain.textTransform },
            todayNum: { background: todayNum.backgroundColor, colour: todayNum.color, weight: todayNum.fontWeight, radius: todayNum.borderTopLeftRadius },
            dots: { d6: dotGroup(day('2026-11-06')), d10: dotGroup(day('2026-11-10')), d13: dotGroup(day('2026-11-13')), d19: dotGroup(day('2026-11-19')), d20: dotGroup(day('2026-11-20')), d21: dotGroup(day('2026-11-21')), pad: dotGroup(day('2026-12-02')) },
            numCentre: (numRect.left + numRect.right) / 2,
            numBottom: numRect.bottom,
            header: { prevLeft: prev.left - rect(root).left, nextRight: rect(root).right - next.right, titleCentre: (month.left + year.right) / 2 - (rect(root).left + rect(root).width / 2), titleTop: month.top, yearTop: year.top },
            saturdayLetter: getComputedStyle(document.querySelector('.mec-magenda-weekday-saturday')).color,
            padColour: getComputedStyle(day('2026-12-03')).color,
            agendaRule: getComputedStyle(document.querySelector('.mec-magenda-agenda')).borderTopWidth,
            clock: getComputedStyle(document.querySelector('.mec-magenda-agenda .mec-sl-clock-o')).display,
            colourDotFirst: rect(document.querySelector('.mec-magenda-agenda .event-color')).left < rect(document.querySelector('.mec-magenda-agenda .mec-event-title a')).left,
        };
    });

    // Sidebar: about 143 px.
    await page.evaluate(() => document.documentElement.style.setProperty('--frame-width', '143px'));
    let m = await measure();
    assert.equal(Math.round(m.width), 143);
    assert.ok(!m.overflow, 'the narrow calendar must not overflow');
    assert.equal(m.rows, 5);
    assert.equal(m.rowPitch, 28, `sidebar rows are 28 px, got ${m.rowPitch}`);
    assert.equal(m.plainDay.background, 'rgba(0, 0, 0, 0)', 'day cells have no box');
    assert.equal(m.plainDay.border, '0px');
    assert.equal(m.plainDay.shadow, 'none');
    assert.equal(m.plainDay.transform, 'none', 'the theme button style does not leak in');
    assert.equal(m.todayNum.background, 'rgb(4, 19, 43)', 'today is a filled navy circle');
    assert.equal(m.todayNum.colour, 'rgb(255, 255, 255)');
    assert.equal(m.todayNum.weight, '700');
    assert.equal(m.todayNum.radius, '50%');
    assert.deepEqual([m.dots.d6.count, m.dots.d10.count, m.dots.d13.count, m.dots.d21.count], [1, 2, 3, 3], 'one dot per event, at most three');
    assert.equal(m.dots.d6.width, 3, 'small 3 px dots in the sidebar');
    const gap = Math.min(m.dots.d20.left - m.dots.d19.right, m.dots.d21.left - m.dots.d20.right);
    assert.ok(gap >= 6, `days in a row with three dots stay apart (${gap.toFixed(1)} px between groups)`);
    for (const key of ['d6', 'd10', 'd13']) assert.equal(m.dots[key].colour, 'rgb(45, 92, 136)', `${key} dots use the site blue`);
    assert.equal(m.dots.pad.colour, 'rgb(160, 168, 180)', 'other-month dots are grey');
    assert.ok(Math.abs(m.dots.d13.centre - m.numCentre) < 0.6, `three dots are centred under the number (${m.dots.d13.centre} vs ${m.numCentre})`);
    assert.ok(m.dots.d13.top >= m.numBottom, 'dots sit under the number');
    assert.ok(m.header.prevLeft < 1 && m.header.nextRight < 1, 'arrows at the sides');
    assert.ok(Math.abs(m.header.titleCentre) < 1.5, `month and year centred (${m.header.titleCentre})`);
    assert.equal(Math.round(m.header.titleTop), Math.round(m.header.yearTop), 'the longest month name and the year stay on one line');
    assert.equal(m.saturdayLetter, 'rgb(45, 92, 136)');
    assert.equal(m.padColour, 'rgb(107, 114, 128)', 'other-month days keep 4.8:1 contrast');
    assert.equal(m.agendaRule, '1px');
    assert.equal(m.clock, 'none');
    assert.ok(m.colourDotFirst, 'the event colour dot comes before the title');

    await page.locator('.mec-magenda-day[data-date="2026-11-04"]').focus();
    await page.keyboard.press('Tab');
    const focus = await page.evaluate(() => {
        const style = getComputedStyle(document.activeElement);
        return { date: document.activeElement.dataset.date, outline: `${style.outlineStyle} ${style.outlineWidth} ${style.outlineColor}` };
    });
    assert.deepEqual(focus, { date: '2026-11-05', outline: 'solid 2px rgb(45, 92, 136)' }, 'keyboard focus is visible at once');
    await page.locator('#frame').screenshot({ path: screenshotPath.replace(/\.png$/, '-magenda-narrow.png') });

    // Front page / full width: the calendar's own width scales it up.
    await page.evaluate(() => document.documentElement.style.setProperty('--frame-width', '640px'));
    m = await measure();
    assert.ok(!m.overflow);
    assert.equal(m.rowPitch, 46, `wide rows are 46 px, got ${m.rowPitch}`);
    assert.equal(m.dots.d6.width, 5);
    assert.ok(Math.abs(m.dots.d13.centre - m.numCentre) < 0.6);
    await page.locator('#frame').screenshot({ path: screenshotPath.replace(/\.png$/, '-magenda-wide.png') });

    await page.evaluate(() => document.documentElement.style.setProperty('--frame-width', '311px'));
    m = await measure();
    assert.equal(m.rowPitch, 40, `phone-width rows are 40 px, got ${m.rowPitch}`);
    await page.close();
    console.log('Magenda layout checks passed.');
}

(async () => {
    const browser = await chromium.launch({ headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 1340, height: 320 }, deviceScaleFactor: 1 });
        await page.setContent(`<!doctype html><html><head><style>${baseCss}\n${customCss}</style></head><body>
            <div class="mec-ymtabs mec-ymtabs-gcalbar" id="mec-gcalbar-test">
                <div class="mec-ymtabs-yearbar mec-ymtabs-gcal-navigator">
                    <button type="button" class="mec-ymtabs-today mec-gcalbar-nav-today">Šiandien</button>
                    <button type="button" class="mec-ymtabs-jump mec-gcalbar-nav-prev" aria-label="Previous month">‹</button>
                    <span class="mec-ymtabs-gcal-title">
                        <span class="mec-ymtabs-gcal-title-before">2026 m. </span>
                        <span class="mec-ymtabs-year-control">
                            <select class="mec-ymtabs-year-label mec-ymtabs-year-select" aria-label="Select year">
                                <option value="2025">2025</option><option value="2026" selected>2026</option><option value="2027">2027</option>
                            </select>
                            <span class="mec-ymtabs-year-caret" aria-hidden="true">▾</span>
                        </span>
                        <span class="mec-ymtabs-gcal-title-after">spalis</span>
                    </span>
                    <button type="button" class="mec-ymtabs-jump mec-gcalbar-nav-next" aria-label="Next month">›</button>
                </div>
                <div class="mec-ymtabs-months">${monthMarkup}</div>
            </div>
        </body></html>`);

        const positions = async () => page.evaluate(() => {
            const center = selector => {
                const rect = document.querySelector(selector).getBoundingClientRect();
                return { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
            };
            return {
                previous: center('.mec-gcalbar-nav-prev'),
                next: center('.mec-gcalbar-nav-next'),
            };
        });

        const before = await positions();
        await page.evaluate(() => {
            document.querySelector('.mec-ymtabs-gcal-title-before').textContent = '';
            document.querySelector('.mec-ymtabs-gcal-title-after').textContent = ' rugsėjis — ilgiausias lokalizuotas mėnesio pavadinimas';
        });
        const after = await positions();

        assert.ok(Math.abs(before.previous.x - after.previous.x) < 0.1, 'previous arrow moved when the title changed');
        assert.ok(Math.abs(before.next.x - after.next.x) < 0.1, 'next arrow moved when the title changed');

        const rowTops = await page.locator('.mec-ymtabs-yearbar, .mec-ymtabs-item').evaluateAll(elements =>
            [...new Set(elements.map(element => Math.round(element.getBoundingClientRect().top)))].sort((a, b) => a - b)
        );
        assert.equal(rowTops.length, 3, `expected three navigation rows, found ${rowTops.length}`);
        assert.equal(await page.locator('.mec-gcalbar-nav-prev, .mec-gcalbar-nav-next').count(), 2, 'only month arrows should be rendered');
        assert.equal(await page.locator('.mec-ymtabs-year-select').count(), 1, 'year must be a native selector');
        assert.equal(await page.locator('.mec-ymtabs-year-select').getAttribute('aria-label'), 'Select year');

        await page.screenshot({ path: screenshotPath });
        console.log(`Calendar layout checks passed. Preview: ${screenshotPath}`);

        await checkMagenda(browser);
    } finally {
        await browser.close();
    }
})().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
