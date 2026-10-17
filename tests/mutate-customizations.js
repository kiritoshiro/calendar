#!/usr/bin/env node

'use strict';

// Mutation check for the linter verification: each entry below breaks the
// linter setup on purpose in a scratch copy of the repository, and
// tests/verify-customizations.js must fail on every one of them. A mutation
// that survives is a break the checks would let through.
//
// Usage: node tests/mutate-customizations.js

const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const root = path.resolve(__dirname, '..');

// `from` is replaced by `to` (first occurrence, or every one with `all`);
// `to: null` deletes the file. `from` must still match the current file, so a
// mutation that no longer applies is reported instead of passing silently.
const MUTATIONS = [
    {
        name: 'Dependabot no longer holds back PHPCS 4',
        file: '.github/dependabot.yml',
        from: '  ignore:\n  - dependency-name: squizlabs/php_codesniffer\n    update-types:\n    - version-update:semver-major\n',
        to: '',
    },
    {
        name: 'Dependabot also holds back PHPCS minor updates',
        file: '.github/dependabot.yml',
        from: '    - version-update:semver-major\n',
        to: '    - version-update:semver-major\n    - version-update:semver-minor\n',
    },
    {
        name: 'Dependabot schedule time unquoted',
        file: '.github/dependabot.yml',
        from: 'time: "05:00"',
        to: 'time: 05:00',
    },
    {
        name: 'Dependabot ecosystem directories swapped',
        file: '.github/dependabot.yml',
        from: /directory: \/\n([\s\S]*)directory: \/tools\/linters\n/,
        to: 'directory: /tools/linters\n$1directory: /\n',
    },
    {
        name: 'Dependabot composer entry removed, directory left in a comment',
        file: '.github/dependabot.yml',
        from: /- package-ecosystem: composer[\s\S]*$/,
        to: '# directory: /tools/linters\n',
    },
    { name: 'phpstan.neon deleted', file: 'tools/linters/phpstan.neon', to: null },
    { name: 'phpcs-compatibility.xml deleted', file: 'tools/linters/phpcs-compatibility.xml', to: null },
    {
        name: 'PHP compatibility tests an older PHP version',
        file: 'tools/linters/phpcs-compatibility.xml',
        from: 'value="8.4-"',
        to: 'value="7.4-"',
    },
    {
        name: 'PHPStan loses the WordPress extension',
        file: 'tools/linters/phpstan.neon',
        from: '    - vendor/szepeviktor/phpstan-wordpress/extension.neon\n',
        to: '',
    },
    {
        name: 'PHPStan exit status swallowed',
        file: '.github/workflows/static-analysis.yml',
        from: '--memory-limit=3G -- "${files[@]}"',
        to: '--memory-limit=3G -- "${files[@]}" || true',
    },
    {
        name: 'PHPStan step continues on error',
        file: '.github/workflows/static-analysis.yml',
        from: '      - name: PHPStan bug analysis\n',
        to: '      - name: PHPStan bug analysis\n        continue-on-error: true\n',
    },
    {
        name: 'PHPStan step switched off',
        file: '.github/workflows/static-analysis.yml',
        from: '      - name: PHPStan bug analysis\n        if: ${{ !cancelled() }}\n',
        to: '      - name: PHPStan bug analysis\n        if: false\n',
    },
    {
        name: 'PHP compatibility exit status swallowed',
        file: '.github/workflows/static-analysis.yml',
        from: '--report=full -- "${files[@]}"',
        to: '--report=full -- "${files[@]}" || :',
    },
    {
        name: 'phpcs failing to run no longer fails the review',
        file: '.github/workflows/static-analysis.yml',
        from: 'if [ "$status" -gt 2 ]; then',
        to: 'if false; then',
    },
    {
        name: 'empty fork file list no longer fails',
        file: '.github/workflows/static-analysis.yml',
        from: 'if [ "${#files[@]}" -eq 0 ]; then',
        to: 'if [ "${#files[@]}" -lt 0 ]; then',
        all: true,
    },
    {
        name: 'empty zizmor report no longer fails',
        file: '.github/workflows/static-analysis.yml',
        from: 'if [ ! -s zizmor.sarif ]; then',
        to: 'if false; then',
    },
    {
        name: 'linter tooling installed from elsewhere, old line left in a comment',
        file: '.github/workflows/static-analysis.yml',
        from: 'run: composer install --working-dir=tools/linters ',
        to: 'run: composer install --working-dir=tools/other  # composer install --working-dir=tools/linters ',
    },
    {
        name: 'fork-owned-files.sh lists nothing',
        file: 'tools/linters/fork-owned-files.sh',
        from: 'git diff --name-only --diff-filter=d "$base" HEAD -- "$@" | grep -E "$pattern" || true',
        to: 'true',
    },
    {
        name: 'fork-owned-files.sh lists deleted files',
        file: 'tools/linters/fork-owned-files.sh',
        from: ' --diff-filter=d',
        to: '',
    },
    {
        name: 'Plugin Check baseline comparison ignores new findings',
        file: 'tools/linters/plugin-check-baseline.py',
        from: '    if new:\n',
        to: '    if False:\n',
    },
    {
        name: 'Plugin Check baseline comparison passes without results',
        file: 'tools/linters/plugin-check-baseline.py',
        from: "results file at {results_path}.')\n        return 1",
        to: "results file at {results_path}.')\n        return 0",
    },
    {
        name: 'Plugin Check baseline step continues on error',
        file: '.github/workflows/plugin-checks.yml',
        from: '      - name: Compare with the legacy baseline\n',
        to: '      - name: Compare with the legacy baseline\n        continue-on-error: true\n',
    },
    {
        name: 'mutation check removed from CI',
        file: '.github/workflows/plugin-checks.yml',
        from: '      - name: Check the customization checks catch deliberate breaks\n        run: node tests/mutate-customizations.js\n',
        to: '',
    },
];

