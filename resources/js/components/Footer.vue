<template>
  <b-modal id="help-modal" title="Help" size="lg" ok-only ok-title="Close">
    <b-tabs content-class="mt-3">
      <b-tab title="General" active>
        <div class="m-3">
          <div>Costanza treats all EZproxy entries as one of five types:</div>
          <div class="row my-3">
            <div class="col-3 col-lg-2"><button class="btn btn-sm btn-secondary btn-block">COMMENT</button></div>
            <div class="col-9 col-lg-10">These are comments; notes, descriptions or explanations that do not affect how EZproxy functions.</div>
          </div>
          <div class="row my-3">
            <div class="col-3 col-lg-2"><button class="btn btn-sm btn-warning btn-block">DIRECTIVE</button></div>
            <div class="col-9 col-lg-10">Directives are any <a href="https://help.oclc.org/Library_Management/EZproxy/Configure_resources" target="oclc">EZproxy config.txt directives</a> that are not part of a stanza. These are typically directives that control how EZproxy works</div>
          </div>
          <div class="row my-3">
            <div class="col-3 col-lg-2"><button class="btn btn-sm btn-success btn-block">GROUP</button></div>
            <div class="col-9 col-lg-10">Groups are used to organize multiple resources together in a block, typically for the purpose of restricting access to the entire set to a specific type of patron.</div>
          </div>
          <div class="row my-3">
            <div class="col-3 col-lg-2"><button class="btn btn-sm btn-primary btn-block">STANZA</button></div>
            <div class="col-9 col-lg-10">Stanzas are known databases or resources. There is no need to manage the individual directives for a stanza as they are maintained by OCLC or the EZproxy community.</div>
          </div>
          <div class="row my-3">
            <div class="col-3 col-lg-2"><button class="btn btn-sm btn-info btn-block">CUSTOM</button></div>
            <div class="col-9 col-lg-10">Custom Stanzas are stanzas that you yourself create for databases or resources not on the OCLC database list, and not contributed by others in the EZproxy community.</div>
          </div>
        </div>
      </b-tab>

      <b-tab title="Editing">
        <div class="m-3">
          <p>Click on an entry to expand it. You can then view or edit its details.</p>
          <p>Drag entries up or down using the <fa icon="grip-lines" fixed-width /> handle to change their order</p>
          <p>The <fa icon="ellipsis-v" fixed-width /> button beside each entry will allow you to:</p>
          <ul>
            <li><fa icon="plus" fixed-width class="text-primary" /> add a new entry to the EZproxy config below the current one.</li>
            <li><fa icon="edit" fixed-width class="text-success" /> add a new entry to the EZproxy config below the current one.</li>
            <li><fa icon="trash-alt" fixed-width class="text-danger" /> delete that entry from the EZproxy config.</li>
          </ul>
          <h5>Labels</h5>
          <div class="my-3">
            <div class="row my-3">
              <div class="col-3 col-lg-2"><button class="btn btn-sm btn-primary btn-block">STANZA</button></div>
              <div class="col-9 col-lg-10">Entries shown with a solid label are <b>active</b>.</div>
            </div>
            <div class="row my-3">
              <div class="col-3 col-lg-2"><button class="btn btn-sm btn-outline-primary btn-block">STANZA</button></div>
              <div class="col-9 col-lg-10">Entries shown with an outlined label are <b>inactive</b>; they will remain in your EZproxy config but will be treated as a comment, and will not affect how EZproxy functions.</div>
            </div>
          </div>
        </div>
      </b-tab>

      <b-tab title="About">
        <div class="m-3">
          <p>
            {{ version }}<br />
            <a href="https://github.com/usask-library/costanza" target="github">https://github.com/usask-library/costanza</a>
          </p>
          <p>
            Developed by the University of Saskatchewan Library.
          </p>
        </div>
      </b-tab>
    </b-tabs>
  </b-modal>
</template>

<script>
import axios from 'axios'

export default {
  name: 'Footer',

  data () {
    return {
      version: 'Costanza'
    }
  },

  mounted () {
    this.getVersion()
  },

  methods: {
    getVersion () {
      axios.get('/api/version')
        .then(response => {
          this.version = response.data.message
        })
        .catch(error => {
          // Nothing to do I guess
        })
    }
  }
}


</script>

<style scoped>
</style>
