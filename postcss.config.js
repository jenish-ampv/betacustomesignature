export const plugins = {
	'postcss-color-functional-notation': {}, // converts oklch() → rgb()
	'postcss-preset-env': {},
	'postcss-import': {},
	'tailwindcss/nesting': 'postcss-nesting',
	'postcss-preset-env': {
		features: { 'nesting-rules': false },
	},
	tailwindcss: {},
	autoprefixer: {},
};
