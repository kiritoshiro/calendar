#!/usr/bin/env node

'use strict';

// Mutation check for the linter verification: each breaking entry below
// breaks the linter setup on purpose in a scratch copy of the repository, and
// tests/verify-customizations.js must fail on every one of them. A mutation
// that survives is a break the checks would let through. Equivalent entries
// change only formatting, and the checks must still pass on them.
//
// Usage: node tests/mutate-customizations.js

const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const VERIFY_TIMEOUT_MS = 5 * 60_000;

// `from` is replaced by `to` (first occurrence, or every one with `all`);
// `to: null` deletes the file. Both are written with LF line endings and
// matched against the file with its line endings normalised to LF. `from`
// must still match the current file, so a mutation that no longer applies is
// reported as stale instead of passing silently.
const MUTATIONS = [
    // Dependabot
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
    {
        name: 'Dependabot gains a second composer entry without a quoted time',
        file: '.github/dependabot.yml',
        from: '- package-ecosystem: composer\n',
        to: '- package-ecosystem: composer\n  directory: /\n  schedule:\n    interval: weekly\n    time: 05:00\n- package-ecosystem: composer\n',
    },
    {
        name: 'Dependabot schedule times single-quoted',
        file: '.github/dependabot.yml',
        from: 'time: "05:00"',
        to: "time: '05:00'",
        all: true,
        equivalent: true,
    },

    // Linter configuration
    { name: 'phpstan.neon deleted', file: 'tools/linters/phpstan.neon', to: null },
    { name: 'phpcs-compatibility.xml deleted', file: 'tools/linters/phpcs-compatibility.xml', to: null },
    {
        name: 'PHP compatibility tests an older PHP version',
        file: 'tools/linters/phpcs-compatibility.xml',
        from: 'value="8.4-"',
        to: 'value="7.4-"',
    },
    {
        name: 'PHP compatibility version only inside an XML comment',
        file: 'tools/linters/phpcs-compatibility.xml',
        from: '<config name="testVersion" value="8.4-"/>',
        to: '<!-- <config name="testVersion" value="8.4-"/> -->',
    },
    {
        name: 'PHP compatibility version reformatted',
        file: 'tools/linters/phpcs-compatibility.xml',
        from: '<config name="testVersion" value="8.4-"/>',
        to: "<config\n        value='8.4-'\n        name=\"testVersion\" />",
        equivalent: true,
    },
    {
        name: 'WordPress review loses its security rule',
        file: 'tools/linters/phpcs-wordpress.xml',
        from: '    <rule ref="WordPress.Security"/>\n',
        to: '',
    },
    {
        name: 'WordPress review excludes output escaping',
        file: 'tools/linters/phpcs-wordpress.xml',
        from: '<rule ref="WordPress.Security"/>',
        to: '<rule ref="WordPress.Security"><exclude name="WordPress.Security.EscapeOutput"/></rule>',
    },
    {
        name: 'PHPCS installer plugin disabled in Composer',
        file: 'tools/linters/composer.json',
        from: '"dealerdirect/phpcodesniffer-composer-installer": true',
        to: '"dealerdirect/phpcodesniffer-composer-installer": false',
    },
    {
        name: 'PHPCS installer plugin no longer required',
        file: 'tools/linters/composer.json',
        from: ',\n        "dealerdirect/phpcodesniffer-composer-installer": "^1.1"',
        to: '',
    },
    {
        name: 'PHPStan loses the WordPress extension',
        file: 'tools/linters/phpstan.neon',
        from: '    - vendor/szepeviktor/phpstan-wordpress/extension.neon\n',
        to: '',
    },
    {
        name: 'PHPStan ignores every error',
        file: 'tools/linters/phpstan.neon',
        from: '    ignoreErrors:\n',
        to: "    ignoreErrors:\n        - '#.*#'\n",
    },

    // Static analysis workflow
    {
        name: 'static analysis runs only when started by hand',
        file: '.github/workflows/static-analysis.yml',
        from: 'on:\n  push:\n  pull_request:\n',
        to: 'on:\n',
    },
    {
        name: 'PHP analysis job made advisory',
        file: '.github/workflows/static-analysis.yml',
        from: '  php-analysis:\n    name: PHPStan and PHP 8.4+ compatibility\n',
        to: '  php-analysis:\n    name: PHPStan and PHP 8.4+ compatibility\n    continue-on-error: true\n',
    },
    {
        name: 'PHP analysis job runs only when started by hand',
        file: '.github/workflows/static-analysis.yml',
        from: '  php-analysis:\n    name: PHPStan and PHP 8.4+ compatibility\n',
        to: "  php-analysis:\n    name: PHPStan and PHP 8.4+ compatibility\n    if: github.event_name == 'workflow_dispatch'\n",
    },
    {
        name: 'PHPStan exit status swallowed',
        file: '.github/workflows/static-analysis.yml',
        from: '--memory-limit=3G -- "${files[@]}"',
        to: '--memory-limit=3G -- "${files[@]}" || true',
    },
    {
        name: 'PHPStan failure only echoed',
        file: '.github/workflows/static-analysis.yml',
        from: '--memory-limit=3G -- "${files[@]}"',
        to: '--memory-limit=3G -- "${files[@]}" || echo "PHPStan reported problems"',
    },
    {
        name: 'PHPStan command split over two lines',
        file: '.github/workflows/static-analysis.yml',
        from: '--no-progress --memory-limit=3G',
        to: '--no-progress \\\n            --memory-limit=3G',
        equivalent: true,
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
        name: 'PHPStan step skipped after a compatibility failure',
        file: '.github/workflows/static-analysis.yml',
        from: '      - name: PHPStan bug analysis\n        if: ${{ !cancelled() }}\n',
        to: '      - name: PHPStan bug analysis\n',
    },
    {
        name: 'PHP compatibility step skipped after an earlier failure',
        file: '.github/workflows/static-analysis.yml',
        from: '      - name: PHP 8.4+ forward compatibility\n        if: ${{ !cancelled() }}\n',
        to: '      - name: PHP 8.4+ forward compatibility\n',
    },
    {
        name: 'run conditions quoted',
        file: '.github/workflows/static-analysis.yml',
        from: 'if: ${{ !cancelled() }}',
        to: 'if: "${{ !cancelled() }}"',
        all: true,
        equivalent: true,
    },
    {
        name: 'PHP compatibility exit status swallowed',
        file: '.github/workflows/static-analysis.yml',
        from: '--report=full -- "${files[@]}"',
        to: '--report=full -- "${files[@]}" || :',
    },
    {
        name: 'PHP compatibility ignores errors on exit',
        file: '.github/workflows/static-analysis.yml',
        from: '--report=full -- "${files[@]}"',
        to: '--report=full --runtime-set ignore_errors_on_exit 1 -- "${files[@]}"',
    },
    {
        name: 'WordPress review job made advisory',
        file: '.github/workflows/static-analysis.yml',
        from: '  wordpress-standards:\n    name: WordPress security and efficiency review (advisory)\n',
        to: '  wordpress-standards:\n    name: WordPress security and efficiency review (advisory)\n    continue-on-error: true\n',
    },
    {
        name: 'WordPress review step continues on error',
        file: '.github/workflows/static-analysis.yml',
        from: '      - name: Review escaping, queries and dead code\n',
        to: '      - name: Review escaping, queries and dead code\n        continue-on-error: true\n',
    },
    {
        name: 'phpcs failing to run no longer fails the review',
        file: '.github/workflows/static-analysis.yml',
        from: 'if [ "$status" -gt 2 ]; then',
        to: 'if false; then',
    },
    {
        name: 'WordPress review reads the exit status of tee',
        file: '.github/workflows/static-analysis.yml',
        from: 'status="${PIPESTATUS[0]}"',
        to: 'status="${PIPESTATUS[1]}"',
    },
    {
        name: 'empty fork file list no longer fails',
        file: '.github/workflows/static-analysis.yml',
        from: 'if [ "${#files[@]}" -eq 0 ]; then',
        to: 'if [ "${#files[@]}" -lt 0 ]; then',
        all: true,
    },
    {
        name: 'zizmor job runs only when started by hand',
        file: '.github/workflows/static-analysis.yml',
        from: '  workflow-security:\n    name: Workflow supply-chain review\n',
        to: "  workflow-security:\n    name: Workflow supply-chain review\n    if: github.event_name == 'workflow_dispatch'\n",
    },
    {
        name: 'zizmor step continues on error',
        file: '.github/workflows/static-analysis.yml',
        from: '      - name: Audit GitHub Actions workflows\n',
        to: '      - name: Audit GitHub Actions workflows\n        continue-on-error: true\n',
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

    // fork-owned-files.sh
    {
        name: 'fork-owned-files.sh lists nothing',
        file: 'tools/linters/fork-owned-files.sh',
        from: 'done | grep -E "$pattern" || true',
        to: 'done | grep -E "$pattern" >/dev/null || true',
    },
    {
        name: 'fork-owned-files.sh lists files the rename only carried over',
        file: 'tools/linters/fork-owned-files.sh',
        from: '            only_renamed "$path" "$new_path" && continue\n',
        to: '',
    },
    {
        name: 'fork-owned-files.sh lists one-line files the rename only carried over',
        file: 'tools/linters/fork-owned-files.sh',
        from: 'git cat-file -e "$base:$old_path" 2>/dev/null',
        to: 'false',
    },
    {
        name: 'fork-owned-files.sh ignores the pathspec',
        file: 'tools/linters/fork-owned-files.sh',
        from: '[ -n "${wanted[$path]:-}" ] && printf',
        to: 'printf',
    },
    {
        name: 'fork-owned-files.sh ignores UPSTREAM_IMPORT_REF',
        file: 'tools/linters/fork-owned-files.sh',
        from: 'base="${UPSTREAM_IMPORT_REF:-}"',
        to: 'base=""',
    },
    {
        name: 'fork-owned-files.sh compares with the previous commit',
        file: 'tools/linters/fork-owned-files.sh',
        from: 'git rev-list --max-parents=0 HEAD | tail -n 1',
        to: 'git rev-parse HEAD~1',
    },

    // Plugin Check baseline and the plugin checks workflow
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
        name: 'Plugin Check baseline step rewrites the baseline instead of comparing',
        file: '.github/workflows/plugin-checks.yml',
        from: 'tools/linters/plugin-check-baseline.json',
        to: 'tools/linters/plugin-check-baseline.json --write',
    },
    {
        name: 'Plugin Check job runs only when started by hand',
        file: '.github/workflows/plugin-checks.yml',
        from: '  wordpress-security:\n    name: WordPress Plugin Check security review (legacy baseline)\n',
        to: "  wordpress-security:\n    name: WordPress Plugin Check security review (legacy baseline)\n    if: github.event_name == 'workflow_dispatch'\n",
    },
    {
        name: 'customization checks job made advisory',
        file: '.github/workflows/plugin-checks.yml',
        from: '  code-quality:\n    name: PHP 8.4 and regression checks\n',
        to: '  code-quality:\n    name: PHP 8.4 and regression checks\n    continue-on-error: true\n',
    },
    {
        name: 'mutation check removed from CI',
        file: '.github/workflows/plugin-checks.yml',
        from: '      - name: Check the customization checks catch deliberate breaks\n        run: node tests/mutate-customizations.js\n',
        to: '',
    },
    {
        name: 'mutation check result ignored',
        file: '.github/workflows/plugin-checks.yml',
        from: 'run: node tests/mutate-customizations.js',
        to: 'run: node tests/mutate-customizations.js || true',
    },
    {
        name: 'security gate no longer waits for the plugin checks',
        file: '.github/workflows/security-gate.yml',
        from: 'needs: [baseline, checks, codeql, dependency-review, wordpress]',
        to: 'needs: [baseline, codeql, dependency-review, wordpress]',
    },
];

