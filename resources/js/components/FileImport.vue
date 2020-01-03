<template>
  <div>
    <!--UPLOAD-->
    <form v-if="isInitial || isSaving" enctype="multipart/form-data" novalidate>
      <div class="form-check">
        <input class="form-check-input" v-model="allowOverwrite" type="checkbox" id="allowOverwrite">
        <label class="form-check-label" for="allowOverwrite">
          Allow existing files to be overwritten
        </label>
      </div>
      <div class="dropbox bg-light">
        <input type="file" multiple :name="uploadFieldName" :disabled="isSaving"
               @change="filesChange($event.target.name, $event.target.files); fileCount = $event.target.files.length"
               accept="text/plain" class="input-file">
        <p v-if="isInitial">
          Drag your file(s) here to begin<br> or click to browse
        </p>
        <p v-if="isSaving">
          Uploading {{ fileCount }} files...
        </p>
      </div>
    </form>
    <!--SUCCESS-->
    <div v-if="isSuccess">
      <div class="alert alert-success">Uploaded {{ uploadedFiles.length }} file(s) successfully.</div>
      <p>
        <a href="javascript:void(0)" @click="reset()">Upload again</a>
      </p>
    </div>
    <!--FAILED-->
    <div v-if="isFailed || isWarning">
      <div class="alert" v-bind:class="{ 'alert-danger': isFailed, 'alert-warning': isWarning }">
        <template v-if="isFailed">
          <p>Upload failed.</p>
          <ul v-html="uploadError"></ul>
        </template>
        <template v-else-if="isWarning">
          <p>Upload succeeded, but the following issues were discovered:</p>
          <ul>
            <li v-for="(item, filename) in warnings">{{ filename }}
              <ul>
                <li v-for="error in warnings[filename]">{{ error }}</li>
              </ul>
            </li>
          </ul>
        </template>
      </div>
      <p>
        <a href="javascript:void(0)" @click="reset()">Try again</a>
      </p>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2'

// Sweet status message popup (toast)
const Toast = Swal.mixin({
  toast: true,
  position: 'bottom-right',
  timer: 5000,
  showConfirmButton: false,
  showCancelButton: false
});

const STATUS_INITIAL = 0;
const STATUS_SAVING = 1;
const STATUS_SUCCESS = 2;
const STATUS_WARNING = 3;
const STATUS_FAILED = 4;

export default {
  name: 'FileImport',

  data () {
    return {
      uploadedFiles: [],
      uploadError: null,
      currentStatus: null,
      uploadFieldName: 'EZproxyFiles[]',
      allowOverwrite: false,
      warnings: null,
      errors: null
    }
  },

  computed: {
    isInitial () {
      return this.currentStatus === STATUS_INITIAL;
    },
    isSaving () {
      return this.currentStatus === STATUS_SAVING;
    },
    isSuccess () {
      return this.currentStatus === STATUS_SUCCESS;
    },
    isWarning () {
      return this.currentStatus === STATUS_WARNING;
    },
    isFailed () {
      return this.currentStatus === STATUS_FAILED;
    }
  },

  mounted () {
    this.reset();
  },

  methods: {
    reset () {
      // reset form to initial state
      this.currentStatus = STATUS_INITIAL;
      this.uploadedFiles = [];
      this.uploadError = null;
      this.errors = null;
      this.warnings = null;
    },
    save (formData) {
      // upload data to the server
      this.currentStatus = STATUS_SAVING;

      axios.post('/api/files/import', formData)
        .then(response => {
          this.uploadedFiles = [].concat(response);
          this.currentStatus = STATUS_SUCCESS;
          Toast.fire({
            type: 'success',
            title: 'Import successful',
            html: response.data.message
          });
        })
        .catch(error => {
          this.currentStatus = STATUS_FAILED;
          var errors = [];
          if (error.response.data.hasOwnProperty('errors')) {
            Object.entries(error.response.data.errors).forEach(([key, val]) => errors.push(val));
          } else if (error.response.data.hasOwnProperty('warnings')) {
            this.currentStatus = STATUS_WARNING;
            this.warnings = error.response.data.warnings;
          } else if (error.response.data.hasOwnProperty('data')) {
            errors.push('<pre>' + JSON.stringify(error.response.data.data) + '</pre>');
          }
          this.uploadError = '<li>' + errors.join('</li><li>') + '</li>';

          Toast.fire({
            type: 'error',
            title: 'Error!',
            html: error.response.data.message
          })
        });
    },

    filesChange (fieldName, fileList) {
      // handle file changes
      const formData = new FormData();

      if (!fileList.length) return;

      // append the files to FormData
      Array
        .from(Array(fileList.length).keys())
        .map(x => {
          formData.append(fieldName, fileList[x], fileList[x].name);
        });

      formData.append('allowOverwrite', this.allowOverwrite ? 1 : 0);

      // save it
      this.save(formData);
    }
  }
}
</script>

<style lang="scss">
  .dropbox {
    outline: 2px dashed grey; /* the dash box */
    outline-offset: -10px;
    padding: 10px 10px;
    min-height: 200px; /* minimum height */
    position: relative;
    cursor: pointer;
  }

  .input-file {
    opacity: 0; /* invisible but it's there! */
    width: 100%;
    height: 200px;
    position: absolute;
    cursor: pointer;
  }

  .dropbox:hover {
    background: lightblue; /* when mouse over to the drop zone, change color */
  }

  .dropbox p {
    font-size: 1.2em;
    text-align: center;
    padding: 50px 0;
  }
</style>
