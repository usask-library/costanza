<template>
  <b-container id="entry-list" class="card-sortable" fluid>
    <template v-if="loading">
      <div>
        <b-spinner label="Loading..." variant="secondary" type="grow"></b-spinner>
      </div>
    </template>
    <template v-else>

      <div v-if="entries.length === 0" class="alert alert-info mt-5">
        <p>There are no stanzas or directives in this file yet.  Go ahead and add one!</p>
        <button v-b-modal.AddEntryModal class="btn btn-primary" @click="setInsertAfter('top', 0)">Add an entry</button>
      </div>

      <draggable v-model="entries" handle=".handle" @end="onEnd">
        <transition-group>
          <div v-for="(entry, index) in entries" :id="entry.id" :key="entry.id" class="card" :data-type="entry.type">
            <div class="card-header">
              <div class="row">
                <div class="col-11" data-toggle="collapse" :data-target="'#collapse-' + entry.id" aria-expanded="false" :aria-controls="'collapse-' + entry.id">
                  <fa icon="grip-lines" fixed-width class="handle" />

                  <template v-if="entry.type === 'directive'">
                    <button class="btn btn-sm col-3 col-md-2 mr-2" :class="[ isActive(entry) ? 'btn-warning' : 'btn-outline-warning']">DIRECTIVE</button>
                    <b :class="{'text-muted': ! isActive(entry)}">{{ entry.name }}</b>
                    <code>{{ firstValue(entry) }}</code>
                  </template>

                  <template v-if="entry.type === 'group'">
                    <button class="btn btn-sm col-3 col-md-2 mr-2" :class="isActive(entry) ? 'btn-success' : 'btn-outline-success'">GROUP</button>
                    <b :class="{'text-muted': ! isActive(entry)}">{{ entry.name }}</b>
                  </template>

                  <template v-if="entry.type === 'stanza'">
                    <button class="btn btn-sm col-3 col-md-2 mr-2" :class="[isActive(entry) ? 'btn-primary' : 'btn-outline-primary']">STANZA</button>
                    <b :class="{'text-muted': ! isActive(entry)}">{{ stanzaName(entry) }}</b>
                    <span v-if="isOclcStanza(entry) === true">
                      &nbsp; <a :href="oclcStanzaUrl(entry)" target="oclc"><img :src="publicPath + 'images/oclc_logo.png'" title="View this stanza on the OCLC website" alt="View this stanza on the OCLC website" height="16px" /></a>
                    </span>
                  </template>

                  <template v-if="entry.type === 'custom_stanza'">
                    <button class="btn btn-sm col-3 col-md-2 mr-2" :class="[isActive(entry) ? 'btn-info' : 'btn-outline-info']">CUSTOM</button>
                    <b :class="{'text-muted': ! isActive(entry)}">{{ entry.name }}</b>
                  </template>

                  <template v-if="entry.type === 'comment'">
                    <button class="btn btn-sm col-3 col-md-2 mr-2" :class="[isActive(entry) ? 'btn-secondary' : 'btn-outline-secondary']">COMMENT</button>
                    <code>{{ firstValue(entry) }}</code>
                  </template>
                </div>

                <div class="col-1 text-right">
                  <div class="dropdown">
                    <button :id="'dropdown-' + entry.id" class="btn btn-light btn-sm" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <fa icon="ellipsis-v" />
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" :aria-labelledby="'dropdown-' + entry.id">
                      <a v-b-modal.AddEntryModal @click="setInsertAfter(entry.id, index)" class="dropdown-item"><fa icon="plus" fixed-width class="text-primary" /> Add entry below</a>
                      <a v-b-modal.EditEntryModal @click="setEntry(entry)" class="dropdown-item"><fa icon="edit" fixed-width class="text-success" /> Edit this entry</a>
                      <a class="dropdown-item delete-entry" @click="deleteEntry(index)"><fa icon="trash-alt" fixed-width class="text-danger"></fa> Delete this entry</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div :id="'collapse-' + entry.id" class="collapse dynamic-card-body" :aria-labelledby="'heading-' + entry.id">
              <div class="card-body">
                <div :id="'update-entry-messages-' + entry.id" class="hidden"></div>
                <template v-if="entry.type === 'comment'">
                  <CommentView :entry="entry"></CommentView>
                </template>
                <template v-else-if="entry.type === 'directive'">
                  <DirectiveView :entry="entry"></DirectiveView>
                </template>
                <template v-else-if="entry.type === 'group'">
                  <GroupView :entry="entry"></GroupView>
                </template>
                <template v-else-if="entry.type === 'stanza'">
                  <StanzaView :entry="entry" :stanza="stanzas[entry.code]"></StanzaView>
                </template>
                <template v-else-if="entry.type === 'custom_stanza'">
                  <CustomStanzaView :entry="entry"></CustomStanzaView>
                </template>
              </div>
            </div>
          </div><!-- Entry -->

        </transition-group>
      </draggable>
    </template>
    <AddEntryModal :insertAfter="insertAfter" :filename="filename" :stanzas="stanzas"></AddEntryModal>
    <EditEntryModal :entry="entry" :filename="filename" :stanzas="stanzas"></EditEntryModal>
  </b-container>
