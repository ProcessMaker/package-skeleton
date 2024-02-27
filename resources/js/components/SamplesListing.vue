<template>
  <div class="data-table">
    <b-table
      class="w-100"
      striped
      hover
      :items="processes"
      :fields="fields"
    >
      <template #cell(actions)="{ item }">
        <b-button
          variant="link"
          size="sm"
          @click="onAction('edit-item', item)"
        >
          <i class="fas fa-edit" />
          Edit
        </b-button>
        <b-button
          variant="link"
          size="sm"
          @click="onAction('remove-item', item)"
        >
          <i class="fas fa-trash" />
          Delete
        </b-button>
      </template>
    </b-table>
  </div>
</template>

<script>
export default {
  props: {
    /**
     * Filter of the table
     */
    filter: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      processes: [],
      orderBy: 'name',
      orderDirection: 'asc',
      page: 1,
      perPage: 10,
      fields: [
        {
          label: 'Name',
          key: 'name',
          sortField: 'name',
        },
        {
          label: 'Status',
          key: 'status',
          sortField: 'status',
        },
        {
          label: 'Created at',
          key: 'created_at',
          sortField: 'created_at',
        },
        {
          label: 'Actions',
          key: 'actions',
        },
      ],
    };
  },
  mounted() {
    this.fetch();
  },
  methods: {
    transform(data) {
      return data.data;
    },
    formatStatus(status) {
      const stat = status.toLowerCase();
      const bubbleColor = {
        active: 'text-success',
        inactive: 'text-danger',
        draft: 'text-warning',
        archived: 'text-info',
      };
      return `<i class="fas fa-circle ${bubbleColor[stat]} small"></i> ${stat
        .charAt(0)
        .toUpperCase()}${stat.slice(1)}`;
    },
    async onAction(action, data) {
      switch (action) {
        case 'edit-item':
          this.$emit('edit', data);
          break;
        case 'remove-item': {
          const confirmed = await this.$bvModal.msgBoxConfirm(
            `Are you sure to inactive the sample '${data.name}'?`,
            {
              title: 'Caution!',
              size: 'sm',
              buttonSize: 'sm',
              okVariant: 'secondary',
              okTitle: 'Yes',
              cancelVariant: 'outlined-secondary',
              cancelTitle: 'No',
              footerClass: 'p-2',
              hideHeaderClose: false,
              centered: true,
            },
          );
          if (confirmed) {
            ProcessMaker.apiClient
              .delete(`admin/package-skeleton/${data.id}`)
              .then(() => {
                ProcessMaker.alert(
                  `Sample ${data.name} has been deleted`,
                  'warning',
                );
                this.$emit('reload');
              });
          }
          break;
        }
        default:
          break;
      }
    },
    fetch() {
      this.loading = true;
      // change method sort by sample
      this.orderBy = this.orderBy === 'name' ? 'name' : this.orderBy;
      // Load from our api client
      ProcessMaker.apiClient
        .get(
          `admin/package-skeleton/fetch?page=${this.page}&per_page=${this.perPage}&filter=${this.filter}&order_by=${this.orderBy}&order_direction=${this.orderDirection}`,
        )
        .then((response) => {
          this.processes = this.transform(response.data);
          this.loading = false;
        });
    },
  },
};
</script>
