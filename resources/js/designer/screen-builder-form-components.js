/* global Vue */
import VueComponentRenderTemplate from '../components/screen-builder/freeEditor/RenderTemplate';
import VueComponentEditor from '../components/screen-builder/freeEditor/VueComponentEditor.vue';
import { VueComponentEditorConfig } from './componentsConfig';
import mergeVueComponentInspectorPanels from './vueComponentInspectorPanels';
import { ensureScreenComponentRegistry } from '../componentsDesigner/customComponents';

Vue.component('VueComponentRenderTemplate', VueComponentRenderTemplate);
Vue.component('VueComponentEditor', VueComponentEditor);

window.ProcessMaker.EventBus.$on('screen-builder-init', (manager) => {
  mergeVueComponentInspectorPanels(manager);

  manager.addControl(
    VueComponentEditorConfig.control,
    VueComponentEditorConfig.rendererComponent,
    VueComponentEditorConfig.rendererBinding,
    VueComponentEditorConfig.editorComponent,
    VueComponentEditorConfig.editorBinding,
  );
});

ensureScreenComponentRegistry();
