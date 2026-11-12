<template>
  <div class="form-group pointer-events-none">
    <div :class="classList">
      <VueComponentRenderTemplate
        v-if="registryReady"
        :key="componentRenderKey"
        :content-vue="contentVue"
        :handler-vue="handlerVue"
        :components="resolvedComponents"
        :validation-data="validationData || {}"
        :transient-data="validationData || {}"
      />
    </div>
    <div
      v-if="(validator && validator.errorCount) || error"
      class="invalid-feedback"
    >
      <div
        v-for="(error, index) in validator.errors.get(name)"
        :key="index"
      >
        {{ error }}
      </div>
      <div v-if="error">
        {{ error }}
      </div>
    </div>
    <small
      v-if="helper"
      class="form-text text-muted"
    >{{ helper }}</small>
  </div>
</template>
<script>
import VueComponentRenderTemplate from './RenderTemplate';
import {
  ensureScreenComponentRegistry,
  isScreenComponentRegistryReady,
  resolveDeclaredComponents,
  removeCustomComponentStyle,
} from '../../../componentsDesigner/customComponents';

export default {
  components: { VueComponentRenderTemplate },
  inheritAttrs: false,
  props: {
    error: { type: String, default: null },
    name: { type: String, default: null },
    helper: { type: String, default: null },
    controlClass: { type: String, default: null },
    contentVue: { type: String, default: null },
    handlerVue: { type: String, default: null },
    validationData: { type: Object, default: null },
    label: { type: String, default: null },
    renderVarHtml: { type: Boolean, default: null },
    components: { type: Array, default: null },
  },
  data() {
    return {
      registryReady: isScreenComponentRegistryReady(),
      ownedStyleKeys: [],
      originalEscapeFn: null,
      customFunctions: {},
      editorSettings: {
        inline: true,
        menubar: false,
        plugins: ['link', 'lists'],
        toolbar: `undo redo | link | styleselect | bold italic forecolor |
             alignleft aligncenter alignright alignjustify | bullist numlist outdent indent`,
        skin: false,
        relative_urls: false,
        convert_urls: false,
        remove_script_host: false,
      },
    };
  },
  computed: {
    usesWithOverride() {
      return (Array.isArray(this.components) ? this.components : [])
        .map(({ name, key, override }) => ({ name, key: (override && override.key) || key }));
    },
    componentRenderKey() {
      return this.usesWithOverride.map(({ name, key }) => `${name}@${key}`).join('|');
    },
    resolvedComponents() {
      if (!this.registryReady) return {};
      return resolveDeclaredComponents(this.usesWithOverride);
    },
    classList() {
      const classList = {
        'is-invalid':
                    (this.validator && this.validator.errorCount) || this.error,
      };
      if (this.controlClass) {
        classList[this.controlClass] = true;
      }
      return classList;
    },
    rendered() {
      // If we have't validationData, we can't evaluate the mustache variables
      // Used by ScreenBuilder in Design Mode
      return this.contentVue;
    },
    inPreviewMode() {
      return ((this.mode === 'preview' && !window.exampleScreens) || this.mode === 'editor');
    },
    mode() {
      return this.$root.$children[0].mode;
    },
  },
  watch: {
    componentRenderKey: {
      immediate: true,
      handler() {
        this.syncOwnedStyles();
      },
    },
    registryReady(ready) {
      if (ready) this.syncOwnedStyles();
    },
  },
  created() {
    if (this.registryReady) return;
    ensureScreenComponentRegistry().then(() => {
      this.registryReady = true;
    });
  },
  methods: {
    syncOwnedStyles() {
      if (!this.registryReady) return;
      const next = this.usesWithOverride.map((entry) => entry.key);
      const nextSet = new Set(next);
      this.ownedStyleKeys.forEach((key) => {
        if (!nextSet.has(key)) removeCustomComponentStyle(key);
      });
      this.ownedStyleKeys = next;
    },
  },
};
</script>

<style scoped>
.invalid-feedback {
    display: block;
}

.is-invalid {
    border: 1px solid #dc3545;
    border-radius: 0.25rem;
}
.pointer-events-none {
    pointer-events: none;
}
</style>
