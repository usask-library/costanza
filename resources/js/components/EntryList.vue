<template>
  <div id="entry-list" class="card-sortable">
    <template v-if="loading">
      <div>
        <b-spinner label="Loading..." variant="secondary" type="grow"></b-spinner>
      </div>
    </template>
    <template v-else>
      <entry-item v-for="entry in entries" :key="entry.id" :entry="entry" :stanzas="stanzas" />
    </template>
  </div>
</template>

<script>
import axios from 'axios'
import Entry from '~/components/Entry'
import { getStanzaList } from '../plugins/stanzas'

export default {
  components: {
    'entry-item': Entry
  },
  data () {
    return {
      loading: false,
      entries: [],
      stanzas: [],
    }
  },
  created () {
    this.getDataFromApi()
  },
  mounted () {
    this.getStanzaList()
  },
  methods: {
    getStanzaList () {
      getStanzaList().then((stanzas) => {
        this.stanzas = stanzas
      })
    },

    getDataFromApi () {
      this.loading = true
      axios.get('/api/files/load')
        .then(response => {
          this.loading = false
          this.entries = response.data
        })
        .catch(error => {
          this.loading = false
        })
    }
  }
}
</script>
