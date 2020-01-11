/*
 * Copyright (c) 2016 The Polymer Project Authors. All rights reserved.
 * This code may only be used under the BSD style license found at
 * http://polymer.github.io/LICENSE.txt The complete set of authors may be found
 * at http://polymer.github.io/AUTHORS.txt The complete set of contributors may
 * be found at http://polymer.github.io/CONTRIBUTORS.txt Code distributed by
 * Google as part of the polymer project is also subject to an additional IP
 * rights grant found at http://polymer.github.io/PATENTS.txt
 */
'use strict';
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : new P(function (resolve) { resolve(result.value); }).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
Object.defineProperty(exports, "__esModule", { value: true });
const chai_1 = require("chai");
const path = require("path");
const yeoman_test_1 = require("yeoman-test");
const application_1 = require("../../init/application/application");
const run_command_1 = require("./run-command");
const element_1 = require("../../init/element/element");
const github_1 = require("../../init/github");
const puppeteer = require("puppeteer");
const polyserve_1 = require("polyserve");
const polymer_project_config_1 = require("polymer-project-config");
const tempMod = require("temp");
const fs = require("fs");
const util_1 = require("util");
const debugging = !!process.env['DEBUG_CLI_TESTS'];
const temp = tempMod.track();
const disposables = [];
// A zero privilege github token of a nonce account, used for quota.
const githubToken = '8d8622bf09bb1d85cb411b5e475a35e742a7ce35';
// TODO(https://github.com/Polymer/tools/issues/74): some tests time out on
//     windows.
const isWindows = process.platform === 'win32';
const skipOnWindows = isWindows ? test.skip : test;
const binPath = path.join(__dirname, '../../../', 'bin', 'polymer.js');
// Serves the given directory with polyserve, returns a fully qualified
// url of the server.
function serve(dirToServe, options = {}) {
    return __awaiter(this, void 0, void 0, function* () {
        const startResult = yield polyserve_1.startServers(Object.assign({ root: dirToServe }, options));
        if (startResult.kind === 'MultipleServers') {
            for (const server of startResult.servers) {
                server.server.close();
            }
            throw new Error(`Unexpected startResult`);
        }
        disposables.push(() => {
            startResult.server.close();
        });
        const address = startResult.server.address();
        if (typeof address === 'string') {
            return `http://${address}`;
        }
        else if (!util_1.isNull(address)) {
            return `http://${address.address}:${address.port}`;
        }
        else {
            // How you gonna serve without an address?  How is that even a server?  If a
            // server can't respond to requests, is it *really* a server?
            throw new Error(`No address returned when starting server. ${startResult}`);
        }
    });
}
function requestAnimationFrame(page) {
    return __awaiter(this, void 0, void 0, function* () {
        yield page.waitFor(function () {
            return new Promise((resolve) => {
                this.requestAnimationFrame(resolve);
            });
        });
    });
}
/**
 * Like puppeteer's page.goto(), except it fails if any uncaught exceptions are
 * thrown, and it waits a few rAFs after the load to be really sure the page is
 * ready.
 */
