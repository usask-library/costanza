<template>
  <div>
    <h1 class="mb-5">Open File</h1>

    <p>Select one of the following files</p>

    <b-list-group>
      <b-list-group-item v-for="file in fileList" :key="file" :to="{name: 'edit', params: {filename: file}}" action>
        {{ file }}
      </b-list-group-item>
    </b-list-group>
  </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'

export default {
  middleware: 'auth',

  layout: 'default',

  metaInfo () {
    return { title: this.$t('home') }
  },

  data () {
    return {
      title: window.config.appName,
      fileList: []
    }
  },

  computed: mapGetters({
    authenticated: 'auth/check'
  }),

  mounted () {
    this.getFileList()
  },

  methods: {
    getFileList () {
      if (this.authenticated) {
        axios.get('/api/files')
          .then(response => {
            this.fileList = response.data.data
          })
          .catch(error => {
            console.log(error)
          })
      }
    }
  }

}
</script>

<style scoped>
</style>
