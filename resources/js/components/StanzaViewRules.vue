<template>
  <div>
    <p>
      Processing rules allow a stanza to be modified by prepending, appending or replacing text.<br/>
      <template v-if="entry.rules">Each rule will be applied in turn, in the order they are defined below.</template>
      <template v-else>This stanza does not have any processing rules defined; go ahead and create some!</template>
    </p>

    <b-container fluid class="mb-2">
      <draggable v-model="entry.rules" group="rules" @end="saveChanges">
        <b-form-row v-for="(rule, index) in entry.rules" :key="'rule-' + index">
          <b-col><fa icon="grip-lines" fixed-width class="handle" /></b-col>
          <b-col>{{ rule.rule }}</b-col>

          <b-col v-if="rule.rule === 'Replace'" cols="4">
            <b-form-input v-model="rule.term" placeholder="Enter a search term" @change="saveChanges" />
          </b-col>
          <b-col v-if="rule.rule === 'Replace'" cols="4">
            <b-form-input v-model="rule.value" :placeholder="rule.placeholder || 'Enter a replacement value'" @change="saveChanges" />
          </b-col>

          <b-col v-if="rule.rule === 'Append'" cols="8">
            <b-form-textarea v-model="rule.value" rows="2" max-rows="6" @change="saveChanges" />
          </b-col>
          <b-col v-if="rule.rule === 'Prepend'" cols="8">
            <b-form-textarea v-model="rule.value" rows="2" max-rows="6" @change="saveChanges" />
          </b-col>

          <b-col><b-form-checkbox v-model="rule.enabled" switch @change="saveChanges" /></b-col>
          <b-col>
            <b-link class="text-danger" @click="deleteRule(index)">
              <fa icon="trash-alt" fixed-width title="Delete this rule" />
            </b-link>
          </b-col>
        </b-form-row>
      </draggable>
    </b-container>

    <hr>

    <b-container fluid class="mb-2">
      <b-form-row>
        <b-col cols="1">
          New:
        </b-col>
        <b-col cols="2">
          <b-form-select v-model="newRule.rule" :options="options">
            <template v-slot:first>
              <option :value="null" hidden>Select a rule</option>
            </template>
          </b-form-select>
        </b-col>

        <b-col v-if="newRule.rule === 'Replace'">
          <b-form-input v-model="newRule.term" placeholder="Enter a search term"></b-form-input>
        </b-col>
        <b-col v-if="newRule.rule === 'Replace'">
          <b-form-input v-model="newRule.value" placeholder="Enter a replacement value"></b-form-input>
        </b-col>

        <b-col v-if="newRule.rule === 'Append'">
          <b-form-textarea v-model="newRule.value" rows="2" max-rows="6" />
        </b-col>
        <b-col v-if="newRule.rule === 'Prepend'">
          <b-form-textarea v-model="newRule.value" rows="2" max-rows="6" />
        </b-col>

        <b-col v-if="newRule.rule" cols="1">
          <b-form-checkbox v-model="newRule.enabled" switch />
        </b-col>
        <b-col cols="1">
          <b-button variant="primary" @click="addRule">Add</b-button>
        </b-col>
      </b-form-row>
    </b-container>
  </div>
</template>

<script>
import axios from 'axios'
import Swal from 'sweetalert2'
import draggable from 'vuedraggable'

// Sweet status message popup (toast)
const Toast = Swal.mixin({
  toast: true,
  position: 'bottom-right',
  timer: 5000,
  showConfirmButton: false,
  showCancelButton: false
});

export default {
  name: 'StanzaViewRules.vue',

  components: {
    draggable
  },

  props: {
    filename: { type: String, required: true },
    entry: { type: Object, required: true },
    stanza: { type: Object, required: true }
  },

  data () {
    return {
      newRule: {
        rule: null,
        value: null,
        enabled: true
      },

      options: [
        { value: 'Prepend', text: 'Prepend' },
        { value: 'Append', text: 'Append' },
        { value: 'Replace', text: 'Replace' }
      ]
    }
  },

  methods: {
    addRule () {
      if (this.newRule.rule) {
        var r = Object.assign({}, this.newRule);
        if (this.entry.hasOwnProperty('rules')) {
          this.entry.rules.push(r);
        } else {
          this.$set(this.entry, 'rules', [r]);
        }

        this.saveChanges();

        this.newRule.value = null;
        this.newRule.enabled = true;
        if (this.newRule.hasOwnProperty('term')) {
          this.newRule.term = null;
        }
      }
    },

    deleteRule (index) {
      this.entry.rules.splice(index, 1);
      this.saveChanges();
    },

    saveChanges () {
      axios.post('/api/files/' + this.filename + '/entries/' + this.entry.id + '/rules', {
        rules: this.entry.rules
      })
        .then(response => {
          Toast.fire({
            type: 'success',
            title: 'Rules updates!',
            html: response.data.message
          });
        })
        .catch(error => {
          Toast.fire({
            type: 'error',
            title: 'Failed to update rules!',
            html: error.response.data.message
          })
        })
    }
  }

}
</script>
