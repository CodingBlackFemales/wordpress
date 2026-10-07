import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for the `playwright` test suite.
 *
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig( {
	testDir: './tests/playwright',
	globalSetup: './tests/playwright/global-setup',
	outputDir: './tests/_output/playwright',
	preserveOutput: 'failures-only',
	snapshotPathTemplate:
		'{testDir}/{testFileDir}/__screenshots__/{testName}-{arg}{ext}',
	updateSnapshots: process.env.CI ? 'none' : 'missing',
	/* The tests share a single site, so they cannot run against it in parallel. */
	fullyParallel: false,
	forbidOnly: !! process.env.CI,
	retries: 0,
	workers: 1,
	reporter: process.env.CI ? [ [ 'github' ], [ 'list' ] ] : 'list',
	use: {
		baseURL: process.env.WP_URL ?? 'http://wordpress.test',
		/* Kept on failure only: this is what the CI job uploads when the suite goes red. */
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'infra',
			testDir: './tests/playwright/infra',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
