#!/usr/bin/env node

'use strict';

// The linter setup is a fork customization like any other: tools/linters/,
// the workflows that run it and the Dependabot rules that keep it current.
// These checks parse the configuration and run the workflow scripts against
// stand-in tools, so they test what the setup does rather than how a line is
// spelled. tests/mutate-customizations.js proves they notice when it breaks.

const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
// Line endings follow the checkout (core.autocrlf), not the setup.
const read = (relativePath) => fs.readFileSync(path.join(root, relativePath), 'utf8').replace(/\r\n?/g, '\n');
const run = (command, args, options = {}) => {
    const result = spawnSync(command, args, { encoding: 'utf8', timeout: 60_000, ...options });
    assert.ifError(result.error);
    assert.equal(result.signal, null, `${command} ${args.join(' ')} was stopped by ${result.signal}`);
    return result;
};

// A reader for the YAML these files use: block mappings and sequences, plain
// and quoted scalars, literal block scalars and one-line flow sequences.
// Anything else is reported instead of guessed at.
const parseYaml = (source, file) => {
    const lines = source.replace(/\r\n?/g, '\n').split('\n');
    let index = 0;
    const fail = (message) => { throw new Error(`${file}:${index + 1}: ${message}`); };
    const indentOf = (line) => line.match(/^ */)[0].length;
    const ignorable = (line) => /^\s*(?:#.*)?$/.test(line) || /^(?:---|\.\.\.)\s*$/.test(line);
    const next = () => {
        while (index < lines.length && ignorable(lines[index])) index++;
        if (index >= lines.length) return null;
        if (/^ *\t/.test(lines[index])) fail('tab indentation');
        return { indent: indentOf(lines[index]), text: lines[index].trimStart() };
    };
    const isItem = (text) => text === '-' || text.startsWith('- ');
    const keyLine = /^("[^"]*"|'[^']*'|[^\s#'"\-[{][^:]*?|-[^\s:][^:]*?)\s*:(?:\s+(.*)|$)/;

    const plain = (text) => {
        if (/^(?:~|null|Null|NULL)?$/.test(text)) return null;
        if (/^(?:true|True|TRUE)$/.test(text)) return true;
        if (/^(?:false|False|FALSE)$/.test(text)) return false;
        if (/^[-+]?(?:0|[1-9][0-9_]*)$/.test(text)) return Number(text.replace(/_/g, ''));
        // YAML 1.1, which Dependabot reads, takes 05:00 as the base-60 integer 300.
        if (/^[0-9][0-9_]*(?::[0-5]?[0-9])+$/.test(text)) return text.split(':').reduce((sum, part) => sum * 60 + Number(part), 0);
        if (/^[-+]?(?:[0-9]+\.[0-9]*|\.[0-9]+)$/.test(text)) return Number(text);
        if (/^[&*!%@`|>{[]/.test(text) || text.startsWith('<<')) fail(`unsupported YAML: ${text}`);
        return text;
    };
    const unescape = { n: '\n', t: '\t', '"': '"', '\\': '\\', '/': '/', '0': '\0', ' ': ' ' };
    const quoted = (text) => {
        const quote = text[0];
        let body = text.slice(1);
        let result = '';
        for (;;) {
            for (let at = 0; at < body.length; at++) {
                const char = body[at];
                if (quote === "'" && char === "'") {
                    if (body[at + 1] === "'") { result += "'"; at++; continue; }
                    if (!/^\s*(?:#.*)?$/.test(body.slice(at + 1))) fail('text after a quoted scalar');
                    return result;
                }
                if (quote === '"' && char === '\\') {
                    const escaped = unescape[body[++at]];
                    if (escaped === undefined) fail(`unsupported escape \\${body[at]}`);
                    result += escaped;
                    continue;
                }
                if (quote === '"' && char === '"') {
                    if (!/^\s*(?:#.*)?$/.test(body.slice(at + 1))) fail('text after a quoted scalar');
                    return result;
                }
                result += char;
            }
            // A line break folds to a space; each empty line is a newline.
            let breaks = 0;
            for (index++; index < lines.length && /^\s*$/.test(lines[index]); index++) breaks++;
            if (index >= lines.length) fail('unterminated quoted scalar');
            result = result.trimEnd() + (breaks ? '\n'.repeat(breaks) : ' ');
            body = lines[index].trim();
        }
    };
    const flowItem = (text) => {
        if (/^'.*'$/.test(text)) return text.slice(1, -1).replace(/''/g, "'");
        if (/^".*"$/.test(text)) return JSON.parse(text);
        return plain(text);
    };
    const block = (header, parentIndent) => {
        const match = header.match(/^\|([+-]?)\s*(?:#.*)?$/) ?? fail(`unsupported block scalar header: ${header}`);
        index++;
        const body = [];
        let contentIndent = null;
        while (index < lines.length) {
            const line = lines[index];
            if (/^\s*$/.test(line)) { body.push(''); index++; continue; }
            const indent = indentOf(line);
            if (contentIndent === null) {
                if (indent <= parentIndent) break;
                contentIndent = indent;
            }
            if (indent < contentIndent) break;
            body.push(line.slice(contentIndent));
            index++;
        }
        let trailing = 0;
        while (body.length && body[body.length - 1] === '') { body.pop(); trailing++; }
        let text = body.join('\n');
        if (body.length && match[1] !== '-') text += '\n';
        if (match[1] === '+') text += '\n'.repeat(trailing);
        return text;
    };
    // The value after "key:" or "- " on the current line.
    const value = (rest, parentIndent, afterKey) => {
        const text = rest.trim();
        if (text === '' || text.startsWith('#')) {
            index++;
            const child = next();
            if (child && child.indent > parentIndent) return node(child.indent);
            if (child && afterKey && child.indent === parentIndent && isItem(child.text)) return sequence(parentIndent);
            return null;
        }
        if (text.startsWith('|') || text.startsWith('>')) return block(text, parentIndent);
        if (text.startsWith('"') || text.startsWith("'")) {
            const result = quoted(text);
            index++;
            return result;
        }
        if (text.startsWith('[')) {
            const match = text.match(/^\[([^[\]{}]*)\]\s*(?:#.*)?$/) ?? fail(`unsupported flow sequence: ${text}`);
            index++;
            return match[1].split(',').map((item) => item.trim()).filter(Boolean).map(flowItem);
        }
        let result = text.replace(/\s+#.*$/, '');
        index++;
        // A plain scalar continues on more-indented lines.
        for (;;) {
            let ahead = index;
            while (ahead < lines.length && ignorable(lines[ahead])) ahead++;
            if (ahead >= lines.length || indentOf(lines[ahead]) <= parentIndent) break;
            result += ` ${lines[ahead].trim().replace(/\s+#.*$/, '')}`;
            index = ahead + 1;
        }
        return plain(result);
    };
    const mapping = (indent) => {
        const result = {};
        for (;;) {
            const line = next();
            if (!line || line.indent < indent) return result;
            if (line.indent > indent) fail('unexpected indentation');
            if (isItem(line.text)) fail('a sequence item where a key was expected');
            const match = line.text.match(keyLine) ?? fail(`expected "key: value", found ${line.text}`);
            const key = /^["']/.test(match[1]) ? flowItem(match[1]) : match[1];
            if (Object.hasOwn(result, key)) fail(`duplicate key ${key}`);
            result[key] = value(match[2] ?? '', indent, true);
        }
    };
    const sequence = (indent) => {
        const result = [];
        for (;;) {
            const line = next();
            if (!line || line.indent < indent || (line.indent === indent && !isItem(line.text))) return result;
            if (line.indent > indent) fail('unexpected indentation');
            const content = line.text.slice(1).trimStart();
            const column = indent + line.text.length - content.length;
            if (keyLine.test(content) || isItem(content)) {
                // "- key: value" opens a mapping at the column of its key.
                lines[index] = ' '.repeat(column) + content;
                result.push(node(column));
            } else {
                result.push(value(content, indent, false));
            }
        }
    };
    const node = (indent) => (isItem(next().text) ? sequence(indent) : mapping(indent));

    const first = next();
    if (!first) return null;
    const document = node(first.indent);
    if (next()) fail('unexpected content');
    return document;
};

// The active elements of a PHPCS ruleset: comments removed, attributes read
// in any order, spacing or quoting.
const rulesetElements = (relativePath) => {
    const xml = read(relativePath).replace(/<!--[\s\S]*?-->/g, '');
    assert.doesNotMatch(xml, /<!--|<!\[CDATA\[|<!DOCTYPE/i, `${relativePath} has XML this check cannot read`);
    return [...xml.matchAll(/<([A-Za-z][\w.-]*)((?:\s+[\w.:-]+\s*=\s*(?:"[^"]*"|'[^']*'))*)\s*\/?>/g)].map(([, name, attributes]) => ({
        name,
        attributes: Object.fromEntries([...attributes.matchAll(/([\w.:-]+)\s*=\s*(?:"([^"]*)"|'([^']*)')/g)]
            .map(([, key, double, single]) => [key, double ?? single])),
    }));
};
// Only these elements may appear, so an <exclude>, <severity> or <arg> that
// weakens a rule fails instead of passing unnoticed.
const checkRuleset = (relativePath, { configs, rules }) => {
    const elements = rulesetElements(relativePath);
    const unexpected = elements.filter((element) => !['ruleset', 'description', 'config', 'rule'].includes(element.name));
    assert.deepEqual(unexpected.map((element) => element.name), [], `${relativePath} may only contain configs and plain rules`);
    const activeConfigs = Object.fromEntries(elements.filter((element) => element.name === 'config').map((element) => [element.attributes.name, element.attributes.value]));
    assert.deepEqual(activeConfigs, configs, `${relativePath} must set exactly ${JSON.stringify(configs)}`);
    const activeRules = elements.filter((element) => element.name === 'rule').map((element) => element.attributes.ref);
    for (const rule of rules) assert.ok(activeRules.includes(rule), `${relativePath} must apply ${rule}`);
};

// The tooling lives outside .github/: Dependabot may only change files there
// for GitHub Actions updates, so a composer manifest under .github/ could
// never be updated.
for (const file of ['composer.json', 'composer.lock', 'phpstan.neon', 'phpcs-compatibility.xml', 'phpcs-wordpress.xml', 'fork-owned-files.sh', 'plugin-check-baseline.py', 'plugin-check-baseline.json']) {
    const full = path.join(root, 'tools', 'linters', file);
    assert.ok(fs.existsSync(full) && fs.statSync(full).size > 0, `tools/linters/${file} must exist`);
}
const composer = JSON.parse(read('tools/linters/composer.json'));
const composerLock = JSON.parse(read('tools/linters/composer.lock'));
const lockedPackages = [...(composerLock.packages ?? []), ...(composerLock['packages-dev'] ?? [])].map((entry) => entry.name);
const installer = 'dealerdirect/phpcodesniffer-composer-installer';
for (const name of ['phpstan/phpstan', 'szepeviktor/phpstan-wordpress', 'squizlabs/php_codesniffer', 'wp-coding-standards/wpcs', 'phpcompatibility/phpcompatibility-wp', installer]) {
    assert.ok(composer['require-dev']?.[name], `tools/linters must require ${name}`);
    assert.ok(lockedPackages.includes(name), `tools/linters/composer.lock must lock ${name}`);
}
// PHPCS finds the WordPress and PHPCompatibility standards only through the
// installer plugin, and Composer skips a plugin it is not allowed to run.
const allowedPlugins = composer.config?.['allow-plugins'];
assert.ok(allowedPlugins === true || allowedPlugins?.[installer] === true, `Composer must be allowed to run ${installer}`);

const phpstanConfig = parseYaml(read('tools/linters/phpstan.neon'), 'tools/linters/phpstan.neon');
assert.ok(phpstanConfig.includes?.includes('vendor/szepeviktor/phpstan-wordpress/extension.neon'), 'PHPStan must load the WordPress extension');
assert.equal(phpstanConfig.parameters?.phpVersion, 80400, 'PHPStan must analyse for PHP 8.4');
assert.equal(typeof phpstanConfig.parameters.level, 'number', 'PHPStan must set a rule level');
assert.ok(phpstanConfig.parameters.scanDirectories?.includes('../../renginiu-kalendorius'), 'PHPStan must scan the plugin for symbols');
assert.equal(phpstanConfig.parameters.excludePaths, undefined, 'PHPStan must not exclude paths; the workflow picks the files');
for (const ignored of phpstanConfig.parameters.ignoreErrors ?? []) {
    assert.ok(ignored && typeof ignored.identifier === 'string' && !('message' in ignored) && !('messages' in ignored),
        `PHPStan may only ignore errors by identifier, found ${JSON.stringify(ignored)}`);
}
checkRuleset('tools/linters/phpcs-compatibility.xml', {
    configs: { testVersion: '8.4-', minimum_wp_version: '6.6' },
    rules: ['PHPCompatibilityWP'],
});
checkRuleset('tools/linters/phpcs-wordpress.xml', {
    configs: { minimum_wp_version: '6.6' },
    rules: ['WordPress.Security', 'WordPress.DB.PreparedSQL', 'WordPress.DB.PreparedSQLPlaceholders', 'WordPress.DB.SlowDBQuery',
        'WordPress.DB.DirectDatabaseQuery', 'WordPress.WP.AlternativeFunctions', 'Generic.CodeAnalysis', 'Squiz.PHP.NonExecutableCode'],
});

// Dependabot keeps both ecosystems current, with one entry each.
const dependabot = parseYaml(read('.github/dependabot.yml'), '.github/dependabot.yml');
assert.equal(dependabot.version, 2);
assert.deepEqual(dependabot.updates.map((entry) => entry['package-ecosystem']).sort(), ['composer', 'github-actions'], 'Dependabot must update GitHub Actions and the linter tooling, one entry each');
const updates = Object.fromEntries(dependabot.updates.map((entry) => [entry['package-ecosystem'], entry]));
assert.equal(updates['github-actions'].directory, '/', 'Dependabot must update GitHub Actions from the repository root');
assert.equal(updates.composer.directory, '/tools/linters', 'Dependabot must watch tools/linters');
// PHPCS 4 cannot be resolved until WPCS and php-compatibility accept it (#28);
// only that major is held back.
assert.deepEqual(updates.composer.ignore, [{ 'dependency-name': 'squizlabs/php_codesniffer', 'update-types': ['version-update:semver-major'] }],
    'Dependabot must hold back PHPCS majors and nothing else');
// Unquoted, YAML reads 05:00 as the integer 300 and Dependabot rejects it (#29).
for (const [ecosystem, entry] of Object.entries(updates)) {
    assert.equal(typeof entry.schedule?.time, 'string', `${ecosystem} schedule time must be a quoted string`);
    assert.match(entry.schedule.time, /^\d\d:\d\d$/, `${ecosystem} schedule time must be HH:MM`);
}

const workflows = Object.fromEntries(fs.readdirSync(path.join(root, '.github', 'workflows'))
    .filter((name) => /\.ya?ml$/.test(name))
    .map((name) => [name, parseYaml(read(`.github/workflows/${name}`), `.github/workflows/${name}`)]));
const switchedOff = (condition) => condition === false || (typeof condition === 'string' && /^\$\{\{\s*false\s*\}\}$/.test(condition.trim()));
for (const [name, workflow] of Object.entries(workflows)) {
    for (const [id, job] of Object.entries(workflow.jobs ?? {})) {
        assert.ok(!switchedOff(job.if), `${name} must not switch job ${id} off`);
        for (const step of job.steps ?? []) assert.ok(!switchedOff(step.if), `${name} must not switch step "${step.name ?? step.uses}" off`);
    }
}
assert.doesNotMatch(JSON.stringify([workflows, dependabot]), /\.github\/linters/, 'no workflow may still use .github/linters');

const RUN_AFTER_FAILURE = '${{ !cancelled() }}';
const stepNamed = (job, name) => {
    const found = (job.steps ?? []).filter((step) => step.name === name);
    assert.equal(found.length, 1, `expected exactly one step named "${name}"`);
    return found[0];
};
// A gate job runs on every trigger of its workflow and can fail the run: the
// job and its steps neither continue on error nor carry conditions beyond
// still running after an earlier step failed.
const gateJob = (workflowName, jobId, { advisorySteps = [], runAfterFailure = [] } = {}) => {
    const job = workflows[workflowName]?.jobs?.[jobId];
    const label = `${workflowName} job ${jobId}`;
    assert.ok(job, `${label} must exist`);
    assert.equal(job.if, undefined, `${label} must run whenever its workflow runs`);
    assert.ok([undefined, false].includes(job['continue-on-error']), `${label} must not continue on error`);
    for (const step of job.steps ?? []) {
        const stepLabel = `${label} step "${step.name ?? step.uses}"`;
        if (!advisorySteps.includes(step.name)) assert.ok([undefined, false].includes(step['continue-on-error']), `${stepLabel} must not continue on error`);
        if (step.if !== undefined) assert.equal(step.if, RUN_AFTER_FAILURE, `${stepLabel} may only skip when the run is cancelled`);
    }
    for (const name of runAfterFailure) assert.equal(stepNamed(job, name).if, RUN_AFTER_FAILURE, `${label} step "${name}" must still run after an earlier check fails`);
    return job;
};

// Static analysis runs on every push and pull request.
const staticAnalysis = workflows['static-analysis.yml'];
for (const trigger of ['push', 'pull_request']) {
    assert.ok(staticAnalysis.on && Object.hasOwn(staticAnalysis.on, trigger), `static analysis must run on ${trigger}`);
    assert.equal(staticAnalysis.on[trigger], null, `static analysis must run on every ${trigger}, without filters`);
}
// PHPStan and PHP 8.4 compatibility block, each running even when the other
// failed. The WordPress review and zizmor report findings without blocking,
// but must fail when the tool itself cannot run.
const phpAnalysis = gateJob('static-analysis.yml', 'php-analysis', { runAfterFailure: ['PHP 8.4+ forward compatibility', 'PHPStan bug analysis'] });
const wordpressStandards = gateJob('static-analysis.yml', 'wordpress-standards');
const workflowSecurity = gateJob('static-analysis.yml', 'workflow-security', { advisorySteps: ['Upload zizmor results'] });
const installTooling = 'composer install --working-dir=tools/linters --no-interaction --no-progress';
for (const job of [phpAnalysis, wordpressStandards]) assert.equal(stepNamed(job, 'Install analysis tooling').run?.trim(), installTooling, 'static analysis must install tools/linters');

// The security gate runs the plugin checks on every trigger and only passes
// when they did.
const securityGate = workflows['security-gate.yml'];
assert.equal(securityGate.jobs.checks?.uses, './.github/workflows/plugin-checks.yml', 'the security gate must run plugin-checks.yml');
assert.equal(securityGate.jobs.checks.if, undefined, 'the security gate must always run the plugin checks');
assert.ok(securityGate.jobs.gate?.needs?.includes('checks'), 'the security gate must wait for the plugin checks');
assert.equal(securityGate.jobs.gate.if, '${{ always() }}', 'the security gate must report even when a check failed');

// Plugin Check fails only on findings beyond the legacy baseline. The
// official check may fail (the upstream tree always has findings); the
// comparison may not.
const codeQuality = gateJob('plugin-checks.yml', 'code-quality');
assert.equal(stepNamed(codeQuality, 'Verify fork customizations').run?.trim(), 'node tests/verify-customizations.js', 'CI must run the customization checks as they are');
assert.equal(stepNamed(codeQuality, 'Check the customization checks catch deliberate breaks').run?.trim(), 'node tests/mutate-customizations.js', 'CI must run the mutation check as it is');
const wordpressSecurity = gateJob('plugin-checks.yml', 'wordpress-security', { advisorySteps: ['Run official WordPress Plugin Check security checks'] });
const officialCheck = stepNamed(wordpressSecurity, 'Run official WordPress Plugin Check security checks');
assert.match(officialCheck.uses ?? '', /^WordPress\/plugin-check-action@[0-9a-f]{40}$/);
assert.deepEqual(officialCheck.with, { 'build-dir': './renginiu-kalendorius', categories: 'security' }, 'Plugin Check must review the plugin for security');

const scratch = fs.mkdtempSync(path.join(os.tmpdir(), 'verify-linters-'));
try {
    // Behaviour: the workflow scripts, run as GitHub runs them, against
    // stand-in tools whose exit status each case controls. They must invoke
    // the tools exactly as configured and fail whenever the tool fails.
    const shellPath = (file) => file.replace(/\\/g, '/');
    const workspace = path.join(scratch, 'workspace');
    const bin = path.join(scratch, 'bin');
    const logs = path.join(scratch, 'logs');
    const runnerTemp = path.join(scratch, 'runner-temp');
    for (const dir of [path.join(workspace, 'tools', 'linters'), bin, runnerTemp]) fs.mkdirSync(dir, { recursive: true });
    const stub = (file, body) => fs.writeFileSync(file, `#!/usr/bin/env bash\n${body}`, { mode: 0o755 });
    stub(path.join(workspace, 'tools', 'linters', 'fork-owned-files.sh'), [
        'printf \'%s\\n\' "$@" > "$STUB_LOG/fork-owned-files.sh.args"',
        'for file in $STUB_FILES; do printf \'%s\\n\' "$file"; done',
        '',
    ].join('\n'));
    stub(path.join(bin, 'php'), [
        'tool="${1##*/}"',
        'printf \'%s\\n\' "$@" > "$STUB_LOG/$tool.args"',
        'echo "$tool stand-in"',
        'case "$tool" in',
        '    phpcs) exit "$STUB_PHPCS" ;;',
        '    phpstan) exit "$STUB_PHPSTAN" ;;',
        'esac',
        'echo "unexpected tool $tool" >&2',
        'exit 99',
        '',
    ].join('\n'));
    stub(path.join(bin, 'zizmor'), [
        'printf \'%s\\n\' "$@" > "$STUB_LOG/zizmor.args"',
        'if [ "$STUB_ZIZMOR_REPORT" = 1 ]; then echo \'{"runs": []}\'; fi',
        'exit "$STUB_ZIZMOR"',
        '',
    ].join('\n'));
    fs.copyFileSync(path.join(root, 'tools', 'linters', 'plugin-check-baseline.py'), path.join(workspace, 'tools', 'linters', 'plugin-check-baseline.py'));
    const baselineFixture = JSON.stringify({ 'a.php': { 'ERROR WordPress.Security.EscapeOutput.OutputNotEscaped': 1 } });
    fs.writeFileSync(path.join(workspace, 'tools', 'linters', 'plugin-check-baseline.json'), baselineFixture);

    const FILES = ['renginiu-kalendorius/a.php', 'renginiu-kalendorius/b.php'];
    const baseEnv = Object.fromEntries(Object.entries(process.env).filter(([key]) => key.toUpperCase() !== 'PATH'));
    const runStep = (step, { files = FILES, phpcs = 0, phpstan = 0, zizmor = 0, zizmorReport = true, results = null } = {}) => {
        assert.equal(typeof step.run, 'string', `step "${step.name}" must run a script`);
        // How GitHub starts `shell: bash` and the default shell on Linux.
        const flags = step.shell === 'bash' ? ['--noprofile', '--norc', '-eo', 'pipefail'] : step.shell === undefined ? ['-e'] : null;
        assert.ok(flags, `step "${step.name}" must run in bash`);
        fs.rmSync(logs, { recursive: true, force: true });
        fs.mkdirSync(logs);
        for (const leftover of ['zizmor.sarif', 'phpcs-review.txt']) fs.rmSync(path.join(workspace, leftover), { force: true });
        const resultsFile = path.join(runnerTemp, 'plugin-check-results.txt');
        if (results === null) fs.rmSync(resultsFile, { force: true });
        else fs.writeFileSync(resultsFile, results);
        const output = path.join(scratch, 'github-output');
        fs.writeFileSync(output, '');
        fs.writeFileSync(path.join(workspace, 'step.sh'), step.run);
        const result = run('bash', [...flags, 'step.sh'], {
            cwd: workspace,
            env: {
                ...baseEnv,
                PATH: `${bin}${path.delimiter}${process.env.PATH ?? process.env.Path ?? ''}`,
                STUB_LOG: shellPath(logs),
                STUB_FILES: files.join(' '),
                STUB_PHPCS: String(phpcs),
                STUB_PHPSTAN: String(phpstan),
                STUB_ZIZMOR: String(zizmor),
                STUB_ZIZMOR_REPORT: zizmorReport ? '1' : '0',
                GITHUB_OUTPUT: shellPath(output),
                GITHUB_STEP_SUMMARY: shellPath(path.join(scratch, 'step-summary')),
                RUNNER_TEMP: shellPath(runnerTemp),
            },
        });
        const logged = (tool) => {
            const file = path.join(logs, `${tool}.args`);
            return fs.existsSync(file) ? fs.readFileSync(file, 'utf8').split('\n').slice(0, -1) : null;
        };
        return { status: result.status, output: fs.readFileSync(output, 'utf8'), logged, log: `${result.stdout}${result.stderr}` };
    };
    const passes = (outcome, message) => assert.equal(outcome.status, 0, `${message}\n${outcome.log}`);
    const fails = (outcome, message) => assert.notEqual(outcome.status, 0, `${message}\n${outcome.log}`);
    const forkArgs = ['[.]php$', 'renginiu-kalendorius'];

    const listFiles = stepNamed(phpAnalysis, 'List the files this fork maintains');
    let outcome = runStep(listFiles);
    passes(outcome, 'listing the fork files must pass');
    assert.deepEqual(outcome.logged('fork-owned-files.sh'), forkArgs, 'the fork file list must cover the plugin PHP files');
    fails(runStep(listFiles, { files: [] }), 'an empty fork file list must fail PHPStan');

    const compatibility = stepNamed(phpAnalysis, 'PHP 8.4+ forward compatibility');
    outcome = runStep(compatibility);
    passes(outcome, 'a clean PHP compatibility run must pass');
    assert.deepEqual(outcome.logged('fork-owned-files.sh'), forkArgs, 'PHP compatibility must check the fork files');
    assert.deepEqual(outcome.logged('phpcs'), ['tools/linters/vendor/bin/phpcs', '--standard=tools/linters/phpcs-compatibility.xml', '--report=full', '--', ...FILES],
        'PHP compatibility must run phpcs with its ruleset on exactly the fork files');
    for (const status of [1, 2, 3]) fails(runStep(compatibility, { phpcs: status }), `PHP compatibility must fail when phpcs exits ${status}`);

    const phpstan = stepNamed(phpAnalysis, 'PHPStan bug analysis');
    outcome = runStep(phpstan);
    passes(outcome, 'a clean PHPStan run must pass');
    assert.deepEqual(outcome.logged('fork-owned-files.sh'), forkArgs, 'PHPStan must analyse the fork files');
    assert.deepEqual(outcome.logged('phpstan'), ['tools/linters/vendor/bin/phpstan', 'analyse', '--configuration=tools/linters/phpstan.neon', '--no-progress', '--memory-limit=3G', '--', ...FILES],
        'PHPStan must run with its configuration on exactly the fork files');
    for (const status of [1, 255]) fails(runStep(phpstan, { phpstan: status }), `PHPStan must fail when it exits ${status}`);

    const review = stepNamed(wordpressStandards, 'Review escaping, queries and dead code');
    outcome = runStep(review);
    passes(outcome, 'a clean WordPress review must pass');
    assert.match(outcome.output, /^findings=no$/m);
    assert.deepEqual(outcome.logged('fork-owned-files.sh'), forkArgs, 'the WordPress review must cover the fork files');
    assert.deepEqual(outcome.logged('phpcs'), ['tools/linters/vendor/bin/phpcs', '--standard=tools/linters/phpcs-wordpress.xml', '--report=summary', '--report-source', '--', ...FILES],
        'the WordPress review must run phpcs with its ruleset on exactly the fork files');
    for (const status of [1, 2]) assert.match(runStep(review, { phpcs: status }).output, /^findings=yes$/m, `phpcs exiting ${status} must be reported as findings`);
    for (const status of [3, 16]) fails(runStep(review, { phpcs: status }), `phpcs failing to run (exit ${status}) must fail the review`);
    fails(runStep(review, { files: [] }), 'an empty fork file list must fail the WordPress review');

    const zizmor = stepNamed(workflowSecurity, 'Audit GitHub Actions workflows');
    outcome = runStep(zizmor);
    passes(outcome, 'a clean zizmor audit must pass');
    assert.match(outcome.output, /^findings=no$/m);
    assert.deepEqual(outcome.logged('zizmor'), ['--format', 'sarif', '--no-online-audits', '.'], 'zizmor must audit the repository offline as SARIF');
    assert.match(runStep(zizmor, { zizmor: 13 }).output, /^findings=yes$/m, 'zizmor findings must be reported');
    for (const status of [0, 1]) fails(runStep(zizmor, { zizmor: status, zizmorReport: false }), `an empty zizmor report (exit ${status}) must fail`);

    const finding = (code) => `{"type":"ERROR","code":"${code}"}`;
    const known = finding('WordPress.Security.EscapeOutput.OutputNotEscaped');
    const baselineStep = stepNamed(wordpressSecurity, 'Compare with the legacy baseline');
    passes(runStep(baselineStep, { results: `FILE: a.php\n[${known}]\n` }), 'the baseline comparison step must pass known findings');
    fails(runStep(baselineStep, { results: `FILE: a.php\n[${known},${known}]\n` }), 'the baseline comparison step must fail new findings');
    fails(runStep(baselineStep, { results: null }), 'the baseline comparison step must fail without results');
    assert.equal(fs.readFileSync(path.join(workspace, 'tools', 'linters', 'plugin-check-baseline.json'), 'utf8'), baselineFixture, 'the baseline comparison step must not rewrite the baseline');

    // Behaviour: the baseline comparison passes known and resolved findings
    // and fails new ones, a missing results file and unreadable results.
    const results = path.join(scratch, 'results.txt');
    const baseline = path.join(scratch, 'baseline.json');
    fs.writeFileSync(baseline, JSON.stringify({ 'a.php': { 'ERROR WordPress.Security.EscapeOutput.OutputNotEscaped': 2 } }));
    const compare = (resultsText) => {
        if (resultsText === null) fs.rmSync(results, { force: true });
        else fs.writeFileSync(results, resultsText);
        return run('python3', [path.join(root, 'tools', 'linters', 'plugin-check-baseline.py'), results, baseline], { env: { ...process.env, GITHUB_STEP_SUMMARY: '' } }).status;
    };
    assert.equal(compare(`FILE: a.php\n[${known},${known}]\n`), 0, 'baseline findings must pass');
    assert.equal(compare(`FILE: a.php\n[${known}]\n`), 0, 'resolved findings must pass');
    assert.equal(compare(`FILE: a.php\n[${known},${known},${known}]\n`), 1, 'a new finding must fail the baseline comparison');
    assert.equal(compare(`FILE: a.php\n[${known},${finding('WordPress.DB.PreparedSQL.NotPrepared')}]\n`), 1, 'a new finding code in a known file must fail the baseline comparison');
    assert.equal(compare(`FILE: b.php\n[${known}]\n`), 1, 'a finding in a new file must fail the baseline comparison');
    assert.equal(compare(''), 1, 'unreadable results must fail the baseline comparison');
    assert.equal(compare(null), 1, 'a missing results file must fail the baseline comparison');

    // Behaviour: fork-owned-files.sh lists the plugin PHP files changed since
    // the import commit (not since the previous one), skipping deleted files,
    // other extensions, files outside the pathspec and files only carried
    // over by the plugin rename; UPSTREAM_IMPORT_REF overrides the import.
    const repo = path.join(scratch, 'repo');
    fs.mkdirSync(repo);
    const git = (...args) => {
        const result = run('git', ['-c', 'user.name=verify', '-c', 'user.email=verify@example.invalid', '-c', 'commit.gpgsign=false', '-c', 'core.autocrlf=false', ...args], { cwd: repo });
        assert.equal(result.status, 0, `git ${args.join(' ')}: ${result.stderr}`);
        return result.stdout.trim();
    };
    const write = (file, content) => {
        fs.mkdirSync(path.dirname(path.join(repo, file)), { recursive: true });
        fs.writeFileSync(path.join(repo, file), content);
    };
    const body = (name, header = `// ${name}`) => `<?php\n${header}\n${Array.from({ length: 12 }, (_, line) => `$${name}_${line} = ${line};\n`).join('')}`;
    const commit = (message) => {
        git('add', '-A');
        git('commit', '-q', '-m', message);
        return git('rev-parse', 'HEAD');
    };
    const OLD = 'modern-events-calendar-lite';
    const NEW = 'renginiu-kalendorius';
    git('init', '-q');
    for (const name of ['upstream', 'changed', 'removed', 'later']) write(`${OLD}/${name}.php`, body(name));
    write(`${OLD}/changed.js`, body('script'));
    write(`${OLD}/slug.php`, body('slug', `// Text domain: ${OLD}`));
    write(`${OLD}/tiny.php`, `<?php // ${OLD}\n`);
    write('outside.php', body('outside'));
    commit('import');
    fs.renameSync(path.join(repo, OLD), path.join(repo, NEW));
    write(`${NEW}/slug.php`, body('slug', `// Text domain: ${NEW}`));
    write(`${NEW}/tiny.php`, `<?php // ${NEW}\n`);
    write(`${NEW}/changed.php`, `${body('changed')}// fork\n`);
    write(`${NEW}/changed.js`, `${body('script')}// fork\n`);
    write(`${NEW}/added.php`, body('added'));
    write('outside.php', `${body('outside')}// fork\n`);
    fs.rmSync(path.join(repo, NEW, 'removed.php'));
    const renamed = commit('rename and fork');
    write(`${NEW}/later.php`, `${body('later')}// fork\n`);
    commit('later fork change');
    const forkOwned = (importRef) => {
        const env = { ...process.env };
        delete env.UPSTREAM_IMPORT_REF;
        if (importRef) env.UPSTREAM_IMPORT_REF = importRef;
        const listed = run('bash', [path.join(root, 'tools', 'linters', 'fork-owned-files.sh'), '[.]php$', NEW], { cwd: repo, env });
        assert.equal(listed.status, 0, listed.stderr);
        return listed.stdout.split('\n').filter(Boolean).sort();
    };
    assert.deepEqual(forkOwned(), [`${NEW}/added.php`, `${NEW}/changed.php`, `${NEW}/later.php`], 'fork-owned-files.sh must list exactly the fork-changed PHP files');
    assert.deepEqual(forkOwned(renamed), [`${NEW}/later.php`], 'fork-owned-files.sh must honour UPSTREAM_IMPORT_REF');
} finally {
    fs.rmSync(scratch, { recursive: true, force: true });
}
