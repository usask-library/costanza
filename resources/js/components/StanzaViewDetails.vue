<template>
  <div>
    <div class="row mb-3">
      <div class="col-md-2">Name</div>
      <div class="col-md-10">
        {{ entry.name }}
      </div>
    </div>
    <div v-if="entry.display_name" class="row mb-3">
      <div class="col-md-2">Display Name</div>
      <div class="col-md-10">
        {{ entry.display_name }}
      </div>
    </div>
    <div v-if="entry.comment" class="row mb-3">
      <div class="col-md-2">Comment</div>
      <div class="col-md-10">
        <pre style="white-space: pre-wrap">{{ Array.isArray(entry.comment) ? entry.comment.join("\n") : entry.comment }}</pre>
      </div>
    </div>
    <div v-if="stanza && stanza.hasOwnProperty('oclcIncludeFile')" class="row mb-3">
      <div class="col-md-2">OCLC Hosted Include File</div>
      <div class="col-md-10">
        {{ stanza.oclcIncludeFile }}
      </div>
    </div>
    <div class="row mb-3">
      <div class="col-md-2">Status</div>
      <div class="col-md-10">
        <template v-if="entry.active == false">
          <strong>Inactive</strong>. This stanza will still appear in your EZproxy config, but will be marked inactive (i.e. treated like a comment).
        </template>
        <template v-else>
          Active
        </template>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StanzaViewDetails.vue',

  props: {
    entry: Object,
    stanza: { type: Object, default: () => ({}) }
  }
}
</script>
