/* eslint-disable import/no-extraneous-dependencies */
import { expect, within } from '@storybook/test';
import RenderTemplate from '../resources/js/components/screen-builder/freeEditor/RenderTemplate';

/**
 * First SFC under resources/js/projects, as the catalog key Screen Builder stores
 * ("@/projects/..."). A fresh plugin has none yet, so the catalog story falls back to a
 * plain template instead of failing.
 */
const [firstProjectModule] = Object.keys(import.meta.glob('../resources/js/projects/**/*.vue'));
const projectComponentKey = firstProjectModule
  ? firstProjectModule.replace('../resources/js/', '@/')
  : null;
const projectComponentName = projectComponentKey
  ? projectComponentKey.split('/').pop().replace('.vue', '')
  : null;
const kebabCase = (name) => name.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();

function renderWithDeclaredComponents(args) {
  return {
    components: { RenderTemplate },
    data() {
      return { args, transientData: { startDate: '', endDate: '' } };
    },
    template: `<render-template
      v-bind="args"
      :transient-data="transientData"
    />`,
  };
}

export default {
  title: 'Components/ScreenBuilder/RenderTemplate',
  component: RenderTemplate,
  render: renderWithDeclaredComponents,
};

/**
 * Screen Builder Preview and request runtime mount this renderer directly and pass
 * `components` exactly as stored on the screen: a list of { name, key }.
 * Uses the first project component found; without one it checks a plain template.
 */
export const CatalogComponentsFromScreenConfig = {
  args: projectComponentKey
    ? {
      contentVue: `<${projectComponentName} v-model="data" :submit="submit" />`,
      handlerVue: 'return {};',
      components: [{ name: projectComponentName, key: projectComponentKey }],
    }
    : {
      contentVue: '<p>No project component yet</p>',
      handlerVue: 'return {};',
      components: [],
    },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    if (!projectComponentKey) {
      await expect(canvas.getByText('No project component yet')).toBeTruthy();
      return;
    }
    // An unresolved key would leave the literal custom element in the DOM.
    await expect(canvasElement.querySelector(kebabCase(projectComponentName))).toBeNull();
  },
};

export const InlineTemplateWithoutComponents = {
  args: {
    contentVue: '<p>Plain template</p>',
    handlerVue: 'return {};',
    components: [],
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    await expect(canvas.getByText('Plain template')).toBeTruthy();
  },
};

export const ResolvedComponentMap = {
  args: {
    contentVue: '<Greeting />',
    handlerVue: 'return {};',
    components: {
      Greeting: { template: '<p>Already resolved</p>' },
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    await expect(canvas.getByText('Already resolved')).toBeTruthy();
  },
};
