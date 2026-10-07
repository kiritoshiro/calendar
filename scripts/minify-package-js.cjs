#!/usr/bin/env node
/*
 * Minifies the plugin's JavaScript inside a staged release package.
 *
 *   node scripts/minify-package-js.cjs <staged plugin directory>
 *
 * Every .js file under assets/ that is not already *.min.js is rewritten in
 * place with esbuild (whitespace, syntax and local names only: top-level
 * names such as mecSingleEventDisplayer stay global, and the syntax level is
 * unchanged). Each result is compiled once to prove it still parses. The
 * repository keeps the readable sources; only the ZIP gets the small files.
 */
'use strict';

const fs = require('fs');
const path = require('path');
const vm = require('vm');
const esbuild = require('esbuild');

const plugin = process.argv[2];
if (!plugin || !fs.existsSync(path.join(plugin, 'modern-events-calendar-lite.php'))) {
    console.error('Usage: node scripts/minify-package-js.cjs <staged modern-events-calendar-lite directory>');
    process.exit(2);
}

function walk(dir) {
    return fs.readdirSync(dir, {withFileTypes: true}).flatMap((entry) => {
        const file = path.join(dir, entry.name);
        if (entry.isDirectory()) return walk(file);
        return entry.name.endsWith('.js') && !entry.name.endsWith('.min.js') ? [file] : [];
    });
}

let before = 0;
let after = 0;
const files = walk(path.join(plugin, 'assets'));
for (const file of files) {
    const source = fs.readFileSync(file, 'utf8');
    const {code} = esbuild.transformSync(source, {
        loader: 'js',
        minify: true,
        legalComments: 'inline',
        charset: 'utf8',
        sourcefile: path.relative(plugin, file),
    });
    // Parse check only; nothing is executed.
    new vm.Script(code, {filename: file});
    fs.writeFileSync(file, code);
    before += Buffer.byteLength(source);
    after += Buffer.byteLength(code);
}
if (!files.length) {
    console.error('No JavaScript found under assets/');
    process.exit(1);
}
console.log(`Minified ${files.length} scripts: ${before} → ${after} bytes.`);
