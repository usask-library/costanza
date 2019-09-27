<template>
  <div>
    <div class="form-group row">
      <label for="custom_name" class="col-md-2 col-form-label"><strong>Name</strong></label>
      <div class="col-md-10">
        <input id="custom_name" v-model="custom_name" class="form-control" required />
      </div>
    </div>
    <div class="form-group row">
      <label for="custom_value" class="col-md-2 col-form-label"><strong>Stanza Directives</strong></label>
      <div class="col-md-10">
        <textarea id="custom_value" v-model="custom_value" class="form-control text-monospace col-12" rows="5" required></textarea>
      </div>
    </div>
    <div class="form-group row">
      <label for="custom_comment" class="col-md-2 col-form-label">Comment</label>
      <div class="col-md-10">
        <textarea id="custom_comment" v-model="custom_comment" class="form-control text-monospace col-12" rows="5"></textarea>
        <p class="form-text text-muted">A comment or note specific to this custom stanza.</p>
      </div>
    </div>
    <div class="form-group row">
      <div class="col-md-2">Inactive</div>
      <div class="col-md-10">
        <div class="form-check">
          <input id="custom_inactive" type="checkbox" v-model="custom_inactive" class="form-check-input" />
          <label for="custom_inactive" class="form-check-label text-muted">
            This will leave the custom stanza in your EZproxy config, but mark it inactive.<br/>
            It is the equivalent of placing a # character at the beginning of each line to turn it into a comment.
          </label>
        </div>
      </div>
    </div>
    <button type="button" class="btn btn-primary float-right" data-type="custom" @click="addCustomStanza()">Add Custom Stanza</button>
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
  name: 'CustomStanzaNew.vue',

  props: {
    insertAfter: { type: String, required: true },
    filename: { type: String, required: true }
  },

  data () {
    return {
      custom_name: '',
      custom_value: '',
      custom_comment: '',
      custom_inactive: false
    }
  },

  methods: {
    addCustomStanza () {
      axios.post('/api/files/' + this.filename + '/entries', {
        placeAfter: this.insertAfter,
        type: 'custom_stanza',
        custom_name: this.custom_name,
        custom_value: this.custom_value,
        custom_comment: this.custom_comment,
        custom_inactive: this.custom_inactive
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