const toLf = (text) => text.replace(/\r\n/g, '\n');
const mutate = (text, mutation) => {
    const matches = typeof mutation.from === 'string' ? text.includes(mutation.from) : mutation.from.test(text);
    if (!matches) return null;
    if (mutation.all) return text.replaceAll(mutation.from, mutation.to);
    return text.replace(mutation.from, mutation.to);
};
const firstLine = (output) => (output.match(/^\w*Error\b[^\n]*?: (.*)$/m)?.[1] ?? output.trim().split('\n')[0] ?? '').trim();

const run = (scratch) => {
    const copy = path.join(scratch, 'repo');
    const skip = new Set(['.git', 'node_modules'].map((name) => path.join(root, name)));
    fs.cpSync(root, copy, { recursive: true, filter: (source) => !skip.has(source) });

    // A verifier run that was stopped, timed out or could not start is an
    // error, never a caught mutation.
    const verify = () => {
        const result = spawnSync(process.execPath, [path.join(copy, 'tests', 'verify-customizations.js')], {
            encoding: 'utf8',
            timeout: VERIFY_TIMEOUT_MS,
            maxBuffer: 64 * 1024 * 1024,
        });
        if (result.error || result.signal !== null || result.status === null) {
            return { outcome: 'error', detail: result.error?.message ?? `stopped by ${result.signal}` };
        }
        return { outcome: result.status === 0 ? 'passed' : 'failed', detail: firstLine(result.stderr || result.stdout) };
    };

    const clean = verify();
    if (clean.outcome !== 'passed') {
        console.error(`The unmutated copy must pass tests/verify-customizations.js first (${clean.outcome}): ${clean.detail}`);
        return 1;
    }
    // The checks must not depend on how the checkout stores line endings, so
    // they run again with every mutated file in the other style. Shell
    // scripts stay LF, as .gitattributes keeps them.
    const flipped = new Map([...new Set(MUTATIONS.map((mutation) => mutation.file))]
        .filter((file) => !file.endsWith('.sh'))
        .map((file) => [file, fs.readFileSync(path.join(copy, file))]));
    for (const [file, bytes] of flipped) {
        const text = bytes.toString('utf8');
        fs.writeFileSync(path.join(copy, file), text.includes('\r\n') ? toLf(text) : text.replace(/\n/g, '\r\n'));
    }
    const otherStyle = verify();
    for (const [file, bytes] of flipped) fs.writeFileSync(path.join(copy, file), bytes);
    if (otherStyle.outcome !== 'passed') {
        console.error(`The unmutated copy must also pass with its line endings switched (${otherStyle.outcome}): ${otherStyle.detail}`);
        return 1;
    }

    const counts = { caught: 0, survived: 0, stale: 0, errors: 0, accepted: 0, rejected: 0 };
    for (const mutation of MUTATIONS) {
        const target = path.join(copy, mutation.file);
        const original = fs.readFileSync(target);
        if (mutation.to === null) {
            fs.rmSync(target);
        } else {
            const text = original.toString('utf8');
            const normalised = toLf(text);
            const mutated = mutate(normalised, mutation);
            if (mutated === null || mutated === normalised) {
                console.log(`STALE     ${mutation.name}: ${mutation.file} no longer contains the text it changes`);
                counts.stale++;
                continue;
            }
            fs.writeFileSync(target, text.includes('\r\n') ? mutated.replace(/\n/g, '\r\n') : mutated);
        }
        let result;
        try {
            result = verify();
        } finally {
            fs.writeFileSync(target, original);
        }
        if (result.outcome === 'error') {
            console.log(`ERROR     ${mutation.name}: ${result.detail}`);
            counts.errors++;
        } else if (mutation.equivalent && result.outcome === 'passed') {
            console.log(`accepted  ${mutation.name}`);
            counts.accepted++;
        } else if (mutation.equivalent) {
            console.log(`REJECTED  ${mutation.name} (${result.detail})`);
            counts.rejected++;
        } else if (result.outcome === 'passed') {
            console.log(`SURVIVED  ${mutation.name}`);
            counts.survived++;
        } else {
            console.log(`caught    ${mutation.name} (${result.detail})`);
            counts.caught++;
        }
    }

    const breaking = MUTATIONS.filter((mutation) => !mutation.equivalent).length;
    console.log(`${breaking} breaking mutations: ${counts.caught} caught, ${counts.survived} survived. ${MUTATIONS.length - breaking} equivalent edits: ${counts.accepted} accepted, ${counts.rejected} rejected. ${counts.stale} stale, ${counts.errors} errors.`);
    if (counts.survived) console.error('A surviving mutation is a break the checks let through: tighten tests/verify-linters.js.');
    if (counts.rejected) console.error('A rejected equivalent edit means the checks depend on formatting: parse the configuration instead.');
    if (counts.stale) console.error('A stale mutation no longer matches its file: update it to the current text.');
    if (counts.errors) console.error('The verifier could not run to completion for some mutations.');
    return counts.survived || counts.rejected || counts.stale || counts.errors ? 1 : 0;
};

const scratch = fs.mkdtempSync(path.join(os.tmpdir(), 'mutate-customizations-'));
try {
    process.exitCode = run(scratch);
} finally {
    fs.rmSync(scratch, { recursive: true, force: true });
}
