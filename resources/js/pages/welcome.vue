<template>
  <div class="jumbotron">
    <h1 class="display-4">{{ title }}</h1>
    <p class="lead">Costanza is an EZproxy configuration management system. It focuses on the management of stanzas within the EZproxy config file rather than individual EZproxy directives.</p>
    <hr class="my-4">
    <h4>Get started:</h4><br/>

    <template v-if="authenticated">
      <b-card-group deck>
        <b-card class="border-primary mx-2" no-body>
          <b-card-body>
            <b-card-title class="text-primary">Start fresh.</b-card-title>
            <p class="card-text">Create a new config file using the default EZproxy template as a starting point.</p>
          </b-card-body>
          <b-card-footer>
            <router-link tag="a" class="btn btn-primary" to="new">New</router-link>
          </b-card-footer>
        </b-card>

        <b-card class="border-success mx-2" no-body>
          <b-card-body>
            <b-card-title class="text-success">Continue working.</b-card-title>
            <p class="card-text">A previous EZproxy configuration was found on the server. You can continue working on that!.</p>
          </b-card-body>
          <b-card-footer>
            <router-link tag="a" class="btn btn-success" to="open">Resume</router-link>
          </b-card-footer>
        </b-card>

        <!--
        <b-card class="border-info mx-2" no-body>
          <b-card-body>
            <b-card-title class="text-info">Upload a Costanza file.</b-card-title>
            <p class="card-text">Upload a <code>config.json</code> file that you previously created with Costanza.</p>
          </b-card-body>
          <b-card-footer><router-link tag="a" class="btn btn-info" to="upload">Upload</router-link></b-card-footer>
        </b-card>
        -->

        <b-card class="border-warning mx-2" no-body>
          <b-card-body>
            <b-card-title class="text-warning">Import an EZproxy config.</b-card-title>
            <p class="card-text">Import your existing EZproxy <code>config.txt</code> file. It will automatically be converted to the format required by Costanza.</p>
          </b-card-body>
          <b-card-footer>
            <router-link tag="a" class="btn btn-warning" to="import">Import</router-link>
          </b-card-footer>
        </b-card>

        <b-card class="border-info mx-2" no-body>
          <b-card-body>
            <b-card-title class="text-info">Export to EZproxy.</b-card-title>
            <p class="card-text">Export your Costanza data to the text format required by EZproxy.</p>
          </b-card-body>
          <b-card-footer><router-link tag="a" class="btn btn-info" to="export">Export</router-link></b-card-footer>
        </b-card>

      </b-card-group>
    </template>

    <template v-else>
      <b-card-group deck>
        <b-card class="border-primary mx-2" no-body>
          <b-card-body>
            <b-card-title class="text-primary">Login or create an account.</b-card-title>
            <p class="card-text">
              Costanza requires an account to keep track of your EZproxy configuration.
              If you have not yet registered, please do so! Once you create your account, you can start with a fresh
              EZproxy config, or upload your existing file and start editing right away. If you already have an account,
              login and get back to work!
            </p>
          </b-card-body>
          <b-card-footer>
            <router-link :to="{ name: 'login' }" active-class="active">
              {{ $t('login') }}
            </router-link>
            or
            <router-link :to="{ name: 'register' }" active-class="active">
              {{ $t('register') }}
            </router-link>
          </b-card-footer>
        </b-card>

        <b-card class="border-success mx-2" no-body>
          <b-card-body>
            <b-card-title class="text-success">Costanza features.</b-card-title>
            <ul>
              <li>Create and edit one or more EZproxy configuration files</li>
              <li>Share editing duties with other people at your institution</li>
              <li>Upload your existing EZproxy configuration to use as a starting point</li>
              <li>Easily add, remove or organize stanzas and directives</li>
              <li>Generate a config file for a hosted EZproxy instance</li>
            </ul>
          </b-card-body>
        </b-card>
      </b-card-group>
    </template>
  </div>
</template>

<script>
import axios from 'axios'
import router from '~/router'
import { mapGetters } from 'vuex'

export default {
  layout: 'default',

  metaInfo () {
    return { title: this.$t('home') }
  },

  data () {
    return {
      title: window.config.appName
    }
  },

  computed: mapGetters({
    authenticated: 'auth/check'
  }),

  methods: {
    newConfig () {
      if (this.authenticated) {
        axios.get('/api/files/new')
          .then(response => {
            router.push({ path: '/edit/config.json' })
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
.top-right {
  position: absolute;
  right: 10px;
  top: 18px;
}

.title {
  font-size: 85px;
}
</style>
