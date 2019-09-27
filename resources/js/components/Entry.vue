<template>
  <div :id="entry.id" class="card" :data-id="entry.id" :data-type="entry.type">
    <div class="card-header">
      <div class="row">

        <div class="col-11" data-toggle="collapse" :data-target="'#collapse-' + entry.id" aria-expanded="false" :aria-controls="'collapse-' + entry.id">
          <fa icon="grip-lines" fixed-width class="handle" />

          <template v-if="entry.type === 'directive'">
            <button class="btn btn-sm col-3 col-md-2 mr-2" :class="[ isActive ? 'btn-warning' : 'btn-outline-warning']">DIRECTIVE</button>
            <b :class="{'text-muted': ! isActive}">{{ entry.name }}</b>
            <code>{{ firstValue }}</code>
          </template>

          <template v-if="entry.type === 'group'">
            <button class="btn btn-sm col-3 col-md-2 mr-2" :class="isActive ? 'btn-success' : 'btn-outline-success'">GROUP</button>
            <b :class="{'text-muted': ! isActive}">{{ entry.name }}</b>
          </template>

          <template v-if="entry.type === 'stanza'">
            <button class="btn btn-sm col-3 col-md-2 mr-2" :class="[isActive ? 'btn-primary' : 'btn-outline-primary']">STANZA</button>
            <b :class="{'text-muted': !isActive}">{{ stanzaName }}</b>
            <span v-if="isOclcStanza === true">
              &nbsp; <a :href="oclcStanzaUrl" target="oclc"><img src="../../../public/images/oclc_logo.png" height="16px" alt="View this stanza on the OCLC website" /></a>
            </span>
          </template>

          <template v-if="entry.type === 'custom_stanza'">
            <button class="btn btn-sm col-3 col-md-2 mr-2" :class="[isActive ? 'btn-info' : 'btn-outline-info']">CUSTOM</button>
            <b :class="{'text-muted': !isActive}">{{entry.name}}</b>
          </template>

          <template v-if="entry.type === 'comment'">
            <button class="btn btn-sm col-3 col-md-2 mr-2" :class="[isActive ? 'btn-secondary' : 'btn-outline-secondary']">COMMENT</button>
            <code>{{ firstValue }}</code>
          </template>
        </div>

        <div class="col-1 text-right">
          <div class="dropdown">
            <button :id="'dropdown-' + entry.id" class="btn btn-light btn-sm" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <fa icon="ellipsis-v" />
            </button>
            <div class="dropdown-menu dropdown-menu-right" :aria-labelledby="'dropdown-' + entry.id">
              <a class="dropdown-item delete-entry"><fa icon="trash-alt" fixed-width class="text-danger"></fa> Delete this entry</a>
              <a class="dropdown-item" data-toggle="modal" data-target="#addEntryModal" :data-after="entry.id"><fa icon="plus" fixed-width class="text-primary" /> Add entry below</a>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div :id="'collapse-' + entry.id" class="collapse dynamic-card-body" :aria-labelledby="'heading-' + entry.id">
      <div class="card-body">
        <div :id="'update-entry-messages-' + entry.id" class="hidden"></div>
        Entry ID: {{ entry.id }}<br />
        Type: {{ entry.type }}<br />
        OCLC: {{ isOclcStanza }}<br />

        Entry: <pre><code>{{ JSON.stringify(entry, null, 2) }}</code></pre>

        <template v-if="entry.type == 'stanza'">
          Stanza: {{entry.name}}<br />
          Code: {{entry.code}}<br />
          Stanza details: <code>{{ stanzas[entry.code] }}</code>
        </template>
      </div>
    </div>
  </div>
</template>

<script>

export default {
  props: {
    entry: Object,
    stanzas: Object
  },
  computed: {
    isActive () {
      return this.entry.active !== false
    },
    isOclcStanza () {
      return (this.entry.hasOwnProperty('code') && this.stanzas[this.entry.code].hasOwnProperty('oclcStanzaId'))
    },
    oclcStanzaUrl () {
      return (this.isOclcStanza)
        ? 'https://help.oclc.org/Library_Management/EZproxy/Database_stanzas/' + this.stanzas[this.entry.code].oclcStanzaId
        : null
    },
    firstValue () {
      return Array.isArray(this.entry.value) ? this.entry.value[0] : this.entry.value
    },
    stanzaName () {
      return (this.entry.hasOwnProperty('display_name'))
        ? this.entry.display_name
        : (
          (this.entry.hasOwnProperty('name'))
            ? this.entry.name
            : (
              (this.entry.hasOwnProperty('code') && this.stanzas[this.entry.code].hasOwnProperty('name'))
                ? this.stanzas[this.entry.code].name
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
