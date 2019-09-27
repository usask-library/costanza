<template>
  <div>
    <input type="hidden" name="insert_after" value="insert_after_id_goes_here">
    <div class="form-group row">
      <label for="stanza_id" class="col-md-2 col-form-label">Stanza</label>
      <div class="col-md-10">
        <input id="stanza_id" v-model="stanza.name" class="form-control-plaintext" />
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
    <button type="button" class="btn btn-primary float-right" data-type="stanza" @click="updateStanza()">Update Database Stanza</button>
  </div>
</template>

<script>
import axios from 'axios'
import Swal from 'sweetalert2'
import { EventBus } from '~/plugins/EventBus.js'

// Sweet status message popup (toast)
const Toast = Swal.mixin({
  toast: true,
  position: 'bottom-right',
  timer: 5000,
  showConfirmButton: false,
  showCancelButton: false
})

export default {
  name: 'StanzaEdit.vue',

  props: {
    entry: { type: Object, required: true },
    filename: { type: String, required: true },
    stanza: Object
  },

  data () {
    return {
      stanza_id: this.entry.code,
      stanza_name: this.entry.name,
      stanza_comment: Array.isArray(this.entry.comment) ? this.entry.comment.join('\n') : this.entry.comment,
      stanza_inactive: !(this.entry.active !== false)
    }
  },

  methods: {
    updateStanza () {
      axios.put('/api/files/' + this.filename + '/entries/' + this.entry.id, {
        type: 'stanza',
        stanza_id: this.stanza_id,
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
          EventBus.$emit('entry-updated', response.data.data);
          // Saved the form data via the API. Simple hack to get the component to update
          this.entry.name = this.stanza_name;
          this.entry.comment = this.stanza_comment;
          this.entry.active = !this.stanza_inactive;
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
