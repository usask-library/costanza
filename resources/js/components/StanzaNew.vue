<template>
  <form>
    <input type="hidden" name="insert_after" value="insert_after_id_goes_here">
    <div class="form-group row">
      <label for="stanza_id" class="col-md-2 col-form-label">Stanza</label>
      <div class="col-md-10">
        <VSelect
          id="stanza_id"
          v-model="stanza_id"
          :options="selectOptions"
          :searchable="selectSearchable"
          :textProp="selectName"
          :valueProp="selectValue"
        />
      </div>
    </div>

    <div class="form-group row">
      <label for="stanza_name" class="col-md-2 col-form-label">Name</label>
      <div class="col-md-10">
        <input id="stanza_name" v-model="stanza_name" class="form-control" />
        <p class="form-text text-muted">You can override the default name of the stanza using this field.</p>
      </div>
    </div>
    <div class="form-group row">
      <label for="stanza_comment" class="col-md-2 col-form-label">Comment</label>
      <div class="col-md-10">
        <textarea id="stanza_comment" v-model="stanza_comment" class="form-control text-monospace col-12" rows="5"></textarea>
        <p class="form-text text-muted">A comment or note specific to this directive.</p>
      </div>
    </div>
    <div class="form-group row">
      <div class="col-md-2">Inactive</div>
      <div class="col-md-10">
        <div class="form-check">
          <input id="stanza_inactive" type="checkbox" v-model="stanza_inactive" class="form-check-input" />
          <label for="stanza_inactive" class="form-check-label text-muted">
            This will leave the stanza in your EZproxy config, but mark it inactive.<br/>
            It is the equivalent of placing a # character at the beginning of the each line to turn it into a comment.
          </label>
        </div>
      </div>
    </div>
    <button type="button" class="btn btn-primary float-right" data-type="stanza" @click="addStanza()">Add Database Stanza</button>
  </form>
</template>

<script>
import axios from 'axios'
import Swal from 'sweetalert2'
import { EventBus } from '~/plugins/EventBus.js'
import VSelect from '@alfsnd/vue-bootstrap-select';

// Sweet status message popup (toast)
const Toast = Swal.mixin({
  toast: true,
  position: 'bottom-right',
  timer: 5000,
  showConfirmButton: false,
  showCancelButton: false
});

export default {
  name: 'StanzaNew.vue',

  components: {
    VSelect
  },

  props: {
    insertAfter: { type: String, required: true },
    filename: { type: String, required: true },
    stanzas: Object
  },

  data () {
    return {
      stanza_id: '',
      stanza_name: '',
      stanza_comment: '',
      stanza_inactive: false,
      selectSearchable: true,
      selectValue: 'code',
      selectName: 'name',
      selectOptions: Object.values(this.stanzas)
    }
  },

  methods: {
    addStanza () {
      axios.post('/api/files/' + this.filename + '/entries', {
        placeAfter: this.insertAfter,
        type: 'stanza',
        stanza_id: this.stanza_id.code,
        stanza_name: this.stanza_name,
        stanza_comment: this.stanza_comment,
        stanza_inactive: this.stanza_inactive
      })
        .then(response => {
          Toast.fire({
            type: 'success',
            title: 'Entry added!',
            html: response.data.message
          });
          EventBus.$emit('entry-added', response.data.data);
        })
        .catch(error => {
          Toast.fire({
            type: 'error',
            title: 'Error!',
            html: error.response.data.message
          })
        })
    }
  }
}
</script>

<style>
  .v-select-toggle, .v-dropdown-item {
    font-size: 1rem !important;
  }
</style>
