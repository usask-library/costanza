<template>
  <div>
    <h1 class="mb-5">New EZproxy File</h1>

    <p>
      Create a new file for use with Costanza. The default EZproxy config file can be used as a starting
      point if desired.
    </p>
    <form @submit.prevent="createNewFile" novalidate>
      <div class="form-group row">
        <label for="filename" class="col-2 col-form-label"><strong>Filename</strong></label>
        <div class="col-8">
          <input id="filename" v-model="filename" class="form-control" :class="{ 'is-invalid': errors['filename'] }" placeholder="Enter filename" required />
          <small id="filenameHelpText" class="form-text text-muted">
            Costanza stores data in JSON format, so please use the <i>.json</i> file extension.
          </small>
          <small v-if="errors['filename']" id="filenameHelpTextErrors" class="form-text text-danger">
            {{ errors['filename'].join('; ') }}
          </small>
        </div>
        <div class="col-2">
          <button type="button" class="btn btn-primary" @click.prevent="createNewFile">Create File</button>
        </div>
      </div>

      <div class="form-group row">
        <div class="col-10 offset-2">
          <div class="form-check">
            <input id="default" v-model="useDefault" type="checkbox" class="form-check-input" />
            <label for="default" class="form-check-label text-muted">
              Use default EZproxy config.
            </label>
          </div>
        </div>
      </div>
    </form>
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

      errors: [],

      filename: '',
      useDefault: false
    }
  },

  computed: mapGetters({
    authenticated: 'auth/check'
  }),

  methods: {
    createNewFile () {
      if (this.authenticated) {
        axios.post('/api/files/new', {
          filename: this.filename,
          default: this.useDefault
        })
          .then(response => {
            router.push({ path: '/edit/' + this.filename })
          })
          .catch(error => {
            this.errors = error.response.data.errors;
            console.log(this.errors)
          })
      }
    }
  }
}
</script>

<style scoped>
</style>
