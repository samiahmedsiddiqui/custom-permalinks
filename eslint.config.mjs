import wordpress from '@wordpress/eslint-plugin';
import globals from 'globals';

export default [
	{
		ignores: ['vendor/**', '**/*.min.js'],
	},
	...wordpress.configs.recommended,
	{
		languageOptions: {
			globals: {
				...globals.browser,
				wpApiSettings: 'readonly',
			},
		},
	},
];
