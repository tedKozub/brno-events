#!/usr/bin/env node

/**
 * Static syntax checker for executable JavaScript embedded inside Laravel Blade templates.
 * Scans all *.blade.php files in resources/views and verifies JS syntax with Node.js VM.
 */

import fs from 'fs';
import path from 'path';
import vm from 'vm';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '..');
const viewsDir = path.join(projectRoot, 'resources', 'views');

function walk(dir) {
    let results = [];
    if (!fs.existsSync(dir)) return results;
    const list = fs.readdirSync(dir);
    for (const file of list) {
        const full = path.join(dir, file);
        const stat = fs.statSync(full);
        if (stat && stat.isDirectory()) {
            results = results.concat(walk(full));
        } else if (file.endsWith('.blade.php')) {
            results.push(full);
        }
    }
    return results;
}

const files = walk(viewsDir);
let errorsCount = 0;
let scriptsCount = 0;
const errorReports = [];

for (const file of files) {
    const relPath = path.relative(projectRoot, file);
    const content = fs.readFileSync(file, 'utf8');
    const scriptRegex = /<script\b([^>]*)>([\s\S]*?)<\/script>/gi;
    let match;
    let idx = 0;

    while ((match = scriptRegex.exec(content)) !== null) {
        idx++;
        const attrs = match[1] || '';
        const rawContent = match[2];

        // Skip external script tags (<script src="...">)
        if (/\bsrc\s*=/i.test(attrs)) {
            continue;
        }

        // Skip JSON or JSON-LD data scripts
        if (/\btype\s*=\s*["']application\/(ld\+)?json["']/i.test(attrs)) {
            continue;
        }

        // Sanitize Blade directives and interpolations for JavaScript parser
        const sanitizedJs = rawContent
            .replace(/@js\([\s\S]*?\)/g, 'null')
            .replace(/@json\([\s\S]*?\)/g, 'null')
            .replace(/\{\{[\s\S]*?\}\}/g, 'null')
            .replace(/\{!![\s\S]*?!!\}/g, 'null');

        scriptsCount++;
        try {
            new vm.Script(sanitizedJs, {
                filename: `${relPath} [script #${idx}]`,
                displayErrors: true,
            });
        } catch (e) {
            errorsCount++;
            errorReports.push(`[SYNTAX ERROR] in ${relPath} (script #${idx}):\n  ${e.message}\n`);
        }
    }
}

if (errorsCount > 0) {
    console.error(`\nFAILED: Found ${errorsCount} JavaScript syntax error(s) in Blade templates:\n`);
    errorReports.forEach(err => console.error(err));
    process.exit(1);
} else {
    console.log(`PASS: Verified ${files.length} Blade templates (${scriptsCount} inline script blocks checked). No syntax errors found.`);
    process.exit(0);
}
