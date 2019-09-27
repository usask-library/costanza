<template>
  <div>
    <div class="form-group row">
      <label for="comment_value" class="col-md-2 col-form-label"><strong>Comment</strong></label>
      <div class="col-md-10">
        <textarea id="comment_value" v-model="comment_value" class="form-control text-monospace col-12" rows="5" required></textarea>
        <p class="form-text text-muted">Don't worry about using the <code>#</code> symbol at the beginning of each line. Costanza will add that for you.</p>
      </div>
    </div>
    <button type="button" class="btn btn-primary float-right" data-type="comment" @click="addComment()">Add Comment</button>
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
  name: 'CommentNew.vue',

  props: {
    insertAfter: { type: String, required: true },
    filename: { type: String, required: true }
  },

  data () {
    return {
      comment_value: ''
    }
  },

  methods: {
    addComment () {
      axios.post('/api/files/' + this.filename + '/entries', {
        placeAfter: this.insertAfter,
        type: 'comment',
        comment_value: this.comment_value
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
