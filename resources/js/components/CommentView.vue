<template>
  <div>
    <div class="row mb-3">
      <div class="col-md-2">Comment</div>
      <div class="col-md-10">
        <pre style="white-space: pre-wrap">{{ Array.isArray(entry.value) ? entry.value.join("\n") : entry.value }}</pre>
      </div>
    </div>
  </div>
</template>

<script>

export default {
  name: 'CommentView.vue',

  props: {
    entry: Object
  }
}
</script>
