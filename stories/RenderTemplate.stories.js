/* eslint-disable import/no-extraneous-dependencies */
import { expect, within } from '@storybook/test';
import RenderTemplate from '../resources/js/components/screen-builder/freeEditor/RenderTemplate';

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
 */
export const CatalogComponentsFromScreenConfig = {
  args: {
    contentVue: '<PtoRequest v-model="data" :submit="submit" />',
    handlerVue: 'return {};',
    components: [
      { name: 'PtoRequest', key: '@/projects/pto/request/PtoRequest.vue' },
    ],
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    await expect(await canvas.findByRole('heading', { name: 'Request PTO' })).toBeTruthy();
    await expect(canvas.getByRole('button', { name: 'Submit request' })).toBeTruthy();
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