function gotoOrDie(page, url) {
    return __awaiter(this, void 0, void 0, function* () {
        let error;
        const handler = (e) => (error = error || e);
        // Grab the first page error, if any.
        page.on('pageerror', handler);
        yield page.goto(url);
        if (error) {
            throw new Error(`Error loading ${url} in Chrome: ${error}`);
        }
        for (let i = 0; i < 3; i++) {
            yield requestAnimationFrame(page);
        }
        if (error) {
            throw new Error(`Error during rAFs after loading ${url} in Chrome. Browser Error:\n${error}`);
        }
        page.removeListener('pageerror', handler);
    });
}
suite('integration tests', function () {
    // Extend timeout limit to 90 seconds for slower systems
    this.timeout(4 * 60 * 1000);
    suiteTeardown(() => __awaiter(this, void 0, void 0, function* () {
        yield Promise.all(disposables.map((d) => d()));
        disposables.length = 0;
    }));
    suite('init templates', () => {
        skipOnWindows('test the Polymer 3.x element template', () => __awaiter(this, void 0, void 0, function* () {
            const dir = yield yeoman_test_1.run(element_1.createElementGenerator('polymer-3.x'))
                .withPrompts({ name: 'my-element' }) // Mock the prompt answers
                .toPromise();
            yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['lint'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['test'], { cwd: dir });
        }));
        skipOnWindows('test the Polymer 3.x application template', () => __awaiter(this, void 0, void 0, function* () {
            const dir = yield yeoman_test_1.run(application_1.createApplicationGenerator('polymer-3.x'))
                .withPrompts({ name: 'my-app' }) // Mock the prompt answers
                .toPromise();
            yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['lint'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['test'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['build'], { cwd: dir });
        }));
        skipOnWindows('test the Polymer 1.x application template', () => __awaiter(this, void 0, void 0, function* () {
            const dir = yield yeoman_test_1.run(application_1.createApplicationGenerator('polymer-1.x'))
                .withPrompts({ name: 'my-app' }) // Mock the prompt answers
                .toPromise();
            yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['lint'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['test'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['build'], { cwd: dir });
        }));
        skipOnWindows('test the Polymer 2.x application template', () => __awaiter(this, void 0, void 0, function* () {
            const dir = yield yeoman_test_1.run(application_1.createApplicationGenerator('polymer-2.x'))
                .withPrompts({ name: 'my-app' }) // Mock the prompt answers
                .toPromise();
            yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['lint'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['test'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['build'], { cwd: dir });
        }));
        skipOnWindows('test the Polymer 2.x "element" template', () => __awaiter(this, void 0, void 0, function* () {
            const dir = yield yeoman_test_1.run(element_1.createElementGenerator('polymer-2.x'))
                .withPrompts({ name: 'my-element' }) // Mock the prompt answers
                .toPromise();
            yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['lint'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['test'], { cwd: dir });
        }));
        skipOnWindows('test the Polymer 1.x "element" template', () => __awaiter(this, void 0, void 0, function* () {
            const dir = yield yeoman_test_1.run(element_1.createElementGenerator('polymer-1.x'))
                .withPrompts({ name: 'my-element' }) // Mock the prompt answers
                .toPromise();
            yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['lint'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['test'], { cwd: dir });
        }));
        test('test the "shop" template', () => __awaiter(this, void 0, void 0, function* () {
            const ShopGenerator = github_1.createGithubGenerator({
                owner: 'Polymer',
                repo: 'shop',
                semverRange: '^2.0.0',
                githubToken,
                installDependencies: {
                    bower: true,
                    npm: false,
                },
            });
            const dir = yield yeoman_test_1.run(ShopGenerator).toPromise();
            yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
            // See: https://github.com/Polymer/shop/pull/114
            // await runCommand(
            //   binPath, ['lint', '--rules=polymer-2-hybrid'],
            //   {cwd: dir})
            // await runCommand(binPath, ['test'], {cwd: dir})
            yield run_command_1.runCommand(binPath, ['build'], { cwd: dir });
        }));
        // TODO(justinfagnani): consider removing these integration tests
        // or checking in the contents so that we're not subject to the
        // other repo changing
        test.skip('test the Polymer 1.x "starter-kit" template', () => __awaiter(this, void 0, void 0, function* () {
            const PSKGenerator = github_1.createGithubGenerator({
                owner: 'Polymer',
                repo: 'polymer-starter-kit',
                semverRange: '^2.0.0',
                githubToken,
                installDependencies: {
                    bower: true,
                    npm: false,
                },
            });
            const dir = yield yeoman_test_1.run(PSKGenerator).toPromise();
            yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['lint', '--rules=polymer-2-hybrid'], {
                cwd: dir,
            });
            // await runCommand(binPath, ['test'], {cwd: dir})
            yield run_command_1.runCommand(binPath, ['build'], { cwd: dir });
        }));
        // TODO(justinfagnani): consider removing these integration tests
        // or checking in the contents so that we're not subject to the
        // other repo changing
        test.skip('test the Polymer 2.x "starter-kit" template', () => __awaiter(this, void 0, void 0, function* () {
            const PSKGenerator = github_1.createGithubGenerator({
                owner: 'Polymer',
                repo: 'polymer-starter-kit',
                semverRange: '^3.0.0',
                githubToken,
                installDependencies: {
                    bower: true,
                    npm: false,
                },
            });
            const dir = yield yeoman_test_1.run(PSKGenerator).toPromise();
            yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['lint', '--rules=polymer-2'], { cwd: dir });
            // await runCommand(binPath, ['test'], {cwd: dir}));
            yield run_command_1.runCommand(binPath, ['build'], { cwd: dir });
        }));
    });
    // TODO(justinfagnani): consider removing these integration tests
    // or checking in the contents so that we're not subject to the
    // other repo changing
    suite.skip('tools-sample-projects templates', () => {
        let tspDir;
        suiteSetup(() => __awaiter(this, void 0, void 0, function* () {
            const TSPGenerator = github_1.createGithubGenerator({
                owner: 'Polymer',
                repo: 'tools-sample-projects',
                githubToken,
            });
            tspDir = yield yeoman_test_1.run(TSPGenerator).toPromise();
        }));
        test('test the "polymer-1-app" template', () => __awaiter(this, void 0, void 0, function* () {
            const dir = path.join(tspDir, 'polymer-1-app');
            yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['lint'], { cwd: dir });
            // await runCommand(binPath, ['test'], {cwd: dir});
            yield run_command_1.runCommand(binPath, ['build'], { cwd: dir });
        }));
        test('test the "polymer-2-app" template', () => __awaiter(this, void 0, void 0, function* () {
            const dir = path.join(tspDir, 'polymer-2-app');
            yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
            yield run_command_1.runCommand(binPath, ['lint'], { cwd: dir });
            // await runCommand(binPath, ['test'], {cwd: dir})
            yield run_command_1.runCommand(binPath, ['build'], { cwd: dir });
        }));
    });
});
suite('import.meta support', () => __awaiter(this, void 0, void 0, function* () {
    let tempDir;
    // Build options, copied from shop.
    const options = {
        entrypoint: 'index.html',
        builds: [
            {
                name: 'esm-bundled',
                browserCapabilities: ['es2015', 'modules'],
                js: { minify: true },
                css: { minify: true },
                html: { minify: true },
                bundle: true,
            },
            {
                name: 'es6-bundled',
                browserCapabilities: ['es2015'],
                js: { minify: true, transformModulesToAmd: true },
                css: { minify: true },
                html: { minify: true },
                bundle: true,
            },
            {
                name: 'es5-bundled',
                js: { compile: true, minify: true, transformModulesToAmd: true },
                css: { minify: true },
                html: { minify: true },
                bundle: true,
            },
        ],
        moduleResolution: 'node',
        npm: true,
    };
    suiteSetup(function () {
        tempDir = temp.mkdirSync('-import-meta');
        // An inline import.meta test fixture!
        fs.writeFileSync(path.join(tempDir, 'index.html'), `
        <script type="module">
            import './subdir/foo.js';
            window.indexHtmlUrl = import.meta.url;
        </script>
      `);
        fs.mkdirSync(path.join(tempDir, 'subdir'));
        fs.writeFileSync(path.join(tempDir, 'subdir/index.html'), `
        <script type="module">
            import './foo.js';
            window.indexHtmlUrl = import.meta.url;
        </script>
      `);
        fs.writeFileSync(path.join(tempDir, 'subdir', 'foo.js'), `
        window.fooUrl = import.meta.url;
    `);
        fs.writeFileSync(path.join(tempDir, 'package.json'), JSON.stringify({ name: 'import-meta-test' }));
        fs.writeFileSync(path.join(tempDir, 'polymer.json'), JSON.stringify(options));
    });
    teardown(() => __awaiter(this, void 0, void 0, function* () {
        yield Promise.all(disposables.map((d) => d()));
        disposables.length = 0;
    }));
    // The given url should be a fully qualified
    const assertPageWorksCorrectly = (baseUrl, skipTestingSubdir = false) => __awaiter(this, void 0, void 0, function* () {
        const browser = yield puppeteer.launch();
        disposables.push(() => browser.close());
        const page = yield browser.newPage();
        yield gotoOrDie(page, `${baseUrl}/`);
        chai_1.assert.deepEqual(yield page.evaluate(`window.indexHtmlUrl`), `${baseUrl}/`);
        chai_1.assert.deepEqual(yield page.evaluate('window.fooUrl'), `${baseUrl}/subdir/foo.js`);
        yield gotoOrDie(page, `${baseUrl}/index.html`);
        chai_1.assert.deepEqual(yield page.evaluate(`window.indexHtmlUrl`), `${baseUrl}/index.html`);
        chai_1.assert.deepEqual(yield page.evaluate('window.fooUrl'), `${baseUrl}/subdir/foo.js`);
        if (!skipTestingSubdir) {
            yield gotoOrDie(page, `${baseUrl}/subdir/`);
            chai_1.assert.deepEqual(yield page.evaluate(`window.indexHtmlUrl`), `${baseUrl}/subdir/`);
            chai_1.assert.deepEqual(yield page.evaluate('window.fooUrl'), `${baseUrl}/subdir/foo.js`);
            yield gotoOrDie(page, `${baseUrl}/subdir/index.html`);
            chai_1.assert.deepEqual(yield page.evaluate(`window.indexHtmlUrl`), `${baseUrl}/subdir/index.html`);
            chai_1.assert.deepEqual(yield page.evaluate('window.fooUrl'), `${baseUrl}/subdir/foo.js`);
        }
        return page;
    });
    test('import.meta works uncompiled in chrome', function () {
        return __awaiter(this, void 0, void 0, function* () {
            const url = yield serve(tempDir, { compile: 'never' });
            const page = yield assertPageWorksCorrectly(url);
            yield gotoOrDie(page, `${url}/`);
            chai_1.assert.include(yield page.content(), 'import.meta', 'expected import.meta to not be compiled out!');
        });
    });
    let testName = 'import.meta works in chrome with polyserve es5 compilation';
    test(testName, function () {
        return __awaiter(this, void 0, void 0, function* () {
            const url = yield serve(tempDir, { compile: 'always' });
            const page = yield assertPageWorksCorrectly(url);
            yield gotoOrDie(page, `${url}/`);
            chai_1.assert.notInclude(yield page.content(), 'import.meta', 'expected import.meta to be compiled out!');
        });
    });
    suite('after building', () => {
        suiteSetup(function () {
            return __awaiter(this, void 0, void 0, function* () {
                this.timeout(20 * 1000);
                yield run_command_1.runCommand(binPath, ['build'], { cwd: tempDir });
            });
        });
        for (const buildOption of options.builds) {
            const buildName = buildOption.name || 'default';
            testName = `import.meta works in build configuration ${buildName}`;
            test(testName, function () {
                return __awaiter(this, void 0, void 0, function* () {
                    const url = yield serve(path.join(tempDir, 'build', buildName), {
                        compile: 'always',
                    });
                    const page = yield assertPageWorksCorrectly(url, true);
                    yield gotoOrDie(page, `${url}/`);
                    if (buildName !== 'esm-bundle') {
                        chai_1.assert.notInclude(yield page.content(), 'import.meta', 'expected import.meta to be compiled out!');
                    }
                    else {
                        chai_1.assert.include(yield page.content(), 'import.meta', 'expected import.meta to not be compiled out!');
                    }
                });
            });
        }
    });
}));
suite('polymer shop', function () {
    this.timeout(60 * 1000);
    // Given the URL of a server serving out Polymer shop, opens a Chrome tab
    // and pokes around to test that Shop is working there.
    function assertThatShopWorks(baseUrl) {
        return __awaiter(this, void 0, void 0, function* () {
            let browser;
            if (debugging) {
                browser = yield puppeteer.launch({ headless: false, slowMo: 250 });
            }
            else {
                // TODO(usergenic): For some unknown reason, tests failed in headless
                // Chrome involving the `/cart` route for the Polymer/shop lit-element
                // branch only.  Remove the `{headless: false}` when this problem is
                // fixed.
                browser = yield puppeteer.launch({ headless: false });
            }
            disposables.push(() => browser.close());
            const page = yield browser.newPage();
            page.on('pageerror', (e) => (error = error || e));
            // Evaluate an expression as a string in the browser.
            const evaluate = (expression) => __awaiter(this, void 0, void 0, function* () {
                try {
                    return yield page.evaluate(expression);
                }
                catch (e) {
                    throw new Error(`Failed evaluating expression \`${expression} in the browser. Error: ${e}`);
                }
            });
            // Assert on an expression's result in the browser.
            const assertTrueInPage = (expression) => __awaiter(this, void 0, void 0, function* () {
                chai_1.assert(yield evaluate(expression), `Expected \`${expression}\` to evaluate to true in the browser`);
            });
            const waitFor = (name, expression, timeout) => __awaiter(this, void 0, void 0, function* () {
                try {
                    yield page.waitForFunction(expression, { timeout });
                }
                catch (e) {
                    throw new Error(`Error waiting for ${name} in the browser`);
                }
            });
            yield gotoOrDie(page, `${baseUrl}/`);
            chai_1.assert.deepEqual(`${baseUrl}/`, page.url());
            yield waitFor('shop-app to be defined', `this.customElements.get('shop-app') !== undefined`);
            yield waitFor('shop-app children to exist', `this.document.querySelector('shop-app')
            .shadowRoot.querySelector('a[href="/cart"], shop-cart-button')`);
            const isLitElement = yield evaluate(`!!this.document.querySelector('shop-app')
            .shadowRoot.querySelector('shop-cart-button')`);
            if (isLitElement) {
                // Wait a few more rAFs for the button to definitely be there.
                for (let i = 0; i < 10; i++) {
                    yield requestAnimationFrame(page);
                }
                yield page.waitForFunction(`!!(
          document.querySelector('shop-app').shadowRoot
            .querySelector('shop-cart-button').shadowRoot)`);
            }
            // The cart shouldn't be registered yet, because we've only loaded the
            // main page.
            yield assertTrueInPage(`customElements.get('shop-cart') === undefined`);
            // Click the shopping cart button.
            yield evaluate(`
        (
          // shop 3.0
          document.querySelector('shop-app').shadowRoot
              .querySelector('a[href="/cart"]')
          ||
          // shop lit
          document.querySelector('shop-app').shadowRoot
              .querySelector('shop-cart-button').shadowRoot
                  .querySelector('a[href="/cart"]')
        ).click()`);
            // The url changes immediately
            chai_1.assert.deepEqual(`${baseUrl}/cart`, page.url());
            // We'll lazy load the code for shop-cart. We'll know that it worked
            // when the element is registered. If this resolves, it loaded
            // successfully!
            yield waitFor('shop-cart to be defined', `this.customElements.get('shop-cart') !== undefined`, 3 * 60 * 1000);
        });
    }
    let error;
    setup(() => __awaiter(this, void 0, void 0, function* () {
        error = undefined;
    }));
    teardown(() => __awaiter(this, void 0, void 0, function* () {
        if (error !== undefined) {
            throw new Error(`Error encountered in browser page while testing: ${error}`);
        }
        yield Promise.all(disposables.map((d) => d()));
        disposables.length = 0;
    }));
    suite('the 3.0 branch', () => {
        let dir;
        suiteSetup(function () {
            return __awaiter(this, void 0, void 0, function* () {
                const debugDir = process.env['CLI_TEST_SHOP_3_DIR'];
                if (debugDir != null) {
                    dir = debugDir;
                }
                else {
                    // Cloning and installing can take a few minutes
                    this.timeout(4 * 60 * 1000);
                    const ShopGenerator = github_1.createGithubGenerator({
                        owner: 'Polymer',
                        repo: 'shop',
                        githubToken,
                        tag: 'v3.0.0',
                        installDependencies: {
                            bower: false,
                            npm: true,
                        },
                    });
                    dir = yield yeoman_test_1.run(ShopGenerator).toPromise();
                    yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
                }
            });
        });
        test('serving sources with polyserve and `never` compile', () => __awaiter(this, void 0, void 0, function* () {
            const baseUrl = yield serve(dir, {
                compile: 'never',
                moduleResolution: 'node',
            });
            yield assertThatShopWorks(baseUrl);
        }));
        const testName = 'serving sources with polyserve and `always` compile';
        test(testName, function () {
            return __awaiter(this, void 0, void 0, function* () {
                // Compiling is a little slow.
                this.timeout(30 * 1000);
                const baseUrl = yield serve(dir, {
                    compile: 'always',
                    moduleResolution: 'node',
                });
                yield assertThatShopWorks(baseUrl);
            });
        });
        test('serving sources with polyserve and `auto` compile', () => __awaiter(this, void 0, void 0, function* () {
            const baseUrl = yield serve(dir, {
                compile: 'auto',
                moduleResolution: 'node',
            });
            yield assertThatShopWorks(baseUrl);
        }));
        suite('when built with polymer build', () => {
            const expectedBuildNames = [
                'es5-bundled',
                'es6-bundled',
                'esm-bundled',
            ].sort();
            suiteSetup(function () {
                return __awaiter(this, void 0, void 0, function* () {
                    // Building takes a few minutes.
                    this.timeout(10 * 60 * 1000);
                    yield run_command_1.runCommand(binPath, ['lint'], { cwd: dir });
                    yield run_command_1.runCommand(binPath, ['build'], { cwd: dir });
                    const config = polymer_project_config_1.ProjectConfig.loadConfigFromFile(path.join(dir, 'polymer.json'));
                    if (config == null) {
                        throw new Error('Failed to load shop\'s polymer.json');
                    }
                    chai_1.assert.deepEqual(config.builds.map((b) => b.name || 'default').sort(), expectedBuildNames);
                });
            });
            for (const buildName of expectedBuildNames) {
                test(`works in the ${buildName} configuration`, () => __awaiter(this, void 0, void 0, function* () {
                    const baseUrl = yield serve(path.join(dir, 'build', buildName));
                    yield assertThatShopWorks(baseUrl);
                }));
            }
        });
    });
    suite('the lit-element branch', function () {
        let dir;
        suiteSetup(function () {
            return __awaiter(this, void 0, void 0, function* () {
                const debugDir = process.env['CLI_TEST_SHOP_LIT_DIR'];
                if (debugDir != null) {
                    dir = debugDir;
                }
                else {
                    // Cloning and installing can take a few minutes
                    this.timeout(4 * 60 * 1000);
                    const ShopGenerator = github_1.createGithubGenerator({
                        owner: 'Polymer',
                        repo: 'shop',
                        githubToken,
                        branch: 'lit-element',
                        installDependencies: {
                            bower: false,
                            npm: true,
                        },
                    });
                    dir = yield yeoman_test_1.run(ShopGenerator).toPromise();
                    yield run_command_1.runCommand(binPath, ['install'], { cwd: dir });
                }
            });
        });
        test('serving sources with polyserve and `never` compile', () => __awaiter(this, void 0, void 0, function* () {
            const baseUrl = yield serve(dir, {
                compile: 'never',
                moduleResolution: 'node',
            });
            yield assertThatShopWorks(baseUrl);
        }));
        const testName = 'serving sources with polyserve and `always` compile';
        test(testName, function () {
            return __awaiter(this, void 0, void 0, function* () {
                // Compiling is a little slow.
                this.timeout(30 * 1000);
                const baseUrl = yield serve(dir, {
                    compile: 'always',
                    moduleResolution: 'node',
                });
                yield assertThatShopWorks(baseUrl);
            });
        });
        test('serving sources with polyserve and `auto` compile', () => __awaiter(this, void 0, void 0, function* () {
            const baseUrl = yield serve(dir, {
                compile: 'auto',
                moduleResolution: 'node',
            });
            yield assertThatShopWorks(baseUrl);
        }));
        suite('when built with polymer build', () => {
            const expectedBuildNames = [
                'es5-bundled',
                'es6-bundled',
                'esm-bundled',
            ].sort();
            suiteSetup(function () {
                return __awaiter(this, void 0, void 0, function* () {
                    // Building takes a few minutes.
                    this.timeout(10 * 60 * 1000);
                    yield Promise.all([
                        // Does not lint clean at the moment.
                        // TODO: https://github.com/Polymer/tools/issues/274
                        // runCommand(binPath, ['lint', '--rules=polymer-3'], {cwd: dir}),
                        run_command_1.runCommand(binPath, ['build'], { cwd: dir }),
                    ]);
                    const config = polymer_project_config_1.ProjectConfig.loadConfigFromFile(path.join(dir, 'polymer.json'));
                    if (config == null) {
                        throw new Error('Failed to load shop\'s polymer.json');
                    }
                    chai_1.assert.deepEqual(config.builds.map((b) => b.name || 'default').sort(), expectedBuildNames);
                });
            });
            for (const buildName of expectedBuildNames) {
                test(`works in the ${buildName} configuration`, () => __awaiter(this, void 0, void 0, function* () {
                    const baseUrl = yield serve(path.join(dir, 'build', buildName));
                    yield assertThatShopWorks(baseUrl);
                }));
            }
        });
    });
});
//# sourceMappingURL=integration_test.js.map