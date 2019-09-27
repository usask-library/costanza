import axios from 'axios'

export { getStanzaList, getStanza }

function getStanzaList() {
  return axios.get('/api/stanza').then(response => response.data.data)
}

function getStanza(code) {
  return axios.get('/api/stanza/' + code).then(response => response.data.data)
}