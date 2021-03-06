<template>
  <div>
    <div class="form-group row">
      <label for="directive_name" class="col-md-2 col-form-label"><strong>Directive</strong></label>
      <div class="col-md-10">
        <input id="directive_name" v-model="directive_name" class="form-control text-monospace" required />
        <p class="form-text text-muted">A complete list of all EZproxy directives can be found on the <a href="https://help.oclc.org/Library_Management/EZproxy/Configure_resources" target="oclc">OCLC website <fa icon="external-link-alt" size="sm" /></a>.</p>
      </div>
    </div>
    <div class="form-group row">
      <label for="directive_value" class="col-md-2 col-form-label"><strong>Value(s)</strong></label>
      <div class="col-md-10">
        <textarea id="directive_value" v-model="directive_value" class="form-control text-monospace col-12" rows="5" required></textarea>
        <p class="form-text text-muted">You can specify multiple values here, one per line. Each will be written as a separate directive in the EZproxy config file.</p>
      </div>
    </div>
    <div class="form-group row">
      <label for="directive_comment" class="col-md-2 col-form-label">Comment</label>
      <div class="col-md-10">
        <textarea id="directive_comment" v-model="directive_comment" class="form-control text-monospace col-12" rows="5"></textarea>
        <p class="form-text text-muted">A comment or note specific to this directive.</p>
      </div>
    </div>
    <div class="form-group row">
      <div class="col-md-2">Inactive</div>
      <div class="col-md-10">
        <div class="form-check">
          <input id="directive_inactive" type="checkbox" v-model="directive_inactive" class="form-check-input" />
          <label for="directive_inactive" class="form-check-label text-muted">
            This will leave the directive in your EZproxy config, but mark it inactive.<br/>
            It is the equivalent of placing a # character at the beginning of the line to turn it into a comment.
          </label>
        </div>
      </div>
    </div>
    <button type="button" class="btn btn-primary float-right" data-type="directive" @click="addDirective()">Add Directive</button>
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
  name: 'DirectiveNew.vue',

  props: {
    insertAfter: { type: String, required: true },
    filename: { type: String, required: true }
  },

  data () {
    return {
      directive_name: '',
      directive_value: '',
      directive_comment: '',
      directive_inactive: false
    }
  },

  methods: {
    addDirective () {
      axios.post('/api/files/' + this.filename + '/entries', {
        placeAfter: this.insertAfter,
        type: 'directive',
        directive_name: this.directive_name,
        directive_value: this.directive_value,
        directive_comment: this.directive_comment,
        directive_inactive: this.directive_inactive
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
