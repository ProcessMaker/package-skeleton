import fs from 'fs';
import path from 'path';
import md5 from 'crypto-js/md5';

/**
 * Vite plugin to convert manifest.json to mix-manifest.json format.
 */
export default function convertToMixManifest(config) {
  return {
    name: 'convert-to-mix-manifest', // required, will show up in warnings and errors
    writeBundle() {
      // Specify the path to the generated manifest.json and desired mix-manifest.json
      const manifestPath = path.resolve(config.outDir, 'manifest.json');
      const mixManifestPath = path.resolve(config.outDir, 'mix-manifest.json');

      // Read the Vite manifest.json
      fs.readFile(manifestPath, (err, data) => {
        if (err) throw err;

        const manifest = JSON.parse(data);
        const mixManifest = {};

        // Convert each entry in the Vite manifest into Mix manifest format
        Object.entries(manifest).forEach(([, value]) => {
          // read file content
          const file = fs.readFileSync(path.resolve(config.outDir, value.file));
          const hash = md5(file.toString()).toString();
          // Assuming the value.src and value.file structure, adjust as necessary
          mixManifest[`/${value.file}`] = `/${value.file}?id=${hash}`;
        });

        // Write the new mix-manifest.json
        fs.writeFile(mixManifestPath, JSON.stringify(mixManifest, null, 2), (err) => {
          if (err) throw err;
          // eslint-disable-next-line no-console
          console.log('mix-manifest.json has been generated.');
        });
      });
    },
  };
}
