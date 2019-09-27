<template>
  <div>
    <input type="hidden" name="insert_after" value="insert_after_id_goes_here">
    <div class="form-group row">
      <label for="group_name" class="col-md-2 col-form-label"><strong>Group name</strong></label>
      <div class="col-md-10">
        <input id="group_name" v-model="group_name" class="form-control text-monospace" required />
      </div>
    </div>
    <div class="form-group row">
      <label for="group_comment" class="col-md-2 col-form-label">Comment</label>
      <div class="col-md-10">
        <textarea id="group_comment" v-model="group_comment" class="form-control text-monospace col-12" rows="5"></textarea>
        <p class="form-text text-muted">A comment or note specific to this group.</p>
      </div>
    </div>
    <div class="form-group row">
      <div class="col-md-2">Inactive</div>
      <div class="col-md-10">
        <div class="form-check">
          <input id="group_inactive" type="checkbox" v-model="group_inactive" class="form-check-input" />
          <label for="group_inactive" class="form-check-label text-muted">
            This will leave the group in your EZproxy config, but mark it inactive.<br/>
            It is the equivalent of placing a # character at the beginning of the line to turn it into a comment.
          </label>
        </div>
      </div>
    </div>
    <button type="button" class="btn btn-primary float-right" data-type="group" @click="updateGroup()">Update Group</button>
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
  name: 'GroupEdit.vue',

  props: {
    entry: { type: Object, required: true },
    filename: { type: String, required: true }
  },

  data () {
    return {
      group_name: this.entry.name,
      group_comment: Array.isArray(this.entry.comment) ? this.entry.comment.join('\n') : this.entry.comment,
      group_inactive: !(this.entry.active !== false)
    }
  },

  methods: {
    updateGroup () {
      axios.put('/api/files/' + this.filename + '/entries/' + this.entry.id, {
        type: 'group',
        group_name: this.group_name,
        group_comment: this.group_comment,
        group_inactive: this.group_inactive
      })
        .then(response => {
          Toast.fire({
            type: 'success',
            title: 'Entry added!',
            html: response.data.message
          });
          // Saved the form data via the API. Simple hack to get the component to update
          this.entry.name = this.group_name;
          this.entry.comment = this.group_comment;
          this.entry.active = !this.group_inactive;
          EventBus.$emit('entry-updated', response.data.data);
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
