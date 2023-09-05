<template>
  <div class="row mb-2 pl-2 pr-2">
    <div class="col-md-6 col-sm-12 d-flex">
      <div
        v-if="tablePagination && tablePagination.last_page > 1"
        class="pagination"
      >
        {{ tablePagination.from + 1 }} - {{ tablePagination.to }} of {{ tablePagination.total }}
        {{ title }}
      </div>
      <div
        v-if="tablePagination && tablePagination.last_page < 1"
        class="pagination"
      >
        {{ tablePagination.total }} {{ title }}
      </div>
    </div>
    <div class="col-md-6 col-sm-12 d-flex justify-content-end button-pagination">
      <div
        v-show="tablePagination && tablePagination.last_page > 1"
        :class="css.wrapperClass"
      >
        <div
          :class="['pagination-nav-item', css.linkClass, isOnFirstPage ? css.disabledClass : '']"
          @click="loadPage(1)"
        >
          <i class="fas fa-angle-double-left" />
        </div>
        <div
          :class="['pagination-nav-item', css.linkClass, isOnFirstPage ? css.disabledClass : '']"
          @click="loadPage('prev')"
        >
          <i class="fas fa-angle-left" />
        </div>
        <template v-if="notEnoughPages">
          <template v-for="n in totalPage">
            <div
              :class="[
                'pagination-nav-item',
                css.pageClass,
                isCurrentPage(n) ? css.activeClass : '',
              ]"
              @click="loadPage(n)"
              v-html="n"
            />
          </template>
        </template>
        <template v-else>
          <template v-for="n in windowSize">
            <div
              :class="[
                'pagination-nav-item',
                css.pageClass,
                isCurrentPage(windowStart + n - 1) ? css.activeClass : '',
              ]"
              @click="loadPage(windowStart + n - 1)"
              v-html="windowStart + n - 1"
            />
          </template>
        </template>
        <div
          :class="['pagination-nav-item', css.linkClass, isOnLastPage ? css.disabledClass : '']"
          @click="loadPage('next')"
        >
          <i class="fas fa-angle-right" />
        </div>
        <div
          :class="['pagination-nav-item', css.linkClass, isOnLastPage ? css.disabledClass : '']"
          @click="loadPage(totalPage)"
        >
          <i class="fas fa-angle-double-right" />
        </div>
        <select
          v-if="perPageSelectEnabled"
          v-model="perPage"
          class="pagination-nav-item pagination-nav-drop"
        >
          <option value="10">
            10
          </option>
          <option value="25">
            25
          </option>
          <option value="50">
            50
          </option>
        </select>
      </div>
    </div>
  </div>
</template>

<script>
import PaginationMixin from 'vuetable-2/src/components/VuetablePaginationMixin.vue';

export default {
  mixins: [PaginationMixin],
  props: ['perPageSelectEnabled', 'single', 'plural'],
  data() {
    return {
      perPage: 10,
    };
  },
  computed: {
    title() {
      if (this.tablePagination.total === 1) {
        return this.single;
      }
      return this.plural;
    },
  },
  watch: {
    perPage(value) {
      this.$emit('changePerPage', value);
    },
  },
};
</script>
