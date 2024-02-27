<template>
  <div class="jumbotron jumbotron-fluid">
    <div class="container text-center">
      <div v-if="noResults">
        <div class="icon-container">
          <div v-if="emptyIconType() === 'beach'">
            <i class="fas fa-umbrella-beach" />
          </div>
          <div v-if="emptyIconType() === 'noData'">
            <i class="fas fa-umbrella-beach" />
          </div>
        </div>
        <h3 class="display-6">
          {{ emptyText() }}
        </h3>
        <p class="lead">
          {{ emptyDescText() }}
        </p>
      </div>
      <div v-else-if="error">
        <div class="icon-container">
          <i class="fas fa-exclamation-triangle" />
        </div>
        <h3 class="display-6">
          {{ errorTitleText() }}
        </h3>
        <p class="lead">
          {{ errorDescText() }}
        </p>
      </div>
      <div v-else>
        <div class="icon-container">
          <div v-if="iconType() === 'gear'">
            <i class="fas fa-cog" />
          </div>
        </div>
        <h3 class="display-6">
          {{ loadingText() }}
        </h3>
        <p class="lead">
          {{ descText() }}
        </p>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    loading: {
      type: Boolean,
    },
    desc: {
      type: String,
      default: '',
    },
    icon: {
      type: String,
      default: '',
    },
    empty: {
      type: String,
      default: '',
    },
    emptyDesc: {
      type: String,
      default: '',
    },
    emptyIcon: {
      type: String,
      default: '',
    },
    for: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      noResults: false,
      dataLoading: true,
      error: false,
    };
  },
  watch: {
    dataLoading() {
      ProcessMaker.EventBus.$emit('api-data-loading', this.dataLoading);
    },
    noResults() {
      ProcessMaker.EventBus.$emit('api-data-no-results', this.noResults);
    },
  },

  mounted() {
    ProcessMaker.EventBus.$on('api-client-loading', (request) => {
      if (this.for?.test(request.url)) {
        this.dataLoading = true;
        this.error = false;
        this.noResults = false;
      }
    });
    ProcessMaker.EventBus.$on('api-client-done', (response) => {
      if (response.config?.url?.test(this.for)) {
        if (response?.data?.data?.length === 0) {
          this.noResults = true;
        }
        this.dataLoading = false;
      }
    });
    ProcessMaker.EventBus.$on('api-client-error', (error) => {
      ProcessMaker.alert(error, 'danger');
      this.noResults = false;
      this.error = true;
    });
  },
  methods: {
    loadingText() {
      return this.loading ? this.loading : this.$t('Loading');
    },
    descText() {
      return this.desc ? this.desc : this.$t('Please wait while your content is loaded');
    },
    emptyText() {
      return this.empty ? this.empty : this.$t('No Results');
    },
    emptyDescText() {
      return this.emptyDesc ? this.emptyDesc : '';
    },
    iconType() {
      return this.icon ? this.icon : 'gear';
    },
    emptyIconType() {
      return this.emptyIcon ? this.emptyIcon : 'none';
    },
    errorTitleText() {
      return this.$t('Sorry! API failed to load');
    },
    errorDescText() {
      return this.$t('Something went wrong. Try refreshing the application');
    },
  },
};
</script>

<style scoped>
.jumbotron {
  background-color: transparent;
}
.icon-container {
  display:inline-block;
  width: 5em;
  margin-bottom: 1em;

  i {
    color: #b7bfc5;
    font-size: 5em;
  }

  svg {
    fill: #b7bfc5;
  }
}
</style>
