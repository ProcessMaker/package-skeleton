/* eslint-disable import/no-extraneous-dependencies */
import {
  within, userEvent, expect, waitFor,
} from '@storybook/test';
import './bootstrap';
// b-tabs from bootstrap-vue
import SamplesListing from '@/components/SamplesListing.vue';

export default {
  title: 'Components/SamplesListing',
  component: SamplesListing,
  tags: ['autodocs'],
  render: (args, { argTypes }) => ({
    props: Object.keys(argTypes),
    components: { SamplesListing },
    template: `<samples-listing ref="component" v-bind="$props" />
      </sample-listing>`,
    data() {
      return {};
    },
    methods: {
    },
  }),
  // Mock XHR requests
  parameters: {
    mockData: [
      {
        url: '/api/1.0/admin/package-skeleton/fetch?page=1&per_page=10&filter=&order_by=name&order_direction=asc',
        method: 'GET',
        status: 200,
        response: {
          data: [
            {
              name: 'Sample 1',
              status: 'ACTIVE',
              created_at: '2024-02-27T00:07:32+00:00',
            },
          ],
        },
      },
    ],
  },
};

/**
 * Stories of the component
 */
// Preview the component
export const Preview = {
  args: {
    filter: '',
  },
};
