export default {
  name: 'VueComponentRenderTemplate',
  inheritAttrs: false,
  props: [
    'error',
    'name',
    'value',
    'helper',
    'controlClass',
    'contentVue',
    'handlerVue',
    'validationData',
    'label',
    'renderVarHtml',
    'components',
  ],
  methods: {
    /**
     * Screen Builder binds v-model on this renderer when `config.name` is set.
     * Forward nested data writes so `$v.vdata.<name>` stays in sync.
     */
    emitInput(nextValue) {
      this.$emit('input', nextValue);
      this.$emit('change', nextValue);
    },
    resolveTransientData() {
      return this.$attrs['transient-data']
        || this.$attrs.transientData
        || this.validationData
        || null;
    },
  },
  render(createElement) {
    // Dynamic mixin from screen-builder; string is authored in designer, not user input at runtime.
    // eslint-disable-next-line no-new-func -- required to evaluate handlerVue as a function body
    const newMixin = this.handlerVue ? new Function(this.handlerVue)() : {};
    const self = this;
    const component = createElement({
      mixins: [
        newMixin,
      ],
      components: this.components || {},
      template: this.contentVue || '',
      data() {
        return {
          // Same object ref as ProcessMaker screen data when available.
          // Children that v-model emit a replacement object must be merged back
          // (see watch.data) or they disconnect from transientData.
          data: self.resolveTransientData() || {},
        };
      },
      computed: {
        transientData: {
          get() {
            return self.resolveTransientData() || {};
          },
          set(value) {
            const target = self.resolveTransientData();
            if (target && typeof target === 'object') {
              Object.assign(target, value);
            }
          },
        },
      },
      watch: {
        data(value) {
          const target = self.resolveTransientData();
          if (!target || !value || typeof value !== 'object' || value === target) {
            return;
          }
          Object.assign(target, value);
          // Re-bind wrapper to the live screen object so later edits stay connected.
          this.data = target;
        },
      },
      methods: {
        submit() {
          self.$emit('submit');
        },
        getData() {
          return self.resolveTransientData() || {};
        },
        updateTransientData(value) {
          this.transientData = value;
        },
      },
    });

    const children = [component];

    if (this.error) {
      children.push(createElement(
        'div',
        {
          class: 'invalid-feedback d-block',
          attrs: { role: 'alert' },
        },
        this.error,
      ));
    }

    if (this.helper) {
      children.push(createElement(
        'small',
        { class: 'form-text text-muted' },
        this.helper,
      ));
    }

    return createElement(
      'div',
      {
        class: {
          'form-group': true,
          'form-group--error': Boolean(this.error),
          [this.controlClass]: Boolean(this.controlClass),
        },
      },
      children,
    );
  },
  mounted() {
    if (!this.name) {
      return;
    }
    const data = this.resolveTransientData();
    if (!data || typeof data !== 'object') {
      return;
    }
    this.sbDataUnwatch = this.$watch(
      () => data[this.name],
      (nextValue) => {
        if (nextValue !== this.value) {
          this.emitInput(nextValue === undefined ? null : nextValue);
        }
      },
      { immediate: true },
    );
  },
  beforeDestroy() {
    if (typeof this.sbDataUnwatch === 'function') {
      this.sbDataUnwatch();
    }
  },
};
