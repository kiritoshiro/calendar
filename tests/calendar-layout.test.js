#!/usr/bin/env node

'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '..');
const customCss = fs.readFileSync(path.join(root, 'modern-events-calendar-lite/assets/css/adventistai-calendar.css'), 'utf8');
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
    } finally {
        await browser.close();
    }
})().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
