<template>
  <div>
    <h1 class="mb-5">Import EZproxy File</h1>

    <div class="alert alert-info">
      Parsing an EZproxy config file and identifying each stanza with 100% accuracy is a difficult task.
      Costanza will do it's best but you should review each directive and stanza after the import process is complete
      to ensure it is correct.
    </div>

    <FileImport></FileImport>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import FileImport from '../components/FileImport';

export default {
  middleware: 'auth',

  layout: 'default',

  components: {
    FileImport
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
