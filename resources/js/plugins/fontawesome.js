import Vue from 'vue'
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import {
  faQuestionCircle
} from '@fortawesome/free-regular-svg-icons'

import {
  faUser, faLock, faSignOutAlt, faCog, faSpinner, faEllipsisV, faTrashAlt, faPlus, faEdit, faGripLines,
  faExternalLinkAlt
} from '@fortawesome/free-solid-svg-icons'

import {
  faGithub
} from '@fortawesome/free-brands-svg-icons'

library.add(
  faUser, faLock, faSignOutAlt, faCog, faSpinner, faEllipsisV, faTrashAlt, faPlus, faEdit, faGripLines,
  faExternalLinkAlt,
  faQuestionCircle,
  faGithub
)

Vue.component('fa', FontAwesomeIcon)
