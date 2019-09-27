<template>
  <div>
    <div class="row mb-3">
      <div class="col-md-2">Name</div>
      <div class="col-md-10 text-monospace">
        {{ entry.name }}
      </div>
    </div>

    <div v-if="entry.comment" class="row mb-3">
      <div class="col-md-2">Comment</div>
      <div class="col-md-10">
        <pre>{{ Array.isArray(entry.comment) ? entry.comment.join("\n") : entry.comment }}</pre>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-2">Stanza Directives</div>
      <div class="col-md-10">
        <pre>{{ Array.isArray(entry.value) ? entry.value.join("\n") : entry.value }}</pre>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-2">Status</div>
      <div class="col-md-10">
        <template v-if="entry.active === false">
          <strong>Inactive</strong>. This custom stanza will still appear in your EZproxy config, but will be mark it inactive (i.e. treated like a comment).
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
  name: 'CustomStanzaView.vue',

  props: {
    entry: Object
  }
}
</script>
