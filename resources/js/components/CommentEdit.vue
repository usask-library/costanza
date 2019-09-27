<template>
  <div>
    <div class="form-group row">
      <label for="comment_value" class="col-md-2 col-form-label"><strong>Comment</strong></label>
      <div class="col-md-10">
        <textarea id="comment_value" v-model="comment_value" class="form-control text-monospace col-12" rows="5" required></textarea>
        <p class="form-text text-muted">Don't worry about using the <code>#</code> symbol at the beginning of each line. Costanza will add that for you.</p>
      </div>
    </div>
    <button type="button" class="btn btn-primary float-right" data-type="comment" @click="updateComment()">Update Comment</button>
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
  name: 'CommentEdit.vue',

  props: {
    entry: { type: Object, required: true },
    filename: { type: String, required: true }
  },

  data () {
    return {
      comment_value: Array.isArray(this.entry.value) ? this.entry.value.join('\n') : this.entry.value
    }
  },

  methods: {
    updateComment () {
      axios.put('/api/files/' + this.filename + '/entries/' + this.entry.id, {
        type: 'comment',
        comment_value: this.comment_value
      })
        .then(response => {
          Toast.fire({
            type: 'success',
            title: 'Entry updated!',
            html: response.data.message
          });
          EventBus.$emit('entry-updated', response.data.data);
          // Saved the form data via the API. Simple hack to get the component to update
          this.entry.value = this.comment_value
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
