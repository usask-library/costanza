<template>
  <div>
    <b-card no-body>
      <b-tabs pills card vertical end>
        <b-tab title="Details" active>
          <b-card-text>
            <StanzaViewDetails :entry="entry" :stanza="stanza"></StanzaViewDetails>
          </b-card-text>
        </b-tab>
        <b-tab title="Rules">
          <b-card-text>
            <StanzaViewRules :filename="filename" :entry="entry" :stanza="stanza"></StanzaViewRules>
          </b-card-text>
        </b-tab>
      </b-tabs>
    </b-card>
  </div>
</template>

<script>
import StanzaViewDetails from './StanzaViewDetails';
import StanzaViewRules from './StanzaViewRules';

export default {
  name: 'StanzaView.vue',

  components: {
    StanzaViewDetails,
    StanzaViewRules
  },

  props: {
    filename: { type: String, required: true },
    entry: { type: Object, required: true },
    stanza: { type: Object, default: () => ({}) }
  }
}
</script>
