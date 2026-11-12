import { buildScreenBuilderConfig } from './vite.config.template';

export default buildScreenBuilderConfig({
  input: ['resources/js/designer/screen-builder-form-components.js'],
  manifestFileName: 'manifest-screen-form.json',
});
