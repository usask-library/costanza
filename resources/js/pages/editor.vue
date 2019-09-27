<template>
  <div>
    <h1 class="mb-5">EZproxy Editor <span class="badge badge-pill badge-info">{{ filename }}</span></h1>

    <p>
      Click any entry to expand it and see its details.
      Entries can be ordered up or down by dragging the <fa icon="grip-lines" fixed-width /> icon.
    </p>

    <EntryList :filename="filename"></EntryList>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  middleware: 'auth',

  layout: 'default',

  components: {
    EntryList: () => import('~/components/EntryListComplete')
  },

  props: {
    filename: String
  },

  metaInfo () {
    return { title: this.$t('home') }
  },

  data: () => ({
    title: window.config.appName
  }),

  computed: mapGetters({
    authenticated: 'auth/check'
  })
}
</script>

<style scoped>
</style>
