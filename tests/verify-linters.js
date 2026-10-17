#!/usr/bin/env node

'use strict';

// The linter setup is a fork customization like any other: tools/linters/,
// the workflows that run it and the Dependabot rules that keep it current.
// These checks look at what the configuration does, not only that a line
// exists, and tests/mutate-customizations.js proves they notice when it breaks.

const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const read = (relativePath) => fs.readFileSync(path.join(root, relativePath), 'utf8');

// Drops YAML comments so a line that was only commented out no longer counts.
const uncommented = (text) => text.split(/\r?\n/)
    .filter((line) => !/^\s*#/.test(line))
    .map((line) => line.replace(/\s+#\s.*$/, ''))
    .join('\n');
const steps = (workflow) => workflow.split(/\n(?=\s*- name: )/);
const step = (workflow, needle) => {
    const found = steps(workflow).filter((candidate) => candidate.includes(needle));
    assert.equal(found.length, 1, `expected exactly one workflow step running ${needle}`);
    return found[0];
};
// A gate step must be able to fail the job.
const blocking = (gate, label) => {
    assert.doesNotMatch(gate, /continue-on-error:/, `${label} must not continue on error`);
    assert.doesNotMatch(gate, /\|\|\s*(?:true|:|exit 0)/, `${label} must not swallow its exit status`);
    for (const condition of gate.matchAll(/^\s*if:\s*(.*)$/gm)) {
        assert.equal(condition[1], '${{ !cancelled() }}', `${label} may only skip when the run is cancelled`);
    }
};

const workflows = Object.fromEntries(fs.readdirSync(path.join(root, '.github', 'workflows'))
    .filter((name) => name.endsWith('.yml'))
    .map((name) => [name, uncommented(read(path.join('.github', 'workflows', name)))]));
for (const [name, workflow] of Object.entries(workflows)) {
    assert.doesNotMatch(workflow, /^\s*if:\s*(?:false|\$\{\{\s*false\s*\}\})\s*$/m, `${name} must not switch a job or step off`);
}
assert.doesNotMatch(Object.values(workflows).join('\n') + read('.github/dependabot.yml'), /\.github\/linters/, 'no workflow may still use .github/linters');

// The tooling lives outside .github/: Dependabot may only change files there
// for GitHub Actions updates, so a composer manifest under .github/ could
// never be updated.
for (const file of ['composer.json', 'composer.lock', 'phpstan.neon', 'phpcs-compatibility.xml', 'phpcs-wordpress.xml', 'fork-owned-files.sh', 'plugin-check-baseline.py', 'plugin-check-baseline.json']) {
    const full = path.join(root, 'tools', 'linters', file);
    assert.ok(fs.existsSync(full) && fs.statSync(full).size > 0, `tools/linters/${file} must exist`);
}
const composer = JSON.parse(read('tools/linters/composer.json'));
const lockedPackages = JSON.parse(read('tools/linters/composer.lock'))['packages-dev'].map((entry) => entry.name);
for (const name of ['phpstan/phpstan', 'szepeviktor/phpstan-wordpress', 'squizlabs/php_codesniffer', 'wp-coding-standards/wpcs', 'phpcompatibility/phpcompatibility-wp']) {
    assert.ok(composer['require-dev'][name], `tools/linters must require ${name}`);
    assert.ok(lockedPackages.includes(name), `tools/linters/composer.lock must lock ${name}`);
}
const phpstanConfig = read('tools/linters/phpstan.neon');
assert.match(phpstanConfig, /^\s*- vendor\/szepeviktor\/phpstan-wordpress\/extension\.neon$/m, 'PHPStan must load the WordPress extension');
assert.match(phpstanConfig, /^\s*phpVersion: 80400$/m, 'PHPStan must analyse for PHP 8.4');
assert.match(phpstanConfig, /^\s*- \.\.\/\.\.\/modern-events-calendar-lite$/m, 'PHPStan must scan the plugin for symbols');
const phpcsCompatibility = read('tools/linters/phpcs-compatibility.xml');
assert.match(phpcsCompatibility, /<config name="testVersion" value="8\.4-"\/>/, 'PHP compatibility must test 8.4 and later');
assert.match(phpcsCompatibility, /<rule ref="PHPCompatibilityWP"\/>/);

// Dependabot keeps both ecosystems current; each entry is checked on its own
// so a value under the wrong ecosystem does not count.
const updates = Object.fromEntries(uncommented(read('.github/dependabot.yml'))
    .split(/\n(?=- package-ecosystem: )/)
    .slice(1)
    .map((entry) => [entry.match(/^- package-ecosystem: (\S+)/)[1], entry]));
assert.deepEqual(Object.keys(updates).sort(), ['composer', 'github-actions'], 'Dependabot must update GitHub Actions and the linter tooling');
assert.match(updates['github-actions'], /^ {2}directory: \/$/m, 'Dependabot must update GitHub Actions from the repository root');
assert.match(updates.composer, /^ {2}directory: \/tools\/linters$/m, 'Dependabot must watch tools/linters');
// PHPCS 4 cannot be resolved until WPCS and php-compatibility accept it (#28);
// only that major is held back.
assert.match(updates.composer, /- dependency-name: squizlabs\/php_codesniffer\n\s+update-types:\n\s+- version-update:semver-major\n/, 'Dependabot must hold back PHPCS majors');
assert.doesNotMatch(updates.composer, /semver-(?:minor|patch)/, 'Dependabot must still offer PHPCS minor and patch updates');
// Unquoted, YAML reads 05:00 as the integer 300 and Dependabot rejects it (#29).
for (const [ecosystem, entry] of Object.entries(updates)) {
    const times = [...entry.matchAll(/^\s*time:\s*(.*)$/gm)].map((match) => match[1]);
    assert.equal(times.length, 1, `${ecosystem} must set one schedule time`);
    assert.match(times[0], /^"\d\d:\d\d"$/, `${ecosystem} schedule time must be a quoted string`);
}

// Static analysis: PHPStan and PHP 8.4 compatibility block, the WordPress
// review is advisory but must not pass when phpcs itself cannot run, and an
// empty file list is an error rather than a clean result.
const staticAnalysis = workflows['static-analysis.yml'];
assert.equal(staticAnalysis.match(/run: composer install --working-dir=tools\/linters /g)?.length, 2, 'both static analysis jobs must install tools/linters');
const forkFiles = /mapfile -t files < <\(bash tools\/linters\/fork-owned-files\.sh '\[\.\]php\$' modern-events-calendar-lite\)/;
const phpstan = step(staticAnalysis, 'phpstan analyse');
blocking(phpstan, 'PHPStan');
assert.match(phpstan, /set -euo pipefail/);
assert.match(phpstan, forkFiles);
assert.match(phpstan, /phpstan analyse --configuration=tools\/linters\/phpstan\.neon .*-- "\$\{files\[@\]\}"/);
const compatibility = step(staticAnalysis, 'phpcs --standard=tools/linters/phpcs-compatibility.xml');
blocking(compatibility, 'PHP 8.4 compatibility');
assert.match(compatibility, /set -euo pipefail/);
assert.match(compatibility, forkFiles);
assert.match(compatibility, /phpcs --standard=tools\/linters\/phpcs-compatibility\.xml .*-- "\$\{files\[@\]\}"/);
const emptyListGuard = /if \[ "\$\{#files\[@\]\}" -eq 0 \]; then\n(?:\s*echo .*\n)?\s*exit 1\n/;
assert.match(step(staticAnalysis, 'List the files this fork maintains'), emptyListGuard, 'an empty fork file list must fail PHPStan');
const review = step(staticAnalysis, 'phpcs --standard=tools/linters/phpcs-wordpress.xml');
assert.match(review, emptyListGuard, 'an empty fork file list must fail the WordPress review');
assert.match(review, /status="\$\{PIPESTATUS\[0\]\}"/);
assert.match(review, /if \[ "\$status" -gt 2 \]; then\n(?:\s*echo .*\n)?\s*exit "\$status"\n/, 'phpcs failing to run must fail the review');
assert.match(step(staticAnalysis, 'zizmor --format sarif'), /if \[ ! -s zizmor\.sarif \]; then\n(?:\s*echo .*\n)?\s*exit 1\n/, 'an empty zizmor report must fail');

// Plugin Check fails only on findings beyond the legacy baseline, and the
// comparison itself must be able to fail.
const pluginChecks = workflows['plugin-checks.yml'];
const baselineStep = step(pluginChecks, 'plugin-check-baseline.py');
blocking(baselineStep, 'the Plugin Check baseline comparison');
assert.match(baselineStep, /python3 tools\/linters\/plugin-check-baseline\.py "\$RUNNER_TEMP\/plugin-check-results\.txt" tools\/linters\/plugin-check-baseline\.json/);
blocking(step(pluginChecks, 'node tests/mutate-customizations.js'), 'the mutation check');

// Behaviour: fork-owned-files.sh lists files changed since the import commit,
// skipping deleted ones and other extensions.
const scratch = fs.mkdtempSync(path.join(os.tmpdir(), 'verify-linters-'));
try {
    const repo = path.join(scratch, 'repo');
    const git = (...args) => {
        const result = spawnSync('git', ['-c', 'user.name=verify', '-c', 'user.email=verify@example.invalid', '-c', 'commit.gpgsign=false', ...args], { cwd: repo, encoding: 'utf8' });
        assert.equal(result.status, 0, `git ${args.join(' ')}: ${result.stderr}`);
    };
    const write = (file, content) => {
        fs.mkdirSync(path.dirname(path.join(repo, file)), { recursive: true });
        fs.writeFileSync(path.join(repo, file), content);
    };
    fs.mkdirSync(repo);
    git('init', '-q');
    for (const file of ['upstream.php', 'changed.php', 'removed.php', 'changed.js']) write(`modern-events-calendar-lite/${file}`, '<?php\n');
    write('outside.php', '<?php\n');
    git('add', '-A');
    git('commit', '-q', '-m', 'import');
    for (const file of ['changed.php', 'added.php', 'changed.js']) write(`modern-events-calendar-lite/${file}`, '<?php // fork\n');
    write('outside.php', '<?php // fork\n');
    fs.rmSync(path.join(repo, 'modern-events-calendar-lite', 'removed.php'));
    git('add', '-A');
    git('commit', '-q', '-m', 'fork');
    const listed = spawnSync('bash', [path.join(root, 'tools', 'linters', 'fork-owned-files.sh'), '[.]php$', 'modern-events-calendar-lite'], { cwd: repo, encoding: 'utf8' });
    assert.equal(listed.status, 0, listed.stderr);
    assert.deepEqual(listed.stdout.trim().split('\n').sort(), ['modern-events-calendar-lite/added.php', 'modern-events-calendar-lite/changed.php'], 'fork-owned-files.sh must list exactly the fork-changed PHP files');

    // Behaviour: the baseline comparison passes known findings and fails new
    // ones, a missing results file and unreadable results.
    const results = path.join(scratch, 'results.txt');
    const baseline = path.join(scratch, 'baseline.json');
    const finding = '{"type":"ERROR","code":"WordPress.Security.EscapeOutput.OutputNotEscaped"}';
    fs.writeFileSync(baseline, JSON.stringify({ 'a.php': { 'ERROR WordPress.Security.EscapeOutput.OutputNotEscaped': 1 } }));
    const compare = (resultsText) => {
        if (resultsText === null) fs.rmSync(results, { force: true });
        else fs.writeFileSync(results, resultsText);
        return spawnSync('python3', [path.join(root, 'tools', 'linters', 'plugin-check-baseline.py'), results, baseline], { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: '' } }).status;
    };
    assert.equal(compare(`FILE: a.php\n[${finding}]\n`), 0, 'baseline findings must pass');
    assert.equal(compare(`FILE: a.php\n[${finding},${finding}]\n`), 1, 'a new finding must fail the baseline comparison');
    assert.equal(compare(`FILE: b.php\n[${finding}]\n`), 1, 'a finding in a new file must fail the baseline comparison');
    assert.equal(compare(''), 1, 'unreadable results must fail the baseline comparison');
    assert.equal(compare(null), 1, 'a missing results file must fail the baseline comparison');
} finally {
    fs.rmSync(scratch, { recursive: true, force: true });
}
