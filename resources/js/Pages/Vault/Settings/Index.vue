<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-28">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <users :layout-data="layoutData" :data="data" />

        <default-template :data="data" />

        <labels :data="data" />

        <contact-important-date-types :data="data" />

        <delete :data="data" />
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout.vue';
import Delete from '@/Pages/Vault/Settings/Partials/Delete.vue';
import DefaultTemplate from '@/Pages/Vault/Settings/Partials/DefaultTemplate.vue';
import Labels from '@/Pages/Vault/Settings/Partials/Labels.vue';
import Users from '@/Pages/Vault/Settings/Partials/Users.vue';
import ContactImportantDateTypes from '@/Pages/Vault/Settings/Partials/ContactImportantDateTypes.vue';

export default {
  components: {
    Layout,
    Delete,
    DefaultTemplate,
    Labels,
    Users,
    ContactImportantDateTypes,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
  },
};
</script>

<style lang="scss" scoped>
.section-head {
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
}
</style>
