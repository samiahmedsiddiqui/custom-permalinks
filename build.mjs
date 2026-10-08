import { build } from 'esbuild';
import { readdirSync, rmSync } from 'node:fs';
import pkg from './package.json' with { type: 'json' };

for (const type of ['css', 'js']) {
	const dir = `assets/${type}`;

	// Remove min files from previous versions.
	readdirSync(dir)
		.filter((file) => file.endsWith(`.min.${type}`))
		.forEach((file) => rmSync(`${dir}/${file}`));

	await build({
		entryPoints: [`${dir}/src/*.${type}`],
		outdir: dir,
		entryNames: `[name]-${pkg.version}.min`,
		minify: true,
		logLevel: 'info',
	});
}
