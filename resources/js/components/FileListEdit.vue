<template>
  <b-list-group>
    <b-list-group-item v-for="file in fileList" :key="file" :to="{name: 'edit', params: {filename: file}}" action>
      {{ file }}
    </b-list-group-item>
  </b-list-group>
</template>

<script>
import axios from 'axios';
import { mapGetters } from 'vuex'

export default {
  name: 'FileListEdit',

  data () {
    return {
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
