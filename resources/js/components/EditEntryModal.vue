<template>
  <b-modal id="EditEntryModal" class="fade" title="Edit entry" size="lg" lazy hide-footer>
    <div id="edit-entry-messages" class="hidden"></div>
    <div v-if="entry.type === 'comment'" id="comment">
      <CommentEdit :entry="entry" :filename="filename" @exit="closeModal"></CommentEdit>
    </div>
    <div v-else-if="entry.type === 'directive'" id="directive">
      <DirectiveEdit :entry="entry" :filename="filename" @exit="closeModal"></DirectiveEdit>
    </div>
    <div v-else-if="entry.type === 'group'" id="group">
      <GroupEdit :entry="entry" :filename="filename" @exit="closeModal"></GroupEdit>
    </div>
    <div v-else-if="entry.type === 'stanza'" id="stanza">
      <StanzaEdit :entry="entry" :filename="filename" :stanza="stanzas[entry.code]" @exit="closeModal"></StanzaEdit>
    </div>
    <div v-else-if="entry.type === 'custom_stanza'" id="custom">
      <CustomStanzaEdit :entry="entry" :filename="filename" @exit="closeModal"></CustomStanzaEdit>
    </div>
  </b-modal>
</template>

<script>
import CommentEdit from './CommentEdit'
import DirectiveEdit from './DirectiveEdit'
import GroupEdit from './GroupEdit'
import StanzaEdit from './StanzaEdit';
import CustomStanzaEdit from './CustomStanzaEdit'

import { EventBus } from '~/plugins/EventBus.js'

export default {
  name: 'EditEntryModal.vue',

  components: {
    CommentEdit,
    DirectiveEdit,
    GroupEdit,
    StanzaEdit,
    CustomStanzaEdit
  },

  props: {
    entry: { type: Object, required: true },
    filename: { type: String, required: true },
    stanzas: { type: Object, required: true }
  },

  data () {
    return {
    }
  },

  mounted () {
    EventBus.$on('entry-updated', data => {
      this.closeModal()
    })
  },

  methods: {
    closeModal () {
      this.$bvModal.hide('EditEntryModal')
    }
  }
}

</script>
