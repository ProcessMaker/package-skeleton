import fs from 'fs';
import path from 'path';

/**
 * Vite plugin to convert manifest.json to mix-manifest.json format.
 */
export default function convertToMixManifest(config) {
  return {
    name: 'convert-to-mix-manifest', // required, will show up in warnings and errors
    writeBundle(options, bundle) {
      console.log(options);
      // Specify the path to the generated manifest.json and desired mix-manifest.json
      const manifestPath = path.resolve(config.outDir, 'manifest.json');
      const mixManifestPath = path.resolve(config.outDir, 'mix-manifest.json');

      // Read the Vite manifest.json
      fs.readFile(manifestPath, (err, data) => {
        if (err) throw err;

        const manifest = JSON.parse(data);
        const mixManifest = {};

        // Convert each entry in the Vite manifest into Mix manifest format
        for (const [key, value] of Object.entries(manifest)) {
          // Assuming the value.src and value.file structure, adjust as necessary
          const newName = `/${value.file}`;
          mixManifest[newName] = newName;
        }

        // Write the new mix-manifest.json
        fs.writeFile(mixManifestPath, JSON.stringify(mixManifest, null, 2), (err) => {
          if (err) throw err;
          console.log('mix-manifest.json has been generated.');
        });
      });
    },
  };
}