</template>

<script>
// ToDo: Add Clone entry feature

import axios from 'axios';
import router from '~/router';
import draggable from 'vuedraggable';
import Swal from 'sweetalert2';

import CommentView from './CommentView';
import DirectiveView from './DirectiveView';
import GroupView from './GroupView';
import StanzaView from './StanzaView';
import CustomStanzaView from './CustomStanzaView';
import { getStanzaList } from '../plugins/stanzas'
import { EventBus } from '~/plugins/EventBus.js'
import AddEntryModal from './AddEntryModal'
import EditEntryModal from './EditEntryModal'

// Sweet status message popup (toast)
const Toast = Swal.mixin({
  toast: true,
  position: 'bottom-right',
  timer: 5000,
  showConfirmButton: false,
  showCancelButton: false
});

// Alert message
const Alert = Swal.mixin({
  type: 'error',
  title: 'An error occured',
  reverseButtons: true,
  confirmButtonText: 'Ok',
  cancelButtonText: 'Cancel',
  showConfirmButton: true
});

export default {
  middleware: 'auth',

  components: {
    draggable,
    AddEntryModal,
    EditEntryModal,
    CommentView,
    DirectiveView,
    GroupView,
    StanzaView,
    CustomStanzaView
  },

  props: {
    filename: String
  },

  data () {
    return {
      publicPath: process.env.MIX_APP_PATH,
      loading: false,
      dragging: false,
      entries: [],
      stanzas: {},
      insertAfter: null,
      insertIndex: 0,
      entry: null
    }
  },

  computed: {
  },

  created () {
    this.getConfigFile()
  },

  mounted () {
    this.getStanzaList();

    // Watch for the entry-added message, indicating a new entry was successfully added to config
    EventBus.$on('entry-added', data => {
      // Insert the newly added entry into the array of entries (this will signal a refresh of the entry list component)
      this.entries.splice(this.insertIndex + 1, 0, data)
    });
  },

  methods: {
    getStanzaList () {
      getStanzaList().then((stanzas) => {
        this.stanzas = stanzas
      })
    },

    getConfigFile () {
      this.loading = true
      axios.get('/api/files/' + this.filename)
        .then(response => {
          this.loading = false
          this.entries = response.data.data
        })
        .catch(error => {
          this.loading = false
          Alert.fire({
            title: 'File not found',
            html: error.response.data.message
          }).then(() => {
            router.push({ name: 'welcome' })
          })
        })
    },

    deleteEntry (index) {
      var id = this.entries[index].id
      axios.delete('/api/files/' + this.filename + '/entries/' + id)
        .then(response => {
          this.entries.splice(index, 1)
          Toast.fire({
            type: 'success',
            title: 'Deleted!',
            html: response.data.message
          })
        })
        .catch(error => {
          Toast.fire({
            type: 'error',
            title: 'Error!',
            html: error.response.data.message
          })
        })
    },

    onEnd: function (evt) {
      if (evt.oldIndex !== evt.newIndex) {
        var entryId = this.entries[evt.newIndex].id
        var previousId = (evt.newIndex === 0) ? 'top' : this.entries[evt.newIndex - 1].id

        axios.post('/api/files/' + this.filename + '/entries/' + entryId, {
          placeAfter: previousId
        })
          .then(response => {
            Toast.fire({
              type: 'success',
              title: 'Moved!',
              html: response.data.message
            })
          })
          .catch(error => {
            // Get the entry from the new location, delete it, insert it back at the old location
            var entry = this.entries[evt.newIndex]
            this.entries.splice(evt.newIndex, 1)
            this.entries.splice(evt.oldIndex, 0, entry)

            Toast.fire({
              type: 'error',
              title: 'Error!',
              html: error.response.data.message
            })
          })
      }
    },

    setInsertAfter: function (id, index) {
      this.insertAfter = id;
      this.insertIndex = index;
    },
    setEntry: function (entry) {
      this.entry = entry
    },

    isActive: function (entry) {
      return (entry.hasOwnProperty('active')) ? entry.active : true
    },
    isOclcStanza: function (entry) {
      return (entry.hasOwnProperty('code') && this.stanzas[entry.code] && this.stanzas[entry.code].hasOwnProperty('oclcStanzaId')) ? true : false
    },
    oclcStanzaUrl: function (entry) {
      return (this.isOclcStanza(entry)) ? 'https://help.oclc.org/Library_Management/EZproxy/Database_stanzas/' + this.stanzas[entry.code].oclcStanzaId : null
    },
    firstValue: function (entry) {
      return Array.isArray(entry.value) ? entry.value[0] : entry.value
    },
    stanzaName: function (entry) {
      return (entry.hasOwnProperty('display_name') && entry.display_name)
        ? entry.display_name
        : (
          (entry.hasOwnProperty('name'))
            ? entry.name
            : (
              (entry.hasOwnProperty('code') && this.stanzas[entry.code] && this.stanzas[entry.code].hasOwnProperty('name'))
                ? this.stanzas[entry.code].name
                : 'UNKNOWN STANZA'
            )
        )
    }
  }
}
</script>

<style>
  .handle { margin-right: 10px; }
</style>
