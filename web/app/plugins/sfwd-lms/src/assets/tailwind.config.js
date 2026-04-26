module.exports = {
	relative: false,
	content: ['../../src/admin_views/**/*.php'],
	prefix: 'ld-',
	theme: {
		extend: {},
	},
	plugins: [],
	corePlugins: {
		preflight: false,
	},
	safelist: [
		{
			pattern: /col-span-([1-9]|1[0-2])/, // For dashboard sections.
			variants: ['lg', 'md'],
		},
	],
};
