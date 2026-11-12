export const VueComponentEditorConfig = {
  editorComponent: 'VueComponentEditor',
  editorBinding: 'VueComponentEditor',
  rendererComponent: 'VueComponentRenderTemplate',
  rendererBinding: 'VueComponentRenderTemplate',
  control: {
    popoverContent: 'Mount a Vue component with handler and template strings',
    order: 2.0,
    group: 'package-skeleton',
    label: 'Vue component Editor',
    component: 'VueComponentRenderTemplate',
    'editor-component': 'VueComponentEditor',
    'editor-control': 'VueComponentRenderTemplate',
    config: {
      icon: 'fas fa-pencil-ruler',
      interactive: true,
      contentVue: '<p>Vue component editor</p>',
      handlerVue: 'return {}',
      components: [],
      renderVarHtml: null,
      ignoreMustache: true,
    },
    inspector: [
      {
        type: 'FormInput',
        field: 'componentName',
        config: {
          label: 'Component Name',
          helper: 'Name of the component',
        },
      },
      {
        type: 'FormTextArea',
        field: 'contentVue',
        config: {
          rows: 8,
          label: 'Content Vue',
          helper: 'The template vue component to display',
        },
      },
      {
        type: 'FormTextArea',
        field: 'handlerVue',
        config: {
          rows: 8,
          label: 'Handler Vue',
          helper: 'The code vue component to display (function body)',
        },
      },
    ],
  },
};