const mutate = (text, mutation) => {
    const matches = typeof mutation.from === 'string' ? text.includes(mutation.from) : mutation.from.test(text);
    if (!matches) return null;
    if (mutation.all) return text.replaceAll(mutation.from, mutation.to);
    return text.replace(mutation.from, mutation.to);
};

const scratch = fs.mkdtempSync(path.join(os.tmpdir(), 'mutate-customizations-'));
const copy = path.join(scratch, 'repo');
const verify = () => spawnSync(process.execPath, [path.join(copy, 'tests', 'verify-customizations.js')], { encoding: 'utf8' });
const firstLine = (output) => (output.match(/^AssertionError[^\n]*: (.*)$/m)?.[1] ?? output.trim().split('\n')[0] ?? '').trim();

let failures = 0;
try {
    const skip = new Set(['.git', 'node_modules'].map((name) => path.join(root, name)));
    fs.cpSync(root, copy, { recursive: true, filter: (source) => !skip.has(source) });

    const clean = verify();
    if (clean.status !== 0) {
        console.error('The unmutated copy must pass tests/verify-customizations.js first:');
        console.error(clean.stderr || clean.stdout);
        process.exit(1);
    }

    for (const mutation of MUTATIONS) {
        const target = path.join(copy, mutation.file);
        const original = fs.readFileSync(target, 'utf8');
        if (mutation.to === null) {
            fs.rmSync(target);
        } else {
            const mutated = mutate(original, mutation);
            if (mutated === null || mutated === original) {
                console.log(`STALE     ${mutation.name}: ${mutation.file} no longer contains the text it changes`);
                failures++;
                continue;
            }
            fs.writeFileSync(target, mutated);
        }
        const result = verify();
        fs.writeFileSync(target, original);
        if (result.status === 0) {
            console.log(`SURVIVED  ${mutation.name}`);
            failures++;
        } else {
            console.log(`caught    ${mutation.name} (${firstLine(result.stderr)})`);
        }
    }
} finally {
    fs.rmSync(scratch, { recursive: true, force: true });
}

if (failures) {
    console.error(`${failures} of ${MUTATIONS.length} mutations were not caught. Tighten tests/verify-linters.js, or update the mutation if the file changed on purpose.`);
    process.exit(1);
}
console.log(`All ${MUTATIONS.length} mutations were caught.`);
