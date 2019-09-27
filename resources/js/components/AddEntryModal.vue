<template>
  <b-modal id="AddEntryModal" class="fade" title="Add a new entry" size="lg" lazy hide-footer>
    <p>What type of entry would you like to add to the EZproxy config?</p>
    <div id="add-entry-messages" class="hidden"></div>

    <ul id="addType" class="nav nav-tabs nav-fill" role="tablist">
      <li class="nav-item">
        <a id="comment-tab" class="nav-link active" data-toggle="tab" href="#comment" role="tab" aria-controls="comment" aria-selected="true">Comment</a>
      </li>
      <li class="nav-item">
        <a id="directive-tab" class="nav-link" data-toggle="tab" href="#directive" role="tab" aria-controls="directive" aria-selected="false">Config Directive</a>
      </li>
      <li class="nav-item">
        <a id="group-tab" class="nav-link" data-toggle="tab" href="#group" role="tab" aria-controls="group" aria-selected="false">Group</a>
      </li>
      <li class="nav-item">
        <a id="stanza-tab" class="nav-link" data-toggle="tab" href="#stanza" role="tab" aria-controls="stanza" aria-selected="false">Database Stanza</a>
      </li>
      <li class="nav-item">
        <a id="custom-tab" class="nav-link" data-toggle="tab" href="#custom" role="tab" aria-controls="custom" aria-selected="false">Custom Stanza</a>
      </li>
    </ul>
    <div class="tab-content my-3" id="addTypeContent">
      <div id="comment" class="tab-pane fade show active" role="tabpanel" aria-labelledby="comment-tab">
        <CommentNew :insertAfter="insertAfter" :filename="filename" @exit="closeModal"></CommentNew>
      </div>
      <div id="directive" class="tab-pane fade" role="tabpanel" aria-labelledby="directive-tab">
        <DirectiveNew :insertAfter="insertAfter" :filename="filename" @exit="closeModal"></DirectiveNew>
      </div>
      <div id="group" class="tab-pane fade" role="tabpanel" aria-labelledby="group-tab">
        <GroupNew :insertAfter="insertAfter" :filename="filename" @exit="closeModal"></GroupNew>
      </div>
      <div id="stanza" class="tab-pane fade" role="tabpanel" aria-labelledby="stanza-tab">
        <StanzaNew :insertAfter="insertAfter" :filename="filename" :stanzas="stanzas" @exit="closeModal"></StanzaNew>
      </div>
      <div id="custom" class="tab-pane fade" role="tabpanel" aria-labelledby="custom-tab">
        <CustomStanzaNew :insertAfter="insertAfter" :filename="filename" @exit="closeModal"></CustomStanzaNew>
      </div>
    </div>
  </b-modal>
</template>

<script>
import CommentNew from './CommentNew'
import DirectiveNew from './DirectiveNew'
import GroupNew from './GroupNew'
import StanzaNew from './StanzaNew';
import CustomStanzaNew from './CustomStanzaNew'
import { EventBus } from '~/plugins/EventBus.js'

export default {
  name: 'AddEntryModal.vue',

  components: {
    CommentNew,
    DirectiveNew,
    GroupNew,
    StanzaNew,
    CustomStanzaNew
  },

  props: {
    insertAfter: String,
    filename: String,
    stanzas: Object
  },

  data () {
    return {
    }
  },

  mounted () {
    EventBus.$on('entry-added', data => {
      this.closeModal()
    })
  },

  methods: {
    closeModal () {
      this.$bvModal.hide('AddEntryModal')
    }
  }
}

</script>
