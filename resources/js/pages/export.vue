<template>
  <div>
    <h1 class="mb-5">Export File</h1>

    <p>
      Internally, Costanza stores data in JSON formatted files.  The export procedure will convert all selected
      Costanza files to the plain text format required by EZproxy.  Both the JSON and text formatted files
      will be bundled into a ZIP file for you do download, extract, and copy to your EZproxy server.
    </p>
    <p>
      Select all the files you would like to export.
    </p>

    <div v-if="success" class="alert alert-success">
      <p>The files you request for export have been sent to you via email.</p>
    </div>
    <div v-if="errors" class="alert alert-danger">
      <p>The following issues were reported during the export process:</p>
      <ul v-if="errors['files']">
        <li v-for="error in errors['files']">{{ error }}</li>
      </ul>
      <ul v-else>
        <li v-for="(item, filename) in errors">{{ filename }}
          <ul>
            <li v-for="error in errors[filename]">{{ error }}</li>
          </ul>
        </li>
      </ul>
    </div>

    <b-list-group class="mb-3">
      <b-form-checkbox-group id="exportFiles" v-model="export_files" name="exportFiles">
        <b-list-group-item v-for="file in fileList" :key="file">
          <b-form-checkbox :value="file">{{ file }}</b-form-checkbox>
        </b-list-group-item>
      </b-form-checkbox-group>
    </b-list-group>

    <div class="form-group">
      <div class="form-check">
        <input id="oclc_includes" v-model="oclc_includes" type="checkbox" class="form-check-input" />
        <label for="oclc_includes" class="form-check-label">
          Use OCLC's Hosted EZproxy Include File for resources that have one.<br>
          This will generate an EZproxy file that can be used by OCLC hosted EZproxy customers.
        </label>
      </div>
    </div>

    <button type="button" class="btn btn-primary" @click.prevent="exportFiles">Export selected files</button>

  </div>
</template>

<script>
import axios from 'axios'
import router from '../router';
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
      fileList: [],
      export_files: [],
      oclc_includes: false,
      errors: null,
      success: null
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
          })
      }
    },

    exportFiles () {
      if (this.authenticated) {
        this.errors = null;
        this.success = null;

        axios.post('/api/files/export', {
          files: this.export_files,
          oclc_includes: this.oclc_includes
        })
          .then(response => {
            this.success = response.data.message;
          })
          .catch((error) => {
            this.errors = error.response.data.errors;
          });
      }
    }

  }
}
</script>

<style scoped>
</style>
