import { buildScreenBuilderConfig } from './vite.config.template';

export default buildScreenBuilderConfig({
  input: ['resources/js/designer/screen-builder-display-components.js'],
  manifestFileName: 'manifest-screen-display.json',
});
